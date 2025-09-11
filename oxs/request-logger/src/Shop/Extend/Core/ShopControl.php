<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Extend\Core;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopControl as CoreShopControl;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\RequestLogger\ShopRequestRecorder\ShopRequestRecorderInterface;
use OxidSupport\RequestLogger\Logger\SymbolTracker;
use OxidSupport\RequestLogger\Sanitize\Sanitizer;
use OxidSupport\RequestLogger\Shop\Facade\Facts;

class ShopControl extends CoreShopControl
{
    public function start($controllerKey = null, $function = null, $parameters = null, $viewsChain = null): void
    {
        // Do not make it a class property to not interfere in the request lifecycle
        $recorder = ContainerFactory::getInstance()
            ->getContainer()
            ->get(ShopRequestRecorderInterface::class);

        $this->logstart($recorder);

        SymbolTracker::enable();
        $calculateDurationTimestampStart = microtime(true);

        try {
            parent::start($controllerKey, $function, $parameters, $viewsChain);
        } finally {

            $calculateDurationTimestampStop = microtime(true);

            $this->logSymbols(
                $recorder,
                SymbolTracker::report()
            );

            $this->logFinish(
                $recorder,
                $calculateDurationTimestampStart,
                $calculateDurationTimestampStop
            );
        }
    }

    private function logStart(ShopRequestRecorderInterface $recorder): void
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

        $recorder->logStart([
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

    private function logSymbols(ShopRequestRecorderInterface $recorder, array $symbols): void
    {
        $recorder->logSymbols($symbols);
    }

    private function logFinish(
        ShopRequestRecorderInterface $recorder,
        float $calculateDurationStartTimestamp,
        float $calculateDurationStopTimestamp,
    ): void
    {
        $duration = (int) round(
            ($calculateDurationStopTimestamp - $calculateDurationStartTimestamp) * 1000
        );

        $recorder->logFinish([
            'durationMs' => $duration,
            'memoryMb'   => round(memory_get_peak_usage(true) / 1048576, 1),
        ]);
    }
}
