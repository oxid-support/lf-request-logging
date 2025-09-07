<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Logger;

final class StackTraceProcessor
{
    public function __construct(
        private int $maxDepth = 12,
        private bool $includeArgs = false,
        /** @var list<string> */
        private array $ignoreFragments = [
            '/vendor/monolog/',
            '/repo/oxs/logger/src/',      // dein Modul-Pfad
            '/source/modules/oxs/logger', // falls anderes Layout
        ]
    ) {}

    /** @param array<string,mixed> $record */
    public function __invoke(array $record): array
    {
        $msg = (string)($record['message'] ?? '');
        $isActionEvent =
            str_starts_with($msg, 'request.route') ||
            str_starts_with($msg, 'controller.render') ||
            str_starts_with($msg, 'user.view');

        if (!$isActionEvent) {
            return $record;
        }

        $flags = $this->includeArgs ? 0 : DEBUG_BACKTRACE_IGNORE_ARGS;
        $trace = debug_backtrace($flags, $this->maxDepth);

        $norm = [];
        foreach ($trace as $f) {
            $file = $f['file'] ?? '';
            if ($file !== '') {
                foreach ($this->ignoreFragments as $frag) {
                    if (str_contains($file, $frag)) {
                        continue 2; // Frame überspringen
                    }
                }
            }
            $norm[] = [
                'file'  => $file ?: null,
                'line'  => $f['line'] ?? null,
                'class' => $f['class'] ?? null,
                'type'  => $f['type'] ?? null,
                'func'  => $f['function'] ?? null,
            ];
        }

        $record['extra']['trace'] = $norm;
        return $record;
    }
}