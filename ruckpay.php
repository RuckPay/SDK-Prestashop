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
require_once _PS_MODULE_DIR_ . 'ruckpay/vendor/autoload.php';

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use RuckPay\Transaction;

if (!defined('_PS_VERSION_')) {
    exit;
}

class RuckPay extends PaymentModule
{
    const CONF_ENABLED = 'RUCKPAY_ENABLED';

    const CONF_KEY_MODE = 'RUCKPAY_MODE';
    const LIVE_MODE = 'live';
    const TEST_MODE = 'test';

    const CONF_KEY_TEST_KEY = 'RUCKPAY_TEST_KEY';
    const CONF_KEY_TEST_SECRET = 'RUCKPAY_TEST_SECRET';
    const CONF_KEY_LIVE_KEY = 'RUCKPAY_LIVE_KEY';
    const CONF_KEY_LIVE_SECRET = 'RUCKPAY_LIVE_SECRET';

    const CONF_KEY_PAYMENT_METHODS = 'RUCKPAY_PAYMENT_METHODS';

    const CONFIG_STATE_PENDING_ID = 'RUCKPAY_STATE_PENDING_ID';

    const CONFIG_STATE_ACCEPTED_ID = 'RUCKPAY_STATE_ACCEPTED_ID';

    const CONFIG_STATE_REJECTED_ID = 'RUCKPAY_STATE_REJECTED_ID';

    const MODULE_ADMIN_CONTROLLER = 'AdminConfigureRuckPay';

    const HOOKS = [
        'actionObjectShopAddAfter',
        'paymentOptions',
        'displayAdminOrderMainBottom',
        'displayPaymentByBinaries',
    ];

    public function __construct()
    {
        $this->name = 'ruckpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.1';
        $this->author = 'RuckPay';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->ps_versions_compliancy = [
            'min' => '1.7.8',
            'max' => _PS_VERSION_,
        ];
        $this->controllers = [
            'validation',
        ];

        parent::__construct();

        $this->displayName = $this->l('RuckPay Payments', null, 'en');
        $this->description = $this->l(
            'Accept and process all your payments with a single solution anywhere in the world. Furthermore, thanks to RuckPay’s payment solution you will improve your customers’ experience and your acceptance rate thanks to fast and clear online payments.',
            null,
            'en'
        );
    }

    /**
     * @return bool
     */
    public function install()
    {
        return (bool) parent::install()
            && (bool) $this->registerHook(static::HOOKS)
            && $this->installOrderState()
            && $this->installTables()
            && $this->installConfiguration()
            && $this->installTabs();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return (bool) parent::uninstall()
            && $this->deleteOrderState()
            && $this->uninstallTables()
            && $this->uninstallConfiguration()
            && $this->uninstallTabs();
    }

    /**
     * Module configuration page
     */
    public function getContent()
    {
        // Redirect to our ModuleAdminController when click on Configure button
        Tools::redirectAdmin($this->context->link->getAdminLink(static::MODULE_ADMIN_CONTROLLER, true));
    }

    /**
     * This hook called after a new Shop is created
     *
     * @param array $params
     */
    public function hookActionObjectShopAddAfter(array $params)
    {
        if (empty($params['object'])) {
            return;
        }

        /** @var Shop $shop */
        $shop = $params['object'];

        if (false === Validate::isLoadedObject($shop)) {
            return;
        }

        $this->addCheckboxCarrierRestrictionsForModule([(int) $shop->id]);
        $this->addCheckboxCountryRestrictionsForModule([(int) $shop->id]);

        if ($this->currencies_mode === 'checkbox') {
            $this->addCheckboxCurrencyRestrictionsForModule([(int) $shop->id]);
        } elseif ($this->currencies_mode === 'radio') {
            $this->addRadioCurrencyRestrictionsForModule([(int) $shop->id]);
        }
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function hookPaymentOptions(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (Configuration::get(static::CONF_ENABLED) !== '1') {
            return [];
        }

        if (false === Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart)) {
            return [];
        }

        return $this->getPaymentOptions();
    }

