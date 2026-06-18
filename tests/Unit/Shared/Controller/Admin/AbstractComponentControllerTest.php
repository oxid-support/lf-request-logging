<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Shared\Controller\Admin;

use OxidSupport\Heartbeat\Shared\Controller\Admin\AbstractComponentController;
use OxidSupport\Heartbeat\Shared\Controller\Admin\ComponentControllerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractComponentController::class)]
final class AbstractComponentControllerTest extends TestCase
{
    /**
     * @dataProvider statusClassDataProvider
     */
    #[DataProvider('statusClassDataProvider')]
    public function testGetStatusClassReturnsCorrectValue(bool $isActive, string $expectedClass): void
    {
        $controller = $this->createControllerWithActiveState($isActive);

        $this->assertSame($expectedClass, $controller->getStatusClass());
    }

    public static function statusClassDataProvider(): array
    {
        return [
            'active component returns active class' => [
                true,
                ComponentControllerInterface::STATUS_CLASS_ACTIVE,
            ],
            'inactive component returns inactive class' => [
                false,
                ComponentControllerInterface::STATUS_CLASS_INACTIVE,
            ],
        ];
    }

    /**
     * @dataProvider statusTextKeyDataProvider
     */
    #[DataProvider('statusTextKeyDataProvider')]
    public function testGetStatusTextKeyReturnsCorrectValue(bool $isActive, string $expectedKey): void
    {
        $controller = $this->createControllerWithActiveState($isActive);

        $this->assertSame($expectedKey, $controller->getStatusTextKey());
    }

    public static function statusTextKeyDataProvider(): array
    {
        return [
            'active component returns active text key' => [
                true,
                'OXSHEARTBEAT_LF_STATUS_ACTIVE',
            ],
            'inactive component returns inactive text key' => [
                false,
                'OXSHEARTBEAT_LF_STATUS_INACTIVE',
            ],
        ];
    }

    public function testStatusClassConstantsAreDefined(): void
    {
        $this->assertSame('active', ComponentControllerInterface::STATUS_CLASS_ACTIVE);
        $this->assertSame('inactive', ComponentControllerInterface::STATUS_CLASS_INACTIVE);
        $this->assertSame('warning', ComponentControllerInterface::STATUS_CLASS_WARNING);
    }

    private function createControllerWithActiveState(bool $isActive): AbstractComponentController
    {
        return new class ($isActive) extends AbstractComponentController {
            public function __construct(private bool $active)
            {
            }

            public function isComponentActive(): bool
            {
                return $this->active;
            }
        };
    }
}
