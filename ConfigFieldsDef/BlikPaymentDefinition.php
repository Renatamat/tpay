<?php
/**
 * Created by tpay.com.
 * Date: 26.05.2017
 * Time: 17:30
 */
return array(
    'form' => array(
        'legend' => array(
            'title' => $this->l('Settings for BLIK payments and refunds'),
            'image' => $this->_path . 'views/img/logo.png',
        ),
        'input'  => array(
            array(
                'type'    => $switch,
                'label'   => $this->l('BLIK payments active'),
                'name'    => 'TPAY_BLIK_ACTIVE',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'blik_active_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'blik_active_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
            ),
            array(
                'type'     => 'text',
                'label'    => $this->l('Transactions and refunds API key'),
                'name'     => 'TPAY_APIKEY',
                'size'     => 50,
                'required' => true,
            ),
            array(
                'type'     => 'text',
                'label'    => $this->l('Transactions and refunds API password'),
                'name'     => 'TPAY_APIPASS',
                'size'     => 50,
                'required' => true,
            ),

        ),
        'submit' => array(
            'title' => $this->l('Save'),
            'class' => 'button',
        ),
    ),
);
