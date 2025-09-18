<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger\Security;

use OxidSupport\RequestLogger\Shop\Facade\ModuleSettingFacadeInterface;

class SensitiveDataRedactor
{
    public function __construct(
        private ModuleSettingFacadeInterface $moduleSettingFacade
    ) {}

    public function redact(array $values): array
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
