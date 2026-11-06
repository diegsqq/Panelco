<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:128'],
            'category' => ['nullable', Rule::in(['auth', 'document', 'user'])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);
        $logs = AuditLog::with('user')->latest();
        if (! empty($filters['category'])) {
            $logs->where('action', 'like', $filters['category'].'.%');
        }
        if (! empty($filters['action'])) {
            $logs->where('action', 'like', '%'.$filters['action'].'%');
        }
        if (! empty($filters['q'])) {
            $search = '%'.$filters['q'].'%';
            $logs->where(function ($query) use ($search) {
                $query->where('action', 'like', $search)
                    ->orWhere('metadata->note', 'like', $search)
                    ->orWhereHas('user', function ($actor) use ($search) {
                        $actor->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    })
                    ->orWhereExists(function ($target) use ($search) {
                        $target->selectRaw('1')
                            ->from('users as audit_targets')
                            ->whereColumn('audit_targets.id', 'audit_logs.entity_id')
                            ->where('audit_logs.entity_type', User::class)
                            ->where(function ($targetUser) use ($search) {
                                $targetUser->where('audit_targets.name', 'like', $search)
                                    ->orWhere('audit_targets.email', 'like', $search);
                            });
                    });
            });
        }
        $logs = $logs->paginate(20)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }
}
