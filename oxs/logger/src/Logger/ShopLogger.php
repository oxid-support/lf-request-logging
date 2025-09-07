<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Logger;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OxidSupport\Logger\Bootstrap\Module;
use Psr\Log\LoggerInterface;

final class ShopLogger
{
    private static ?LoggerInterface $instance = null;

    public static function get(): LoggerInterface
    {
        if (self::$instance) {
            return self::$instance;
        }

        $logDir = self::logFilePath();
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $handler = new StreamHandler(
            self::logFilePath() . self::logFileName(),
            Logger::DEBUG,
            true,
            0664
        );
        $handler->setFormatter(new JsonFormatter());

        $logger = new Logger(Module::ID);
        $logger->pushHandler($handler);

        // Kontext-Processor (Request-Kontext automatisch anhängen)
        $logger->pushProcessor(function (array $record) {
            $record['extra']['context'] = RequestContext::build();
            return $record;
        });

        // Stacktrace nur für Aktions-Events, nicht für Errors
        $logger->pushProcessor(new StackTraceProcessor(
            maxDepth: (int) ($_ENV['OXSL_STACK_DEPTH'] ?? 10),
            includeArgs: false
        ));

        self::$instance = $logger;
        return self::$instance;
    }

    private static function logFilePath(): string
    {
        return
            OX_BASE_PATH .
            'log' . DIRECTORY_SEPARATOR .
            'oxs-logger' . DIRECTORY_SEPARATOR;
    }

    private static function logFileName(): string
    {
        //return RequestContext::requestId() . '.log';
        return 'oxs-request.log';
    }
}