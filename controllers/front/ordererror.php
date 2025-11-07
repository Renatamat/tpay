<?php
/**
 * NOTICE OF LICENSE.
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    tpay.com
 *  @copyright 2010-2016 tpay.com
 *  @license   LICENSE.txt
 */

/**
 * Class TpayOrderErrorModuleFrontController.
 */
class TpayOrderErrorModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        $this->emptyCart();
        $this->context->cookie->__unset('last_order');
        $this->context->cookie->last_order = false;
        $this->cartId = 0;
        $this->context->controller->addCss(_MODULE_DIR_.'tpay/views/css/style.css');
        $this->display_column_left = false;
        parent::initContent();

        $orderId = Tools::getValue('orderId');
        $order = new Order($orderId);
        $payments = $order->getOrderPayments();
        $currency = new Currency($order->id_currency);
        if ($order->module !== 'tpay' || !empty($payments) || (int)$currency->iso_code_num !== 985) {
            TPAY_PS_17 ? $this->setTemplate(TPAY_17_PATH.'/orderError17.tpl') : $this->setTemplate('orderError.tpl');
        } else {
            $this->context->smarty->assign(array(
                    'tpayUrl' => $this->context->link->getModuleLink(
                        'tpay', 'renewPayment', array('orderId' => $orderId)),
                )
            );
            TPAY_PS_17 ? $this->setTemplate(TPAY_17_PATH.'/orderErrorWithRenew17.tpl') :
                $this->setTemplate('../hook/renew.tpl');
        }
    }

    private function emptyCart()
    {
        $products = $this->context->cart->getProducts();
        foreach ($products as $product) {
            $this->context->cart->deleteProduct($product['id_product']);
        }
    }

}
