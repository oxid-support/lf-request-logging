<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Compatibility\DiContainer;

use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;

class Oxid6DiContainerAdapter implements DiContainerPort
{
    public function get(string $interface)
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get($interface);
    }
}
