<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPath;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogPathType;
use OxidSupport\Heartbeat\Component\LogSender\DataType\LogSource;
use OxidSupport\Heartbeat\Component\LogSender\Exception\LogSourceNotFoundException;
use OxidSupport\Heartbeat\Module\Module;

/**
 * Service that collects log sources from static paths and DI-tagged providers.
 */
final class LogCollectorService implements LogCollectorServiceInterface
{
    private ModuleSettingBridgeInterface $moduleSettingService;
    private StaticPathGuardInterface $staticPathGuard;

    /** @var LogPathProviderInterface[] */
    private array $providers;

    /**
     * @param ModuleSettingBridgeInterface $moduleSettingService
     * @param StaticPathGuardInterface $staticPathGuard
     * @param iterable<LogPathProviderInterface> $providers Injected via !tagged_iterator
     */
    public function __construct(
        ModuleSettingBridgeInterface $moduleSettingService,
        StaticPathGuardInterface $staticPathGuard,
        iterable $providers
    ) {
        $this->moduleSettingService = $moduleSettingService;
        $this->staticPathGuard = $staticPathGuard;
        $this->providers = $providers instanceof \Traversable
            ? iterator_to_array($providers)
            : (array) $providers;
    }

    /**
     * @inheritDoc
     */
    public function getSources(): array
    {
        $sources = [];

        // 1. Static paths from settings
        $staticPaths = $this->getStaticPaths();
        foreach ($staticPaths as $index => $path) {
            $sources[] = new LogSource(
                'static_' . $index,
                $path->name,
                $path->description,
                LogSource::ORIGIN_STATIC,
                null,
                [$path],
                $path->exists()
            );
        }

        // 2. Provider paths from DI-tagged services
        foreach ($this->providers as $provider) {
            $paths = $provider->getLogPaths();
            $pathsAvailable = $this->checkAllPathsAvailable($paths);

            $sources[] = new LogSource(
                'provider_' . $provider->getProviderId(),
                $provider->getProviderName(),
                $provider->getProviderDescription(),
                LogSource::ORIGIN_PROVIDER,
                $provider->getProviderId(),
                $paths,
                $provider->isActive() && $pathsAvailable
            );
        }

        return $sources;
    }

    /**
     * @inheritDoc
     */
    public function getSourceById(string $sourceId): LogSource
    {
        $sources = $this->getSources();

        foreach ($sources as $source) {
            if ($source->id === $sourceId) {
                return $source;
            }
        }

        throw new LogSourceNotFoundException($sourceId);
    }

    /**
     * @inheritDoc
     */
    public function getStaticPaths(): array
    {
        try {
            $configured = (array) $this->moduleSettingService->get(
                Module::SETTING_LOGSENDER_STATIC_PATHS,
                Module::ID
            );
        } catch (\Throwable $e) {
            // Setting not configured yet
            return [];
        }

        $paths = [];
        foreach ($configured as $config) {
            if (!is_array($config) || !isset($config['path'], $config['type'])) {
                continue;
            }

            // Drop paths that point into another shop's request logs or at sensitive
            // files, so a per-shop setting cannot become a cross-shop / arbitrary
            // file read for the shop's service user. See OXS-3131.
            if (!$this->staticPathGuard->isAllowed((string) $config['path'])) {
                continue;
            }

            $type = LogPathType::tryFrom($config['type']);
            if ($type === null) {
                continue;
            }

            $paths[] = new LogPath(
                $config['path'],
                $type,
                $config['name'] ?? basename($config['path']),
                $config['description'] ?? '',
                $config['pattern'] ?? null
            );
        }

        return $paths;
    }

    /**
     * @inheritDoc
     */
    public function isInstallationWideSource(string $sourceId): bool
    {
        foreach ($this->providers as $provider) {
            if ('provider_' . $provider->getProviderId() === $sourceId) {
                return $provider instanceof InstallationWideLogPathProviderInterface;
            }
        }

        return false;
    }

    /**
     * Check if all paths in the array are available.
     *
     * @param LogPath[] $paths
     */
    private function checkAllPathsAvailable(array $paths): bool
    {
        if (empty($paths)) {
            return false;
        }

        foreach ($paths as $path) {
            if (!$path->exists()) {
                return false;
            }
        }

        return true;
    }
}
