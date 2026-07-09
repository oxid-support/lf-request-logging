<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\DiagnosticsProvider\Controller\Admin;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusServiceInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shared\Controller\Admin\AbstractComponentController;
use OxidSupport\Heartbeat\Shared\Controller\Admin\TogglableComponentInterface;

/**
 * Diagnostics Provider management controller for Heartbeat.
 * Allows activation/deactivation of the Diagnostics Provider component.
 */
class ManageController extends AbstractComponentController implements TogglableComponentInterface
{
    protected $_sThisTemplate = '@oxsheartbeat/admin/heartbeat_diagnosticsprovider_manage';

    private ?ApiUserStatusServiceInterface $apiUserStatusService = null;

    public function isComponentActive(): bool
    {
        try {
            return $this->getModuleSettingService()->getBoolean(
                Module::SETTING_DIAGNOSTICSPROVIDER_ACTIVE,
                Module::ID
            );
        } catch (\Throwable) {
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
            return 'OXSHEARTBEAT_DIAGNOSTICSPROVIDER_STATUS_WARNING';
        }
        return parent::getStatusTextKey();
    }

    public function toggleComponent(): void
    {
        if (!$this->canToggle()) {
            return;
        }

        $this->getModuleSettingService()->saveBoolean(
            Module::SETTING_DIAGNOSTICSPROVIDER_ACTIVE,
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
        } catch (\Exception) {
            return false;
        }
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
}
