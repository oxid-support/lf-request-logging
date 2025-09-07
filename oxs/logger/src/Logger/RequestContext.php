<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Logger;

use OxidEsales\Eshop\Core\Exception\LanguageNotFoundException;
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\Eshop\Core\ShopVersion;

final class RequestContext
{
    /**
     * @throws RandomException
     * @throws LanguageNotFoundException
     */
    public static function build(): array
    {
        $user = Registry::getSession()->getUser() ?: null;

        return [
            'ts'          => date('c'),
            'shopId'      => (int) Registry::getConfig()->getShopId(),
            'shopUrl'     => Registry::getConfig()->getShopUrl(),
            'requestId'   => self::requestId(),
            'sessionId'   => Registry::getSession()->getId(),
            'userId'      => $user?->getId(),
            'userLogin'   => $user?->oxuser__oxusername->rawValue ?? null,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'method'      => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri'         => ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''),
            'lang'        => Registry::getLang()->getLanguageAbbr(),
            'edition'     => 'todo',
            'php'         => PHP_VERSION,
            'oxid'        => ShopVersion::getVersion(),
        ];
    }

    /**
     * @throws RandomException
     */
    public static function requestId(): string
    {
        static $id = null;
        if ($id === null) {
            $id = bin2hex(random_bytes(8));
        }
        return $id;
    }
}
