<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Compatibility\DiContainer;

use OxidEsales\EshopCommunity\Core\Di\ContainerFacade;

class Oxid7DiContainerAdapter implements DiContainerPort
{
    public function get(string $interface)
    {
        return ContainerFacade::get($interface);
    }
}
