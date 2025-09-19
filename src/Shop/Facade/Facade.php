<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Facade;

use OxidEsales\Eshop\Core\Config;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\Request;
use OxidEsales\Eshop\Core\Session;
use OxidEsales\Eshop\Core\ShopVersion;
use OxidEsales\Facts\Facts;
use Psr\Log\LoggerInterface;

class Facade implements FacadeInterface
{
    public function getShopId(): int
    {
        return (int) $this->getConfig()->getShopId();
    }

    public function getShopUrl(): ?string
    {
        return $this->getConfig()->getShopUrl();
    }

    public function getLogsPath(): string
    {
        return $this->getConfig()->getLogsDir();
    }

    public function getShopVersion(): string
    {
        return ShopVersion::getVersion();
    }

    public function getShopEdition(): string
    {
        return (new Facts())->getEdition();
    }

    public function getLanguageAbbreviation(): string
    {
        return Registry::getLang()->getLanguageAbbr();
    }

    public function getSessionId(): ?string
    {
        return $this->getSession()->getId();
    }

    public function getUserId(): ?string
    {
        if ($user = $this->getSession()->getUser()) {
            return (string) $user->getId();
        }
        return null;
    }

    public function getUsername(): ?string
    {
        if ($user = $this->getSession()->getUser()) {
            return (string) $user->getFieldData('oxusername');
        }
        return null;
    }

    public function getRequestParameter(string $name): ?string
    {
        return $this->getRequest()->getRequestParameter($name);
    }

    public function getLogger(): LoggerInterface
    {
        return Registry::getLogger();
    }

    private function getConfig(): Config
    {
        return Registry::getConfig();
    }

    private function getSession(): Session
    {
        return Registry::getSession();
    }

    private function getRequest(): Request
    {
        return Registry::getRequest();
    }
}
