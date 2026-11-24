<?php
declare(strict_types=1);

namespace Engine;

/**
 * CSRF token kept in the session and verified by the kernel on every write.
 * A form only has to carry the csrf_token field.
 */
class Csrf
{
    public const FIELD = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::FIELD])) {
            $_SESSION[self::FIELD] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::FIELD];
    }

    public static function verify(?string $token): bool
    {
        // hash_equals compares in constant time.
        return is_string($token)
            && $token !== ''
            && !empty($_SESSION[self::FIELD])
            && hash_equals($_SESSION[self::FIELD], $token);
    }
}
