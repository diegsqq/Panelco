<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $response = Password::broker()->sendResetLink($request->only('email'));

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
