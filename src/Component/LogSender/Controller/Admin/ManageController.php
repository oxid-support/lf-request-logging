<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\LogSender\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusServiceInterface;
use OxidSupport\Heartbeat\Component\LogSender\Service\LogCollectorServiceInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shared\Controller\Admin\AbstractComponentController;
use OxidSupport\Heartbeat\Shared\Controller\Admin\TogglableComponentInterface;

/**
 * Log Sender management controller for Heartbeat.
 * Displays recognized log sources and allows activation/deactivation.
 */
class ManageController extends AbstractComponentController implements TogglableComponentInterface
{
    protected $_sThisTemplate = 'heartbeat_logsender_manage.tpl';

    private ?ApiUserStatusServiceInterface $apiUserStatusService = null;
    private ?LogCollectorServiceInterface $logCollectorService = null;

    public function isComponentActive(): bool
    {
        try {
            return (bool) $this->getModuleSettingService()->get(
                Module::SETTING_LOGSENDER_ACTIVE,
                Module::ID
            );
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Custom status class: warning if API User not set up.
     */
    public function getStatusClass(): string
    {
        if (!$this->isApiUserSetupComplete()) {
            return self::STATUS_CLASS_WARNING;
        }
        return parent::getStatusClass();
    }

    /**
     * Custom status text: warning message if API User not set up.
     */
    public function getStatusTextKey(): string
    {
        if (!$this->isApiUserSetupComplete()) {
            return 'OXSHEARTBEAT_LOGSENDER_STATUS_WARNING';
        }
        return parent::getStatusTextKey();
    }

    public function toggleComponent(): void
    {
        if (!$this->canToggle()) {
            return;
        }

        $this->getModuleSettingService()->save(
            Module::SETTING_LOGSENDER_ACTIVE,
            !$this->isComponentActive(),
            Module::ID
        );
    }

    public function canToggle(): bool
    {
        return $this->isApiUserSetupComplete();
    }

    /**
     * Check if the API User setup is complete (api user created + password set).
     */
    public function isApiUserSetupComplete(): bool
    {
        try {
            return $this->getApiUserStatusService()->isSetupComplete();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all recognized log sources with enabled status.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLogSources(): array
    {
        try {
            $sources = $this->getLogCollectorService()->getSources();
            $enabledSources = $this->getEnabledSources();

            return array_map(function ($source) use ($enabledSources) {
                $data = $source->toArray();
                $data['enabled'] = in_array($source->id, $enabledSources, true);
                return $data;
            }, $sources);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Toggle a source's enabled status.
     */
    public function toggleSource(): void
    {
        $sourceId = Registry::getRequest()->getRequestParameter('sourceId');
        if (!is_string($sourceId) || $sourceId === '') {
            return;
        }

        $enabledSources = $this->getEnabledSources();

        if (in_array($sourceId, $enabledSources, true)) {
            $enabledSources = array_values(array_filter(
                $enabledSources,
                fn($id) => $id !== $sourceId
            ));
        } else {
            $enabledSources[] = $sourceId;
        }

        $this->getModuleSettingService()->save(
            Module::SETTING_LOGSENDER_ENABLED_SOURCES,
            $enabledSources,
            Module::ID
        );
    }

    /**
     * Get list of enabled source IDs.
     *
     * @return array<string>
     */
    public function getEnabledSources(): array
    {
        try {
            $sources = (array) $this->getModuleSettingService()->get(
                Module::SETTING_LOGSENDER_ENABLED_SOURCES,
                Module::ID
            );
            return array_values($sources);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Check if a specific source is enabled.
     */
    public function isSourceEnabled(string $sourceId): bool
    {
        return in_array($sourceId, $this->getEnabledSources(), true);
    }

    /**
     * Get the number of available log sources.
     */
    public function getAvailableSourceCount(): int
    {
        $sources = $this->getLogSources();
        return count(array_filter($sources, fn($s) => $s['available']));
    }

    /**
     * Get the total number of log sources.
     */
    public function getTotalSourceCount(): int
    {
        return count($this->getLogSources());
    }

    protected function getApiUserStatusService(): ApiUserStatusServiceInterface
    {
        if ($this->apiUserStatusService === null) {
            $this->apiUserStatusService = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ApiUserStatusServiceInterface::class);
        }
        return $this->apiUserStatusService; // @phpstan-ignore return.type
    }

    protected function getLogCollectorService(): LogCollectorServiceInterface
    {
        if ($this->logCollectorService === null) {
            $this->logCollectorService = ContainerFactory::getInstance()
                ->getContainer()
                ->get(LogCollectorServiceInterface::class);
        }
        return $this->logCollectorService; // @phpstan-ignore return.type
    }

    /**
     * Get static paths as text (one path per line).
     * Format: path (trailing / = directory, otherwise = file)
     */
    public function getStaticPathsText(): string
    {
        try {
            $paths = (array) $this->getModuleSettingService()->get(
                Module::SETTING_LOGSENDER_STATIC_PATHS,
                Module::ID
            );

            $lines = [];
            foreach ($paths as $config) {
                if (!is_array($config) || !isset($config['path'])) {
                    continue;
                }
                $path = $config['path'];
                // Add trailing slash for directories to preserve type
                if (isset($config['type']) && $config['type'] === 'directory' && substr($path, -1) !== '/') {
                    $path .= '/';
                }
                $lines[] = $path;
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Refresh log sources by invalidating the DI container cache.
     * This forces rediscovery of tagged providers on next request.
     */
    public function refreshSources(): void
    {
        ContainerFactory::resetContainer();
    }

    /**
     * Save static paths from textarea.
     */
    public function saveStaticPaths(): void
    {
        $pathsText = Registry::getRequest()->getRequestParameter('staticPaths');
        if (!is_string($pathsText)) {
            return;
        }

        $lines = array_filter(
            array_map('trim', explode("\n", $pathsText)),
            fn($line) => $line !== ''
        );

        $paths = [];
        foreach ($lines as $line) {
            $isDirectory = substr($line, -1) === '/';
            $path = rtrim($line, '/');

            $paths[] = [
                'path' => $isDirectory ? $path . '/' : $path,
                'type' => $isDirectory ? 'directory' : 'file',
                'name' => basename($path),
            ];
        }

        $this->getModuleSettingService()->save(
            Module::SETTING_LOGSENDER_STATIC_PATHS,
            $paths,
            Module::ID
        );
    }
}
