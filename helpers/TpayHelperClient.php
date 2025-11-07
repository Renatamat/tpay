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

use tpayLibs\examples\BasicRefundsExample;
use tpayLibs\examples\BlikExample;
use tpayLibs\examples\CardBasicForm;
use tpayLibs\examples\CardNotification;
use tpayLibs\examples\CardRefundsExample;
use tpayLibs\examples\TpayBasicExample;
use tpayLibs\examples\TransactionNotification;

require_once _PS_MODULE_DIR_ . 'tpay/tpayLibs/examples/loader.php';

/**
 * Class TpayHelperClient.
 */
class TpayHelperClient extends Helper
{
    /**
     * Returns basic tpay client.
     *
     * @return TpayBasicExample
     */
    public static function getBasicClient()
    {
        $merchantId = (int)Configuration::get('TPAY_ID');
        $merchantSecret = Configuration::get('TPAY_KEY');

        return new TpayBasicExample($merchantId, $merchantSecret);
    }
    /**
     * Returns basic tpay client.
     *
     * @return TransactionNotification
     */
    public static function getBasicValidator()
    {
        $merchantId = (int)Configuration::get('TPAY_ID');
        $merchantSecret = Configuration::get('TPAY_KEY');

        return new TransactionNotification($merchantId, $merchantSecret);
    }
    /**
     * Returns transaction api tpay client.
     *
     * @return BlikExample
     */
    public static function getApiClient()
    {
        $merchantId = (int)Configuration::get('TPAY_ID');
        $merchantSecret = Configuration::get('TPAY_KEY');
        $apiKey = Configuration::get('TPAY_APIKEY');
        $apiPass = Configuration::get('TPAY_APIPASS');

        return new BlikExample($apiKey, $apiPass, $merchantId, $merchantSecret);
    }

    /**
     * Returns transaction api tpay client.
     *
     * @return BasicRefundsExample
     */
    public static function getRefundsApiClient()
    {
        $merchantId = (int)Configuration::get('TPAY_ID');
        $merchantSecret = Configuration::get('TPAY_KEY');
        $apiKey = Configuration::get('TPAY_APIKEY');
        $apiPass = Configuration::get('TPAY_APIPASS');

        return new BasicRefundsExample($apiKey, $apiPass, $merchantId, $merchantSecret);
    }

    /**
     * Returns card tpay client.
     *
     * @param $midId
     * @return CardBasicForm
     */
    public static function getCardClient($midId)
    {
        $apiKey = Configuration::get('TPAY_CARD_KEY' . $midId);
        $apiPass = Configuration::get('TPAY_CARD_PASS' . $midId);
        $verificationCode = Configuration::get('TPAY_CARD_CODE' . $midId);
        $hashType = Configuration::get('TPAY_CARD_HASH' . $midId);
        $keyRSA = Configuration::get('TPAY_CARD_RSA' . $midId);

        return new CardBasicForm($apiKey, $apiPass, $verificationCode, $hashType, $keyRSA);
    }

    public static function getCardRefundsClient($midId)
    {
        $apiKey = Configuration::get('TPAY_CARD_KEY' . $midId);
        $apiPass = Configuration::get('TPAY_CARD_PASS' . $midId);
        $verificationCode = Configuration::get('TPAY_CARD_CODE' . $midId);
        $hashType = Configuration::get('TPAY_CARD_HASH' . $midId);
        $keyRSA = Configuration::get('TPAY_CARD_RSA' . $midId);

        return new CardRefundsExample($apiKey, $apiPass, $verificationCode, $hashType, $keyRSA);
    }

    public static function getCardValidator($midId)
    {
        $apiKey = Configuration::get('TPAY_CARD_KEY' . $midId);
        $apiPass = Configuration::get('TPAY_CARD_PASS' . $midId);
        $verificationCode = Configuration::get('TPAY_CARD_CODE' . $midId);
        $hashType = Configuration::get('TPAY_CARD_HASH' . $midId);
        $keyRSA = Configuration::get('TPAY_CARD_RSA' . $midId);

        return new CardNotification($apiKey, $apiPass, $verificationCode, $hashType, $keyRSA);
    }

    public static function getCardMidNumber($currency, $domain)
    {

        $counter = 10;
        $validMidId = array();
        $midForCurrency = '';
        $midPLN = '';
        $midId = 11;

        for ($i = 1; $i <= $counter; $i++) {
            if (Configuration::get('TPAY_CARD_DOMAIN' . $i) === $domain) {
                $validMidId[] = $i;
            }
        }
        for ($i = 0; $i < count($validMidId); $i++) {

            $midCurrency = explode(',', trim(Configuration::get('TPAY_CARD_CURRENCY' . $validMidId[$i]), ' '));
            $midType = Configuration::get('TPAY_CARD_MULTI_CURRENCY' . $validMidId[$i]);
            $midOn = Configuration::get('TPAY_CARD_MID_ACTIVE' . $validMidId[$i]);

            if (!(bool)$midType && $currency === 'PLN' && (bool)$midOn) {
                $midId = $validMidId[$i];
                $midPLN = $validMidId[$i];
                break;
            }
            foreach ($midCurrency as $key => $value) {
                if ((strcasecmp($midCurrency[$key], $currency) === 0
                        || strcasecmp($midCurrency[$key], filter_input(INPUT_POST, 'currency')) === 0)
                    && $midOn !== 0 && (int)$midType === 1
                ) {
                    $midId = $validMidId[$i];
                    $midForCurrency = $validMidId[$i];

                } elseif ($midCurrency[$key] === '' && $midOn !== 0) {
                    $midId = $validMidId[$i];
                }
            }
        }
        if ($midId === 11) {
            return false;
        } elseif (!empty($midForCurrency) && empty($midPLN)) {
            $midId = $midForCurrency;
        }

        return $midId;

    }

    /**
     * Return merchant shop details.
     *
     * @return string
     */
    public static function getMerchantData()
    {
        $shopDetails = Configuration::getMultiple(
            array(
                'PS_SHOP_NAME',
                'PS_SHOP_ADDR1',
                'PS_SHOP_CODE',
                'PS_SHOP_CITY',
                'PS_SHOP_DETAILS'
            )
        );

        return implode(' ', $shopDetails);
    }

    /**
     * Return surcharge value for order.
     *
     * @param float $orderTotal order value
     *
     * @return float
     */
    public static function getSurchargeValue($orderTotal)
    {
        if ((int)Configuration::get('TPAY_SURCHARGE_ACTIVE') !== 1) {
            return 0.00;
        }
        $surchargeValue = (float)Configuration::get('TPAY_SURCHARGE_VALUE');
        if ((int)Configuration::get('TPAY_SURCHARGE_TYPE') === TPAY_SURCHARGE_PERCENT) {
            $surcharge = ($orderTotal / 100) * $surchargeValue;
        } else {
            $surcharge = $surchargeValue;
        }
        $feeProduct = new Product(TpayHelperClient::getTpayFeeProductId(), true);
        $feeProduct->price = $surcharge;
        $feeProduct->save();
        $feeProduct->flushPriceCache();

        return $feeProduct->getPrice(true, null, 2);
    }

    public static function getTpayFeeProductId()
    {
        return (int)Configuration::get('TPAY_FEE_PRODUCT_ID');
    }

}
