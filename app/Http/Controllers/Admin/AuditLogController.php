<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request): View
    {
        $logs = AuditLog::with('user')->latest();
        if ($request->filled('action')) {
            $logs->where('action', 'like', '%' . $request->get('action') . '%');
        }
        $logs = $logs->paginate(20)->withQueryString();

        return view('admin.audit-logs.index', compact('logs'));
    }
}