    public function hookDisplayAdminOrderMainBottom(array $params)
    {
        if (empty($params['id_order'])) {
            return '';
        }

        $order = new Order((int) $params['id_order']);

        if (false === Validate::isLoadedObject($order) || $order->module !== $this->name) {
            return '';
        }

        if (!$order->getOrderPaymentCollection()->count()) {
            return '';
        }

        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getOrderPaymentCollection()->getFirst();

        if (!preg_match('@RuckPay@', $orderPayment->payment_method)) {
            return '';
        }

        $references = (new Transaction($order->id_cart))->getReferences();

        $this->context->smarty->assign([
            'moduleName' => $this->name,
            'moduleDisplayName' => $this->displayName,
            'moduleLogoSrc' => $this->getPathUri() . '/views/img/logo.png',
            'externalReference' => $references['external_reference'],
        ]);

        return $this->context->smarty->fetch('module:ruckpay/views/templates/hook/displayAdminOrderMainBottom.tpl');
    }

    private function getPaymentOptions()
    {
        $paymentOptions = [];

        $paymentMethodsConf = json_decode(
            \Tools::file_get_contents(_PS_MODULE_DIR_ . $this->name . '/config/payment_methods.json'),
            true
        )['payment_methods'];

        foreach ($paymentMethodsConf as $paymentMethod) {
            if ($paymentMethod['enabled'] === false) {
                continue;
            }

            $paymentOptionDisplayName = $paymentMethod['display_name'];
            if (is_array($paymentOptionDisplayName)) {
                if (isset($paymentOptionDisplayName[$this->context->language->iso_code])) {
                    $paymentOptionDisplayName = $paymentOptionDisplayName[$this->context->language->iso_code];
                } else {
                    $paymentOptionDisplayName = $paymentOptionDisplayName['en'];
                }
            }

            $paymentOption = new PaymentOption();
            $paymentOption->setModuleName($this->name);
            $paymentOption->setCallToActionText(
                $paymentOptionDisplayName . ' (RuckPay)'
            );
            $paymentOption->setAdditionalInformation(
                $this->context->smarty->fetch('module:ruckpay/views/templates/front/paymentOptionBinary.tpl')
            );
            $paymentOption->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logo-complete-small.png'));
            $paymentOption->setBinary(true);
            $paymentOption->setInputs([
                'option' => [
                    'name' => 'ruckpay_payment_method',
                    'type' => 'hidden',
                    'value' => $paymentMethod['name'],
                ],
            ]);

            $paymentOptions[] = $paymentOption;
        }

        return $paymentOptions;
    }

    /**
     * Check if currency is allowed in Payment Preferences
     *
     * @param Cart $cart
     *
     * @return bool
     */
    private function checkCurrency(Cart $cart)
    {
        $currency_order = new Currency($cart->id_currency);
        /** @var array $currenciesModule */
        $currenciesModule = $this->getCurrency($cart->id_currency);

        if (empty($currenciesModule)) {
            return false;
        }

        foreach ($currenciesModule as $currencyModule) {
            if ($currency_order->id == $currencyModule['id_currency']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function hookDisplayPaymentByBinaries(array $params)
    {
        /** @var Cart $cart */
        $cart = $params['cart'];

        if (false === Validate::isLoadedObject($cart) || false === $this->checkCurrency($cart) || Configuration::get(static::CONF_ENABLED) !== '1') {
            return '';
        }

        $billingAddress = new Address($params['cart']->id_address_invoice);
        $shippingAddress = new Address($params['cart']->id_address_delivery);

        $transaction = new Transaction($cart->id);
        $references = $transaction->getReferences();

        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'validation', [], true),
            'public_key' => Configuration::get(static::CONF_KEY_MODE) === static::TEST_MODE
                ? Configuration::get(static::CONF_KEY_TEST_KEY)
                : Configuration::get(static::CONF_KEY_LIVE_KEY),
            'mode' => Configuration::get(static::CONF_KEY_MODE),
            'amount' => $cart->getOrderTotal(),
            'currency' => $this->context->currency->iso_code,
            'customer_email' => $this->context->customer->email,
            'reference' => $references['internal_reference'],
            'return_url' => $this->context->link->getModuleLink(
                $this->name,
                'validation',
                ['option' => 'binary'],
                true
            ),
            'locale' => str_replace('-', '_', Context::getContext()->currentLocale->getCode()),
            'billing' => [
                'first_name' => $this->context->customer->firstname,
                'last_name' => $this->context->customer->lastname,
                'address' => $billingAddress->address1,
                'city' => $billingAddress->city,
                'zip' => $billingAddress->postcode,
                'country' => (new Country($billingAddress->id_country))->iso_code,
            ],
            'shipping' => [
                'first_name' => $this->context->customer->firstname,
                'last_name' => $this->context->customer->lastname,
                'address' => $shippingAddress->address1,
                'city' => $shippingAddress->city,
                'zip' => $shippingAddress->postcode,
                'country' => (new Country($shippingAddress->id_country))->iso_code,
            ],
        ]);

        return $this->context->smarty->fetch('module:ruckpay/views/templates/hook/displayPaymentByBinaries.tpl');
    }

