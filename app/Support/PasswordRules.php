<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public static function newPassword(bool $required = true, int $minLength = 12): array
    {
        return [
            $required ? 'required' : 'nullable',
            'string',
            Password::min($minLength)->mixedCase()->numbers()->symbols(),
        ];
    }
}
