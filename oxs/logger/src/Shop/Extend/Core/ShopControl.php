<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Shop\Extend\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopControl as CoreShopControl;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Logger\Logger\ShopLoggerFactory;
use OxidSupport\Logger\Logger\ShopLoggerInterface;
use OxidSupport\Logger\Logger\SymbolTracker;
use OxidSupport\Logger\Sanitize\Sanitizer;
use OxidSupport\Logger\Shop\Facade\Facts;

class ShopControl extends CoreShopControl
{
    public function start($controllerKey = null, $function = null, $parameters = null, $viewsChain = null): void
    {
        /** @var ShopLoggerInterface $shopLogger */
        $shopLogger = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ShopLoggerFactory::class);

        $this->logstart($shopLogger);

        SymbolTracker::enable();
        $calculateDurationTimestampStart = microtime(true);

        try {
            parent::start($controllerKey, $function, $parameters, $viewsChain);
        } finally {

            $calculateDurationTimestampStop = microtime(true);

            $this->logSymbols(
                $shopLogger,
                SymbolTracker::report()
            );

            $this->logFinish(
                $shopLogger,
                $calculateDurationTimestampStart,
                $calculateDurationTimestampStop
            );
        }
    }

    private function logStart(ShopLoggerInterface $shopLogger): void
    {
        $referer   = $_SERVER['HTTP_REFERER']    ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $sanitizer = new Sanitizer();
        $get  = $sanitizer->sanitize($_GET ?? []);
        $post = $sanitizer->sanitize($_POST ?? []);

        $config  = Registry::getConfig();
        $session = Registry::getSession();
        $user    = $session?->getUser();

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http');
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $shopLogger->logStart([
            'userAgent'  => $userAgent,
            'referer'    => $referer,
            'get'        => $get,
            'post'       => $post,
            'shopId'     => (int) $config->getShopId(),
            'shopUrl'    => (string) $config->getShopUrl(),
            'sessionId'  => $session->getId(),
            'userId'     => $user === false ? 'no user' : $user->getId(),
            'userLogin'  => $user?->oxuser__oxusername->rawValue ?? null,
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
            'method'     => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri'        => sprintf("%s://%s%s", $scheme, $host, $uri),
            'lang'       => Registry::getLang()->getLanguageAbbr(),
            'edition'    => (new Facts())->getEdition(),
            'php'        => PHP_VERSION,
            'oxid'       => '',
            'cl'         => (string) (Registry::getRequest()->getRequestParameter('cl') ?? ''),    // nach SEO-Auflösung
            'fnc'        => (string) (Registry::getRequest()->getRequestParameter('fnc') ?? 'render'),
        ]);
    }

    private function logSymbols(ShopLoggerInterface $shopLogger, array $symbols): void
    {
        $shopLogger->logSymbols($symbols);
    }

    private function logFinish(
        ShopLoggerInterface $shopLogger,
        float $calculateDurationStartTimestamp,
        float $calculateDurationStopTimestamp,
    ): void
    {
        $duration = (int) round(
            ($calculateDurationStopTimestamp - $calculateDurationStartTimestamp) * 1000
        );

        $shopLogger->logFinish([
            'durationMs' => $duration,
            'memoryMb'   => round(memory_get_peak_usage(true) / 1048576, 1),
        ]);
    }
}
