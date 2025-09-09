<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Logger;

class ShopLoggerFactory
{
    public static function create(ShopLoggerInterface $shopLogger): ShopLoggerInterface
    {
        $shopLogger->create();
        return $shopLogger;
    }
}
