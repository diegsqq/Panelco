<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(): View
    {
        $users = User::with(['department', 'departments'])->latest()->paginate(15);
        $departments = Department::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'departments'));
    }

    public function create(): View
    {
        $departments = Department::orderBy('name')->get();

        return view('admin.users.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(User::ROLES)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'departments' => ['nullable', 'array'],
            'departments.*' => ['exists:departments,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'department_id' => $data['department_id'] ?? null,
        ]);
        $user->departments()->sync($data['departments'] ?? []);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.created',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['email' => $user->email, 'role' => $user->role],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): View
    {
        $departments = Department::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'departments'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(User::ROLES)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'departments' => ['nullable', 'array'],
            'departments.*' => ['exists:departments,id'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'department_id' => $data['department_id'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->departments()->sync($data['departments'] ?? []);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.updated',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['email' => $user->email, 'role' => $user->role],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ((int) $user->id === (int) auth()->id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        $userId = $user->id;
        $user->departments()->detach();
        $user->authorizedDocuments()->detach();
        $user->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'user.deleted',
            'entity_type' => User::class,
            'entity_id' => $userId,
            'metadata' => [],
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User deleted successfully.');
    }
}
