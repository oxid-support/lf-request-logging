<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Compatibility\DiContainer;

use RuntimeException;

class Factory
{
    public static function create(): DiContainerPort
    {
        // v7: ContainerFacade::get()
        $facade = '\OxidEsales\EshopCommunity\Core\Di\ContainerFacade';
        if (class_exists($facade) && method_exists($facade, 'get')) {
            return new Oxid7DiContainerAdapter();
        }

        // v6: ContainerFactory -> ContainerInterface
        $factory = '\OxidEsales\EshopCommunity\Internal\Container\ContainerFactory';
        if (class_exists($factory) && method_exists($factory, 'getInstance')) {
            return new Oxid6DiContainerAdapter();
        }

        throw new RuntimeException('Cannot obtain DI container.');
    }
}
