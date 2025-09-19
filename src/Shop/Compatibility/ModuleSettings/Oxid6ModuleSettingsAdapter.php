<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;

class Oxid6ModuleSettingsAdapter implements ModuleSettingsPort
{
    public function __construct(private ModuleSettingBridgeInterface $bridge) {}

    public function getInteger(string $name, string $moduleId): int
    {
        return $this->bridge->get($moduleId, $name);
    }

    public function getFloat(string $name, string $moduleId): float
    {
        return $this->bridge->get($moduleId, $name);
    }

    public function getString(string $name, string $moduleId): string
    {
        return (string) $this->bridge->get($name, $moduleId);
    }
    public function getBoolean(string $name, string $moduleId): bool
    {
        return $this->bridge->get($name, $moduleId);
    }

    public function getCollection(string $name, string $moduleId): array
    {
        return $this->bridge->get($name, $moduleId);
    }

    public function saveInteger(string $name, int $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function saveFloat(string $name, float $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function saveString(string $name, string $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function saveBoolean(string $name, bool $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function saveCollection(string $name, array $value, string $moduleId): void
    {
        $this->bridge->save($name, $value, $moduleId);
    }

    public function exists(string $name, string $moduleId): bool
    {
        return $this->bridge->get($name, $moduleId) !== null;
    }
}
