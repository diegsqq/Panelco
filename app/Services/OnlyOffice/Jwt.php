<?php

namespace App\Services\OnlyOffice;

/**
 * Minimal HS256 JWT helper for OnlyOffice Document Server payloads.
 */
class Jwt
{
    public static function sign(array $payload, string $secret): string
    {
        $input = self::encode(['alg' => 'HS256', 'typ' => 'JWT']) . '.' . self::encode($payload);

        return $input . '.' . self::signature($input, $secret);
    }

    public static function verify(string $token, string $secret): ?array
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 3) {
            return null;
        }

        $expected = self::signature($parts[0] . '.' . $parts[1], $secret);
        if (! hash_equals($expected, $parts[2])) {
            return null;
        }

        $payload = json_decode(self::decode($parts[1]), true);

        return is_array($payload) ? $payload : null;
    }

    private static function signature(string $input, string $secret): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', $input, $secret, true)), '+/', '-_'), '=');
    }

    private static function encode(array $value): string
    {
        return rtrim(strtr(base64_encode(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');
    }

    private static function decode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
