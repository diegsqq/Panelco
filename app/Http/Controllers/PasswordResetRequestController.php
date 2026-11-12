<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SecurityAlert;

class PasswordResetRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['create', 'store']);
    }

    /**
     * Show the form for a user to request a password reset.
     */
    public function create()
    {
        return view('auth.passwords.request-reset');
    }

    /**
     * Store a new password reset request and notify admins.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'string', 'size:4'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = User::where('employee_id', $data['employee_id'])->first();

        if (! $user) {
            return back()->withInput()->withErrors(['employee_id' => 'No account found with this employee ID.']);
        }

        $resetRequest = PasswordResetRequest::create([
            'user_id' => $user->id,
            'employee_id' => $data['employee_id'],
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        // Notify all active admins
        $admins = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->get();
        foreach ($admins as $admin) {
            $admin->notifications()->create([
                'type' => 'password_reset_request',
                'title' => 'Password reset requested',
                'message' => "{$user->name} (ID: {$user->employee_id}) requested a password reset. Reason: {$data['reason']}",
                'metadata' => ['password_reset_request_id' => $resetRequest->id],
            ]);

            try {
                Mail::to($admin->email)->send(new SecurityAlert(
                    'Password reset requested',
                    "{$user->name} (ID: {$user->employee_id}) requested a password reset. Reason: {$data['reason']}. Review in the admin panel."
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('login')->with('status', 'Your password reset request has been submitted. An administrator will review it and notify you of the decision.');
    }

    /**
     * Show the password reset request details for admin review.
     */
    public function show(PasswordResetRequest $passwordResetRequest)
    {
        $this->authorizeAdmin();

        return view('admin.password-reset-requests.show', [
            'request' => $passwordResetRequest,
        ]);
    }

    /**
     * Approve the reset request: reset password to default, notify user.
     */
    public function approve(Request $request, PasswordResetRequest $passwordResetRequest)
    {
        $this->authorizeAdmin();

        if (! $passwordResetRequest->isPending()) {
            return back()->with('warning', 'This request has already been reviewed.');
        }

        $user = $passwordResetRequest->user;
        if (! $user) {
            return back()->with('warning', 'The associated user no longer exists.');
        }

        // Reset password to default
        $user->update(['password' => 'P@55w0rd']);

        // Update the request
        $passwordResetRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        // Create notification for the user
        $user->notifications()->create([
            'type' => 'password_reset_approved',
            'title' => 'Password reset approved',
            'message' => "Your password has been reset to the default. Login with ID {$user->employee_id} and password P@55w0rd. Please change your password after logging in.",
        ]);

        // Store in session for popup on next login
        session()->put('password_reset_approved_' . $user->id, true);

        try {
            Mail::to($user->email)->send(new SecurityAlert(
                'Password reset approved',
                "Your SmartDocs password has been reset to the default. Login with ID {$user->employee_id} and password P@55w0rd. Please change your password after logging in."
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('admin.password-reset-requests.show', $passwordResetRequest)
            ->with('status', 'Password reset approved. The user has been notified.');
    }

    /**
     * Decline the reset request: notify user.
     */
    public function decline(Request $request, PasswordResetRequest $passwordResetRequest)
    {
        $this->authorizeAdmin();

        if (! $passwordResetRequest->isPending()) {
            return back()->with('warning', 'This request has already been reviewed.');
        }

        $user = $passwordResetRequest->user;

        $passwordResetRequest->update([
            'status' => 'declined',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'admin_note' => $request->input('admin_note'),
        ]);

        if ($user) {
            $user->notifications()->create([
                'type' => 'password_reset_declined',
                'title' => 'Password reset declined',
                'message' => 'Your password reset request was declined by the administrator. Reason: ' . ($request->input('admin_note') ?: 'No reason provided.'),
            ]);

            try {
                Mail::to($user->email)->send(new SecurityAlert(
                    'Password reset declined',
                    'Your SmartDocs password reset request was declined. Reason: ' . ($request->input('admin_note') ?: 'No reason provided.')
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('admin.password-reset-requests.show', $passwordResetRequest)
            ->with('status', 'Password reset request declined. The user has been notified.');
    }

    private function authorizeAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            abort(403);
        }
    }
}
