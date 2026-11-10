<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): Renderable
    {
        $user = auth()->user();

        $documentQuery = Document::query();
        if (! $user->isAdmin()) {
            $documentQuery->where('uploaded_by', $user->id);
        }

        $stats = [
            'total' => (clone $documentQuery)->count(),
            'completed' => (clone $documentQuery)->where('processing_status', 'completed')->count(),
            'pending' => (clone $documentQuery)->whereIn('processing_status', ['pending', 'processing'])->count(),
            'failed' => (clone $documentQuery)->where('processing_status', 'failed')->count(),
        ];

        $pendingApprovals = collect();
        $pendingApprovalCount = 0;
        $pendingClassificationReviews = collect();
        $pendingClassificationReviewCount = 0;
        if ($user->isManager()) {
            $approvalQuery = Document::query()
                ->where('processing_status', 'completed')
                ->where('approval_status', 'pending');

            $classificationReviewQuery = Document::query()
                ->where('processing_status', 'completed')
                ->where('classification_review_status', 'pending')
                ->where(function ($query) {
                    $query->whereNotNull('suggested_category_id')
                        ->orWhereNotNull('suggested_department_id');
                });

            if (! $user->isAdmin()) {
                $allowedDeptIds = $user->departments()->pluck('departments.id')
                    ->push($user->department_id)->filter()->unique()->values();
                $applyVisibility = function ($query) use ($user, $allowedDeptIds) {
                    $query->where(function ($visible) use ($user, $allowedDeptIds) {
                        $visible->where('uploaded_by', $user->id)
                            ->orWhereIn('department_id', $allowedDeptIds)
                            ->orWhereNull('department_id')
                            ->orWhereIn('id', $user->authorizedDocuments()->pluck('documents.id'));
                    });
                };
                $applyVisibility($approvalQuery);
                $applyVisibility($classificationReviewQuery);
            }

            $pendingApprovalCount = (clone $approvalQuery)->count();
            $pendingApprovals = $approvalQuery
                ->with('uploader:id,name')
                ->orderBy('created_at')
                ->limit(5)
                ->get(['id', 'title', 'original_filename', 'uploaded_by', 'created_at']);
            $pendingClassificationReviewCount = (clone $classificationReviewQuery)->count();
            $pendingClassificationReviews = $classificationReviewQuery
                ->with('uploader:id,name', 'suggestedCategory:id,name', 'suggestedDepartment:id,name')
                ->orderBy('created_at')
                ->limit(5)
                ->get([
                    'id',
                    'title',
                    'original_filename',
                    'uploaded_by',
                    'suggested_category_id',
                    'suggested_department_id',
                    'created_at',
                ]);
        }

        $recent = (clone $documentQuery)->with(['category', 'suggestedCategory', 'department'])
            ->latest()->limit(9)->get();
        $recentFiles = (clone $documentQuery)->with(['category', 'uploader:id,name'])
            ->latest()->limit(3)->get();
        $allFiles = (clone $documentQuery)->with(['category', 'uploader:id,name'])
            ->latest()->limit(10)->get();
        $expiringDocuments = (clone $documentQuery)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<=', now()->addDays(30)->toDateString())
            ->orderBy('expiration_date')
            ->limit(6)
            ->get(['id', 'title', 'original_filename', 'expiration_date']);

        $categoryDistribution = (clone $documentQuery)
            ->leftJoin('document_categories as resolved_category', function ($join) {
                $join->on(
                    'resolved_category.id',
                    '=',
                    DB::raw('COALESCE(documents.category_id, documents.suggested_category_id)')
                );
            })
            ->selectRaw("COALESCE(resolved_category.id, 0) as category_id, COALESCE(resolved_category.name, 'Uncategorized') as category_name, COUNT(documents.id) as total")
            ->groupBy('resolved_category.id', 'resolved_category.name')
            ->get();
        $folders = $categoryDistribution->sortByDesc('total')->values();

        // Department distribution shows every department for all roles, so
        // employees can see organization-wide totals, not only their uploads.
        // Documents with neither a department nor a suggested one are left
        // out, so no "Unassigned" row appears.
        $categories = DocumentCategory::orderBy('name')->get();
        $departmentDistribution = Document::query()
            ->where(function ($q) {
                $q->whereNotNull('department_id')->orWhereNotNull('suggested_department_id');
            })
            ->leftJoin('departments as resolved_department', function ($join) {
                $join->on(
                    'resolved_department.id',
                    '=',
                    DB::raw('COALESCE(documents.department_id, documents.suggested_department_id)')
                );
            })
            ->selectRaw("COALESCE(resolved_department.name, 'Unassigned') as department_name, COUNT(documents.id) as total")
            ->groupBy('resolved_department.name')
            ->orderByDesc('total')
            ->orderBy('department_name')
            ->get()
            ->reject(fn ($row) => $row->department_name === 'Unassigned')
            ->values();

        $recentConversations = Conversation::where('user_id', $user->id)
            ->latest()->limit(5)->get();

        $trendStart = now()->startOfMonth()->subMonths(5);
        $monthlyUploads = collect(range(0, 5))->map(function (int $offset) use ($documentQuery, $trendStart) {
            $month = $trendStart->copy()->addMonths($offset);

            return [
                'label' => $month->format('M'),
                'count' => (clone $documentQuery)->whereBetween('created_at', [
                    $month->copy()->startOfMonth(),
                    $month->copy()->endOfMonth(),
                ])->count(),
            ];
        });
        $maxMonthlyUploads = max(1, (int) $monthlyUploads->max('count'));

        $recentAuditLogs = AuditLog::query()
            ->where('entity_type', Document::class)
            ->whereIn('entity_id', (clone $documentQuery)->select('documents.id'))
            ->with('user:id,name')
            ->latest()
            ->limit(8)
            ->get();
        $activityDocuments = Document::query()
            ->whereIn('id', $recentAuditLogs->pluck('entity_id')->unique())
            ->get(['id', 'title', 'original_filename'])
            ->keyBy('id');
        $activityLabels = [
            'document.uploaded' => 'uploaded',
            'document.updated' => 'updated details for',
            'document.version_uploaded' => 'uploaded a new version of',
            'document.version_downloaded' => 'downloaded a previous version of',
            'document.downloaded' => 'downloaded',
            'document.viewed' => 'viewed',
            'document.processed' => 'processed',
            'document.processing_failed' => 'processing failed for',
            'document.retry' => 'retried processing for',
            'document.approved' => 'approved',
            'document.rejected' => 'rejected',
            'document.classification_approved' => 'reviewed classification for',
            'document.classification_rejected' => 'rejected classification for',
            'document.deleted' => 'deleted',
        ];
        $recentActivity = $recentAuditLogs
            ->filter(fn (AuditLog $log) => $activityDocuments->has($log->entity_id))
            ->map(fn (AuditLog $log) => [
                'actor' => $log->user?->name ?? 'Former user',
                'action' => $activityLabels[$log->action] ?? ucfirst(str_replace('_', ' ', str_replace('document.', '', $log->action))),
                'document' => $activityDocuments->get($log->entity_id),
                'created_at' => $log->created_at,
            ])
            ->values();

        $accessCounts = AuditLog::query()
            ->where('entity_type', Document::class)
            ->whereIn('action', ['document.viewed', 'document.downloaded', 'document.version_downloaded'])
            ->whereIn('entity_id', (clone $documentQuery)->select('documents.id'))
            ->select('entity_id')
            ->selectRaw('COUNT(*) as access_count')
            ->groupBy('entity_id')
            ->orderByDesc('access_count')
            ->limit(5)
            ->get();
        $accessedDocuments = (clone $documentQuery)
            ->whereIn('id', $accessCounts->pluck('entity_id'))
            ->get()
            ->keyBy('id');
        $mostAccessedDocuments = $accessCounts
            ->filter(fn (AuditLog $log) => $accessedDocuments->has($log->entity_id))
            ->map(fn (AuditLog $log) => [
                'document' => $accessedDocuments->get($log->entity_id),
                'access_count' => (int) $log->access_count,
            ])
            ->values();

        return view('home', compact(
            'stats',
            'pendingApprovals',
            'pendingApprovalCount',
            'pendingClassificationReviews',
            'pendingClassificationReviewCount',
            'recent',
            'recentFiles',
            'allFiles',
            'expiringDocuments',
            'categoryDistribution',
            'folders',
            'categories',
            'departmentDistribution',
            'recentConversations',
            'monthlyUploads',
            'maxMonthlyUploads',
            'recentActivity',
            'mostAccessedDocuments'
        ));
    }
}
