<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Document;
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

        $recent = (clone $documentQuery)->with(['category', 'suggestedCategory', 'department'])
            ->latest()->limit(9)->get();

        $categoryDistribution = (clone $documentQuery)
            ->leftJoin('document_categories as resolved_category', function ($join) {
                $join->on(
                    'resolved_category.id',
                    '=',
                    DB::raw('COALESCE(documents.category_id, documents.suggested_category_id)')
                );
            })
            ->selectRaw("COALESCE(resolved_category.name, 'Uncategorized') as category_name, COUNT(documents.id) as total")
            ->groupBy('resolved_category.name')
            ->get();

        $departmentDistribution = (clone $documentQuery)
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
            ->get();

        $recentConversations = Conversation::where('user_id', $user->id)
            ->latest()->limit(5)->get();

        return view('home', compact('stats', 'recent', 'categoryDistribution', 'departmentDistribution', 'recentConversations'));
    }
}
