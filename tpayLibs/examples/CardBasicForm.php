<?php

/*
 * Created by tpay.com
 */

namespace tpayLibs\examples;

use tpayLibs\src\_class_tpay\PaymentForms\PaymentCardForms;
use tpayLibs\src\_class_tpay\Utilities\TException;

include_once 'config.php';
include_once 'loader.php';

class CardBasicForm extends PaymentCardForms
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

    public function getCardTransactionForm()
    {
        try {
            $config = array(
                'name'  => 'Jan Kowalski',
                'email' => 'jan.kowalski@example.com',
                'desc'  => 'Transaction description',
            );
            $this->setAmount(99.15);

            echo $this->getTransactionForm($config);

        } catch (TException $e) {
            var_dump($e);
        }
    }


}

