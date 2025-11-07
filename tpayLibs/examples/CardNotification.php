<?php

/*
 * Created by tpay.com
 */

namespace tpayLibs\examples;

use tpayLibs\src\_class_tpay\Notifications\CardNotificationHandler;

include_once 'config.php';
include_once 'loader.php';

class CardNotification extends CardNotificationHandler
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
