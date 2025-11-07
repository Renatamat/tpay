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
 * @author    tpay.com
 * @copyright 2010-2016 tpay.com
 * @license   LICENSE.txt
 */

use tpayLibs\src\_class_tpay\Utilities\Util;

require_once _PS_MODULE_DIR_.'tpay/helpers/TpayHelperClient.php';
require_once _PS_MODULE_DIR_.'tpay/tpayModel.php';

/**
 * Class TpayPaymentModuleFrontController.
 */
class TpayRenewPaymentModuleFrontController extends ModuleFrontController
{
    const TPAY_URL = 'https://secure.tpay.com';
    public $ssl = true;
    private $tpayClientConfig;

    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();
        if (is_numeric($this->context->cookie->id_customer)) {
            $cookieCustomerId = (int)$this->context->cookie->id_customer;
        } else {
            $cookieCustomerId = 0;
        }
        $orderId = (int)Tools::getValue('orderId');
        $order = new Order($orderId);
        $cart = new Cart($order->id_cart);
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=history');
        }
        if ((int)$order->id_customer !== (int)$this->context->customer->id
            && (int)$order->id_customer !== $cookieCustomerId
        ) {
            Tools::redirect('index.php?controller=history');
        }
        $orderTotal = $cart->getOrderTotal(true, Cart::BOTH);
        $crcSum = $this->getOrderCrcSum($order);
        if ($crcSum === false) {
            Tools::redirect('index.php?controller=history');
        }
        $this->tpayClientConfig['amount'] = number_format(str_replace(array(',', ' '), array('.', ''),
            $orderTotal), 2, '.', '');
        $this->tpayClientConfig['crc'] = $crcSum;
        $this->initBasicClient($cart, $customer, $orderId);
        $this->context->smarty->assign(array(
            'nbProducts' => $cart->nbProducts(),
        ));
        $this->context->cookie->last_order = $orderId;
        unset($this->context->cookie->id_cart);
        $this->redirectToPayment();
    }

    private function getOrderCrcSum($order)
    {
        $reference = $order->reference;
        $referencedOrders = Order::getByReference($reference)->getResults();
        foreach ($referencedOrders as $orderObject) {
            if (!is_null($orderObject->id)) {
                $tpaySum = TpayModel::getHash($orderObject->id);
                if ($tpaySum !== false) {
                    return $tpaySum;
                }
            }
        }

        return false;
    }

    private function initBasicClient($cart, $customer, $orderId)
    {
        $baseUrl = Tools::getHttpHost(true).__PS_BASE_URI__;
        $addressInvoiceId = $cart->id_address_invoice;
        $billingAddress = new AddressCore($addressInvoiceId);
        $this->tpayClientConfig += array(
            'description' => 'Zamówienie nr '.$orderId.'. Klient '.
                $this->context->cookie->customer_firstname.' '.$this->context->cookie->customer_lastname,
            'return_url' => $baseUrl.'index.php?controller=order-confirmation&id_cart='.
                (int)$cart->id.'&id_module='.(int)$this->module->id.'&id_order='.$orderId.
                '&key='.$customer->secure_key.'&status=success',
            'return_error_url' => $this->context->link->getModuleLink('tpay', 'order-error').'?orderId='.$orderId,
            'email' => $this->context->cookie->email,
            'name' => sprintf('%s %s', $billingAddress->firstname, $billingAddress->lastname),
            'phone' => $billingAddress->phone,
            'address' => $billingAddress->address1,
            'city' => $billingAddress->city,
            'zip' => $billingAddress->postcode,
            'result_url' => $this->context->link->getModuleLink('tpay', 'confirmation',
                array('type' => TPAY_PAYMENT_BASIC)),
            'module' => 'prestashop ' . _PS_VERSION_,
        );

        foreach ($this->tpayClientConfig as $key => $value) {
            if (empty($value)) {
                unset($this->tpayClientConfig[$key]);
            }
        }
        Util::log('Tpay renew order parameters', print_r($this->tpayClientConfig, true));
    }

    private function redirectToPayment()
    {
        $tpayBasicClient = TpayHelperClient::getBasicClient();
        $language = $this->context->language->iso_code;
        if ($language !== 'pl') {
            $language = 'en';
        }
        (new Util)->setLanguage($language);
        if (TPAY_PS_17) {
            $this->setTemplate(TPAY_17_PATH.'/redirect.tpl');
            echo $tpayBasicClient->getTransactionForm($this->tpayClientConfig, true);
        } else {
            $this->setTemplate('tpayRedirect.tpl');
            $this->context->smarty->assign(array(
                'tpay_form' => $tpayBasicClient->getTransactionForm($this->tpayClientConfig, true),
                'tpay_path' => _MODULE_DIR_.'tpay/views',
                'HOOK_HEADER' => Hook::exec('displayHeader'),
            ));
        }
    }

}
