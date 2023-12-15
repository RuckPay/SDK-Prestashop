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
<section id="ruckpay-binary-form" class="js-payment-binary js-payment-ruckpay disabled">

	<style type="text/css">
		iframe[id^="st-"] { height: 60px !important;}
	</style>

    <p class="alert alert-warning accept-cgv">{l s='You must accept the terms and conditions to be able to process your order.' mod='ruckpay'}</p>

    <p class="alert alert-danger ruckpay-error" style="display: none"></p>

    <div style="border: solid 1px #ccc;    padding: 10px;    margin-bottom: 15px;    border-radius: 10px; background-color: #f8f9f9">
	
	 <img height="30" src="https://cdn.ruckpay.com/images/payment/transparent/mastercard.svg" class="img" alt="Mastercard" style="float: right;" />
	 <img height="30" src="https://cdn.ruckpay.com/images/payment/transparent/visa.svg"       class="img" alt="Visa" style="float: right;" />
     	
	<div id="ruckpay_iframe_area">
        <!--RuckPay.js injects the Payment Element-->
    </div>
	</div>

    <button type="submit" class="btn btn-primary" id="submit_payment_button">
        {l s='Pay' mod='ruckpay'}
    </button>

</section>

<script type="application/javascript" src="https://cdn.ruckpay.com/lib/js/ruckpay.js"></script>
<link rel="stylesheet" href=https://cdn.ruckpay.com/lib/js/ruckpay.css />

<script type="application/javascript">
    const RuckPayModule = function () {
        'use strict';

        const _self = this;

        const callback = function (data) {
            if (!data || !data.transactionreference) {
                _self.showError(
                    "{l s="Payment platform unreachable, please retry later" mod="ruckpay"}"
                );
            } else if (data.errorcode !== 0) {
                let errorMessage = "{l s="An error has occurred, please retry later" mod="ruckpay"}";

                switch (data.errorcode) {
                    case 50003:
                        errorMessage = "{l s='Invalid payment data' mod='ruckpay'}";
                        break;
                    case 60010:
                        errorMessage = "{l s='Internal error of the payment platform' mod='ruckpay'}";
                        break;
                    case 70000:
                        errorMessage = "{l s='Payment refused' mod='ruckpay'}";
                        break;
                }

                _self.showError(
                    errorMessage + ' (code : ' + data.errorcode + ', message : ' + data.errormessage + ')'
                );
            } else {
                let form = $(
                    '<form action="{$action}" method="post">' +
                    '<input type="hidden" name="external_reference" value="' + data.transactionreference + '" />' +
                    '<input type="hidden" name="payment_method" value="CARD" />' +
                    '</form>'
                );
                $('body').append(form);
                form.submit();
            }
        };

        // TODO passer les bonnes valeurs
        this.options = {
            "mode": "{$mode}",
			"public_key": "{$public_key}",

			"payment_button_id": "submit_payment_button",
			"payment_cards_id": "ruckpay_iframe_area",

            "method": "CARD",

            "locale": "{$locale}",
            "submitcallback": callback,

            "allowed_payment_methods": ["CARD"],

			"styles": {
				"payment": {
					"space-outset-body": "0 4px 0 0",
					"space-inset-input": "8px 16px",
					"space-outset-input": "0",
					"font-size-input": "14px",
					"line-height-input": "20px",
					"border-radius-input": "5px",
					"border-color-input": "#ced4da",
					
					"color-error": "#F44336",
					"font-size-message": "0px",
				},
			},

            "amount": "{$amount}",
            "currency": "{$currency}",
            "billing_contact": {
                "title": "",
                "first_name": "{$billing['first_name']}",
                "last_name": "{$billing['last_name']}",
                "countryiso": "{$billing['country']}",
            },
            "billing_address": {
                "street1": "{$billing['address']}",
                "zipcode": "{$billing['zip']}",
                "city": "{$billing['city']}",
                "Country": "{$billing['country']}"
            },
            "customer_contact": {
                "title": "",
                "first_name": "{$billing['first_name']}",
                "last_name": "{$billing['last_name']}",
                "countryiso": "{$billing['country']}",
            },
            "customer_address": {
                "street1": "{$billing['address']}",
                "zipcode": "{$billing['zip']}",
                "city": "{$billing['city']}",
                "Country": "{$billing['country']}"
            },
            "shipping_contact": {
                "title": "",
                "first_name": "{$shipping['first_name']}",
                "last_name": "{$shipping['last_name']}",
                "countryiso": "{$shipping['country']}",
            },
            "shipping_address": {
                "street1": "{$shipping['address']}",
                "zipcode": "{$shipping['zip']}",
                "city": "{$shipping['city']}",
                "Country": "{$shipping['country']}"
            },
            "reference": "{$reference}"
        };

        this.ruckpaySubmitButton = document.getElementById('submit_payment_button');

        this.init = function () {
            _self.ruckpay = new RuckPay(_self.options);
            _self.ruckpay.init('CARD');

            _self.ruckpaySubmitButton.addEventListener('click', function (e) {
                e.preventDefault();

                _self.hideError();
                _self.options.storepaymentmethod = false;
                _self.updateRuckPay();
            });
        }

        this.updateRuckPay = function () {
            _self.ruckpay.update(_self.options.method);
        }

        this.showError = function (message) {
            const errorElement = document.querySelector('.ruckpay-error');
            errorElement.innerHTML = message;
            errorElement.style.display = 'block';
        }

        this.hideError = function () {
            const errorElement = document.querySelector('.ruckpay-error');
            errorElement.innerHTML = '';
            errorElement.style.display = 'none';
        }
    };

    document.addEventListener("DOMContentLoaded", function () {
        const ruckpayModule = new RuckPayModule();
        ruckpayModule.init();
    });
</script>
