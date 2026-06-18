<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Shared\Controller\Admin;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Dao\ShopConfigurationDaoInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ModuleConfiguration;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\DataObject\ShopConfiguration;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
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

    public function testIsModuleActivatedReturnsTrueForActiveModule(): void
    {
        $moduleId = 'test_module';
        $shopId = 1;

        $moduleConfiguration = $this->createMock(ModuleConfiguration::class);
        $moduleConfiguration
            ->method('isActivated')
            ->willReturn(true);

        $shopConfiguration = $this->createMock(ShopConfiguration::class);
        $shopConfiguration
            ->method('getModuleConfiguration')
            ->with($moduleId)
            ->willReturn($moduleConfiguration);

        $controller = $this->createControllerWithModuleCheck($shopId, $shopConfiguration);

        $result = $this->invokeIsModuleActivated($controller, $moduleId);

        $this->assertTrue($result);
    }

    public function testIsModuleActivatedReturnsFalseForInactiveModule(): void
    {
        $moduleId = 'test_module';
        $shopId = 1;

        $moduleConfiguration = $this->createMock(ModuleConfiguration::class);
        $moduleConfiguration
            ->method('isActivated')
            ->willReturn(false);

        $shopConfiguration = $this->createMock(ShopConfiguration::class);
        $shopConfiguration
            ->method('getModuleConfiguration')
            ->with($moduleId)
            ->willReturn($moduleConfiguration);

        $controller = $this->createControllerWithModuleCheck($shopId, $shopConfiguration);

        $result = $this->invokeIsModuleActivated($controller, $moduleId);

        $this->assertFalse($result);
    }

    public function testIsModuleActivatedReturnsFalseOnException(): void
    {
        $moduleId = 'nonexistent_module';
        $shopId = 1;

        $shopConfiguration = $this->createMock(ShopConfiguration::class);
        $shopConfiguration
            ->method('getModuleConfiguration')
            ->with($moduleId)
            ->willThrowException(new \Exception('Module not found'));

        $controller = $this->createControllerWithModuleCheck($shopId, $shopConfiguration);

        $result = $this->invokeIsModuleActivated($controller, $moduleId);

        $this->assertFalse($result);
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

    private function createControllerWithModuleCheck(
        int $shopId,
        ShopConfiguration $shopConfiguration
    ): AbstractComponentController {
        $context = $this->createMock(ContextInterface::class);
        $context
            ->method('getCurrentShopId')
            ->willReturn($shopId);

        $shopConfigurationDao = $this->createMock(ShopConfigurationDaoInterface::class);
        $shopConfigurationDao
            ->method('get')
            ->with($shopId)
            ->willReturn($shopConfiguration);

        return new class ($context, $shopConfigurationDao) extends AbstractComponentController {
            public function __construct(
                private ContextInterface $contextMock,
                private ShopConfigurationDaoInterface $shopConfigurationDaoMock
            ) {
            }

            public function isComponentActive(): bool
            {
                return false;
            }

            protected function getContext(): ContextInterface
            {
                return $this->contextMock;
            }

            protected function getShopConfigurationDao(): ShopConfigurationDaoInterface
            {
                return $this->shopConfigurationDaoMock;
            }

            public function callIsModuleActivated(string $moduleId): bool
            {
                return $this->isModuleActivated($moduleId);
            }
        };
    }

    private function invokeIsModuleActivated(AbstractComponentController $controller, string $moduleId): bool
    {
        return $controller->callIsModuleActivated($moduleId);
    }
}
