<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SecurityAlert;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Mail;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    protected function credentials($request)
    {
        return array_merge(
            $request->only($this->username(), 'password'),
            ['is_active' => true]
        );
    }

    /**
     * When the password is correct but 2FA is ON, do not start the
     * session yet: park the pending user id and ask for the code.
     */
    protected function sendLoginResponse($request)
    {
        $user = $request->user();

        if ($user && $user->two_factor_enabled && $user->two_factor_secret) {
            session(['two_factor_pending_user' => $user->id]);
            auth()->logout();

            return redirect()->route('two-factor.show');
        }

        $this->sendLoginAlert($user);

        // Mirror AuthenticatesUsers::sendLoginResponse() (trait method —
        // there is no parent version to delegate to).
        $request->session()->regenerate();
        $this->clearLoginAttempts($request);

        return $this->authenticated($request, $user) ?: redirect()->intended($this->redirectPath());
    }

    /**
     * Provide specific failed-login messages so the user knows whether the
     * email is unknown or the password is wrong.
     */
    protected function sendFailedLoginResponse($request)
    {
        $email = $request->input($this->username());
        $user  = \App\Models\User::where($this->username(), $email)->first();

        if (! $user) {
            // Email not found → tell them on the email field.
            return back()->withInput($request->only($this->username(), 'remember'))
                ->withErrors([
                    $this->username() => 'No account found with this email address.',
                ]);
        }

        // Email exists but password wrong → tell them on the password field.
        return back()->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                'password' => 'Wrong password. Please try again.',
            ]);
    }

    /** Best-effort login alert; mail problems never block signing in. */
    private function sendLoginAlert($user): void
    {
        if (! $user || ! $user->notify_login_alert) {
            return;
        }

        try {
            Mail::to($user->email)->send(new SecurityAlert(
                'New sign-in to your SmartDocs account',
                'A new sign-in to your SmartDocs account was detected from ' . request()->ip() . ' at ' . now()->format('M d, Y h:i A') . '. If this was not you, change your password immediately.'
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
