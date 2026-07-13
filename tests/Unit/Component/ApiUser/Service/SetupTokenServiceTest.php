<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\ApiUser\Service;

use OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\ApiUserStatusServiceInterface;
use OxidSupport\Heartbeat\Component\ApiUser\Service\SetupTokenService;
use OxidSupport\Heartbeat\Component\ApiUser\Service\TokenGeneratorInterface;
use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetupTokenService::class)]
final class SetupTokenServiceTest extends TestCase
{
    /**
     * Password not set on this shop: a fresh token is generated and saved.
     */
    public function testGeneratesFreshTokenWhenPasswordNotSet(): void
    {
        $status = $this->createMock(ApiUserStatusServiceInterface::class);
        $status->method('isApiUserPasswordSet')->willReturn(false);

        $generator = $this->createMock(TokenGeneratorInterface::class);
        $generator->expects($this->once())->method('generate')->willReturn('fresh-per-shop-token');

        $settings = $this->createMock(ModuleSettingBridgeInterface::class);
        $settings->expects($this->once())
            ->method('save')
            ->with(Module::SETTING_APIUSER_SETUP_TOKEN, 'fresh-per-shop-token', Module::ID);

        (new SetupTokenService($status, $generator, $settings))->ensureSetupToken();
    }

    /**
     * Password already set: setup is complete, so the token is cleared (never
     * regenerated) so a leftover/inherited value cannot be replayed.
     */
    public function testClearsTokenWhenPasswordAlreadySet(): void
    {
        $status = $this->createMock(ApiUserStatusServiceInterface::class);
        $status->method('isApiUserPasswordSet')->willReturn(true);

        $generator = $this->createMock(TokenGeneratorInterface::class);
        $generator->expects($this->never())->method('generate');

        $settings = $this->createMock(ModuleSettingBridgeInterface::class);
        $settings->expects($this->once())
            ->method('save')
            ->with(Module::SETTING_APIUSER_SETUP_TOKEN, '', Module::ID);

        (new SetupTokenService($status, $generator, $settings))->ensureSetupToken();
    }

    /**
     * Regression for the EE subshop shared-token bug (OXS-3103): the service does
     * not read the stored token, so an existing (inherited) value never blocks
     * regeneration while the password is not set. Two shops therefore get two
     * independent tokens.
     */
    public function testRegeneratesRegardlessOfAnyExistingTokenValue(): void
    {
        $status = $this->createMock(ApiUserStatusServiceInterface::class);
        $status->method('isApiUserPasswordSet')->willReturn(false);

        $generator = $this->createMock(TokenGeneratorInterface::class);
        $generator->method('generate')->willReturn('this-shops-own-token');

        $settings = $this->createMock(ModuleSettingBridgeInterface::class);
        $settings->expects($this->once())
            ->method('save')
            ->with(Module::SETTING_APIUSER_SETUP_TOKEN, 'this-shops-own-token', Module::ID);

        (new SetupTokenService($status, $generator, $settings))->ensureSetupToken();
    }
}
