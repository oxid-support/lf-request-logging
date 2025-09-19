<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Shop\Compatibility\DiContainer;

interface DiContainerPort
{
    public function get(string $interface);
}
