<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger;

use ReflectionClass;
use Throwable;

/**
 * Minimal symbol tracker:
 * - remembers the classes/interfaces/traits present at the start
 * - provides the delta at the end in the order PHP declared them
 * - optional light filter (remove alias/legacy), without sorting, without ENV
 */
final class SymbolTracker
{
    /** @var list<string> */
    private static array $classesBefore = [];
    /** @var list<string> */
    private static array $interfacesBefore = [];
    /** @var list<string> */
    private static array $traitsBefore = [];
    private static bool $enabled = false;

    public static function enable(): void
    {
        if (self::$enabled) {
            return;
        }
        self::$enabled = true;

        self::$classesBefore    = get_declared_classes();
        self::$interfacesBefore = get_declared_interfaces();
        self::$traitsBefore     = get_declared_traits();
    }

    /**
     * Returns the newly declared symbols in load order.
     * @param bool $strict If true, aliases/eval are filtered out via reflection (slightly more expensive).
     * @return array{symbols: list<string>}
     */
    public static function report(bool $strict = false): array
    {
        $classesNew    = array_values(array_diff(get_declared_classes(),    self::$classesBefore));
        $interfacesNew = array_values(array_diff(get_declared_interfaces(), self::$interfacesBefore));
        $traitsNew     = array_values(array_diff(get_declared_traits(),     self::$traitsBefore));

        // Concatenate sequentially – order remains as declared
        $symbols = array_merge($classesNew, $interfacesNew, $traitsNew);

        $symbols = array_values(array_filter($symbols, static function (string $name): bool {
            $lower = strtolower($name);

            // remove *_parent aliases
            if (str_ends_with($lower, '_parent')) {
                return false;
            }
            // pure legacy short forms, remove outdated class names (e.g. "oxuser")
            if ($name === $lower && !str_contains($name, '\\')) {
                return false;
            }
            return true;
        }));

        if ($strict) {
            // Optional: filter out aliases/eval (when no file is existing) // @todo
            $out = [];
            foreach ($symbols as $name) {
                try {
                    $ref = new ReflectionClass($name);
                    $file = $ref->getFileName();
                    if (!is_string($file) || $file === '') {
                        continue; // alias/eval
                    }
                    $out[] = $name;
                } catch (Throwable) {
                    // discard as a precaution
                }
            }
            $symbols = $out;
        }

        return ['symbols' => $symbols];
    }
}
