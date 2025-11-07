<?php

/**
 * Created by tpay.com.
 * Date: 01.02.2018
 * Time: 15:56
 */
class TpayOrderStatusHandler extends Helper
{
    /**
     * Update orders statuses.
     *
     * @param int $orderId
     * @param string $tpayPaymentId
     * @param bool $error change to error status flag
     */
    public function setOrdersAsConfirmed($orderId, $tpayPaymentId, $error = false)
    {
        $order = new Order($orderId);
        $reference = $order->reference;
        $referencedOrders = Order::getByReference($reference)->getResults();
        foreach ($referencedOrders as $orderObject) {
            if (!is_null($orderObject->id)) {
                $this->setOrderAsConfirmed($orderObject, $tpayPaymentId, $error);
            }
        }
    }

    /**
     * Update order status.
     *
     * @param Order $order
     * @param string $tpayPaymentId
     * @param bool $error change to error status flag
     */
    private function setOrderAsConfirmed($order, $tpayPaymentId, $error = false)
    {
        $orderHistory = new OrderHistory();
        $ownStatusSetting = (int)Configuration::get('TPAY_OWN_STATUS') === 1;
        if ($ownStatusSetting) {
            $targetOrderState = !$error ? Configuration::get('TPAY_OWN_PAID') : Configuration::get('TPAY_OWN_ERROR');
        } else {
            $targetOrderState = !$error ? Configuration::get('TPAY_CONFIRMED') : Configuration::get('TPAY_ERROR');
        }
        $orderStatusesHistory = TpayModel::getOrderStatusHistory($order->id);
        if (!in_array($targetOrderState, $orderStatusesHistory)) {
            if (!$error) {
                $order->addOrderPayment($order->getOrdersTotalPaid(), 'Tpay', $tpayPaymentId);
            }
            $orderHistory->id_order = $order->id;
            $orderHistory->changeIdOrderState($targetOrderState, $order->id, true);
            $orderHistory->addWithemail(true);
        }
    }

}