    private function installOrderState()
    {
        $this->createOrderState(
            self::CONFIG_STATE_PENDING_ID,
            [
                'fr' => 'Paiement en attente de confirmation (RuckPay)',
                'en' => 'Payment awaiting confirmation (RuckPay)',
            ],
            '#00ffff'
        );

        $this->createOrderState(
            self::CONFIG_STATE_ACCEPTED_ID,
            [
                'fr' => 'Paiement accepté (RuckPay)',
                'en' => 'Payment accepted (RuckPay)',
            ],
            '#32CD32',
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            true,
            'payment',
            false,
            false
        );

        $this->createOrderState(
            self::CONFIG_STATE_REJECTED_ID,
            [
                'fr' => 'Paiement refusé (RuckPay)',
                'en' => 'Payment refused (RuckPay)',
            ],
            '#FF0000',
            true,
            false,
            false,
            false,
            false,
            false,
            false,
            false,
            'payment_error',
            false,
            false
        );

        return true;
    }

    /**
     * Install tables
     *
     * @return bool
     */
    private function installTables()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ruckpay_order_reference` (
            `id_cart` INT(10) unsigned NOT NULL,
            `internal_reference` CHAR(36) NOT NULL,
            `external_reference` CHAR(100) NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY `id_cart` (`id_cart`)
        )';

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * Create custom OrderState used for payment
     *
     * @param string $configurationKey Configuration key used to store OrderState identifier
     * @param array $nameByLangIsoCode An array of name for all languages, default is en
     * @param string $color Color of the label
     * @param bool $isLogable consider the associated order as validated
     * @param bool $isPaid set the order as paid
     * @param bool $isInvoice allow a customer to download and view PDF versions of his/her invoices
     * @param bool $isShipped set the order as shipped
     * @param bool $isDelivery show delivery PDF
     * @param bool $isPdfDelivery attach delivery slip PDF to email
     * @param bool $isPdfInvoice attach invoice PDF to email
     * @param bool $isSendEmail send an email to the customer when his/her order status has changed
     * @param string $template Only letters, numbers and underscores are allowed. Email template for both .html and .txt
     * @param bool $isHidden hide this status in all customer orders
     * @param bool $isUnremovable Disallow delete action for this OrderState
     * @param bool $isDeleted Set OrderState deleted
     *
     * @return bool
     */
    private function createOrderState(
        $configurationKey,
        array $nameByLangIsoCode,
        $color,
        $isLogable = false,
        $isPaid = false,
        $isInvoice = false,
        $isShipped = false,
        $isDelivery = false,
        $isPdfDelivery = false,
        $isPdfInvoice = false,
        $isSendEmail = false,
        $template = '',
        $isHidden = false,
        $isUnremovable = true,
        $isDeleted = false
    ) {
        $tabNameByLangId = [];

        foreach ($nameByLangIsoCode as $langIsoCode => $name) {
            foreach (Language::getLanguages(false) as $language) {
                if (Tools::strtolower($language['iso_code']) === $langIsoCode) {
                    $tabNameByLangId[(int) $language['id_lang']] = $name;
                } elseif (isset($nameByLangIsoCode['en'])) {
                    $tabNameByLangId[(int) $language['id_lang']] = $nameByLangIsoCode['en'];
                }
            }
        }

        $orderState = new OrderState();
        $orderState->module_name = $this->name;
        $orderState->name = $tabNameByLangId;
        $orderState->color = $color;
        $orderState->logable = $isLogable;
        $orderState->paid = $isPaid;
        $orderState->invoice = $isInvoice;
        $orderState->shipped = $isShipped;
        $orderState->delivery = $isDelivery;
        $orderState->pdf_delivery = $isPdfDelivery;
        $orderState->pdf_invoice = $isPdfInvoice;
        $orderState->send_email = $isSendEmail;
        $orderState->hidden = $isHidden;
        $orderState->unremovable = $isUnremovable;
        $orderState->template = $template;
        $orderState->deleted = $isDeleted;
        $result = (bool) $orderState->add();

        if (false === $result) {
            $this->_errors[] = sprintf(
                'Failed to create OrderState %s',
                $configurationKey
            );

            return false;
        }

        $result = (bool) Configuration::updateGlobalValue($configurationKey, (int) $orderState->id);

        if (false === $result) {
            $this->_errors[] = sprintf(
                'Failed to save OrderState %s to Configuration',
                $configurationKey
            );

            return false;
        }

        return true;
    }

    /**
     * Delete custom OrderState used for payment
     * We mark them as deleted to not break passed Orders
     *
     * @return bool
     */
    private function deleteOrderState()
    {
        $result = true;

        $orderStateCollection = new PrestaShopCollection('OrderState');
        $orderStateCollection->where('module_name', '=', $this->name);
        /** @var OrderState[] $orderStates */
        $orderStates = $orderStateCollection->getAll();

        foreach ($orderStates as $orderState) {
            $orderState->deleted = true;
            $result = $result && (bool) $orderState->save();
        }

        return $result;
    }

    private function uninstallTables()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ruckpay_order_reference`';

