<?php
declare(strict_types=1);


namespace OxidSupport\RequestLogger\Shop\Facade;

class Facts
{
    public function getEdition()
    {
       return (new \OxidEsales\Facts\Facts())->getEdition();
    }
}
