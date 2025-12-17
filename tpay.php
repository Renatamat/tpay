<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Tpay
 * @copyright 2010-2019 tpay.com
 * @license   LICENSE.txt
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/tpayModel.php';
require_once __DIR__ . '/helpers/TpayHelperClient.php';
require_once __DIR__ . '/ConfigFieldsDef/ConfigFieldsNames.php';

define('TPAY_PAYMENT_BASIC', 'basic');
define('TPAY_PAYMENT_CARDS', 'cards');
define('TPAY_PAYMENT_BANK_ON_SHOP', 'bank');
define('TPAY_PAYMENT_BLIK', 'blik');
define('TPAY_PAYMENT_INSTALLMENTS', 'installments');
define('TPAY_VIEW_REDIRECT', 0);
define('TPAY_VIEW_ICONS', 1);
define('TPAY_VIEW_LIST', 2);
define('TPAY_CARD_MIDS', 11);
define('TPAY_SURCHARGE_AMOUNT', 0);
define('TPAY_SURCHARGE_PERCENT', 1);
define('TPAY_PS_17', (version_compare(_PS_VERSION_, '1.7', '>=')));
define('TPAY_17_PATH', 'module:tpay/views/templates/front');

/**
 * Class Tpay main class.
 */
class Tpay extends PaymentModule
{
    const LOGO_PATH = 'tpay/views/img/tpay_logo_230.png';
    const BANK_ON_SHOP = 'TPAY_BANK_ON_SHOP';
    const CHECK_PROXY = 'TPAY_CHECK_PROXY';
    const POLAND_COUNTRY_ID = 14;

    /**
     * Basic module info.
     */
    public function __construct()
    {
        $this->name = 'tpay';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.0';
        $this->author = 'Krajowy Integrator Płatności S.A.';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->is_eu_compatible = 1;
        parent::__construct();
        $this->displayName = $this->l('Tpay');
        $this->description = $this->l('Accepting online payments');
        $this->confirmUninstall = $this->l('Delete this module?');
        $this->module_key = 'f2eb0ce26233d0b517ba41e81f2e62fe';
    }

    /**
     * Module installation.
     *
     * @return bool
     */
    public function install()
    {
        /*
         * check multishop context
         */
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (version_compare(phpversion(), '8.1.0', '<')) {
            $this->_errors[] = $this->l(
                sprintf(
                    'Your PHP version is too old, please upgrade to a newer version. Your version is %s,' .
                    ' library requires %s',
                    phpversion(),
                    '8.1.0'
                )
            );

            return false;
        }

        if (!parent::install()) {
            $this->_errors[] = $this->l('Initialization failed');
        }

        if (TPAY_PS_17) {
            if (!$this->registerHook('paymentOptions')) {
                $this->_errors[] = $this->l('Error adding payment methods');
            }
        } elseif (!$this->registerHook('payment') || !$this->registerHook('displayPaymentEU')) {
            $this->_errors[] = $this->l('Error adding payment methods');
        }

        if (!TpayModel::createOrdersTable()) {
            $this->_errors[] = $this->l('Error creating orders table');
        }

        if (!TpayModel::createRefundsTable()) {
            $this->_errors[] = $this->l('Error creating refunds table');
        }

        if (!$this->addOrderStates()) {
            $this->_errors[] = $this->l('Error adding order statuses');
        }

        if (!$this->registerHook('displayProductButtons')) {
            $this->_errors[] = $this->l('Error adding tpay logo');
        }
        if (!$this->addTpayFeeProduct()) {
            $this->_errors[] = $this->l('Error adding fee product');
        }
        if (!$this->registerHook('adminOrder')) {
            $this->_errors[] = $this->l('Error refunds hook');
        }
        $this->registerHook('paymentReturn');
        $this->registerHook('displayOrderDetail');

        if (!empty($this->_errors)) {
            return false;
        }

        $this->setValue('TPAY_CHECK_IP', 1);
        $this->setValue('TPAY_CHECK_PROXY', 0);

        return true;
    }

