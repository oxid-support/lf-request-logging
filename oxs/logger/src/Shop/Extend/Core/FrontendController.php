<?php
declare(strict_types=1);

namespace OxidSupport\Logger\Shop\Extend\Core;

use OxidEsales\Eshop\Application\Controller\FrontendController as CoreFC;
use OxidSupport\Logger\Logger\ShopLogger;
use OxidSupport\Logger\Logger\ObjectInspector;
use OxidSupport\Logger\Logger\RequestContext;

class FrontendController extends CoreFC
{
    public function render()
    {
        $tpl = parent::render();

        $ctrlInfo = [
            'class'    => static::class,
            'template' => is_string($tpl) ? $tpl : null,
        ];

        $ctrlEntities = ObjectInspector::fromController($this);

        $vd  = $this->getViewData();
        $view = [
            'viewKeys' => is_array($vd) ? array_values(array_map('strval', array_keys($vd))) : [],
        ];
        if (is_array($vd)) {
            $view = array_merge($view, ObjectInspector::fromViewData($vd));
        }

        ShopLogger::get()->info('user.view', [
            'requestId' => RequestContext::requestId(),
            'controller'=> $ctrlInfo,
            'entities'  => $ctrlEntities, // immer Array (ggf. mit leerem controllerParams)
            'view'      => $view,
        ]);

        return $tpl;
    }
}
