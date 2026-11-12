<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;

class PasswordResetNotificationListener
{
    public function handle(PasswordReset $event): void
    {
        User::notifyAdminsOfPasswordChange($event->user, 'password_reset');
    }
}
