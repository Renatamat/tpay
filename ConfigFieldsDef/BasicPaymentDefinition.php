<?php
/**
 * Created by tpay.com.
 * Date: 26.05.2017
 * Time: 17:30
 */
return array(
    'form' => array(
        'legend' => array(
            'title' => $this->l('Settings for standard payment'),
            'image' => $this->_path . 'views/img/logo.png',
        ),
        'input'  => array(
            array(
                'type'    => $switch,
                'label'   => $this->l('Payment active'),
                'name'    => 'TPAY_BASIC_ACTIVE',
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
            ),
            array(
                'type'    => $switch,
                'label'   => $this->l('Show installments payment option (over 300zł)'),
                'name'    => 'TPAY_INSTALLMENTS_ACTIVE',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_installments_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_installments_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
            ),
            array(
                'type'    => 'radio',
                'label'   => $this->l('View for payment channels'),
                'name'    => 'TPAY_BANK_ON_SHOP',
                'is_bool' => false,
                'class'   => 'child',
                'values'  => array(
                    array(
                        'id'    => 'tpay_bank_selection_redirect',
                        'value' => TPAY_VIEW_REDIRECT,
                        'label' => $this->l('Redirect to tpay'),
                    ),
                    array(
                        'id'    => 'tpay_bank_selection_icons',
                        'value' => TPAY_VIEW_ICONS,
                        'label' => $this->l('Tiles'),
                    ),
                    array(
                        'id'    => 'tpay_bank_selection_list',
                        'value' => TPAY_VIEW_LIST,
                        'label' => $this->l('List'),
                    ),
                ),
            ),
            array(
                'type'    => $switch,
                'label'   => $this->l('Show tpay regulations on site'),
                'name'    => 'TPAY_SHOW_REGULATIONS',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_regulations_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_regulations_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
            ),
        ),
        'submit' => array(
            'title' => $this->l('Save'),
            'class' => 'button',
        ),
    ),
);
