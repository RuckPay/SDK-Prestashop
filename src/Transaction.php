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

declare(strict_types=1);

namespace RuckPay;

use Ramsey\Uuid\Uuid;

class Transaction
{
    private $cartId;

    public function __construct(int $cartId)
    {
        $this->cartId = (int) $cartId;
    }

    private function createInternalReferenceIfNotExists()
    {
        $sql = 'SELECT `internal_reference` FROM `' . _DB_PREFIX_ . 'ruckpay_order_reference` WHERE `id_cart` = ' . $this->cartId;

        $internalReference = \Db::getInstance()->getValue($sql);

        if (empty($internalReference)) {
            $internalReference = Uuid::uuid4();
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'ruckpay_order_reference` (`id_cart`, `internal_reference`, `created_at`) VALUES (' . $this->cartId . ', "' . pSQL($internalReference) . '", NOW())';
            \Db::getInstance()->execute($sql);
        }

        return $internalReference;
    }

    public function getReferences()
    {
        $this->createInternalReferenceIfNotExists();

        $sql = 'SELECT `internal_reference`, `external_reference` FROM `' . _DB_PREFIX_ . 'ruckpay_order_reference` WHERE `id_cart` = ' . $this->cartId;

        return \Db::getInstance()->getRow($sql);
    }
}
