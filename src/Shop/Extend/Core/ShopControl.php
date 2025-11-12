<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Extend\Core;

use OxidEsales\Eshop\Core\ShopControl as CoreShopControl;
use OxidSupport\RequestLogger\Logger\Security\SensitiveDataRedactorInterface;
use OxidSupport\RequestLogger\Logger\ShopRequestRecorder\ShopRequestRecorderInterface;
use OxidSupport\RequestLogger\Logger\SymbolTracker;
use OxidSupport\RequestLogger\Shop\Compatibility\DiContainer\Factory as DiContainerFactory;
use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\RequestLogger\Shop\Facade\ShopFacadeInterface;

class ShopControl extends CoreShopControl
{
    public function start($controllerKey = null, $function = null, $parameters = null, $viewsChain = null): void
    {
        $container = DiContainerFactory::create();

        $shopFacade = $container->get(ShopFacadeInterface::class);
        $settingsFacade = $container->get(ModuleSettingFacadeInterface::class);

        $isAdmin = $shopFacade->isAdmin();
        $shouldLog = ($isAdmin && $settingsFacade->isLogAdminEnabled())
            || (!$isAdmin && $settingsFacade->isLogFrontendEnabled());

        if (!$shouldLog) {
            parent::start($controllerKey, $function, $parameters, $viewsChain);
            return;
        }

        $recorder = $container->get(ShopRequestRecorderInterface::class);

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
        ShopRequestRecorderInterface $recorder
    ): void {

        $container = DiContainerFactory::create();
        $facade = $container->get(ShopFacadeInterface::class);
        $redactor = $container->get(SensitiveDataRedactorInterface::class);
        $settingsFacade = $container->get(ModuleSettingFacadeInterface::class);

        $referer   = $_SERVER['HTTP_REFERER']    ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $get  = $redactor->redact($_GET ?? []);
        $post = $redactor->redact($_POST ?? []);

        $redactAll = $settingsFacade->isRedactAllValuesEnabled();

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http');
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        // Redact query parameters in referer and URI only if redact-all-values is enabled
        if ($redactAll) {
            $referer = $this->redactUrlQueryParams($referer);
            $uri = $this->redactUrlQueryParams(sprintf("%s://%s%s", $scheme, $host, $uri));
        } else {
            $uri = sprintf("%s://%s%s", $scheme, $host, $uri);
        }

        $recorder->logStart([

            'version'    => $facade->getShopVersion(),
            'edition'    => $facade->getShopEdition(),
            'shopId'     => $facade->getShopId(),
            'shopUrl'    => $facade->getShopUrl(),

            'referer'    => $referer,
            'uri'        => $uri,
            'method'     => $_SERVER['REQUEST_METHOD'] ?? null,
            'get'        => $get,
            'post'       => $post,
            'userAgent'  => $redactAll ? '[redacted]' : $userAgent,
            'lang'       => $facade->getLanguageAbbreviation(),

            'sessionId'  => $redactAll ? '[redacted]' : $facade->getSessionId(),
            'userId'     => $redactAll ? '[redacted]' : $facade->getUserId(),
            'username'   => $redactAll ? '[redacted]' : $facade->getUsername(),
            'ip'         => $redactAll ? '[redacted]' : ($_SERVER['REMOTE_ADDR'] ?? null),

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
        float $calculateDurationStopTimestamp
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

    private function redactUrlQueryParams(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $parsedUrl = parse_url($url);
        if ($parsedUrl === false || !isset($parsedUrl['query'])) {
            return $url;
        }

        parse_str($parsedUrl['query'], $queryParams);

        // Parameters that should not be redacted (controller and function names)
        $excludeFromRedaction = ['cl', 'fnc', 'item'];

        // Build query string manually to avoid double URL-encoding of [redacted]
        $queryParts = [];
        foreach ($queryParams as $key => $value) {
            $encodedKey = urlencode($key);

            // Don't redact cl and fnc parameters
            if (in_array($key, $excludeFromRedaction, true)) {
                $encodedValue = urlencode($value);
                $queryParts[] = $encodedKey . '=' . $encodedValue;
            } else {
                // Use literal [redacted] without URL encoding
                $queryParts[] = $encodedKey . '=[redacted]';
            }
        }

        $redactedQuery = implode('&', $queryParts);

        $result = '';
        if (isset($parsedUrl['scheme'])) {
            $result .= $parsedUrl['scheme'] . '://';
        }
        if (isset($parsedUrl['host'])) {
            $result .= $parsedUrl['host'];
        }
        if (isset($parsedUrl['port'])) {
            $result .= ':' . $parsedUrl['port'];
        }
        if (isset($parsedUrl['path'])) {
            $result .= $parsedUrl['path'];
        }
        if ($redactedQuery !== '') {
            $result .= '?' . $redactedQuery;
        }
        if (isset($parsedUrl['fragment'])) {
            $result .= '#' . $parsedUrl['fragment'];
        }

        return $result;
    }
}
