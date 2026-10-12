<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(): View
    {
        $departments = Department::withCount(['documents', 'users'])->orderBy('name')->paginate(15);

        return view('admin.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('admin.departments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string'],
        ]);
        $dept = Department::create($data);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'department.created', 'entity_type' => Department::class, 'entity_id' => $dept->id, 'metadata' => ['name' => $dept->name]]);

        return redirect()->route('admin.departments.index')->with('status', 'Department created.');
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments')->ignore($department->id)],
            'description' => ['nullable', 'string'],
        ]);
        $department->update($data);
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'department.updated', 'entity_type' => Department::class, 'entity_id' => $department->id, 'metadata' => ['name' => $department->name]]);

        return redirect()->route('admin.departments.index')->with('status', 'Department updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $id = $department->id;
        $department->delete();
        AuditLog::create(['user_id' => auth()->id(), 'action' => 'department.deleted', 'entity_type' => Department::class, 'entity_id' => $id, 'metadata' => []]);

        return redirect()->route('admin.departments.index')->with('status', 'Department deleted.');
    }
}
