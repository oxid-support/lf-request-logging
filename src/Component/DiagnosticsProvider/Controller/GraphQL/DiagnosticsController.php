<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Component\DiagnosticsProvider\Controller\GraphQL;

use OxidSupport\Heartbeat\Component\DiagnosticsProvider\DataType\DiagnosticsType;
use OxidSupport\Heartbeat\Component\DiagnosticsProvider\Service\DiagnosticsProviderInterface;
use OxidSupport\Heartbeat\Component\DiagnosticsProvider\Service\DiagnosticsProviderStatusServiceInterface;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Right;

final class DiagnosticsController
{
    public function __construct(
        private readonly DiagnosticsProviderInterface $diagnosticsProvider,
        private readonly DiagnosticsProviderStatusServiceInterface $statusService
    ) {
    }

    /**
     * Get comprehensive diagnostics information about the shop
     */
    #[Query]
    #[Logged]
    #[Right('DIAGNOSTICS_VIEW')]
    public function diagnostics(): ?DiagnosticsType
    {
        if (!$this->statusService->isActive()) {
            return null;
        }

        $diagnosticsArray = $this->diagnosticsProvider->getDiagnostics();
        return DiagnosticsType::fromDiagnosticsArray($diagnosticsArray);
    }
}
