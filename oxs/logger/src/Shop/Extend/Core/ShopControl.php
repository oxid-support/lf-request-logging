<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Shop\Extend\Core;

use OxidEsales\Eshop\Core\ShopControl as CoreShopControl;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Logger\Logger\ShopLoggerFactory;
use OxidSupport\Logger\Logger\ShopLoggerInterface;
use OxidSupport\Logger\Logger\SymbolTracker;
use OxidSupport\Logger\Logger\ShopLogger;
use OxidSupport\Logger\Sanitize\Sanitizer;
use OxidSupport\Logger\Shop\Context\RequestContext;

class ShopControl extends CoreShopControl
{
    public function start($controllerKey = null, $function = null, $parameters = null, $viewsChain = null): void
    {
        /** @var ShopLoggerInterface $shopLogger */
        $shopLogger = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ShopLoggerFactory::class);

        SymbolTracker::enable();

        // ---- Route-/Request-Metadaten (SEO-fest via RequestContext) ----
        $ctx = RequestContext::build();
        $cl  = $ctx['cl']  !== '' ? (string) $ctx['cl']  : null;
        $fnc = $ctx['fnc'] !== '' ? (string) $ctx['fnc'] : 'render';

        $referer = $_SERVER['HTTP_REFERER']    ?? null;
        $ua      = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $sanitizer = new Sanitizer();
        $get  = $sanitizer->sanitize($_GET ?? []);
        $post = $sanitizer->sanitize($_POST ?? []);

        $shopLogger->logRoute([
            'controller' => $cl,
            'action'     => $fnc,
            'referer'    => $referer,
            'userAgent'  => $ua,
            'get'        => $get ?: null,
            'post'       => $post ?: null,
        ]);

        $start = microtime(true);

        try {
            parent::start($controllerKey, $function, $parameters, $viewsChain);
        } finally {

            $report = SymbolTracker::report();

            $shopLogger->logSymbols($report);
            /*
            $shopLogger->info('request.symbols', [
                'symbols'   => $report['symbols'],
            ]);
            */

            $dur = (int) round((microtime(true) - $start) * 1000);


            $shopLogger->logFinish([
                'durationMs' => $dur,
                'memoryMb'   => round(memory_get_peak_usage(true) / 1048576, 1),
            ]);
            /*
            $shopLogger->info('request.finish', [
                'durationMs' => $dur,
                'memoryMb'   => round(memory_get_peak_usage(true) / 1048576, 1),
            ]);
            */
        }
    }
}
