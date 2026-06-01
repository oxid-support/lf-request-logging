<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\ApiVersion\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;
use OxidSupport\Heartbeat\Component\ApiVersion\DataType\ApiVersionType;
use OxidSupport\Heartbeat\Component\ApiVersion\DataType\ComponentStatusType;
use OxidSupport\Heartbeat\Module\Module;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

final class ApiVersionService implements ApiVersionServiceInterface
{
    public function __construct(
        private ModuleSettingServiceInterface $moduleSettingService,
        private AuthenticationServiceInterface $authenticationService,
    ) {
    }

    public function getApiVersion(): ApiVersionType
    {
        if (!$this->authenticationService->isLogged()) {
            return new ApiVersionType(isInstalled: true);
        }

        return new ApiVersionType(
            isInstalled: true,
            apiVersion: Module::API_VERSION,
            apiSchemaHash: self::computeSchemaHash(Module::SUPPORTED_OPERATIONS),
            moduleVersion: Module::VERSION,
            supportedOperations: Module::SUPPORTED_OPERATIONS,
            componentStatus: $this->getComponentStatuses(),
        );
    }

    /**
     * @return ComponentStatusType[]
     */
    private function getComponentStatuses(): array
    {
        return [
            new ComponentStatusType(
                'requestLogger',
                $this->isSettingActive(Module::SETTING_REQUESTLOGGER_ACTIVE),
            ),
            new ComponentStatusType(
                'logSender',
                $this->isSettingActive(Module::SETTING_LOGSENDER_ACTIVE),
            ),
            new ComponentStatusType(
                'diagnosticsProvider',
                $this->isSettingActive(Module::SETTING_DIAGNOSTICSPROVIDER_ACTIVE),
            ),
        ];
    }

    private function isSettingActive(string $settingKey): bool
    {
        try {
            return $this->moduleSettingService->getBoolean($settingKey, Module::ID);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Compute a schema hash from the list of supported operations.
     * Algorithm: sort operations alphabetically, join with newline, SHA-256, take first 16 hex chars.
     *
     * @param string[] $operations
     */
    public static function computeSchemaHash(array $operations): string
    {
        $sorted = $operations;
        sort($sorted);

        return substr(hash('sha256', implode("\n", $sorted)), 0, 16);
    }
}
