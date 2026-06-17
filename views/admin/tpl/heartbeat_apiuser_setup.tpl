[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

[{assign var="setupToken" value=$oView->getSetupToken()}]
[{assign var="migrationExecuted" value=$oView->isMigrationExecuted()}]
[{assign var="moduleActivated" value=$oView->isHeartbeatModuleActivated()}]
[{assign var="graphqlBaseActivated" value=$oView->isGraphqlBaseActivated()}]
[{assign var="apiUserCreated" value=$oView->isApiUserCreated()}]
[{assign var="apiUserPasswordSet" value=$oView->isApiUserPasswordSet()}]
[{assign var="setupComplete" value=$oView->isSetupComplete()}]

<link rel="stylesheet" href="[{$oViewConf->getModuleUrl('oxsheartbeat', 'out/admin/css/heartbeat.css')}]">

[{* Page Header with Status Badge *}]
<h1>
    [{oxmultilang ident="OXSHEARTBEAT_APIUSER_TITLE"}]
    <span class="component-status [{$oView->getStatusClass()}]">
        [{oxmultilang ident=$oView->getStatusTextKey()}]
    </span>
</h1>
<p style="color: #666; margin-bottom: 20px;">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_DESC"}]</p>

<div>
[{if $setupComplete}]
    <div class="setup-complete">
        <h3>&#10004; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_SETUP_COMPLETE_TITLE"}]</h3>
        <p style="margin: 0;">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_SETUP_COMPLETE_TEXT"}]</p>
    </div>
[{else}]
    <div class="setup-workflow">
        <h3>[{oxmultilang ident="OXSHEARTBEAT_APIUSER_SETUP_TITLE"}]</h3>
        <ol class="workflow-steps">
            [{* Step 1: GraphQL Base installed & activated *}]
            <li class="[{if $graphqlBaseActivated}]done[{else}]active[{/if}]">
                [{oxmultilang ident="OXSHEARTBEAT_APIUSER_STEP_GRAPHQL_BASE"}]
                [{if !$graphqlBaseActivated}]<br>
                <small style="font-weight: normal;">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_STEP_GRAPHQL_BASE_DESC"}]</small>
                [{/if}]
            </li>

            [{* Step 2: Heartbeat module installed & activated *}]
            <li class="[{if $moduleActivated}]done[{elseif $graphqlBaseActivated}]active[{else}]pending[{/if}]">
                [{oxmultilang ident="OXSHEARTBEAT_APIUSER_STEP_ACTIVATE"}]
            </li>

            [{* Step 3: Run migrations *}]
            [{if !$migrationExecuted}]
            <li class="[{if $graphqlBaseActivated && $moduleActivated}]active[{else}]pending[{/if}]">
                [{oxmultilang ident="OXSHEARTBEAT_APIUSER_STEP_MIGRATE"}]
                [{if $graphqlBaseActivated && $moduleActivated}]<br>
                <small style="font-weight: normal;">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_MIGRATION_REQUIRED_TEXT"}]</small><br>
                <code id="migrationCode" class="copy-code" onclick="
                    var textarea = document.createElement('textarea');
                    textarea.value = './vendor/bin/oe-eshop-doctrine_migration migrations:migrate oxsheartbeat';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    var msg = document.getElementById('migrationMsg');
                    msg.style.display = 'inline';
                    setTimeout(function() { msg.style.display = 'none'; }, 2000);
                ">&#x1F4D1; ./vendor/bin/oe-eshop-doctrine_migration migrations:migrate oxsheartbeat</code>
                <span id="migrationMsg" class="copied-msg">&#10004; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_COPIED"}]</span>
                [{/if}]
            </li>
            [{else}]
            <li class="done">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_STEP_MIGRATE"}]</li>
            [{/if}]

            [{* Step 4: Send token *}]
            <li class="[{if $migrationExecuted && $graphqlBaseActivated && $setupToken != ''}]active[{elseif $apiUserPasswordSet}]done[{else}]pending[{/if}]">
                [{oxmultilang ident="OXSHEARTBEAT_APIUSER_STEP_SEND_TOKEN"}]
                [{if $migrationExecuted && $graphqlBaseActivated && $setupToken != '' && !$apiUserPasswordSet}]<br>
                <small style="font-weight: normal;">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_STEP_SEND_TOKEN_DESC"}]</small><br>
                <code id="tokenCode" class="copy-code" onclick="
                    var textarea = document.createElement('textarea');
                    textarea.value = '[{$setupToken}]';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    var msg = document.getElementById('tokenMsg');
                    msg.style.display = 'inline';
                    setTimeout(function() { msg.style.display = 'none'; }, 2000);
                ">&#x1F4D1; [{$setupToken}]</code>
                <span id="tokenMsg" class="copied-msg">&#10004; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_COPIED"}]</span>
                [{/if}]
            </li>

            [{* Step 5: Wait for access *}]
            <li class="[{if $apiUserPasswordSet}]done[{else}]pending[{/if}]">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_STEP_WAIT_SUPPORT"}]</li>
        </ol>
        [{if !$graphqlBaseActivated}]
        <p style="margin-top: 15px; padding: 10px; background: #fff3e0; border: 1px solid #ff9800; border-radius: 4px; color: #e65100;">
            <strong>&#9888; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_PREREQUISITES_WARNING"}]</strong>
        </p>
        [{/if}]
    </div>
[{/if}]

<details class="api-reset-section">
    <summary><h3>[{oxmultilang ident="OXSHEARTBEAT_APIUSER_RESET_TITLE"}]</h3></summary>
    <div class="reset-content">
        <p>[{oxmultilang ident="OXSHEARTBEAT_APIUSER_RESET_DESCRIPTION"}]</p>
        <ul>
            <li>&#10060; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_WARNING_1"}]</li>
            <li>&#10060; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_WARNING_2"}]</li>
            <li>&#10060; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_WARNING_3"}]</li>
            <li>&#10060; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_WARNING_4"}]</li>
        </ul>
        <div style="margin: 15px 0 0 0; padding: 15px; background: #fff3e0; border: 1px solid #ff9800; border-radius: 4px;">
            <form method="post" action="[{$oViewConf->getSelfLink()}]">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="heartbeat_apiuser_setup">
                <input type="hidden" name="fnc" value="resetPassword">
                <label style="cursor: pointer; color: #e65100; display: block; margin-bottom: 15px;">
                    <input type="checkbox" id="confirmReset" onchange="
                        var btn = document.getElementById('resetBtn');
                        if (this.checked) {
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            btn.style.cursor = 'pointer';
                        } else {
                            btn.disabled = true;
                            btn.style.opacity = '0.5';
                            btn.style.cursor = 'not-allowed';
                        }
                    ">
                    [{oxmultilang ident="OXSHEARTBEAT_APIUSER_CONFIRM_RESET"}]
                </label>
                <button type="submit" id="resetBtn" disabled style="display: block; background: #dc3545; color: white; border: none; padding: 8px 16px; font-size: 13px; cursor: not-allowed; opacity: 0.5; border-radius: 4px;">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_RESET_BUTTON"}]</button>
            </form>
        </div>
    </div>
</details>

<details class="api-invalidate-section">
    <summary><h3>[{oxmultilang ident="OXSHEARTBEAT_APIUSER_INVALIDATE_TITLE"}]</h3></summary>
    <div class="invalidate-content">
        <p>[{oxmultilang ident="OXSHEARTBEAT_APIUSER_INVALIDATE_DESCRIPTION"}]</p>
        <ul>
            <li>&#10060; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_1"}]</li>
            <li>&#10060; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_2"}]</li>
            <li>&#10060; [{oxmultilang ident="OXSHEARTBEAT_APIUSER_INVALIDATE_WARNING_3"}]</li>
        </ul>
        <div style="margin: 15px 0 0 0; padding: 15px; background: #fff3e0; border: 1px solid #ff9800; border-radius: 4px;">
            <form method="post" action="[{$oViewConf->getSelfLink()}]">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="heartbeat_apiuser_setup">
                <input type="hidden" name="fnc" value="invalidateTokens">
                <label style="cursor: pointer; color: #e65100; display: block; margin-bottom: 15px;">
                    <input type="checkbox" id="confirmInvalidate" onchange="
                        var btn = document.getElementById('invalidateBtn');
                        if (this.checked) { btn.disabled = false; btn.style.opacity = '1'; btn.style.cursor = 'pointer'; }
                        else { btn.disabled = true; btn.style.opacity = '0.5'; btn.style.cursor = 'not-allowed'; }
                    ">
                    [{oxmultilang ident="OXSHEARTBEAT_APIUSER_CONFIRM_INVALIDATE"}]
                </label>
                <button type="submit" id="invalidateBtn" disabled style="display: block; background: #dc3545; color: white; border: none; padding: 8px 16px; font-size: 13px; cursor: not-allowed; opacity: 0.5; border-radius: 4px;">[{oxmultilang ident="OXSHEARTBEAT_APIUSER_INVALIDATE_BUTTON"}]</button>
            </form>
        </div>
    </div>
</details>
</div>

[{include file="bottomitem.tpl"}]
