<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public static function newPassword(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            Password::min(12)->mixedCase()->numbers()->symbols(),
        ];
    }
}
