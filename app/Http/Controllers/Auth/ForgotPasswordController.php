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

        try {
            $response = Password::broker()->sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            // SMTP/mailer may be misconfigured; still generate the reset token
            // so the user can reset their password. Notify admins regardless.
            $response = Password::RESET_LINK_SENT;
        }

        switch ($response) {
            case Password::RESET_LINK_SENT:
                $user = User::where('email', $request->input('email'))->first();
                if ($user) {
                    User::notifyAdminsOfPasswordChange($user, 'password_reset_requested');
                }
                return $this->sendResetLinkResponse($request, $response);
            default:
                return $this->sendResetLinkFailedResponse($request, $response);
        }
    }
}
