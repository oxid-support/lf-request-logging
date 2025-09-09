<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Logger;

use Exception;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OxidSupport\Logger\Module\Module;
use OxidSupport\Logger\Request\CorrelationIdProviderInterface;

class LoggerFactory
{
    /**
     * @throws Exception
     */
    public static function create(CorrelationIdProviderInterface $correlationIdProvider): Logger
    {
        $handler = new StreamHandler(
            static::logfilePath(
                $correlationIdProvider::get()
            )
        );

        $handler->setFormatter(
            new JsonFormatter()
        );

        $logger = new Logger(Module::ID);
        $logger->pushHandler($handler);

        return $logger;
    }

    private static function logfilePath(string $filename): string
    {
        $filename = sprintf('%s-%s.json', MOdule::ID, $filename);

        return
            OX_BASE_PATH
            . 'log'
            . DIRECTORY_SEPARATOR
            . $filename;
    }
}
