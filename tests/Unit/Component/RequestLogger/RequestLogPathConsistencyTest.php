<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Component\RequestLogger;

use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\CorrelationId\CorrelationIdProviderInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\LoggerFactory;
use OxidSupport\Heartbeat\Component\RequestLogger\Infrastructure\Logger\Processor\CorrelationIdProcessorInterface;
use OxidSupport\Heartbeat\Component\RequestLogger\Service\LogPathProvider;
use OxidSupport\Heartbeat\Shop\Facade\ModuleSettingFacadeInterface;
use OxidSupport\Heartbeat\Shop\Facade\ShopFacadeInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * The request log is WRITTEN by LoggerFactory and SERVED (to the Log Sender) by
 * LogPathProvider. Both build the per-shop directory independently, so if they
 * ever drift (separator, name, shop-id handling) logs are written to one
 * directory and served from another — silent data loss, or a subshop served the
 * wrong shop's logs. This couples the two and uses a non-1 shop id, so it also
 * fails if either side hardcodes the shop id. See OXS-3130.
 */
final class RequestLogPathConsistencyTest extends TestCase
{
    public function testWriterFileLivesUnderTheReaderServedDirectory(): void
    {
        if (!class_exists('Monolog\Logger')) {
            $this->markTestSkipped('Monolog is not installed');
        }

        $shopFacade = $this->createMock(ShopFacadeInterface::class);
        $shopFacade->method('getLogsPath')->willReturn('/var/www/log/');
        $shopFacade->method('getShopId')->willReturn(42); // not 1: catches a hardcoded shop id

        $readerDir = (new LogPathProvider(
            $shopFacade,
            $this->createMock(ModuleSettingFacadeInterface::class)
        ))->getLogPaths()[0]->path;

        $factory = new LoggerFactory(
            $this->createMock(CorrelationIdProcessorInterface::class),
            $this->createMock(CorrelationIdProviderInterface::class),
            $shopFacade,
            $this->createMock(ModuleSettingFacadeInterface::class)
        );
        $logFilePath = new ReflectionMethod($factory, 'logFilePath');
        $logFilePath->setAccessible(true);
        $writerFile = $logFilePath->invoke($factory, 'some-correlation-id');

        $this->assertStringContainsString('/oxs-request-logger/42/', $readerDir);
        $this->assertStringStartsWith(
            $readerDir,
            $writerFile,
            'the writer must write into the exact directory the reader serves'
        );
    }
}
