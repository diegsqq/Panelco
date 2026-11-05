<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
        ]);
        $logs = AuditLog::with('user')->latest();
        if (! empty($filters['category'])) {
            $logs->where('action', 'like', $filters['category'].'.%');
        }
        if (! empty($filters['action'])) {
            $logs->where('action', 'like', '%'.$filters['action'].'%');
        }
        $logs = $logs->paginate(20)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }
}
