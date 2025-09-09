<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Processor;

use OxidSupport\Logger\Shop\Context\RequestContext;

final class RequestContextProcessor
{
    /** @param array<string,mixed> $record */
    public function __invoke(array $record): array
    {
        $msg = (string) ($record['message'] ?? '');

        // Bei Start/Ende den vollen Kontext anhängen
        $isBracket =
            str_starts_with($msg, 'request.route') ||
            str_starts_with($msg, 'request.finish');

        if ($isBracket) {
            $record['extra']['context'] = RequestContext::build();
        } else {
            // Schlank: nur Korrelationsdaten
            $record['extra']['ts'] = date('c');
        }

        return $record;
    }
}
