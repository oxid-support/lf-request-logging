<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Facade\ModuleSettingServiceInterface;

class Oxid7ModuleSettingsAdapter implements ModuleSettingsPort
{
    public function __construct(private ModuleSettingServiceInterface $svc) {}

    public function getInteger(string $name, string $moduleId, $default = 0): int
    {
        return $this->svc->getInteger($moduleId, $name);
    }

    public function getFloat(string $name, string $moduleId, $default = 0.0): float
    {
        return $this->svc->getFloat($moduleId, $name);
    }

    public function getString(string $name, string $moduleId): string //@todo UnicodeString
    {
        return (string) $this->svc->getString($name, $moduleId);
    }
    public function getBoolean(string $name, string $moduleId): bool
    {
        return $this->svc->getBoolean($name, $moduleId);
    }

    public function getCollection(string $name, string $moduleId): array
    {
        return $this->svc->getCollection($name, $moduleId);
    }

    public function saveInteger(string $name, int $value, string $moduleId): void
    {
        $this->svc->saveInteger($name, $value, $moduleId);
    }

    public function saveFloat(string $name, float $value, string $moduleId): void
    {
        $this->svc->saveFloat($name, $value, $moduleId);
    }

    public function saveString(string $name, string $value, string $moduleId): void
    {
        $this->svc->saveString($name, $value, $moduleId);
    }

    public function saveBoolean(string $name, bool $value, string $moduleId): void
    {
        $this->svc->saveBoolean($name, $value, $moduleId);
    }

    public function saveCollection(string $name, array $value, string $moduleId): void
    {
        $this->svc->saveCollection($name, $value, $moduleId);
    }

    public function exists(string $name, string $moduleId): bool
    {
        return $this->svc->exists($name, $moduleId);
    }
}
