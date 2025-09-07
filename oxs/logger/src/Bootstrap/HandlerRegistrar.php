<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Bootstrap;

use OxidSupport\Logger\Logger\ShopLogger;

final class HandlerRegistrar
{
    public static function onActivate(): void
    {
        ShopLogger::get()->info('module.activated');
    }

    public static function onDeactivate(): void
    {
        ShopLogger::get()->info('module.deactivated');
    }
}
