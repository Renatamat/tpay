<?php

/*
 * Created by tpay.com
 */

namespace tpayLibs\examples;

use tpayLibs\src\_class_tpay\PaymentForms\PaymentBasicForms;
use tpayLibs\src\_class_tpay\Utilities\Util;

include_once 'config.php';
include_once 'loader.php';

class TpayBasicExample extends PaymentBasicForms
{
    public function __construct($id, $secret)
    {
        $this->merchantSecret = $secret;
        $this->merchantId = $id;
        parent::__construct();
    }

    /**
     * @param array $config transaction config
     * @param bool $redirect redirect automatically
     * @param string $actionURL
     * @param bool $showRegulations
     * @return string
     */
    public function getTransactionForm($config = [], $redirect = false, $actionURL = null, $showRegulations = false) {
        if (!empty($config)) {
            $config = $this->prepareConfig($config);
        }
        $data = [
            static::ACTION_URL => is_null($actionURL) ? $this->panelURL : (string)$actionURL,
            static::FIELDS => $config,
            'redirect' => $redirect,
            'show_regulations_checkbox' => $showRegulations,
            'regulation_url' => static::TPAY_TERMS_OF_SERVICE_URL,
        ];

        return Util::parseTemplate(static::PAYMENT_FORM, $data);
    }

}
