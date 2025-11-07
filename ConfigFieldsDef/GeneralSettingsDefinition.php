<?php
/**
 * Created by tpay.com.
 * Date: 26.05.2017
 * Time: 17:31
 */
$settings = array(
    'form' => array(
        'legend' => array(
            'title' => $this->l('Basic settings'),
            'image' => $this->_path . 'views/img/logo.png',
        ),
        'input'  => array(
            array(
                'type'     => 'text',
                'label'    => $this->l('User Id'),
                'name'     => 'TPAY_ID',
                'size'     => 50,
                'required' => true,
            ),
            array(
                'type'     => 'text',
                'label'    => $this->l('Security code'),
                'name'     => 'TPAY_KEY',
                'size'     => 50,
                'required' => true,
            ),
            array(
                'type'    => $switch,
                'label'   => $this->l('Debug mode'),
                'name'    => 'TPAY_DEBUG',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_debug_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_debug_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
                'desc'    => '<b>' . $this->l('WARNING') . '</b>' . $this->l(' turn off in production mode'),
            ),
            array(
                'type'    => $switch,
                'label'   => $this->l('Check the IP address for notification server'),
                'name'    => 'TPAY_CHECK_IP',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_check_ip_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_check_ip_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
            ),
            array(
                'type'    => $switch,
                'label'   => $this->l('My server use proxy communication'),
                'desc'    => $this->l('Enable this option only if you are 100% sure!'),
                'name'    => 'TPAY_CHECK_PROXY',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_check_proxy_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_check_proxy_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
            ),
            array(
                'type'    => $switch,
                'label'   => $this->l('tpay payments banner on product cards'),
                'name'    => 'TPAY_BANNER',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_banner_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_banner_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
            ),
            array(
                'type'     => 'text',
                'label'    => $this->l('Notification emails'),
                'desc'     => $this->l('Set your own notification emails. Type each one separated by comma. Leave empty to use email configured in merchant panel.'),
                'name'     => 'TPAY_NOTIFICATION_EMAILS',
                'size'     => 50,
                'required' => false,
            ),
            array(
                'type'    => $switch,
                'label'   => $this->l('Surcharge for the use of payment'),
                'name'    => 'TPAY_SURCHARGE_ACTIVE',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_surcharge_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_surcharge_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
            ),
            array(
                'type'    => 'radio',
                'label'   => $this->l('Surcharge type'),
                'name'    => 'TPAY_SURCHARGE_TYPE',
                'is_bool' => false,
                'class'   => 'child',
                'values'  => array(
                    array(
                        'id'    => 'tpay_surcharge_type_on',
                        'value' => TPAY_SURCHARGE_AMOUNT,
                        'label' => $this->l('Quota'),
                    ),
                    array(
                        'id'    => 'tpay_surcharge_type_off',
                        'value' => TPAY_SURCHARGE_PERCENT,
                        'label' => $this->l('Percentage'),
                    ),
                ),
            ),
            array(
                'type'     => 'text',
                'label'    => $this->l('Surcharge value'),
                'name'     => 'TPAY_SURCHARGE_VALUE',
                'size'     => 50,
                'required' => false,
            ),
            array(
                'type'    => $switch,
                'label'   => $this->l('Set user defined statuses'),
                'name'    => 'TPAY_OWN_STATUS',
                'is_bool' => true,
                'class'   => 't',
                'values'  => array(
                    array(
                        'id'    => 'tpay_own_status_on',
                        'value' => 1,
                        'label' => $this->l('Yes'),
                    ),
                    array(
                        'id'    => 'tpay_own_status_off',
                        'value' => 0,
                        'label' => $this->l('No'),
                    ),
                ),
            ),
            array(
                'type'    => 'select',
                'label'   => $this->l('Set waiting for payment status'),
                'name'    => 'TPAY_OWN_WAITING',
                'options' => array(
                    'query' => $orderStates,
                    'id'    => 'id_option',
                    'name'  => 'name'
                )
            ),
            array(
                'type'    => 'select',
                'label'   => $this->l('Set paid order status'),
                'name'    => 'TPAY_OWN_PAID',
                'options' => array(
                    'query' => $orderStates,
                    'id'    => 'id_option',
                    'name'  => 'name'
                )
            ),
            array(
                'type'    => 'select',
                'label'   => $this->l('Set payment error status'),
                'name'    => 'TPAY_OWN_ERROR',
                'options' => array(
                    'query' => $orderStates,
                    'id'    => 'id_option',
                    'name'  => 'name'
                )
            ),
        ),
        'submit' => array(
            'title' => $this->l('Save'),
            'class' => 'button',
        ),
    ),
);

$summarySetting = array(
    'type'    => $switch,
    'label'   => $this->l('Show order summary on checkout page'),
    'name'    => 'TPAY_SUMMARY',
    'is_bool' => true,
    'class'   => 't',
    'values'  => array(
        array(
            'id'    => 'tpay_summary_on',
            'value' => 1,
            'label' => $this->l('Yes'),
        ),
        array(
            'id'    => 'tpay_summary_off',
            'value' => 0,
            'label' => $this->l('No'),
        ),
    ),
);

if (!TPAY_PS_17) {
    $settings['form']['input'][] = $summarySetting;
}

return $settings;
