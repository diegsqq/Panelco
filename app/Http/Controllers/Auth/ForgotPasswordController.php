<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->input('email'))->first();

        // Only create the reset token; do NOT send email (SMTP may be misconfigured).
        // Notify admins so they can assist the user directly.
        if ($user) {
            Password::broker()->createToken(['email' => $request->input('email')]);
            User::notifyAdminsOfPasswordChange($user, 'password_reset_requested');
        }

        return redirect()->back()
            ->with('status', 'If an account exists for that email, an administrator has been notified and will assist you with a password reset.');
    }
}
