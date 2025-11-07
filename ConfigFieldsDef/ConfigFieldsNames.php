<?php

/**
 * Created by tpay.com.
 * Date: 26.05.2017
 * Time: 17:34
 */
class ConfigFieldsNames
{
    public static function getConfigFields()
    {
        return array(
            'TPAY_ID',
            'TPAY_KEY',
            'TPAY_BANK_ON_SHOP',
            'TPAY_BASIC_ACTIVE',
            'TPAY_BLIK_ACTIVE',
            'TPAY_APIKEY',
            'TPAY_APIPASS',
            'TPAY_DEBUG',
            'TPAY_CHECK_IP',
            'TPAY_CHECK_PROXY',
            'TPAY_SHOW_REGULATIONS',
            'TPAY_SURCHARGE_ACTIVE',
            'TPAY_SURCHARGE_TYPE',
            'TPAY_SURCHARGE_VALUE',
            'TPAY_BANNER',
            'TPAY_NOTIFICATION_EMAILS',
            'TPAY_SUMMARY',
            'TPAY_OWN_STATUS',
            'TPAY_OWN_PAID',
            'TPAY_OWN_ERROR',
            'TPAY_OWN_WAITING',
            'TPAY_INSTALLMENTS_ACTIVE',
            'TPAY_CARD_ACTIVE',
            'TPAY_CARD_MID_NB',
        );
    }

    public static function getCardConfigFields()
    {
        return array(

            'TPAY_CARD_MID_ACTIVE',
            'TPAY_CARD_DOMAIN',
            'TPAY_CARD_MULTI_CURRENCY',
            'TPAY_CARD_CURRENCY',
            'TPAY_CARD_KEY',
            'TPAY_CARD_PASS',
            'TPAY_CARD_CODE',
            'TPAY_CARD_HASH',
            'TPAY_CARD_RSA',
        );
    }

}
