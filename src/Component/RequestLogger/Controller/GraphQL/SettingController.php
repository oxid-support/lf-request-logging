<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Controller\GraphQL;

use OxidSupport\Heartbeat\Component\RequestLogger\DataType\SettingType;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\RemoteComponentStatusServiceInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote\SettingServiceInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Right;

final class SettingController
{
    public function __construct(
        private SettingServiceInterface $settingService,
        private RemoteComponentStatusServiceInterface $componentStatusService
    ) {
    }

    /** @return SettingType[] */
    #[Query]
    #[Logged]
    #[Right('REQUEST_LOGGER_VIEW')]
    public function requestLoggerSettings(): array
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->getAllSettings();
    }

    #[Query]
    #[Logged]
    #[Right('REQUEST_LOGGER_VIEW')]
    public function requestLoggerLogLevel(): string
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->getLogLevel();
    }

    #[Query]
    #[Logged]
    #[Right('REQUEST_LOGGER_VIEW')]
    public function requestLoggerLogFrontend(): bool
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->isLogFrontendEnabled();
    }

    #[Query]
    #[Logged]
    #[Right('REQUEST_LOGGER_VIEW')]
    public function requestLoggerLogAdmin(): bool
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->isLogAdminEnabled();
    }

    #[Query]
    #[Logged]
    #[Right('REQUEST_LOGGER_VIEW')]
    public function requestLoggerRedact(): string
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->getRedactItems();
    }

    #[Query]
    #[Logged]
    #[Right('REQUEST_LOGGER_VIEW')]
    public function requestLoggerRedactAllValues(): bool
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->isRedactAllValuesEnabled();
    }

    #[Mutation]
    #[Logged]
    #[Right('REQUEST_LOGGER_CHANGE')]
    public function requestLoggerLogLevelChange(string $value): string
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->setLogLevel($value);
    }

    #[Mutation]
    #[Logged]
    #[Right('REQUEST_LOGGER_CHANGE')]
    public function requestLoggerLogFrontendChange(bool $value): bool
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->setLogFrontendEnabled($value);
    }

    #[Mutation]
    #[Logged]
    #[Right('REQUEST_LOGGER_CHANGE')]
    public function requestLoggerLogAdminChange(bool $value): bool
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->setLogAdminEnabled($value);
    }

    #[Mutation]
    #[Logged]
    #[Right('REQUEST_LOGGER_CHANGE')]
    public function requestLoggerRedactAllValuesChange(bool $value): bool
    {
        $this->componentStatusService->assertComponentActive();
        return $this->settingService->setRedactAllValuesEnabled($value);
    }
}
