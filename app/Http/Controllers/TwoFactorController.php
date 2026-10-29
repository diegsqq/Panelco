<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

/**
 * Second step of login when a user has 2FA ON: password was correct,
 * now the Google Authenticator code is required before the session
 * is created.
 */
class TwoFactorController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function show(): View|RedirectResponse
    {
        $userId = session('two_factor_pending_user');
        if (! $userId || ! User::whereKey($userId)->exists()) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verify(Request $request): RedirectResponse
    {
        $userId = session('two_factor_pending_user');
        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);
        if (! $user || ! $user->two_factor_enabled || ! $user->two_factor_secret) {
            session()->forget('two_factor_pending_user');

            return redirect()->route('login');
        }

        $data = $request->validate(['code' => ['required', 'string', 'size:6']]);

        if (! app(Google2FA::class)->verifyKey($user->two_factor_secret, trim($data['code']))) {
            return back()->withErrors(['code' => 'Incorrect authentication code.']);
        }

        session()->forget('two_factor_pending_user');
        auth()->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/home');
    }
}
