<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Shop\Facade;

class Facts
{
    public function getEdition()
    {
       return (new \OxidEsales\Facts\Facts())->getEdition();
    }
}
