<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SecurityAlert;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'two_factor' => ['nullable', Rule::in(['enabled', 'disabled'])],
            'access_review' => ['nullable', Rule::in(['dormant'])],
        ]);
        $lastSuccessfulSignIn = AuditLog::query()
            ->select('created_at')
            ->where('entity_type', User::class)
            ->whereColumn('entity_id', 'users.id')
            ->where('action', 'auth.login_succeeded')
            ->latest()
            ->limit(1);
        $query = User::with(['department', 'departments'])
            ->select('users.*')
            ->selectSub($lastSuccessfulSignIn, 'last_successful_sign_in_at')
            ->latest('users.created_at');
        if (($filters['two_factor'] ?? null) === 'enabled') {
            $query->where('two_factor_enabled', true);
        } elseif (($filters['two_factor'] ?? null) === 'disabled') {
            $query->where('two_factor_enabled', false);
        }
        if (($filters['access_review'] ?? null) === 'dormant') {
            $recentSuccessfulSignIns = AuditLog::query()
                ->select('entity_id')
                ->where('entity_type', User::class)
                ->where('action', 'auth.login_succeeded')
                ->whereNotNull('entity_id')
                ->where('created_at', '>=', now()->subDays(90));
            $query->whereNotIn('users.id', $recentSuccessfulSignIns);
        }

        $users = $query->paginate(15)->withQueryString();
        $departments = Department::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'departments', 'filters'));
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
            'password' => [...PasswordRules::newPassword(), 'confirmed'],
            'role' => ['sometimes', 'required', Rule::in(User::ROLES)],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? User::ROLE_EMPLOYEE,
            'department_id' => $data['department_id'] ?? null,
        ]);

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
        $user->load(['department', 'departments']);
        $selectedDepartmentIds = old('departments', $user->departments()->pluck('departments.id')->all());
        $selectedDepartmentIds = is_array($selectedDepartmentIds)
            ? array_map('intval', $selectedDepartmentIds)
            : [];
        $authenticationActions = [
            'auth.login_succeeded',
            'auth.login_failed',
            'auth.login_locked_out',
            'auth.two_factor_challenge_started',
            'auth.two_factor_failed',
            'auth.logged_out',
        ];
        $accountEvents = AuditLog::query()
            ->where(function ($query) use ($user) {
                $query->where(function ($ownedEvents) use ($user) {
                    $ownedEvents->where('entity_type', User::class)
                        ->where('entity_id', $user->id);
                })->orWhere('metadata->identifier', $user->email);
            });
        $lastSuccessfulSignIn = (clone $accountEvents)
            ->where('action', 'auth.login_succeeded')
            ->latest()
            ->first();
        $loginHistory = (clone $accountEvents)
            ->whereIn('action', $authenticationActions)
            ->latest()
            ->limit(20)
            ->get();
        $latestAccessReview = AuditLog::query()
            ->where('action', 'user.access_reviewed')
            ->where('entity_type', User::class)
            ->where('entity_id', $user->id)
            ->latest()
            ->first();

        return view('admin.users.edit', compact(
            'user',
            'departments',
            'selectedDepartmentIds',
            'loginHistory',
            'lastSuccessfulSignIn',
            'latestAccessReview'
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => [...PasswordRules::newPassword(false), 'confirmed'],
            'role' => ['required', Rule::in(User::ROLES)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manage_departments' => ['sometimes', 'boolean'],
            'departments' => ['sometimes', 'array'],
            'departments.*' => ['integer', 'distinct', 'exists:departments,id'],
        ]);

        if ($user->is_active && $user->isAdmin()
            && $data['role'] !== User::ROLE_ADMIN
            && User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count() === 1) {
            return back()->withErrors(['role' => 'The last active administrator cannot be demoted. Activate or assign another administrator first.'])->withInput();
        }

        $emailBeforeUpdate = $user->email;
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'department_id' => $data['department_id'] ?? null,
        ]);
        $passwordWasReset = ! empty($data['password']);
        if ($passwordWasReset) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        if (! $user->isAdmin() && ($data['manage_departments'] ?? false)) {
            $user->departments()->sync($data['departments'] ?? []);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $passwordWasReset ? 'user.password_reset' : 'user.updated',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['email' => $user->email, 'role' => $user->role],
        ]);

        if ($passwordWasReset) {
            try {
                Mail::to($emailBeforeUpdate)->send(new SecurityAlert(
                    'Your SmartDocs password was reset',
                    'An administrator reset the password for your SmartDocs account. If you did not expect this change, contact your system administrator immediately.'
                ));
            } catch (\Throwable $e) {
                report($e);

                return redirect()->route('admin.users.index')
                    ->with('warning', 'User updated, but the password-reset notification could not be sent.');
            }

            return redirect()->route('admin.users.index')
                ->with('status', 'User updated. A password-reset notification was sent to the account email.');
        }

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);
        $isActive = (bool) $data['is_active'];

        if (! $isActive && (int) $user->id === (int) $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        // Account access is not offered for System Administrator accounts.
        if (! $isActive && $user->isAdmin()) {
            return back()->withErrors(['user' => 'The System Administrator account cannot be deactivated.']);
        }

        if ($user->is_active === $isActive) {
            return back()->with('status', 'Account status is unchanged.');
        }

        $user->update(['is_active' => $isActive]);
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $isActive ? 'user.activated' : 'user.deactivated',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['email' => $user->email],
        ]);

        return back()->with('status', $isActive ? 'User account activated.' : 'User account deactivated.');
    }

    public function recordAccessReview(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.access_reviewed',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['note' => trim($data['review_note'])],
        ]);

        return back()->with('status', 'Access review recorded.');
    }

    public function resetTwoFactor(Request $request, User $user): RedirectResponse
    {
        if ((int) $user->id === (int) $request->user()->id) {
            return back()->withErrors(['two_factor' => 'Use your own Security settings to manage two-factor authentication.']);
        }

        if (! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return back()->with('status', 'Two-factor authentication is already disabled for this account.');
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'user.two_factor_reset_by_admin',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'metadata' => ['email' => $user->email],
        ]);

        try {
            Mail::to($user->email)->send(new SecurityAlert(
                'Two-factor authentication reset',
                'An administrator reset two-factor authentication for your SmartDocs account. Sign in with your password and set up an authenticator app again from Settings > Security.'
            ));
        } catch (\Throwable $e) {
            report($e);

            return back()->with('warning', 'Two-factor authentication was reset, but the account security email could not be sent.');
        }

        return back()->with('status', 'Two-factor authentication reset. The user was notified by email.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($reason = $this->deleteBlockReason($user, (int) auth()->id())) {
            return back()->withErrors(['user' => $reason]);
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

    private function deleteBlockReason(User $user, int $actorId): ?string
    {
        if ((int) $user->id === $actorId) {
            return 'You cannot delete your own account. Sign in as a different administrator to delete this account.';
        }

        if ($user->is_active && $user->isAdmin()
            && User::where('role', User::ROLE_ADMIN)->where('is_active', true)->count() === 1) {
            return 'The last active administrator cannot be deleted. Activate or assign another administrator first.';
        }

        return null;
    }
}
