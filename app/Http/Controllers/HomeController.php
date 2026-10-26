<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;

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
            $allowedDeptIds = $user->departments()->pluck('departments.id')->push($user->department_id)->filter()->unique()->values();
            $documentQuery->where(function ($q) use ($user, $allowedDeptIds) {
                $q->where('uploaded_by', $user->id)
                    ->orWhereIn('department_id', $allowedDeptIds)
                    ->orWhereNull('department_id')
                    ->orWhereIn('id', $user->authorizedDocuments()->pluck('documents.id'));
            });
        }

        $stats = [
            'total' => (clone $documentQuery)->count(),
            'completed' => (clone $documentQuery)->where('processing_status', 'completed')->count(),
            'pending' => (clone $documentQuery)->whereIn('processing_status', ['pending', 'processing'])->count(),
            'failed' => (clone $documentQuery)->where('processing_status', 'failed')->count(),
        ];

        $recent = (clone $documentQuery)->with(['category', 'department'])
            ->latest()->limit(9)->get();

        $categoryDistribution = (clone $documentQuery)
            ->selectRaw('COALESCE(category_id, suggested_category_id) as category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->groupBy(fn ($row) => $row->category?->name ?? 'Uncategorized')
            ->map(fn ($group) => (object) [
                'category' => $group->first()->category,
                'total' => $group->sum('total'),
            ])
            ->values();

        $recentConversations = Conversation::where('user_id', $user->id)
            ->latest()->limit(5)->get();

        return view('home', compact('stats', 'recent', 'categoryDistribution', 'recentConversations'));
    }
}
