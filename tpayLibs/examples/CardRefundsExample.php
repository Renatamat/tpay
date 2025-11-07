<?php

/*
 * Created by tpay.com
 */

namespace tpayLibs\examples;

use tpayLibs\src\_class_tpay\Refunds\CardRefunds;

include_once 'config.php';
include_once 'loader.php';

class CardRefundsExample extends CardRefunds
{
    public function __construct($apiKey, $apiPass, $verificationCode, $hashType, $keyRSA)
    {
        $this->cardApiKey = $apiKey;
        $this->cardApiPass = $apiPass;
        $this->cardKeyRSA = $keyRSA;
        $this->cardVerificationCode = $verificationCode;
        $this->cardHashAlg = $hashType;
        parent::__construct();

    }

}
