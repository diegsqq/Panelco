<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|password|confirmed',
            'password_confirmation' => 'required',
            'token' => 'required',
        ]);

        $response = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = $password;
                $user->save();
            }
        );

        switch ($response) {
            case Password::PASSWORD_RESET:
                $email = $request->input('email');
                $user = User::where('email', $email)->first();
                if ($user) {
                    User::notifyAdminsOfPasswordChange($user, 'password_reset');
                }
                return $this->sendResetResponse($request, $response);
            default:
                return $this->sendResetFailedResponse($request, $response);
        }
    }
}
