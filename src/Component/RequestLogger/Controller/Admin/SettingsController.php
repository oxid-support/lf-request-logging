<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusServiceInterface;
use OxidSupport\Heartbeat\Module\Module;
use OxidSupport\Heartbeat\Shared\Controller\Admin\AbstractComponentController;
use OxidSupport\Heartbeat\Shared\Controller\Admin\TogglableComponentInterface;

/**
 * Request Logger settings controller for the Heartbeat.
 * Allows configuration of the Request Logger component.
 */
class SettingsController extends AbstractComponentController implements TogglableComponentInterface
{
    protected $_sThisTemplate = '@oxsheartbeat/admin/heartbeat_requestlogger_settings';

    private ?ApiUserStatusServiceInterface $apiUserStatusService = null;

    public function isComponentActive(): bool
    {
        try {
            return $this->getModuleSettingService()->getBoolean(
                Module::SETTING_REQUESTLOGGER_ACTIVE,
                Module::ID
            );
        } catch (\Throwable) {
            return false;
        }
    }

    public function toggleComponent(): void
    {
        if (!$this->canToggle()) {
            return;
        }

        $this->getModuleSettingService()->saveBoolean(
            Module::SETTING_REQUESTLOGGER_ACTIVE,
            !$this->isComponentActive(),
            Module::ID
        );
    }

    public function canToggle(): bool
    {
        return true;
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

    /**
     * Get current settings for the template.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $moduleSettingService = $this->getModuleSettingService();
        $moduleId = Module::ID;

        return [
            'componentActive' => $moduleSettingService->getBoolean(Module::SETTING_REQUESTLOGGER_ACTIVE, $moduleId),
            'logLevel' => (string) $moduleSettingService->getString(Module::SETTING_REQUESTLOGGER_LOG_LEVEL, $moduleId),
            'logFrontend' => $moduleSettingService->getBoolean(Module::SETTING_REQUESTLOGGER_LOG_FRONTEND, $moduleId),
            'logAdmin' => $moduleSettingService->getBoolean(Module::SETTING_REQUESTLOGGER_LOG_ADMIN, $moduleId),
            'redactAllValues' => $moduleSettingService->getBoolean(
                Module::SETTING_REQUESTLOGGER_REDACT_ALL_VALUES,
                $moduleId
            ),
            'redactFields' => $moduleSettingService->getCollection(
                Module::SETTING_REQUESTLOGGER_REDACT_FIELDS,
                $moduleId
            ),
        ];
    }

    /**
     * Save settings from form submission.
     */
    public function save(): void
    {
        $params = Registry::getRequest()->getRequestParameter('editval');
        if (!is_array($params)) {
            return;
        }

        $moduleSettingService = $this->getModuleSettingService();
        $moduleId = Module::ID;

        if (isset($params['logLevel'])) {
            $moduleSettingService->saveString(
                Module::SETTING_REQUESTLOGGER_LOG_LEVEL,
                $params['logLevel'],
                $moduleId
            );
        }

        $moduleSettingService->saveBoolean(
            Module::SETTING_REQUESTLOGGER_LOG_FRONTEND,
            isset($params['logFrontend']) && $params['logFrontend'] === '1',
            $moduleId
        );

        $moduleSettingService->saveBoolean(
            Module::SETTING_REQUESTLOGGER_LOG_ADMIN,
            isset($params['logAdmin']) && $params['logAdmin'] === '1',
            $moduleId
        );

        $moduleSettingService->saveBoolean(
            Module::SETTING_REQUESTLOGGER_REDACT_ALL_VALUES,
            isset($params['redactAllValues']) && $params['redactAllValues'] === '1',
            $moduleId
        );

        if (isset($params['redactFields'])) {
            $fields = array_filter(
                array_map('trim', explode("\n", $params['redactFields']))
            );
            $moduleSettingService->saveCollection(
                Module::SETTING_REQUESTLOGGER_REDACT_FIELDS,
                $fields,
                $moduleId
            );
        }
    }
}
