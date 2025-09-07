<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Shop\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidSupport\Logger\Logger\RequestContext;
use OxidSupport\Logger\Logger\ShopLogger;
use Throwable;

class ShopControlExtend extends ShopControlExtend_parent
{
    /**
     * @throws Throwable
     * @throws RandomException
     */
    public function start($controllerKey = null, $function = null, $parameters = null, $viewsChain = null): void
    {
        $logger = ShopLogger::get();
        $start  = microtime(true);



        $cl  = (string) (Registry::getRequest()->getRequestParameter('cl') ?? '');
        $fnc = (string) (Registry::getRequest()->getRequestParameter('fnc') ?? 'render');
        $ref = $_SERVER['HTTP_REFERER'] ?? null;
        $ua  = $_SERVER['HTTP_USER_AGENT'] ?? null;

        // GET/POST-Whitelist (nur Keys/harmlos)
        $getKeys  = array_keys($_GET  ?? []);
        $postKeys = array_keys($_POST ?? []);

        // Ausgewählte GET-Parameter mitschreiben (Werte): Suchbegriffe, IDs, Paging
        $whitelist = ['searchparam','cnid','mnid','anid','listtype','pgNr','sort','ldtype'];
        $wh = [];
        foreach ($whitelist as $k) {
            if (isset($_GET[$k]) && is_scalar($_GET[$k])) {
                $wh[$k] = (string)$_GET[$k];
            }
        }

        // Route-Event (User-Aktion)
        $logger->info('request.route', [
            'requestId'  => RequestContext::requestId(),
            'controller' => $cl ?: null,
            'action'     => $fnc ?: null,
            'referer'    => $ref,
            'userAgent'  => $ua,
            'getKeys'    => $getKeys,
            'postKeys'   => $postKeys,
            'get'        => $wh ?: null,
        ]);


        $logger->info('request.start', [
            'requestId' => RequestContext::requestId(),
        ]);

        try {
            parent::start();
        } catch (Throwable $e) {
            $logger->error('uncaught.exception', [
                'requestId' => RequestContext::requestId(),
                'class'     => $e::class,
                'message'   => $e->getMessage(),
                'code'      => $e->getCode(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e; // Verhalten unverändert lassen
        } finally {
            $dur = (int) round((microtime(true) - $start) * 1000);

            $logger->info('request.finish', [
                'requestId'  => RequestContext::requestId(),
                'durationMs' => $dur,
                'memoryMb'   => round(memory_get_peak_usage(true) / 1048576, 1),
                'controller' => $cl ?: null,
                'action'     => $fnc ?: null,
            ]);
        }
    }
}
