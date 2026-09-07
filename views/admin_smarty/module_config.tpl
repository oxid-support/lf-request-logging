[{*
    Extends the core block "admin_module_config_form" of module_config.tpl.

    The core block contains the settings form of EVERY module, so the parent
    call at the end of this file is mandatory: without it this block replaces
    the form with its own output and no module can be configured in the admin
    any more. Only add markup around that call, never instead of it.

    Do not write the parent tag inside a comment: the block prefilter replaces
    every occurrence in this file, comments included, before smarty strips them.

    The module id is compared through getEditObjectId(), which every admin
    controller carries, and not through a method of this module's own
    ModuleConfiguration extension: whoever renders module_config.tpl must be
    able to answer it. Same condition as the 7.x twig extension.
*}]
[{if $oView->getEditObjectId() == 'oxsheartbeat'}]
    <div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border: 1px solid #2196f3; border-radius: 4px;">
        <p style="margin: 0;">
            [{oxmultilang ident="OXSHEARTBEAT_MODULE_CONFIG_HINT"}]
            <a href="#"
               onclick="top.basefrm.location='[{$oViewConf->getSelfLink()|replace:"&amp;":"&"}]&cl=heartbeat_apiuser_setup'; return false;"
               style="font-weight: bold;">[{oxmultilang ident="OXSHEARTBEAT_MODULE_CONFIG_HINT_LINK"}]</a>.
        </p>
    </div>
[{/if}]

[{$smarty.block.parent}]
