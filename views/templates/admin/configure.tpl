{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}

<img src="{$link->getMediaLink("`$smarty.const._MODULE_DIR_`ruckpay/views/img/logo-complete.png")}"
     style="width: 100%; max-width: 400px; margin: 0 auto 1em auto; display: block;">

<form action="{$link->getAdminLink('AdminConfigureRuckPay')|escape:'htmlall':'UTF-8'}" id="configuration_form" method="post" class="form-horizontal" enctype="multipart/form-data">
    <div class="panel" id="configuration_fieldset_ruckpay">
        <div class="panel-heading">
            <i class="icon-cogs"></i>
            {l s="RuckPay's Configuration" mod='ruckpay'}
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <div id="conf_id_RUCKPAY_ENABLED">
                    <label class="control-label col-lg-3">
                        {l s="Enable RuckPay for payments" mod='ruckpay'}
                    </label>
                    <div class="col-lg-9">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="RUCKPAY_ENABLED" id="RUCKPAY_ENABLED_on" value="1"{if $RUCKPAY_ENABLED} checked="checked"{/if}/>
                            {strip}
                                <label for="RUCKPAY_ENABLED_off">
                                    {l s="Yes" mod='ruckpay'}
                                </label>
                            {/strip}

                            <input type="radio" name="RUCKPAY_ENABLED" id="RUCKPAY_ENABLED_off" value="0"{if !$RUCKPAY_ENABLED} checked="checked"{/if}/>
                            {strip}
                                <label for="RUCKPAY_ENABLED_on">
                                {l s="No" mod='ruckpay'}
                            </label>
                            {/strip}

                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div id="conf_id_RUCKPAY_MODE">
                    <label class="control-label col-lg-3">
                        {l s="Mode" mod='ruckpay'}
                    </label>
                    <div class="col-lg-9">
                        <p class="radio">
                            <label for="RUCKPAY_MODE_test">
                                <input type="radio" name="RUCKPAY_MODE"
                                       id="RUCKPAY_MODE_test" value="test"
                                        {if $RUCKPAY_MODE === 'test'}
                                            checked="checked"
                                        {/if}
                                >TEST</label>
                        </p>
                        <p class="radio">
                            <label for="RUCKPAY_MODE_live">
                                <input type="radio" name="RUCKPAY_MODE"
                                       id="RUCKPAY_MODE_live" value="live"
                                        {if $RUCKPAY_MODE === 'live'}
                                            checked="checked"
                                        {/if}
                                >LIVE</label>
                        </p>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div id="conf_id_RUCKPAY_TEST_KEY">
                    <label class="control-label col-lg-3">
                        {l s="Test API Key" mod='ruckpay'}
                    </label>
                    <div class="col-lg-9">
                        <input class="form-control " type="text" size="5" name="RUCKPAY_TEST_KEY"
                               value="{$RUCKPAY_TEST_KEY}">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div id="conf_id_RUCKPAY_TEST_SECRET">
                    <label class="control-label col-lg-3">
                        {l s="Test API Secret" mod='ruckpay'}
                    </label>
                    <div class="col-lg-9">
                        <input class="form-control " type="text" size="5" name="RUCKPAY_TEST_SECRET"
                               value="{$RUCKPAY_TEST_SECRET}">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div id="conf_id_RUCKPAY_LIVE_KEY">
                    <label class="control-label col-lg-3">
                        {l s="Live API Key" mod='ruckpay'}
                    </label>
                    <div class="col-lg-9">
                        <input class="form-control " type="text" size="5" name="RUCKPAY_LIVE_KEY"
                               value="{$RUCKPAY_LIVE_KEY}">
                    </div>
                </div>
            </div>
            <div class="form-group">
                <div id="conf_id_RUCKPAY_LIVE_SECRET">
                    <label class="control-label col-lg-3">
                        {l s="Live API Secret" mod='ruckpay'}
                    </label>
                    <div class="col-lg-9">
                        <input class="form-control " type="text" size="5" name="RUCKPAY_LIVE_SECRET"
                               value="{$RUCKPAY_LIVE_SECRET}">
                    </div>
                </div>
            </div>
        </div><!-- /.form-wrapper -->
        <div class="panel-footer">
            <button type="submit" class="btn btn-default pull-right" name="submitOptionsconfiguration">
                <i class="process-icon-save"></i> {l s="Save" mod='ruckpay'}
            </button>
        </div>
    </div>
</form>

<form action="{$link->getAdminLink('AdminConfigureRuckPay')|escape:'htmlall':'UTF-8'}"
      id="configuration_form" method="post" enctype="multipart/form-data" class="form-horizontal">
    <input type="hidden" name="PAYMENT_METHODS_FORM" value="">
    <div class="panel " id="configuration_fieldset_ruckpay">
        <div class="panel-heading">
            <i class="icon-money"></i>
            {l s="Payment Methods" mod='ruckpay'}
        </div>
        <div class="form-wrapper">
            {{foreach from=$payment_methods item=payment_method key=k}}
            <div class="form-group">
                <div id="conf_id_RUCKPAY_PAYMENT_METHODS">
                    <label class="control-label col-lg-3">
                        {l s=$payment_method.display_name mod='ruckpay'}
                    </label>
                    <div class="col-lg-9">
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" name="{$payment_method.name}" id="{$payment_method.name}_on" value="1"{if $payment_method.active} checked="checked"{/if}{if !$payment_method.enabled} disabled="disabled"{/if}/>
                                {strip}
                                <label for="{$payment_method.name}_off">
                                    {l s="Active" mod='ruckpay'}
                                </label>
                                {/strip}

                                <input type="radio" name="{$payment_method.name}" id="{$payment_method.name}_off" value="0"{if !$payment_method.active} checked="checked"{/if}{if !$payment_method.enabled} disabled="disabled"{/if}/>
                                {strip}
                                <label for="{$payment_method.name}_on">
                                    {l s="Inactive" mod='ruckpay'}
                                </label>
                                {/strip}

                                <a class="slide-button btn"></a>
                            </span>
                    </div>
                </div>
            </div>
            {{/foreach}}
        </div><!-- /.form-wrapper -->
        <div class="panel-footer">
            <button type="submit" class="btn btn-default pull-right" name="submitOptionsconfiguration">
                <i class="process-icon-save"></i> {l s="Save" mod='ruckpay'}
            </button>
        </div>
    </div>
</form>