<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Extend\Core;

use OxidEsales\Eshop\Core\ShopControl as CoreShopControl;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\RequestLogger\Logger\Security\SensitiveDataRedactorInterface;
use OxidSupport\RequestLogger\Logger\ShopRequestRecorder\ShopRequestRecorderInterface;
use OxidSupport\RequestLogger\Logger\SymbolTracker;
use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\Factory as DiContainerFactory;
use OxidSupport\RequestLogger\Shop\Facade\ShopFacadeInterface;

class ShopControl extends CoreShopControl
{
    public function start($controllerKey = null, $function = null, $parameters = null, $viewsChain = null): void
    {
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

    private function logStart(
        ShopRequestRecorderInterface $recorder,
    ): void {

        $container = DiContainerFactory::create();
        $facade = $container->get(ShopFacadeInterface::class);
        $redactor = $container->get(SensitiveDataRedactorInterface::class);

        $referer   = $_SERVER['HTTP_REFERER']    ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $get  = $redactor->redact($_GET ?? []);
        $post = $redactor->redact($_POST ?? []);

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http');
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        $recorder->logStart([

            'version'    => $facade->getShopVersion(),
            'edition'    => $facade->getShopEdition(),
            'shopId'     => $facade->getShopId(),
            'shopUrl'    => $facade->getShopUrl(),

            'referer'    => $referer,
            'uri'        => sprintf("%s://%s%s", $scheme, $host, $uri),
            'method'     => $_SERVER['REQUEST_METHOD'] ?? null,
            'get'        => $get,
            'post'       => $post,
            'userAgent'  => $userAgent,
            'lang'       => $facade->getLanguageAbbreviation(),

            'sessionId'  => $facade->getSessionId(),
            'userId'     => $facade->getUserId(),
            'username'   => $facade->getUsername(),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,

            'php'        => PHP_VERSION,
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
