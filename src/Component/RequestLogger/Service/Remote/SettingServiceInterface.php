<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Service\Remote;

use OxidSupport\Heartbeat\Component\RequestLogger\DataType\SettingType;

interface SettingServiceInterface
{
    public function getLogLevel(): string;

    public function setLogLevel(string $value): string;

    public function isLogFrontendEnabled(): bool;

    public function setLogFrontendEnabled(bool $value): bool;

    public function isLogAdminEnabled(): bool;

    public function setLogAdminEnabled(bool $value): bool;

    public function getRedactItems(): string;

    public function isRedactAllValuesEnabled(): bool;

    public function setRedactAllValuesEnabled(bool $value): bool;

    /**
     * @return SettingType[]
     */
    public function getAllSettings(): array;
}
