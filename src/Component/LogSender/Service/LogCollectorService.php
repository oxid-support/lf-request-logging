<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
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
    /** @var LogPathProviderInterface[] */
    private array $providers;

    /**
     * @param ModuleSettingServiceInterface $moduleSettingService
     * @param iterable<LogPathProviderInterface> $providers Injected via !tagged_iterator
     */
    public function __construct(
        private ModuleSettingServiceInterface $moduleSettingService,
        private StaticPathGuardInterface $staticPathGuard,
        iterable $providers
    ) {
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
                id: 'static_' . $index,
                name: $path->name,
                description: $path->description,
                origin: LogSource::ORIGIN_STATIC,
                providerId: null,
                paths: [$path],
                available: $path->exists(),
            );
        }

        // 2. Provider paths from DI-tagged services
        foreach ($this->providers as $provider) {
            $paths = $provider->getLogPaths();
            $pathsAvailable = $this->checkAllPathsAvailable($paths);

            $sources[] = new LogSource(
                id: 'provider_' . $provider->getProviderId(),
                name: $provider->getProviderName(),
                description: $provider->getProviderDescription(),
                origin: LogSource::ORIGIN_PROVIDER,
                providerId: $provider->getProviderId(),
                paths: $paths,
                available: $provider->isActive() && $pathsAvailable,
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
            $configured = $this->moduleSettingService->getCollection(
                Module::SETTING_LOGSENDER_STATIC_PATHS,
                Module::ID
            );
        } catch (\Throwable) {
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
                path: $config['path'],
                type: $type,
                name: $config['name'] ?? basename($config['path']),
                description: $config['description'] ?? '',
                filePattern: $config['pattern'] ?? null,
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