    /**
     * Add order states.
     *
     * @return bool
     */
    private function addOrderStates()
    {
        $languages = Language::getLanguages(false);
        $newState = Configuration::get('TPAY_NEW');
        if (
            !$newState
            ||
            empty($newState)
            ||
            !Validate::isInt($newState)
            ||
            !Validate::isLoadedObject(new OrderState($newState))
        ) {
            $orderState = new OrderState();

            foreach ($languages as $language) {
                $orderState->name[$language['id_lang']] = $this->l('Waiting for payment (Tpay)');
            }
            $orderState->send_email = 0;
            $orderState->invoice = 0;
            $orderState->color = '#5bc0de';
            $orderState->unremovable = false;
            $orderState->logable = 0;
            $orderState->module_name = $this->name;
            if (!$orderState->add()) {
                return false;
            }

            if (!Configuration::updateValue('TPAY_NEW', $orderState->id)) {
                return false;
            }

            try {
                copy(_PS_MODULE_DIR_ . 'tpay/views/img/logo.gif', _PS_IMG_DIR_ . 'os/' . $orderState->id . '.gif');
            } catch (Exception $e) {
                Tools::displayError(
                    $this->l(
                        'Copying image failed. Please copy ' .
                        'logo.gif to directory img/os and change the name to ' . $orderState->id . '.gif'
                    )
                );
            }
        }

        $doneState = Configuration::get('TPAY_CONFIRMED');
        if (
            !$doneState
            ||
            empty($doneState)
            ||
            !Validate::isInt($doneState)
            ||
            !Validate::isLoadedObject(new OrderState($doneState))
        ) {
            $orderState = new OrderState();
            foreach ($languages as $language) {
                $orderState->name[$language['id_lang']] = $this->l('Payment received (Tpay)');
            }
            $orderState->send_email = true;
            $orderState->invoice = true;
            $orderState->color = '#00DE69';
            $orderState->unremovable = false;
            $orderState->logable = true;
            $orderState->module_name = $this->name;
            $orderState->paid = true;
            $orderState->pdf_invoice = true;
            $orderState->pdf_delivery = true;
            if (!$orderState->add()) {
                return false;
            }
            if (!Configuration::updateValue('TPAY_CONFIRMED', $orderState->id)) {
                return false;
            }
            try {
                copy(_PS_MODULE_DIR_ . 'tpay/views/img/done.gif', _PS_IMG_DIR_ . 'os/' . $orderState->id . '.gif');
            } catch (Exception $e) {
                Tools::displayError(
                    $this->l(
                        'Copying image failed. Please copy ' .
                        'done.gif to directory img/os and change the name to ' . $orderState->id . '.gif'
                    )
                );
            }
        }
        $errorState = Configuration::get('TPAY_ERROR');
        if (
            !$errorState
            ||
            empty($errorState)
            ||
            !Validate::isInt($errorState)
            ||
            !Validate::isLoadedObject(new OrderState($errorState))
        ) {
            $orderState = new OrderState();
            foreach ($languages as $language) {
                $orderState->name[$language['id_lang']] = $this->l('Wrong payment (Tpay)');
            }
            $orderState->send_email = false;
            $orderState->invoice = false;
            $orderState->color = '#b52b27';
            $orderState->unremovable = false;
            $orderState->logable = false;
            $orderState->module_name = $this->name;
            $orderState->paid = false;
            $orderState->pdf_invoice = false;
            $orderState->pdf_delivery = false;
            if (!$orderState->add()) {
                return false;
            }
            if (!Configuration::updateValue('TPAY_ERROR', $orderState->id)) {
                return false;
            }
            try {
                copy(_PS_MODULE_DIR_ . 'tpay/views/img/error.gif', _PS_IMG_DIR_ . 'os/' . $orderState->id . '.gif');
            } catch (Exception $e) {
                Tools::displayError(
                    $this->l(
                        'Copying image failed. Please copy ' .
                        'error.gif to directory img/os and change the name to ' . $orderState->id . '.gif'
                    )
                );
            }
        }

        return true;
    }

