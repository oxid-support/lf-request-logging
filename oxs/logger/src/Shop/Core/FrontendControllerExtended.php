<?php
declare(strict_types=1);


namespace OxidSupport\Logger\Shop\Core;

class FrontendControllerExtended extends FrontendControllerExtended_parent
{
    public function render()
    {
        $tpl = parent::render();

        // Metadaten zum „Was sieht der User gerade?“
        $ctrlInfo = [
            'class'    => static::class,
            'template' => is_string($tpl) ? $tpl : null,
        ];

        // Controller-Objekte leichtgewichtig extrahieren
        $ctrlEntities = ObjectInspector::fromController($this);

        // ViewData auswerten (nur Keys/Entity-Metadaten, keine Werte)
        $vd  = $this->getViewData();
        $out = [
            'viewKeys' => is_array($vd) ? array_values(array_map('strval', array_keys($vd))) : [],
        ];
        if (is_array($vd)) {
            $viewEntities = ObjectInspector::fromViewData($vd);
            $out = array_merge($out, $viewEntities);
        }

        ShopLogger::get()->info('user.view', [
            'requestId' => RequestContext::requestId(),
            'controller'=> $ctrlInfo,
            'entities'  => $ctrlEntities ?: null,
            'view'      => $out,
        ]);

        return $tpl;
    }
}
