<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\Security;

use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacadeInterface;

class SensitiveDataRedactor implements SensitiveDataRedactorInterface
{
    private ModuleSettingFacadeInterface $moduleSettingFacade;

    public function __construct(ModuleSettingFacadeInterface $moduleSettingFacade)
    {
        $this->moduleSettingFacade = $moduleSettingFacade;
    }

    public function redact(array $values): array
    {
        // If redact all values is enabled, redact everything
        if ($this->moduleSettingFacade->isRedactAllValuesEnabled()) {
            return $this->redactAllValues($values);
        }

        // Otherwise, only redact specific keys from the blocklist
        return $this->redactBlocklistedKeys($values);
    }

    private function redactAllValues(array $values): array
    {
        // Parameters that should not be redacted (controller and function names)
        $excludeFromRedaction = ['cl', 'fnc'];

        $out = [];

        foreach ($values as $k => $v) {
            $key = (string) $k;

            // Don't redact cl and fnc parameters
            if (in_array($key, $excludeFromRedaction, true)) {
                $out[$key] = $v;
            } else {
                $out[$key] = '[redacted]';
            }
        }

        return $out;
    }

    private function redactBlocklistedKeys(array $values): array
    {
        $blocklistLower = array_map(
            'strtolower',
            $this->moduleSettingFacade->getRedactItems(),
        );

        $out = [];

        foreach ($values as $k => $v) {
            $key = (string) $k;

            if (in_array(strtolower($key), $blocklistLower, true)) {
                $out[$key] = '[redacted]';
                continue;
            }

            // Arrays/objects fully as JSON (no limits, nothing truncated)
            if (is_array($v) || is_object($v)) {
                $json = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $out[$key] = $json !== false ? $json : '[unserializable]';
                continue;
            }

            // Strings/Scalars/NULL: unchanged
            $out[$key] = $v;
        }

        return $out;
    }
}
