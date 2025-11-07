<?php

$midCount = array();
for ($j = 1; $j < TPAY_CARD_MIDS; $j++) {
    $midCount[] = array('id' => $j, 'name' => $j);
}
$cardPaymentConfig = array(
    'form' => array(
        'legend' => array(
            'title' => $this->l('Settings for card payments'),
            'image' => $this->_path . 'views/img/logo.png',
        ),
        'input'  => array(
            array(
                'type'    => $switch,
                'label'   => $this->l('Payment active'),
                'name'    => 'TPAY_CARD_ACTIVE',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_active_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_active_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
                'desc'    => $this->l('Link for merchant panel configuration: ') . '<b>' . _PS_BASE_URL_ . __PS_BASE_URI__ .
                    'module/tpay/confirmation </b>',
            ),
            array(
                'type'     => 'select',
                'label'    => $this->l('MID config number:'),
                'name'     => 'TPAY_CARD_MID_NB',
                'options'  => array(
                    'query' => $midCount,
                    'id'    => 'id',
                    'name'  => 'name'
                ),
                'required' => false,
            ),
        ),
        'submit' => array(
            'title' => $this->l('Save'),
            'class' => 'button',
        ),
    )
);

for ($i = 1; $i < TPAY_CARD_MIDS; $i++) {

    $cardPaymentConfig2 = array(
        array(
            'type'     => $switch,
            'label'    => $this->l('MID active'),
            'name'     => 'TPAY_CARD_MID_ACTIVE' . $i,
            'desc'     => $this->l('Choose if you want to use this configuration.'),
            'is_bool'  => true,
            'class'    => 't',
            'values'   => array(
                array(
                    'id'    => 'tpay_mid_active_on',
                    'value' => 1,
                    'label' => $this->l('Yes'),
                ),
                array(
                    'id'    => 'tpay_mid_active_off',
                    'value' => 0,
                    'label' => $this->l('No'),
                ),
            ),
            'required' => true,
        ),
        array(
            'type'     => 'text',
            'label'    => $this->l('MID domain'),
            'desc'     => $this->l('Type in domain address where you want to use this MID. For ex. ') .
                _PS_BASE_URL_ . __PS_BASE_URI__,
            'name'     => 'TPAY_CARD_DOMAIN' . $i,
            'size'     => 50,
            'required' => true,
        ),
        array(
            'type'     => $switch,
            'label'    => $this->l('Multi-currency MID type'),
            'name'     => 'TPAY_CARD_MULTI_CURRENCY' . $i,
            'desc'     => $this->l('Choose yes if your mid is multi-currency type or no for EDCC'),
            'is_bool'  => true,
            'class'    => 't',
            'values'   => array(
                array(
                    'id'    => 'tpay_mid_active_on',
                    'value' => 1,
                    'label' => $this->l('Yes'),
                ),
                array(
                    'id'    => 'tpay_mid_active_off',
                    'value' => 0,
                    'label' => $this->l('No'),
                ),
            ),
            'required' => true,
        ),
        array(
            'type'     => 'text',
            'label'    => $this->l('MID currency'),
            'desc'     => $this->l('Enter the currencies you want handle by this MID. For ex. EUR,USD.
             Leave empty if you want to handle all or this MID is EDCC type'),
            'name'     => 'TPAY_CARD_CURRENCY' . $i,
            'size'     => 50,
            'required' => false,
        ),
        array(
            'type'     => 'text',
            'label'    => $this->l('Card API key'),
            'name'     => 'TPAY_CARD_KEY' . $i,
            'size'     => 50,
            'required' => true,
        ),
        array(
            'type'     => 'text',
            'label'    => $this->l('Card API password'),
            'name'     => 'TPAY_CARD_PASS' . $i,
            'size'     => 50,
            'required' => true,
        ),
        array(
            'type'     => 'text',
            'label'    => $this->l('Card verification code'),
            'name'     => 'TPAY_CARD_CODE' . $i,
            'size'     => 50,
            'required' => true,
        ),
        array(
            'type'     => 'text',
            'label'    => $this->l('Card RSA public key'),
            'name'     => 'TPAY_CARD_RSA' . $i,
            'size'     => 500,
            'required' => true,
        ),
        array(
            'type'     => 'select',
            'label'    => $this->l('Card RSA hashing method'),
            'name'     => 'TPAY_CARD_HASH' . $i,
            'options'  => array(
                'query' => array(
                    array('id' => 'sha1', 'name' => 'sha1'),
                    array('id' => 'sha256', 'name' => 'sha256'),
                    array('id' => 'sha512', 'name' => 'sha512'),
                    array('id' => 'ripemd160', 'name' => 'ripemd160'),
                    array('id' => 'ripemd320', 'name' => 'ripemd320'),
                    array('id' => 'md5', 'name' => 'md5'),
                ),
                'id'    => 'id',
                'name'  => 'name'
            ),
            'required' => true,
        ),

    );
    foreach ($cardPaymentConfig2 as $key => $value) {
        array_push($cardPaymentConfig['form']['input'], $cardPaymentConfig2[$key]);
    }
}

return $cardPaymentConfig;
