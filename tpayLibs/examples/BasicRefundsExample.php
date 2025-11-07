<?php

/*
 * Created by tpay.com
 */

namespace tpayLibs\examples;

use tpayLibs\src\_class_tpay\Refunds\BasicRefunds;

include_once 'config.php';
include_once 'loader.php';

class BasicRefundsExample extends BasicRefunds
{
    public function __construct($apiKey, $apiPass, $merchantId, $merchantSecret)
    {
        $this->merchantSecret = $merchantSecret;
        $this->merchantId = $merchantId;
        $this->trApiKey = $apiKey;
        $this->trApiPass = $apiPass;
        parent::__construct();
    }

}
