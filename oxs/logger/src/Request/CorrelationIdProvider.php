<?php

declare(strict_types=1);


namespace OxidSupport\Logger\Request;

/**
 * @todo split generation and emitting
 */
final class CorrelationIdProvider implements CorrelationIdProviderInterface
{
    //private const HEADER = 'X-Request-Id';
    private const COOKIE = 'X-Correlation-Id';
    private static bool $emitted = false;

    public static function get(): string
    {
        /*
         * Optional: also emit as HTTP header (e.g., for APIs, proxies, distributed tracing)
         * in case an api has must be observed.
         */
        /*
        $incoming = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;
        if (is_string($incoming) && self::isValid($incoming)) {
            self::emit($incoming);
            return $incoming;
        }
        */

        $cookie = $_COOKIE[self::COOKIE] ?? null;
        if (is_string($cookie) && self::isValid($cookie)) {
            self::emit($cookie);
            return $cookie;
        }

        $id = self::generate();
        self::emit($id);
        return $id;
    }

    private static function emit(string $id): void
    {
        if (!headers_sent() && !self::$emitted) {
            //header(self::HEADER . ': ' . $id);
            setcookie(
                self::COOKIE,
                $id,
                [
                    'expires'  => time() + 60 * 60 * 24 * 30, // 30 Tage
                    'path'     => '/',
                    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
            self::$emitted = true;
        }
    }

    private static function generate(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Exception) {
            return bin2hex((string) random_int(0, PHP_INT_MAX));
        }
    }

    private static function isValid(string $v): bool
    {
        return (bool) preg_match('/^[A-Fa-f0-9]{32,64}$/', $v);
    }
}
