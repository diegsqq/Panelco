<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class AuthenticationAuditListener
{
    public function __construct(private Request $request) {}

    public function login(Login $event): void
    {
        $twoFactorChallenge = $event->user instanceof User
            && $event->user->two_factor_enabled
            && $event->user->two_factor_secret
            && ! $this->request->routeIs('two-factor.verify');

        AuditLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'action' => $twoFactorChallenge
                ? 'auth.two_factor_challenge_started'
                : 'auth.login_succeeded',
            'entity_type' => $event->user instanceof User ? User::class : null,
            'entity_id' => $event->user->getAuthIdentifier(),
            'metadata' => $this->requestMetadata([
                'remember' => $event->remember,
            ]),
        ]);
    }

    public function failed(Failed $event): void
    {
        AuditLog::create([
            'action' => 'auth.login_failed',
            'entity_type' => $event->user instanceof User ? User::class : null,
            'entity_id' => $event->user?->getAuthIdentifier(),
            'metadata' => $this->requestMetadata([
                'identifier' => $this->loginIdentifier($event->credentials),
            ]),
        ]);
    }

    public function lockedOut(Lockout $event): void
    {
        AuditLog::create([
            'action' => 'auth.login_locked_out',
            'metadata' => $this->requestMetadata([
                'identifier' => $this->loginIdentifier($this->request->only('email', 'username')),
            ]),
        ]);
    }

    public function logout(Logout $event): void
    {
        if ($this->request->session()->has('two_factor_pending_user')) {
            return;
        }

        AuditLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'action' => 'auth.logged_out',
            'entity_type' => $event->user instanceof User ? User::class : null,
            'entity_id' => $event->user->getAuthIdentifier(),
            'metadata' => $this->requestMetadata(),
        ]);
    }

    private function loginIdentifier(array $credentials): ?string
    {
        $identifier = $credentials['email'] ?? $credentials['username'] ?? null;

        return is_string($identifier) ? mb_substr(trim($identifier), 0, 255) : null;
    }

    private function requestMetadata(array $metadata = []): array
    {
        $userAgent = $this->request->userAgent();

        return array_merge($metadata, [
            'ip' => $this->request->ip(),
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 500) : null,
        ]);
    }
}
