<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Shop\Extend\Core;

use OxidEsales\Eshop\Core\ShopControl as CoreShopControl;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\Security\SensitiveDataRedactorInterface;
// phpcs:ignore Generic.Files.LineLength.TooLong
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\ShopRequestRecorder\ShopRequestRecorderInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\SymbolTracker;
use OxidSupport\Heartbeat\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;

class ShopControl extends CoreShopControl
{
    /**
     * @param array<mixed>|null $parameters
     * @param array<mixed>|null $viewsChain
     */
    public function start($controllerKey = null, $function = null, $parameters = null, $viewsChain = null): void
    {
        $container = ContainerFactory::getInstance()->getContainer();
        /** @var ShopFacadeInterface $shopFacade */
        $shopFacade = $container->get(ShopFacadeInterface::class);
        /** @var ModuleSettingFacadeInterface $settingsFacade */
        $settingsFacade = $container->get(ModuleSettingFacadeInterface::class);

        if (!$settingsFacade->isRequestLoggerComponentActive()) {
            parent::start($controllerKey, $function, $parameters, $viewsChain);
            return;
        }

        $isAdmin = $shopFacade->isAdmin();
        $shouldLog = ($isAdmin && $settingsFacade->isLogAdminEnabled())
            || (!$isAdmin && $settingsFacade->isLogFrontendEnabled());

        if (!$shouldLog) {
            parent::start($controllerKey, $function, $parameters, $viewsChain);
            return;
        }

        /** @var ShopRequestRecorderInterface $recorder */
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

        $container = ContainerFactory::getInstance()->getContainer();
        /** @var ShopFacadeInterface $facade */
        $facade = $container->get(ShopFacadeInterface::class);
        /** @var SensitiveDataRedactorInterface $redactor */
        $redactor = $container->get(SensitiveDataRedactorInterface::class);
        /** @var ModuleSettingFacadeInterface $settingsFacade */
        $settingsFacade = $container->get(ModuleSettingFacadeInterface::class);

        $referer   = $_SERVER['HTTP_REFERER'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $get  = $redactor->redact($_GET);
        $post = $redactor->redact($_POST);

        $redactAll = $settingsFacade->isRedactAllValuesEnabled();

        $scheme = $_SERVER['REQUEST_SCHEME'] ?? (($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http');
        $host   = $_SERVER['HTTP_HOST'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';

        // Redact query parameters in referer and URI in BOTH modes. In blocklist
        // mode only blocklisted keys are redacted; this closes the leak where a
        // blocklisted value (e.g. ?token=SECRET) was still logged raw in the URI.
        $blocklistLower = array_map('strtolower', $settingsFacade->getRedactItems());
        $uri = $this->redactUrlQueryParams(
            sprintf("%s://%s%s", $scheme, $host, $uri),
            $redactAll,
            $blocklistLower
        );
        $referer = $this->redactUrlQueryParams($referer, $redactAll, $blocklistLower);

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

            'sessionId'  => $redactAll ? '[redacted]' : $this->pseudonymizeSessionId($facade->getSessionId()),
            'userId'     => $redactAll ? '[redacted]' : $facade->getUserId(),
            'username'   => $redactAll ? '[redacted]' : $facade->getUsername(),
            'ip'         => $redactAll ? '[redacted]' : ($_SERVER['REMOTE_ADDR'] ?? null),

            'php'        => PHP_VERSION,
        ]);
    }

    /** @param array<string, mixed> $symbols */
    private function logSymbols(ShopRequestRecorderInterface $recorder, array $symbols): void
    {
        $recorder->logSymbols($symbols);
    }

    private function logFinish(
        ShopRequestRecorderInterface $recorder,
        float $calculateDurationStartTimestamp,
        float $calculateDurationStopTimestamp
    ): void {
        $duration = (int) round(
            ($calculateDurationStopTimestamp - $calculateDurationStartTimestamp) * 1000
        );

        $recorder->logFinish([
            'durationMs' => $duration,
            'memoryMb'   => round(memory_get_peak_usage(true) / 1048576, 1),
        ]);
    }

    /**
     * Return a stable pseudonym of the session id instead of the raw value.
     * The raw session id is a live authentication token; logging it enables
     * session hijacking if logs are readable. The pseudonym keeps request
     * correlation intact for support without exposing the token.
     */
    private function pseudonymizeSessionId(?string $sessionId): ?string
    {
        if ($sessionId === null || $sessionId === '') {
            return $sessionId;
        }

        return 'sha256:' . substr(hash('sha256', $sessionId), 0, 16);
    }

    /**
     * redact-all mode: redact every query value except the harmless routing
     * params. blocklist mode: redact only keys on the blocklist. Either way the
     * query string of uri/referer is covered, so a blocklisted value can no
     * longer leak through the raw URL.
     *
     * @param string[] $excludeFromRedaction
     * @param string[] $blocklistLower lowercase blocklist entries
     */
    private function shouldRedactQueryKey(
        string $key,
        bool $redactAll,
        array $excludeFromRedaction,
        array $blocklistLower
    ): bool {
        if ($redactAll) {
            return !in_array($key, $excludeFromRedaction, true);
        }

        return in_array(strtolower($key), $blocklistLower, true);
    }

    /**
     * @param string[] $blocklistLower lowercase blocklist entries (blocklist mode only)
     */
    private function redactUrlQueryParams(?string $url, bool $redactAll, array $blocklistLower): ?string
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
            $encodedKey = urlencode((string) $key);

            if ($this->shouldRedactQueryKey((string) $key, $redactAll, $excludeFromRedaction, $blocklistLower)) {
                // Use literal [redacted] without URL encoding
                $queryParts[] = $encodedKey . '=[redacted]';
            } else {
                $encodedValue = urlencode(is_array($value) ? '' : (string) $value);
                $queryParts[] = $encodedKey . '=' . $encodedValue;
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
