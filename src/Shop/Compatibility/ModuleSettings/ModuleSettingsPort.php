<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Compatibility\ModuleSettings;

interface ModuleSettingsPort
{
    public function getInteger(string $name, string $moduleId): int;
    public function getFloat(string $name, string $moduleId): float;
    public function getString(string $name, string $moduleId): string;
    public function getBoolean(string $name, string $moduleId): bool;
    /** @return array<string|int|float|bool|array> */
    public function getCollection(string $name, string $moduleId): array;

    public function saveInteger(string $name, int $value, string $moduleId): void;
    public function saveFloat(string $name, float $value, string $moduleId): void;
    public function saveString(string $name, string $value, string $moduleId): void;
    public function saveBoolean(string $name, bool $value, string $moduleId): void;
    /** @param array<string|int|float|bool|array> $value */
    public function saveCollection(string $name, array $value, string $moduleId): void;

    public function exists(string $name, string $moduleId): bool;
}
