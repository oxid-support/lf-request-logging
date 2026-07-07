<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\Security;

use OxidSupport\Heartbeat\Shop\Facade\ModuleSettingFacadeInterface;

class SensitiveDataRedactor implements SensitiveDataRedactorInterface
{
    private ModuleSettingFacadeInterface $moduleSettingFacade;

    public function __construct(ModuleSettingFacadeInterface $moduleSettingFacade)
    {
        $this->moduleSettingFacade = $moduleSettingFacade;
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    public function redact(array $values): array
    {
        // If redact all values is enabled, redact everything
        if ($this->moduleSettingFacade->isRedactAllValuesEnabled()) {
            return $this->redactAllValues($values);
        }

        // Otherwise, only redact specific keys from the blocklist
        return $this->redactBlocklistedKeys($values);
    }

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
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

    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
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

            // Arrays/objects fully as JSON (no limits, nothing truncated).
            // Recurse first so a blocklisted key nested at any depth is redacted
            // instead of leaking in the wholesale JSON encoding.
            if (is_array($v) || is_object($v)) {
                $redacted = $this->redactNested((array) $v, $blocklistLower);
                $json = json_encode($redacted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $out[$key] = $json !== false ? $json : '[unserializable]';
                continue;
            }

            // Strings/Scalars/NULL: unchanged
            $out[$key] = $v;
        }

        return $out;
    }

    /**
     * Recursively redact blocklisted keys at every depth.
     *
     * @param array<array-key, mixed> $values
     * @param string[] $blocklistLower lowercase blocklist entries
     * @return array<array-key, mixed>
     */
    private function redactNested(array $values, array $blocklistLower): array
    {
        $out = [];

        foreach ($values as $k => $v) {
            if (is_string($k) && in_array(strtolower($k), $blocklistLower, true)) {
                $out[$k] = '[redacted]';
                continue;
            }

            if (is_array($v) || is_object($v)) {
                $out[$k] = $this->redactNested((array) $v, $blocklistLower);
                continue;
            }

            $out[$k] = $v;
        }

        return $out;
    }
}
