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

use RuckPay\TransactionsApiClient;

require_once dirname(__FILE__) . '/../../vendor/autoload.php';

/**
 * This Controller receive customer after approval on payment iframe
 */
class RuckPayValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @var PaymentModule
     */
    public $module;

    /**
     * {@inheritdoc}
     */
    public function postProcess()
    {
        if (false === $this->checkIfContextIsValid() || false === $this->checkIfPaymentOptionIsAvailable()) {
            $this->redirectToOrder();
        }

        $customer = new Customer($this->context->cart->id_customer);

        if (false === Validate::isLoadedObject($customer)) {
            $this->redirectToOrder();
        }

        $externalReference = Tools::getValue('external_reference');
        $paymentMethod = Tools::getValue('payment_method');

        if (empty($externalReference) || empty($paymentMethod)) {
            $this->redirectToOrder();
        }

        if ($this->isPaymentValid($externalReference, $paymentMethod)) {
            $this->updateExternalReference((int) $this->context->cart->id, $externalReference);

            $this->module->validateOrder(
                (int) $this->context->cart->id,
                (int) Configuration::get(RuckPay::CONFIG_STATE_ACCEPTED_ID),
                (float) $this->context->cart->getOrderTotal(true, Cart::BOTH),
                $this->getOptionName($paymentMethod),
                null,
                [],
                (int) $this->context->currency->id,
                false,
                $customer->secure_key
            );

            Tools::redirect(
                $this->context->link->getPageLink(
                    'order-confirmation',
                    true,
                    (int) $this->context->language->id,
                    [
                        'id_cart' => (int) $this->context->cart->id,
                        'id_module' => (int) $this->module->id,
                        'id_order' => (int) $this->module->currentOrder,
                        'key' => $customer->secure_key,
                    ]
                )
            );
        } else {
            // Affiche un message d'erreur
            $this->errors[] = $this->module->l('Payment error', 'validation');

            $this->redirectToOrder();
        }
    }

    private function redirectToOrder()
    {
        Tools::redirect(
            $this->context->link->getPageLink(
                'order',
                true,
                (int) $this->context->language->id,
                [
                    'step' => 1,
                ]
            )
        );
    }

    /**
     * Check if the context is valid
     *
     * @return bool
     */
    private function checkIfContextIsValid()
    {
        return true === Validate::isLoadedObject($this->context->cart)
            && true === Validate::isUnsignedInt($this->context->cart->id_customer)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_delivery)
            && true === Validate::isUnsignedInt($this->context->cart->id_address_invoice);
    }

    /**
     * Check that this payment option is still available in case the customer changed
     * his address just before the end of the checkout process
     *
     * @return bool
     */
    private function checkIfPaymentOptionIsAvailable()
    {
        $modules = Module::getPaymentModules();

        if (empty($modules)) {
            return false;
        }

        foreach ($modules as $module) {
            if (isset($module['name']) && $this->module->name === $module['name']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get translated Payment Option name
     *
     * @return string
     */
    private function getOptionName($paymentMethod)
    {
        // TODO Ajouter les autres méthodes de paiement
        // TODO Traduire le nom de la méthode de paiement

        return $this->l('Bank card (RuckPay)', null, 'en');
    }

    private function isPaymentValid($externalReference, $paymentMethod)
    {
        $internalReference = $this->getInternalOrderReference((int) $this->context->cart->id);

        $transactionData = (new TransactionsApiClient(
            Configuration::get(RuckPay::CONF_KEY_MODE) === RuckPay::LIVE_MODE
                ? Configuration::get(RuckPay::CONF_KEY_LIVE_SECRET)
                : Configuration::get(RuckPay::CONF_KEY_TEST_SECRET)
        ))->getTransactionData($externalReference);

        return $transactionData['transaction_id'] === $externalReference
            && $transactionData['reference'] === $internalReference
            && $transactionData['live'] === (Configuration::get(RuckPay::CONF_KEY_MODE) === RuckPay::LIVE_MODE)
            && $transactionData['errorcode'] === 'NONE'
            && $transactionData['status'] === 'OK'
            && isset($transactionData['amount']['currency'])
            && $transactionData['amount']['currency'] === $this->context->currency->iso_code
            && isset($transactionData['amount']['value'])
            && $transactionData['amount']['value'] === $this->context->cart->getOrderTotal(true, Cart::BOTH);
    }

    private function updateExternalReference($cartId, $ruckPayOrderReference)
    {
        // Update reference in table ps_ruckpay_order_reference
        $sql = new DbQuery();
        $sql->select('id_cart');
        $sql->from('ruckpay_order_reference');
        $sql->where('id_cart = ' . (int) $cartId);

        if (Db::getInstance()->getValue($sql)) {
            Db::getInstance()->execute(
                'UPDATE `' . _DB_PREFIX_ . 'ruckpay_order_reference` SET `external_reference` = "' . pSQL($ruckPayOrderReference) . '" WHERE `id_cart` = ' . (int) $cartId . ';'
            );
        } else {
            throw new \Exception('No RuckPay order reference found for cart ' . $cartId);
        }
    }

    private function getInternalOrderReference($cartId)
    {
        // Get reference from table ps_ruckpay_order_reference
        $sql = new DbQuery();
        $sql->select('internal_reference');
        $sql->from('ruckpay_order_reference');
        $sql->where('id_cart = ' . (int) $cartId);

        return Db::getInstance()->getValue($sql);
    }
}
