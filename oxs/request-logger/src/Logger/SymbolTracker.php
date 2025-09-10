<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Logger;

/**
 * Minimaler Symbol-Tracker:
 * - merkt sich am Start die vorhandenen Klassen/Interfaces/Traits
 * - liefert am Ende das Delta in der Reihenfolge, wie PHP sie deklariert hat
 * - optional leichter Filter (Alias/Legacy raus), ohne Sortierung, ohne ENV
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

    /** Einmal am Request-Beginn aufrufen (idempotent). */
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
     * Liefert die neu deklarierten Symbole in Lade-Reihenfolge.
     * @param bool $strict Wenn true, werden Alias/eval per Reflection herausgefiltert (etwas teurer).
     * @return array{symbols: list<string>}
     */
    public static function report(bool $strict = false): array
    {
        $classesNew    = array_values(array_diff(get_declared_classes(),    self::$classesBefore));
        $interfacesNew = array_values(array_diff(get_declared_interfaces(), self::$interfacesBefore));
        $traitsNew     = array_values(array_diff(get_declared_traits(),     self::$traitsBefore));

        // Hintereinander kleben – Reihenfolge bleibt wie deklariert
        $symbols = array_merge($classesNew, $interfacesNew, $traitsNew);

        // Leichter, schneller Filter
        $symbols = array_values(array_filter($symbols, static function (string $name): bool {
            $lower = strtolower($name);

            // *_parent-Aliasse raus
            if (str_ends_with($lower, '_parent')) {
                return false;
            }
            // reine Legacy-Kurzformen (z. B. "oxuser") raus
            if ($name === $lower && !str_contains($name, '\\')) {
                return false;
            }
            return true;
        }));

        if ($strict) {
            // Optional: Aliasse/eval (ohne Datei) rausfiltern
            $out = [];
            foreach ($symbols as $name) {
                try {
                    $ref = new \ReflectionClass($name);
                    $file = $ref->getFileName();
                    if (!is_string($file) || $file === '') {
                        continue; // alias/eval
                    }
                    $out[] = $name;
                } catch (\Throwable) {
                    // sicherheitshalber verwerfen
                }
            }
            $symbols = $out;
        }

        return ['symbols' => $symbols];
    }
}
