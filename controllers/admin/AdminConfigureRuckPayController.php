<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
class AdminConfigureRuckPayController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->className = 'Configuration';
        $this->table = 'configuration';

        parent::__construct();

        if (empty(Currency::checkPaymentCurrencies($this->module->id))) {
            $this->warnings[] = $this->l(
                'No currency has been set for this module.',
                null,
                'en'
            );
        }
    }

    public function postProcess()
    {
        if (Tools::isSubmit('RUCKPAY_ENABLED')) {
            $this->processConfiguration();
        } elseif (Tools::isSubmit('PAYMENT_METHODS_FORM')) {
            $this->processPaymentMethods();
        }
    }

    private function processConfiguration()
    {
        Configuration::updateValue(
            RuckPay::CONF_ENABLED,
            Tools::getValue(RuckPay::CONF_ENABLED) === '1' ? '1' : '0'
        );

        Configuration::updateValue(
            RuckPay::CONF_KEY_MODE,
            Tools::getValue(RuckPay::CONF_KEY_MODE) === 'live' ? 'live' : 'test'
        );

        Configuration::updateValue(
            RuckPay::CONF_KEY_TEST_KEY,
            Tools::getValue(RuckPay::CONF_KEY_TEST_KEY)
        );

        Configuration::updateValue(
            RuckPay::CONF_KEY_TEST_SECRET,
            Tools::getValue(RuckPay::CONF_KEY_TEST_SECRET)
        );

        Configuration::updateValue(
            RuckPay::CONF_KEY_LIVE_KEY,
            Tools::getValue(RuckPay::CONF_KEY_LIVE_KEY)
        );

        Configuration::updateValue(
            RuckPay::CONF_KEY_LIVE_SECRET,
            Tools::getValue(RuckPay::CONF_KEY_LIVE_SECRET)
        );

        $this->confirmations[] = $this->l('Settings updated successfully');
    }

    private function processPaymentMethods()
    {
        $paymentMethods = $this->getPaymentMethodsList();

        $activePaymentMethods = [];
        foreach ($paymentMethods as $paymentMethod) {
            if (Tools::getValue($paymentMethod['name']) === '1') {
                $activePaymentMethods[] = $paymentMethod['name'];
            }
        }

        Configuration::updateValue(
            RuckPay::CONF_KEY_PAYMENT_METHODS,
            implode(',', $activePaymentMethods)
        );

        $this->confirmations[] = $this->l('Payment methods updated successfully');
    }

    private function getPaymentMethodsList()
    {
        $activePaymentMethods = explode(',', Configuration::get(RuckPay::CONF_KEY_PAYMENT_METHODS));

        $paymentMethods = json_decode(
            \Tools::file_get_contents(dirname(__FILE__) . '/../../config/payment_methods.json'),
            true
        )['payment_methods'];

        $indexedPaymentMethods = [];
        foreach ($paymentMethods as $paymentMethod) {
            if (is_array($paymentMethod['display_name'])) {
                if (isset($paymentMethod['display_name'][$this->context->language->iso_code])) {
                    $paymentMethod['display_name'] = $paymentMethod['display_name'][$this->context->language->iso_code];
                } elseif (isset($paymentMethod['display_name']['en'])) {
                    $paymentMethod['display_name'] = $paymentMethod['display_name']['en'];
                } else {
                    $paymentMethod['display_name'] = $paymentMethod['name'];
                }
            }

            $indexedPaymentMethods[$paymentMethod['name']] = $paymentMethod;
            $indexedPaymentMethods[$paymentMethod['name']]['active'] = in_array(
                $paymentMethod['name'],
                $activePaymentMethods,
                true
            );
        }

        return $indexedPaymentMethods;
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign([
            RuckPay::CONF_ENABLED => Configuration::get(RuckPay::CONF_ENABLED),
            RuckPay::CONF_KEY_MODE => Configuration::get(RuckPay::CONF_KEY_MODE),
            RuckPay::CONF_KEY_TEST_KEY => Configuration::get(RuckPay::CONF_KEY_TEST_KEY),
            RuckPay::CONF_KEY_TEST_SECRET => Configuration::get(RuckPay::CONF_KEY_TEST_SECRET),
            RuckPay::CONF_KEY_LIVE_KEY => Configuration::get(RuckPay::CONF_KEY_LIVE_KEY),
            RuckPay::CONF_KEY_LIVE_SECRET => Configuration::get(RuckPay::CONF_KEY_LIVE_SECRET),
            'payment_methods' => $this->getPaymentMethodsList(),
        ]);

        $this->setTemplate('configure.tpl');
    }
}
