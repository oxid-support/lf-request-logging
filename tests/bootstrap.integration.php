<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

// Boot the OXID shop (framework, DI container, shop autoloader).
// Override with OXID_BOOTSTRAP if the shop lives elsewhere than the SDK default.
$shopBootstrap = getenv('OXID_BOOTSTRAP') ?: '/var/www/source/bootstrap.php';

if (!is_file($shopBootstrap)) {
    fwrite(STDERR, "OXID bootstrap not found: {$shopBootstrap}\n");
    fwrite(STDERR, "Set OXID_BOOTSTRAP to the shop's source/bootstrap.php.\n");
    exit(1);
}

require $shopBootstrap;

// Register the module's test namespace. When the module is installed via a
// composer path repository, composer does not refresh the path package's
// autoload, so the production autoloader will not know about tests/. This keeps
// test classes out of the production autoload while still being runnable.
spl_autoload_register(static function (string $class): void {
    $prefix = 'OxidSupport\\Heartbeat\\Tests\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    $file = __DIR__ . '/' . $relative;

    if (is_file($file)) {
        require $file;
    }
});
