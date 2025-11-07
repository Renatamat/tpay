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

use tpayLibs\src\_class_tpay\Utilities\TException;
use tpayLibs\src\_class_tpay\Utilities\Util;
use tpayLibs\src\Dictionaries\FieldsConfigDictionary;

require_once _PS_MODULE_DIR_ . 'tpay/helpers/TpayHelperClient.php';
require_once _PS_MODULE_DIR_ . 'tpay/helpers/TpayOrderStatusHandler.php';
require_once _PS_MODULE_DIR_ . 'tpay/tpayModel.php';

/**
 * Class TpayPaymentModuleFrontController.
 */
class TpayPaymentModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    const TPAY_URL = 'https://secure.tpay.com';

    private $tpayClientConfig;

    private $tpayPaymentId;

    private $midId = 11;

    /**
     * @var TpayOrderStatusHandler
     */
    private $statusHandler;

    public function initContent()
    {
        $this->display_column_left = false;
        parent::initContent();
        $this->statusHandler = new TpayOrderStatusHandler();
        /** @var CartCore $cart */
        $cart = $this->context->cart;
        if (empty($cart->id)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        if ($cart->orderExists() === true) {
            die($this->trans('Cart cannot be loaded or an order has already been placed using this cart', [],
                'Admin.Payment.Notification'));
        }
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }
        $orderTotal = $cart->getOrderTotal(true, Cart::BOTH);
        $surcharge = TpayHelperClient::getSurchargeValue($orderTotal);
        if ($surcharge > 0) {
            $this->addFeeProductToCart($cart);
        }
        $orderTotal = $cart->getOrderTotal(true, Cart::BOTH);
        try {
            $this->insertPsOrder($orderTotal, $customer->secure_key);
            $this->context->smarty->assign(['nbProducts' => $cart->nbProducts()]);
            $this->processPayment($cart, $customer, $orderTotal, $surcharge);

            return true;
        } catch (Exception $e) {
            $this->handleException($e);

            return false;
        }
    }

    private function processPayment($cart, $customer, $orderTotal, $surcharge)
    {
        $orderId = $this->module->currentOrder;
        Util::logLine(sprintf('OrderId %s', $orderId));
        $this->tpayClientConfig['amount'] = number_format(str_replace([',', ' '], ['.', ''], $orderTotal), 2, '.', '');
        $crc = md5($cart->id . $this->context->cookie->mail . $customer->secure_key . time());
        $this->tpayClientConfig['crc'] = $crc;
        $type = Tools::getValue('type');
        $isInstallment = $type === TPAY_PAYMENT_INSTALLMENTS;
        $this->midId = TpayHelperClient::getCardMidNumber(
            $this->context->currency->iso_code,
            _PS_BASE_URL_ . __PS_BASE_URI__
        );
        if (in_array($type, [TPAY_PAYMENT_BASIC, TPAY_PAYMENT_BLIK, TPAY_PAYMENT_INSTALLMENTS], false)) {
            $paymentType = 'basic';
        } else {
            $paymentType = 'card';
        }
        TpayModel::insertOrder($orderId, $crc, $paymentType, false, $surcharge, $this->midId);
        $this->initBasicClient($isInstallment, $cart, $customer);
        $this->context->cookie->last_order = $orderId;
        unset($this->context->cookie->id_cart);
        if ($type === TPAY_PAYMENT_CARDS) {
            $this->processCardPayment($orderId);
        } elseif ($type === TPAY_PAYMENT_BLIK && is_numeric(Tools::getValue('blik_code'))) {
            $this->processBlikPayment();
        } else {
            $this->redirectToPayment();
        }
    }

    private function addFeeProductToCart($cart)
    {
        $feeProductId = TpayHelperClient::getTpayFeeProductId();
        if (!$cart->containsProduct($feeProductId)) {
            $cart->updateQty(1, $feeProductId);
            $cart->update();
            $cart->getPackageList(true);
        }
    }

    private function insertPsOrder($orderTotal, $customerSecureKey)
    {
        $this->module->validateOrder(
            (int)$this->context->cart->id,
            (int)Configuration::get('TPAY_OWN_STATUS') === 1 ?
                Configuration::get('TPAY_OWN_WAITING') : Configuration::get('TPAY_NEW'),
            $orderTotal,
            $this->module->displayName,
            null,
            [],
            (int)$this->context->currency->id,
            false,
            $customerSecureKey
        );
    }

    private function initBasicClient($installments, $cart, $customer)
    {
        $baseUrl = Tools::getHttpHost(true) . __PS_BASE_URI__;
        $addressInvoiceId = $cart->id_address_invoice;
        $billingAddress = new AddressCore($addressInvoiceId);
        $order = new Order($this->module->currentOrder);
        $reference = $order->reference;
        $this->tpayClientConfig += [
            'description' => 'Zamówienie ' . $reference . '. Klient ' .
                $this->context->cookie->customer_firstname . ' ' . $this->context->cookie->customer_lastname,
            'return_url'      => $baseUrl . 'index.php?controller=order-confirmation&id_cart=' .
                (int)$cart->id.'&id_module=' . (int)$this->module->id . '&id_order=' . $this->module->currentOrder .
                '&key='.$customer->secure_key . '&status=success',
            'return_error_url' => $this->context->link->getModuleLink(
                'tpay',
                'ordererror',
                ['orderId' => (int)$this->module->currentOrder]
            ),
            'email' => $this->context->cookie->email,
            'name' => sprintf('%s %s', $billingAddress->firstname, $billingAddress->lastname),
            'phone' => $billingAddress->phone,
            'address' => $billingAddress->address1,
            'city' => $billingAddress->city,
            'zip' => $billingAddress->postcode,
            'result_url' => $this->context->link->getModuleLink('tpay', 'confirmation', ['type' => TPAY_PAYMENT_BASIC]),
            'module' => 'prestashop ' . _PS_VERSION_,
        ];
        if ((int)Tools::getValue('regulations') === 1 || (int)Tools::getValue('accept_tos') === 1 || $installments) {
            $this->tpayClientConfig['accept_tos'] = 1;
        }
        if (!empty(Configuration::get('TPAY_NOTIFICATION_EMAILS'))) {
            $this->tpayClientConfig += ['result_email' => Configuration::get('TPAY_NOTIFICATION_EMAILS')];
        }
        if ((int)Tools::getValue('group') > 0) {
            $this->tpayClientConfig += ['group' => (int)Tools::getValue('group')];
        }
        if ($installments) {
            $this->tpayClientConfig['group'] = 109;
        }
        foreach ($this->tpayClientConfig as $key => $value) {
            if (empty($value)) {
                unset($this->tpayClientConfig[$key]);
            }
        }
        Util::log('Tpay order parameters', print_r($this->tpayClientConfig, true));
    }

    private function processCardPayment($orderId)
    {
        $tpayCardClient = TpayHelperClient::getCardClient($this->midId);
        $cardData = Util::post('carddata', FieldsConfigDictionary::STRING);
        $clientName = $this->tpayClientConfig['name'];
        $clientEmail = $this->tpayClientConfig['email'];
        $saveCard = Util::post('card_save', FieldsConfigDictionary::STRING);
        Util::log('Secure Sale post params', print_r($_POST, true));
        if ($saveCard === 'on') {
            $tpayCardClient->setOneTimer(false);
        }
        $tpayCardClient->setAmount($this->tpayClientConfig['amount'])
            ->setCurrency($this->context->currency->iso_code_num)
            ->setOrderID($this->midId . '*tpay*' . $this->tpayClientConfig['crc']);
        $tpayCardClient->setLanguage($this->context->language->iso_code)
            ->setReturnUrls($this->tpayClientConfig['return_url'], $this->tpayClientConfig['return_url'])
            ->setModuleName('prestashop ' . _PS_VERSION_);
        $response = $tpayCardClient->registerSale(
            $clientName,
            $clientEmail,
            $this->tpayClientConfig['description'],
            $cardData
        );
        if (isset($response['result'], $response['status'])
            && (int)$response['result'] === 1
            && $response['status'] === 'correct'
        ) {
            $tpayCardClient
                ->setAmount($this->tpayClientConfig['amount'])
                ->setOrderID('')
                ->validateCardSign(
                    $response['sign'],
                    $response['sale_auth'],
                    $response['card'],
                    $response['date'],
                    'correct',
                    isset($response['test_mode']) ? '1' : '',
                    '',
                    ''
                );
            $this->tpayPaymentId = $response['sale_auth'];
            $this->statusHandler->setOrdersAsConfirmed($orderId, $this->tpayPaymentId, false);
            Tools::redirect($this->tpayClientConfig['return_url']);
        } elseif (isset($response['3ds_url'])) {
            Tools::redirect($response['3ds_url']);
        } else {
            $this->statusHandler->setOrdersAsConfirmed($orderId, $this->tpayPaymentId, true);
            if ((int)Configuration::get('TPAY_DEBUG') === 1) {
                var_dump($response);
            } else {
                Tools::redirect($this->tpayClientConfig['return_error_url']);
            }
        }
    }

    private function redirectToPayment()
    {
        $tpayBasicClient = TpayHelperClient::getBasicClient();
        $language = $this->context->language->iso_code;
        if ($language !== 'pl') {
            $language = 'en';
        }
        (new Util)->setLanguage($language)->setPath(_MODULE_DIR_.'tpay/tpayLibs/src/');
        if (TPAY_PS_17) {
            $this->setTemplate(TPAY_17_PATH.'/redirect.tpl');
            $this->context->smarty->assign([
                'tpay_form' => $tpayBasicClient->getTransactionForm($this->tpayClientConfig, true),
            ]);
        } else {
            $this->setTemplate('tpayRedirect.tpl');
            $this->context->smarty->assign([
                'tpay_form' => $tpayBasicClient->getTransactionForm($this->tpayClientConfig, true),
                'tpay_path' => _MODULE_DIR_.'tpay/views',
                'HOOK_HEADER' => Hook::exec('displayHeader'),
            ]);
        }
    }

    private function processBlikPayment()
    {
        $data = $this->tpayClientConfig;
        $data['group'] = 150;
        $data['accept_tos'] = 1;
        $errorUrl = $data['return_error_url'];
        $tpayApiClient = TpayHelperClient::getApiClient();
        try {
            $resp = $tpayApiClient->create($data);
            if ((int)$resp['result'] === 1) {
                $blikData['code'] = Tools::getValue('blik_code');
                $blikData['title'] = $resp['title'];
                $respBlik = $tpayApiClient->handleBlikPayment($blikData);

                if ($respBlik) {
                    Tools::redirect($data['return_url']);
                } else {
                    Tools::redirect($resp['url']);
                }
            } else {
                Tools::redirect($errorUrl);
            }
        } catch (TException $e) {
            Tools::redirect($errorUrl);
        }
    }

    /**
     * @param Exception $e
     */
    private function handleException($e)
    {
        Util::log('exception in payment confirmation', $e->getMessage());
        $log = [
            'e'     => $e->getMessage(),
            'post'  => $_POST,
        ];
        if ((bool)(int)Configuration::get('TPAY_DEBUG')) {
            echo '<pre>';
            var_dump($log);
            echo '</pre>';
            die;
        }
    }

}