        return (bool) Db::getInstance()->execute($sql);
    }

    /**
     * Install default module configuration
     *
     * @return bool
     */
    private function installConfiguration()
    {
        return (bool) Configuration::updateGlobalValue(static::CONF_ENABLED, '0')
            && (bool) Configuration::updateGlobalValue(static::CONF_KEY_MODE, static::TEST_MODE)
            && (bool) Configuration::updateGlobalValue(static::CONF_KEY_TEST_KEY, '')
            && (bool) Configuration::updateGlobalValue(static::CONF_KEY_TEST_SECRET, '')
            && (bool) Configuration::updateGlobalValue(static::CONF_KEY_LIVE_KEY, '')
            && (bool) Configuration::updateGlobalValue(static::CONF_KEY_LIVE_SECRET, '')
            && (bool) Configuration::updateGlobalValue(static::CONF_KEY_PAYMENT_METHODS, 'CARD');
    }

    /**
     * Uninstall module configuration
     *
     * @return bool
     */
    private function uninstallConfiguration()
    {
        return (bool) Configuration::deleteByName(static::CONF_ENABLED)
            && (bool) Configuration::deleteByName(static::CONF_KEY_MODE)
            && (bool) Configuration::deleteByName(static::CONF_KEY_TEST_KEY)
            && (bool) Configuration::deleteByName(static::CONF_KEY_TEST_SECRET)
            && (bool) Configuration::deleteByName(static::CONF_KEY_LIVE_KEY)
            && (bool) Configuration::deleteByName(static::CONF_KEY_LIVE_SECRET)
            && (bool) Configuration::deleteByName(static::CONF_KEY_PAYMENT_METHODS);
    }

    /**
     * Install Tabs
     *
     * @return bool
     */
    public function installTabs()
    {
        if (Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER)) {
            return true;
        }

        $tab = new Tab();
        $tab->class_name = static::MODULE_ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(
            Language::getIDs(false),
            $this->displayName
        );

        return (bool) $tab->add();
    }

    /**
     * Uninstall Tabs
     *
     * @return bool
     */
    public function uninstallTabs()
    {
        $id_tab = (int) Tab::getIdFromClassName(static::MODULE_ADMIN_CONTROLLER);

        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool) $tab->delete();
        }

        return true;
    }
}
