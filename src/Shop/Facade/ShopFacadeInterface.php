<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Shop\Facade;

use Psr\Log\LoggerInterface;

interface ShopFacadeInterface
{
    public function getShopId(): int;

    /**
     * Whether the shop shares customer accounts across all subshops
     * (EE `blMallUsers`). Off means users are scoped per subshop.
     */
    public function areMallUsersEnabled(): bool;

    public function getShopUrl(): ?string;

    public function getShopVersion(): string;

    public function getShopEdition(): string;

    public function getLanguageAbbreviation(): string;

    public function getSessionId(): ?string;

    public function getUserId(): ?string;

    public function getUsername(): ?string;

    public function getRequestParameter(string $name): ?string;

    public function getLogsPath(): string;

    public function getLogger(): LoggerInterface;

    public function isAdmin(): bool;
}