    private function setValue($name, $value)
    {
        Configuration::updateValue($name, $value);
    }

    /**
     * Module uninstall.
     *
     * @return bool
     */
    public function uninstall()
    {
        $product = new Product(TpayHelperClient::getTpayFeeProductId());
        if (Validate::isLoadedObject($product)) {
            $product->delete();
        }

        if (!parent::uninstall()) {
            return false;
        }

        Configuration::deleteByName('tpay');

        return true;
    }

    /**
     * Admin config settings check an render form.
     */
    public function getContent()
    {
        $output = null;
        $errors = false;
        if (Tools::isSubmit('submit' . $this->name)) {
            $basicActive = (int)Tools::getValue('TPAY_BASIC_ACTIVE');
            $blikActive = (int)Tools::getValue('TPAY_BLIK_ACTIVE');
            $this->setValue('TPAY_INSTALLMENTS_ACTIVE', (int)Tools::getValue('TPAY_INSTALLMENTS_ACTIVE'));
            $this->setValue('TPAY_BASIC_ACTIVE', $basicActive);
            $this->setValue('TPAY_BLIK_ACTIVE', $blikActive);
            $this->setValue('TPAY_BANNER', (int)Tools::getValue('TPAY_BANNER'));
            $this->setValue('TPAY_NOTIFICATION_EMAILS', (string)Tools::getValue('TPAY_NOTIFICATION_EMAILS'));
            TPAY_PS_17 ? $this->setValue('TPAY_SUMMARY', 0) :
                $this->setValue('TPAY_SUMMARY', (int)Tools::getValue('TPAY_SUMMARY'));
            $this->setValue('TPAY_OWN_STATUS', (int)Tools::getValue('TPAY_OWN_STATUS'));
            /**
             * debug option.
             */
            $this->setValue('TPAY_DEBUG', (int)Tools::getValue('TPAY_DEBUG'));
            /**
             * Notifications options.
             */
            $this->setValue('TPAY_CHECK_IP', (int)Tools::getValue('TPAY_CHECK_IP'));
            $this->setValue(static::CHECK_PROXY, (int)Tools::getValue(static::CHECK_PROXY));
            /**
             * Basic payment settings.
             */
            $this->setValue('TPAY_BANK_ON_SHOP', (int)Tools::getValue('TPAY_BANK_ON_SHOP'));
            $this->setValue('TPAY_SHOW_REGULATIONS', (int)Tools::getValue('TPAY_SHOW_REGULATIONS'));
            $this->setValue('TPAY_SHOW_BLIK_ON_SHOP', (int)Tools::getValue('TPAY_SHOW_BLIK_ON_SHOP'));
            $this->setValue('TPAY_OWN_WAITING', Tools::getValue('TPAY_OWN_WAITING'));
            $this->setValue('TPAY_OWN_ERROR', Tools::getValue('TPAY_OWN_ERROR'));
            $this->setValue('TPAY_OWN_PAID', Tools::getValue('TPAY_OWN_PAID'));
            /**
             * basic settings validation.
             */
            $userKey = (string)Tools::getValue('TPAY_KEY');
            $userId = Tools::getValue('TPAY_ID');
            for ($i = 1; $i < TPAY_CARD_MIDS; $i++) {
                foreach (ConfigFieldsNames::getCardConfigFields() as $key) {
                    Configuration::updateValue($key . $i, Tools::getValue($key . $i));
                }
            }
            $this->setValue('TPAY_KEY', $userKey);
            $this->setValue('TPAY_ID', $userId);
            $this->setValue('TPAY_APIKEY', (string)Tools::getValue('TPAY_APIKEY'));
            $this->setValue('TPAY_APIPASS', (string)Tools::getValue('TPAY_APIPASS'));
            $this->setValue('TPAY_CARD_ACTIVE', (string)Tools::getValue('TPAY_CARD_ACTIVE'));
            if (
                $basicActive === 1
                ||
                $blikActive === 1
            ) {
                if (!$userKey || empty($userKey) || !Validate::isGenericName($userKey)) {
                    $output .= $this->displayError($this->l('Invalid security code'));
                    $errors = true;
                }
                if (!$userId || empty($userId) || !Validate::isInt($userId)) {
                    $output .= $this->displayError($this->l('Invalid user id'));
                    $errors = true;
                }
                if ($errors !== false) {
                    $this->setValue('TPAY_BASIC_ACTIVE', 0);
                    $this->setValue('TPAY_BLIK_ACTIVE', 0);
                }
            }
            $surchargeActive = (int)Tools::getValue('TPAY_SURCHARGE_ACTIVE');
            $surchargeValue = Tools::getValue('TPAY_SURCHARGE_VALUE');
            $surchargeValue = str_ireplace(',', '.', $surchargeValue);
            $surchargeValue = round((float)$surchargeValue, 2);
            $this->setValue('TPAY_SURCHARGE_ACTIVE', $surchargeActive);
            $this->setValue('TPAY_SURCHARGE_TYPE', (int)Tools::getValue('TPAY_SURCHARGE_TYPE'));
            if ($surchargeActive === 1) {
                if (!Validate::isUnsignedFloat($surchargeValue)) {
                    $this->displayError($this->l('Invalid payment value'));
                    $this->setValue('TPAY_SURCHARGE_ACTIVE', 0);
                } else {
                    $this->setValue('TPAY_SURCHARGE_VALUE', $surchargeValue);
                }
            }
            $output .= $this->displayConfirmation($this->l('Settings saved'));
        }
        $output .= TPAY_PS_17 ? $this->fetch('module:tpay/views/templates/front/configuration.tpl') :
            $this->display(__FILE__, 'configuration.tpl');

        return $output . $this->displayForm();
    }

