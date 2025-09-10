<?php

declare(strict_types=1);


namespace OxidSupport\RequestLogger\Sanitize;

class Sanitizer
{
    public function sanitize(array $values): array
    {
        $blocklist = [
            'lgn_pwd'
        ];

        $isBlocked = static fn(string $k): bool => in_array(strtolower($k), $blocklist, true);

        $blocked = '[redacted]';
        $out = [];

        foreach ($values as $k => $v) {
            $key = is_string($k) ? $k : (string)$k;

            if ($isBlocked($key)) {
                // sensible Keys: Wert nie loggen (egal welcher Typ)
                $out[$key] = $blocked;
                continue;
            }

            if (is_scalar($v) || $v === null) {
                // Strings begrenzen
                $out[$key] = is_string($v) && strlen($v) > 500 ? substr($v, 0, 500) . '…' : $v;
            } elseif (is_array($v)) {
                // Arrays kompakt: erste Ebene key=value (max. 20)
                $pairs = [];
                $i = 0;
                foreach ($v as $ak => $av) {
                    if ($i++ >= 20) { $pairs[] = '…'; break; }
                    $pairs[] = $ak . '=' . (is_scalar($av) || $av === null ? (string)$av : '[complex]');
                }
                $out[$key] = implode('&', $pairs);
            } else {
                $out[$key] = '[non-scalar]';
            }
        }
        return $out;
    }
}
