<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Shop\Extend\Core;

use OxidEsales\Eshop\Core\ShopControl as CoreShopControl;
use OxidSupport\Logger\Logger\SymbolTracker;
use OxidSupport\Logger\Logger\ShopLogger;
use OxidSupport\Logger\Logger\RequestContext;

class ShopControl extends CoreShopControl
{
    public function start($controllerKey = null, $function = null, $parameters = null, $viewsChain = null): void
    {
        $logger = ShopLogger::get();
        $start  = microtime(true);

        SymbolTracker::enable();

        // ---- Route-/Request-Metadaten (SEO-fest via RequestContext) ----
        $ctx = RequestContext::build();
        $cl  = $ctx['cl']  !== '' ? (string)$ctx['cl']  : null;
        $fnc = $ctx['fnc'] !== '' ? (string)$ctx['fnc'] : 'render';

        $referer = $_SERVER['HTTP_REFERER']    ?? null;
        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // --- GET/POST einsammeln (ohne Whitelist, feste Blacklist; Keys bleiben erhalten) ---
        $sanitize = static function (array $src): array {
            // feste Blocklist (case-insensitive)
            $blocklist = [
                'lgn_pwd'
            ];
            $isBlack = static fn(string $k): bool => in_array(strtolower($k), $blocklist, true);

            $blocked = '[redacted]';
            $out = [];

            foreach ($src as $k => $v) {
                $key = is_string($k) ? $k : (string)$k;

                if ($isBlack($key)) {
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
        };

        $get  = $sanitize($_GET  ?? []);
        $post = $sanitize($_POST ?? []);

        $logger->info('request.route', [
            'requestId'  => RequestContext::requestId(),
            'controller' => $cl,
            'action'     => $fnc,
            'referer'    => $referer,
            'userAgent'  => $ua,
            'get'        => $get ?: null,
            'post'       => $post ?: null,
        ]);

        try {
            parent::start($controllerKey, $function, $parameters, $viewsChain);
        } finally {

            $report  = SymbolTracker::report();

            $logger->info('request.symbols', [
                'requestId'  => RequestContext::requestId(),
                'symbols'   => $report['symbols'],
            ]);

            $dur = (int) round((microtime(true) - $start) * 1000);
            $logger->info('request.finish', [
                'requestId'  => RequestContext::requestId(),
                'durationMs' => $dur,
                'memoryMb'   => round(memory_get_peak_usage(true) / 1048576, 1),
                'controller' => $cl,
                'action'     => $fnc,
            ]);
        }
    }
}