    /**
     * Configuration form settings.
     *
     * @return mixed
     */
    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Init payment configuration form
        $fields_form = $this->prepareConfigFormArrays();
        // Load current values
        $helper->fields_value = $this->getConfigFieldsValues();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ),
        );

        return $helper->generateForm($fields_form);
    }

    /**
     * Prepare config forms array.
     *
     * @return array
     */
    private function prepareConfigFormArrays()
    {
        if ((float)_PS_VERSION_ >= 1.6) {
            $switch = 'switch';
        } else {
            $switch = 'radio';
        }
        $orderStatesData = OrderState::getOrderStates($this->context->language->id);
        $orderStates = array();
        foreach ($orderStatesData as $state) {
            array_push($orderStates, array(
                'id_option' => $state['id_order_state'],
                'name' => $state['name']
            ));
        }

        $generalSettings = require_once dirname(__FILE__) . '/ConfigFieldsDef/GeneralSettingsDefinition.php';
        $basicPayment = require_once dirname(__FILE__) . '/ConfigFieldsDef/BasicPaymentDefinition.php';
        $blikPayment = require_once dirname(__FILE__) . '/ConfigFieldsDef/BlikPaymentDefinition.php';
        $cardPayment = require_once dirname(__FILE__) . '/ConfigFieldsDef/CardPaymentDefinition.php';

        return array($generalSettings, $basicPayment, $blikPayment, $cardPayment);
    }

    /**
     * Returns config fields array.
     *
     * @return array
     */
    private function getConfigFieldsValues()
    {
        $config = array();
        foreach (ConfigFieldsNames::getConfigFields() as $key) {
            $config[$key] = Configuration::get($key);
        }
        for ($i = 1; $i < TPAY_CARD_MIDS; $i++) {
            foreach (ConfigFieldsNames::getCardConfigFields() as $key) {
                $config[$key . $i] = Configuration::get($key . $i);
            }
        }

        return $config;
    }

    /**
     * Render payment choice blocks.
     *
     * @param bool $returnPayments
     *
     * @return array
     */
    public function hookPayment($params)
    {
        $currency = $this->context->currency;
        if (!$this->active || TPAY_PS_17) {
            return false;
        }
        $returnPayments = false;
        if (is_array($params) && array_key_exists('returnPayments', $params)) {
            $returnPayments = (bool)$params['returnPayments'];
        } elseif ($params === true) {
            $returnPayments = true;
        }
        $feeProductId = TpayHelperClient::getTpayFeeProductId();
        $cart = $this->context->cart;
        $cart->updateQty(0, $feeProductId);
        $cart->update();
        $orderTotal = $cart->getOrderTotal(true, Cart::BOTH);
        $availablePayments = array();

        $basicActive = (int)Configuration::get('TPAY_BASIC_ACTIVE');
        $blikActive = (int)Configuration::get('TPAY_BLIK_ACTIVE');
        $cardActive = (int)Configuration::get('TPAY_CARD_ACTIVE');
        $installmentsActive = (int)Configuration::get('TPAY_INSTALLMENTS_ACTIVE');
        $isPolishDelivery = $this->isPolishDeliveryAddress();
        if ($basicActive === 1 && $currency->iso_code === 'PLN' && $isPolishDelivery) {
            $availablePayments[] = $this->getPaymentOption(TPAY_PAYMENT_BASIC,
                $this->l('Pay by online transfer with Tpay'));
            if ($installmentsActive === 1 && $orderTotal >= 300 && $orderTotal <= 9259) {
                $availablePayments[] = $this->getPaymentOption(TPAY_PAYMENT_INSTALLMENTS,
                    $this->l('Pay by installments with tpay'));
            }
        }
        if ($blikActive === 1 && $currency->iso_code === 'PLN' && $isPolishDelivery) {
            $availablePayments[] = $this->getPaymentOption(TPAY_PAYMENT_BLIK, $this->l('Pay by BLIK code with Tpay'));
        }
        if ($cardActive === 1 && TpayHelperClient::getCardMidNumber($currency->iso_code,
                _PS_BASE_URL_ . __PS_BASE_URI__)
        ) {
            $availablePayments[] = $this->getPaymentOption(TPAY_PAYMENT_CARDS, $this->l('Pay by credit card with Tpay'));
        }

        if ($returnPayments === true) {
            return $availablePayments;
        }

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
            'payments' => $availablePayments,
        ));

        $surcharge = TpayHelperClient::getSurchargeValue($orderTotal);
        if (!empty($surcharge)) {
            $this->smarty->assign('surcharge', $surcharge);
        }
        $this->context->controller->addCSS(_PS_MODULE_DIR_ . 'tpay/views/css/hookPaymentTpay.css', 'all');

        return $this->display(__FILE__, 'payment.tpl');
    }

    private function getPaymentOption($paymentType, $label)
    {
        $paymentLink = $this->context->link->getModuleLink(
            'tpay',
            'validation',
            array('type' => $paymentType)
        );

        return array(
            'type' => $paymentType,
            'paymentLink' => $paymentLink,
            'title' => $label,
            'cta_text' => $label,
            'logo' => _MODULE_DIR_ . 'tpay/views/img/logo.png',
            'action' => $this->context->link->getModuleLink(
                $this->name,
                'validation',
                array('type' => $paymentType),
                true
            ),
        );
    }

    public function hookDisplayPaymentEU()
    {
        if (!$this->active) {
            return [];
        }

        return $this->getActivePayments();
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        $paymentLinkAction = 'validation';
        $options = array();
        $availablePayments = $this->getActivePayments();
        foreach ($availablePayments as $key => $value) {
            $this->smarty->assign(array(
                'this_path' => $this->_path,
                'this_path_ssl' => Tools::getShopDomainSsl(true,
                        true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
                'payments' => $availablePayments[$key],
            ));
            $this->context->cookie->last_order = false;
            unset($this->context->cookie->last_order);
            $cart = $this->context->cart;
            $orderTotal = $cart->getOrderTotal(true, Cart::BOTH);
            $surcharge = TpayHelperClient::getSurchargeValue($orderTotal);
            $surcharge > 0 ? $this->smarty->assign('surcharge',
                number_format($surcharge, 2)) : $this->smarty->assign('surcharge', false);
            $newOption = new PaymentOption();
            $newOption->setModuleName($this->name)
                ->setCallToActionText($this->l($availablePayments[$key]['title']))
                ->setAction(
                    $this->context->link->getModuleLink(
                        $this->name,
                        $paymentLinkAction,
                        ['type' => $availablePayments[$key]['type']],
                        true
                    )
                )
                ->setAdditionalInformation($this->fetch('module:tpay/views/templates/hook/tpay_intro.tpl'))
                ->setLogo(_MODULE_DIR_ . 'tpay/views/img/logo.png');
            $options[] = $newOption;
        }

        return $options;
    }

    private function getActivePayments()
    {
        $cardActive = (bool)Configuration::get('TPAY_CARD_ACTIVE');
        $installmentsActive = (bool)Configuration::get('TPAY_INSTALLMENTS_ACTIVE');
        $basicActive = (bool)Configuration::get('TPAY_BASIC_ACTIVE');
        $blikActive = (bool)Configuration::get('TPAY_BLIK_ACTIVE');
        $paymentLinkAction = 'validation';
        $currency = $this->context->currency;
        $cart = $this->context->cart;
        $orderTotal = $cart->getOrderTotal(true, Cart::BOTH);
        $availablePayments = array();
        $isPolishDelivery = $this->isPolishDeliveryAddress();

        if ($basicActive && $currency->iso_code === 'PLN' && $isPolishDelivery) {
            $paymentTitle = $this->l('Pay by online transfer with Tpay');
            $availablePayments[] = $this->getPaymentData(TPAY_PAYMENT_BASIC, $paymentTitle, $paymentLinkAction);

            if ($installmentsActive && $orderTotal >= 300 && $orderTotal <= 9259.25) {
                $paymentTitle = $this->l('Pay by installments with Tpay');
                $availablePayments[] = $this->getPaymentData(TPAY_PAYMENT_INSTALLMENTS, $paymentTitle,
                    $paymentLinkAction);
            }
        }
        if ($blikActive && $currency->iso_code === 'PLN' && $isPolishDelivery) {
            $paymentTitle = $this->l('Pay by blik code with Tpay');
            $availablePayments[] = $this->getPaymentData(TPAY_PAYMENT_BLIK, $paymentTitle, $paymentLinkAction);
        }
        if ($cardActive && TpayHelperClient::getCardMidNumber($currency->iso_code,
                _PS_BASE_URL_ . __PS_BASE_URI__)
        ) {
            $paymentTitle = $this->l('Pay by credit card with Tpay');
            $availablePayments[] = $this->getPaymentData(TPAY_PAYMENT_CARDS, $paymentTitle, $paymentLinkAction);
        }

        return $availablePayments;
    }

    private function getPaymentData($type, $title, $paymentLinkAction = 'payment')
    {
        return [
            'type' => $type,
            'paymentLink' => $this->context->link->getModuleLink('tpay', $paymentLinkAction, array('type' => $type)),
            'title' => $title,
            'cta_text' => $title,
            'logo' => _MODULE_DIR_ . static::LOGO_PATH,
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array('type' => $type),
                true),
        ];
    }

    public function isPolishDeliveryAddress()
    {
        $cart = $this->context->cart;
        if (!$cart || !(int)$cart->id_address_delivery) {
            return true;
        }
        $deliveryAddress = new Address((int)$cart->id_address_delivery);
        if (!Validate::isLoadedObject($deliveryAddress)) {
            return true;
        }

        return (int)$deliveryAddress->id_country === static::POLAND_COUNTRY_ID;
    }

    /**
     * Hook for displaying tpay logo on product pages.
     *
     * @param $params
     * @return string|void
     */
    public function hookDisplayProductButtons($params)
    {
        if (Configuration::get('PS_CATALOG_MODE') || Configuration::get('TPAY_BANNER') == false) {
            return;
        }
        if (!$this->isCached('paymentlogo.tpl', $this->getCacheId())) {
            $this->smarty->assign(array(
                'banner_img' => 'https://tpay.com/img/banners/tpay-160x75.svg',
            ));
        }

        return $this->display(__FILE__, 'paymentlogo.tpl', $this->getCacheId());
    }

    public function hookAdminOrder($params)
    {
        if (!$this->active) {
            return;
        }
        $orderId = $params['id_order'];
        $order = new Order($orderId);
        $paymentTpay = $order->getOrderPayments();
        if (strcasecmp($order->payment, 'Tpay') === 0 && isset($paymentTpay[0])) {
            $maxRefundAmount = (float)$paymentTpay[0]->amount;
            $refundAmount = Tools::getValue('tpay_refund_amount');
            if ($refundAmount !== false && $this->isValidRefundAmount($refundAmount, $maxRefundAmount) === true) {
                $refundAmount = number_format(str_replace(array(',', ' '), array('.', ''), (float)$refundAmount), 2,
                    '.', '');
                $transactionId = $paymentTpay[0]->transaction_id;
                $paymentType = TpayModel::getPaymentType($orderId);
                try {
                    if ($paymentType === 'card') {
                        $result = $this->processCardRefund($transactionId, $refundAmount, $orderId);
                        if (isset($result['result']) && $result['result'] === 1 && $result['status'] === 'correct') {
                            TpayModel::insertRefund($orderId, $transactionId, $refundAmount);
                            $this->context->smarty->assign(array(
                                'tpay_refund_status' => $this->displayConfirmation($this->l('Refund successful.')),
                            ));
                        }
                        if (isset($result['err_code'])) {
                            $this->context->smarty->assign(array(
                                'tpay_refund_status' => $this->displayError(
                                    sprintf($this->l('Communication error %s'), $result['err_code'])),
                            ));
                        }
                        if (isset($result['reason'])) {
                            $this->context->smarty->assign(array(
                                'tpay_refund_status' => $this->displayError(
                                    sprintf($this->l('Refund error. Reason code %s'), $result['reason'])),
                            ));
                        }
                    } else {
                        $result = $this->processBasicRefund($transactionId, $refundAmount, $maxRefundAmount);
                        if (isset($result['result']) && $result['result'] === 1) {
                            TpayModel::insertRefund($orderId, $transactionId, $refundAmount);
                            $this->context->smarty->assign(array(
                                'tpay_refund_status' => $this->displayConfirmation($this->l('Refund successful.')),
                            ));
                        }
                        if (isset($result['err'])) {
                            $this->context->smarty->assign(array(
                                'tpay_refund_status' => $this->displayError(
                                    sprintf($this->l('Refund error %s'), $result['err'])),
                            ));
                        }
                    }
                } catch (Exception $TException) {
                    $errorMessagePosition = strpos($TException->getMessage(), ' in file');

                    $this->context->smarty->assign(array(
                        'tpay_refund_status' => $this->displayError(
                            substr($TException->getMessage(), 0, $errorMessagePosition))
                    ));
                }
            }
            $this->setOrderRefunds($orderId);

            return $this->display(__FILE__, 'refunds.tpl');
        }
        return;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        $this->context->smarty->assign([
            'status' => Tools::getValue('status'),
            'historyLink' => 'index.php?controller=history',
            'homeLink' => 'index.php',
            'contactLink' => 'index.php?controller=contact',
            'modulesDir' => Tools::getHttpHost(true) . __PS_BASE_URI__ . 'modules/',
            'TPAY_PS_17' => TPAY_PS_17,
        ]);

        return TPAY_PS_17 ? $this->fetch('module:tpay/views/templates/hook/paymentReturn.tpl') :
            $this->display(__FILE__, 'paymentReturn.tpl');
    }

    public function hookDisplayOrderDetail($params)
    {
        if (!$this->active) {
            return;
        }
        $orderId = $params['order']->id;
        $order = new Order($orderId);
        $payments = $order->getOrderPayments();
        $currency = new Currency($order->id_currency);
        $ownStatusSetting = (int)Configuration::get('TPAY_OWN_STATUS') === 1;
        if ($ownStatusSetting) {
            $pendingOrderState = (int)Configuration::get('TPAY_OWN_WAITING');
        } else {
            $pendingOrderState = (int)Configuration::get('TPAY_NEW');
        }
        if (
            $order->module !== 'tpay'
            || !empty($payments)
            || (int)$currency->iso_code_num !== 985
            || (int)$order->getCurrentState() !== $pendingOrderState
        ) {
            return;
        }
        $this->context->smarty->assign(array(
                'tpayUrl' => $this->context->link->getModuleLink('tpay', 'renewPayment', array('orderId' => $orderId)),
            )
        );

        return TPAY_PS_17 ? $this->fetch('module:tpay/views/templates/hook/renew.tpl') :
            $this->display(__FILE__, 'renew.tpl');
    }

    private function isValidRefundAmount($refundAmount, $maxRefundAmount)
    {
        $refundAmount = number_format(str_replace(array(',', ' '), array('.', ''), (float)$refundAmount), 2, '.', '');
        $error = null;
        if ($refundAmount > $maxRefundAmount) {
            //assign to smarty
            $error = sprintf($this->l('amount is greater than allowed %s'), $maxRefundAmount);
        }
        if ($refundAmount <= 0) {
            $error = $this->l('invalid amount');
        }
        if (!is_null($error)) {
            $this->context->smarty->assign(array(
                'tpay_refund_status' => $this->displayError(sprintf($this->l('Unable to process refund - %s'), $error)),
            ));
        }

        return is_null($error);
    }

    private function processCardRefund($transactionId, $refundAmount, $orderId)
    {
        $paymentMidId = TpayModel::getPaymentMidId($orderId);
        $cardRefunds = TpayHelperClient::getCardRefundsClient($paymentMidId);
        $order = new Order($orderId);
        $currency = new Currency($order->id_currency);
        $cardRefunds->setAmount($refundAmount)->setCurrency($currency->iso_code_num);

        return $cardRefunds->refund(
            $transactionId,
            sprintf($this->l('Card payment refund, order %s'), $order->reference)
        );
    }

    private function processBasicRefund($transactionId, $refundAmount, $maxRefundAmount)
    {
        $basicRefunds = TpayHelperClient::getRefundsApiClient();
        $basicRefunds->setTransactionID($transactionId);

        return $refundAmount === $maxRefundAmount ? $basicRefunds->refund() : $basicRefunds->refundAny($refundAmount);
    }

    private function addTpayFeeProduct()
    {
        $psLang = (int)Configuration::get('PS_LANG_DEFAULT');
        $product = new Product();
        $product->name = array($psLang =>  'Opłata za płatność online');
        $product->description = array($psLang =>  'Produkt dodawany do zamówień z prowizją.');
        $product->link_rewrite = array($psLang =>  'tpay-fee');
        $product->reference = 'TPAY_FEE';
        $product->id_category = 1;
        $product->id_category_default = 1;
        $product->id_tax_rules_group = 0;
        $product->active = 1;
        $product->redirect_type = '404';
        $product->price = 0.01;
        $product->quantity = 9999999;
        $product->minimal_quantity = 1;
        $product->show_price = 1;
        $product->on_sale = 0;
        $product->online_only = 1;
        $product->meta_keywords = 'tpay fee';
        $product->is_virtual = 1;
        $product->visibility = 'none';
        $product->add();
        $product->addToCategories(array(1));
        StockAvailable::setQuantity($product->id, null, $product->quantity);

        Configuration::updateValue('TPAY_FEE_PRODUCT_ID', $product->id);

        return true;
    }

    private function setOrderRefunds($orderId)
    {
        $refunds = TpayModel::getOrderRefunds($orderId);
        $smartyRefunds = [];
        foreach ($refunds as $refund) {
            $smartyRefunds[] = [
                'tpay_refund_date' => $refund['tjr_date'],
                'tpay_transaction_id' => $refund['tjr_transaction_title'],
                'tpay_refund_amount' => $refund['tjr_amount'],
            ];
        }
        $this->context->smarty->assign(array(
            'tpayRefunds' => $smartyRefunds,
        ));
    }

}
