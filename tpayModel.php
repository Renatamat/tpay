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
 *  @author    tpay.com
 *  @copyright 2010-2016 tpay.com
 *  @license   LICENSE.txt
 */

/**
 * Class TpayModel.
 */
class TpayModel extends ObjectModel
{
    public static $definition = array(
        'table' => 'tpay',
        'primary' => 'tj_id',
        'multishop' => true,
        'multilang' => false,
        'fields' => array(
            'tj_order_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'def' => 'INT(11)',
            ),
            'tj_crc' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'size' => 255,
                'def' => 'VARCHAR(255)',
            ),
            'tj_payment_type' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isName',
                'required' => false,
                'size' => 255,
                'def' => 'VARCHAR(255)',
            ),
            'tj_register_user' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isInt',
                'required' => false,
                'def' => 'INT(11)',
            ),
            'tj_surcharge' => array(
                'type' => self::TYPE_FLOAT,
                'validate' => 'isUnsignedFloat',
                'required' => false,
                'def' => 'DECIMAL(10, 2)',
            ),
            'tj_mid_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => false,
                'def' => 'INT(11)',
            ),
        ),
    );
    public static $refundsDefinition = array(
        'table' => 'tpay_refunds',
        'primary' => 'tjr_id',
        'multishop' => true,
        'multilang' => false,
        'fields' => array(
            'tjr_order_id' => array(
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'def' => 'INT(11)',
            ),
            'tjr_transaction_title' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'size' => 255,
                'def' => 'VARCHAR(255)',
            ),
            'tjr_date' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isInt',
                'required' => true,
                'def' => 'DATETIME',
            ),
            'tjr_amount' => array(
                'type' => self::TYPE_FLOAT,
                'validate' => 'isUnsignedFloat',
                'required' => true,
                'def' => 'DECIMAL(10, 2)',
            ),
        ),
    );

    public static function createRefundsTable()
    {
        $create_sql = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'tpay_refunds (
                          tjr_id INT NOT NULL AUTO_INCREMENT,
                          tjr_order_id INT NULL,
                          tjr_transaction_title VARCHAR(255),
                          tjr_date DATETIME,
                          tjr_amount DECIMAL(10, 2),
                      PRIMARY KEY (tjr_id)
                      ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        $creationResult = Db::getInstance()->execute($create_sql);

        if ($creationResult) {
            foreach (self::$refundsDefinition['fields'] as $columnName => $columnParams) {
                $checkColumn = 'SELECT COUNT(*) FROM information_schema.COLUMNS
                                WHERE TABLE_SCHEMA = \'' .pSQL(_DB_NAME_).'\'
                                AND TABLE_NAME = \'' .pSQL(_DB_PREFIX_.self::$refundsDefinition['table']).'\'
                                AND COLUMN_NAME = \'' .pSQL($columnName).'\'';

                $checkRes = (bool) (int) Db::getInstance()->getValue($checkColumn);

                if (!$checkRes) {
                    $alterTable = 'ALTER TABLE '._DB_PREFIX_.pSQL(self::$refundsDefinition['table']).'
                                  ADD COLUMN ' .pSQL($columnName).' '.pSQL($columnParams['def']).'';

                    $alterRes = Db::getInstance()->execute($alterTable);

                    if (!$alterRes) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Create tpay table.
     *
     * @return bool
     */
    public static function createOrdersTable()
    {
        $createSql = 'CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'tpay (
                          tj_id INT NOT NULL AUTO_INCREMENT,
                          tj_order_id INT NULL,
                          tj_crc VARCHAR(255),
                          tj_payment_type VARCHAR(255),
                          tj_register_user INT NULL,
                          tj_surcharge DECIMAL(10, 2),
                          tj_mid_id INT NULL,
                      PRIMARY KEY (tj_id)
                      ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

        Db::getInstance()->execute($createSql);
        foreach (self::$definition['fields'] as $columnName => $columnParams) {
            $checkColumn = 'SELECT COUNT(*) FROM information_schema.COLUMNS
                                WHERE TABLE_SCHEMA = \''.pSQL(_DB_NAME_).'\'
                                AND TABLE_NAME = \''.pSQL(_DB_PREFIX_.self::$definition['table']).'\'
                                AND COLUMN_NAME = \''.pSQL($columnName).'\'';
            $checkRes = (bool)(int)Db::getInstance()->getValue($checkColumn);
            if (!$checkRes) {
                $alterTable = 'ALTER TABLE '._DB_PREFIX_.pSQL(self::$definition['table']).'
                                  ADD COLUMN '.pSQL($columnName).' '.pSQL($columnParams['def']).'';
                $alterRes = Db::getInstance()->execute($alterTable);
                if (!$alterRes) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Insert order to tpay.
     *
     * @param int $order_id prestashop order id
     * @param string $crc md5 chcecksum
     * @param string $type payment type
     * @param bool $register_user register user for payment on demand
     * @param float $surcharge transaction surcharge
     * @param int $midId MID ID used to create transaction
     * @return bool
     */
    public static function insertOrder(
        $order_id, $crc, $type = '', $register_user = false, $surcharge = 0.0, $midId = 0)
    {
        $insert_sql = 'INSERT INTO '._DB_PREFIX_.'tpay(
                            tj_order_id,
                            tj_crc,
                            tj_payment_type,
                            tj_register_user,
                            tj_surcharge,
                            tj_mid_id
                        ) VALUES (' .
            (int) $order_id.', '.
            '\''.pSQL($crc).'\', '.
            '\''.pSQL($type).'\', '.
            (int) $register_user.', '.
            (float) $surcharge.', '.
            (int) $midId.'
                        )';

        return Db::getInstance()->execute($insert_sql);
    }

    public static function insertRefund($order_id, $title, $amount)
    {
        $date = date('Y-m-d H:i:s');
        $insert_sql = 'INSERT INTO '._DB_PREFIX_.'tpay_refunds(
                          tjr_order_id,
                          tjr_transaction_title,
                          tjr_date,
                          tjr_amount
                        ) VALUES (' .
            (int) $order_id.', '.
            '\''.pSQL($title).'\', '.
            '\''.pSQL($date).'\', '.
            (float) $amount.'
                        )';

        return Db::getInstance()->execute($insert_sql);
    }

    /**
     * Return prestashop shop order id.
     *
     * @param string $crc md5 checksum
     *
     * @return mixed
     */
    public static function getOrderIdAndSurcharge($crc)
    {
        $get_sql = 'SELECT tj_order_id, tj_surcharge
                    FROM ' ._DB_PREFIX_.'tpay
                    WHERE tj_crc=\'' .pSQL($crc).'\'';

        return Db::getInstance()->getRow($get_sql);
    }

    public static function getOrderRefunds($orderId)
    {
        $get_sql = 'SELECT *
                    FROM ' ._DB_PREFIX_.'tpay_refunds
                    WHERE tjr_order_id=\'' .pSQL($orderId).'\'';

        return Db::getInstance()->executeS($get_sql);
    }

    /**
     * Get register user value.
     *
     * @param int $order_id
     *
     * @return bool
     */
    public static function getRegisterUser($order_id)
    {
        $get_register = 'SELECT tj_register_user
                        FROM ' ._DB_PREFIX_.'tpay
                        WHERE tj_order_id=' .(int) $order_id;

        return (bool) (int) Db::getInstance()->getValue($get_register);
    }

    /**
     * Return hash assigned to order.
     *
     * @param int $orderId
     *
     * @return mixed
     */
    public static function getHash($orderId)
    {
        $get_sql = 'SELECT tj_crc
                    FROM ' ._DB_PREFIX_.'tpay
                    WHERE tj_order_id=' .(int) $orderId;

        return Db::getInstance()->getValue($get_sql);
    }

    public static function getPaymentType($orderId)
    {
        $get_sql = 'SELECT tj_payment_type
                    FROM ' ._DB_PREFIX_.'tpay
                    WHERE tj_order_id=' .(int) $orderId;

        return Db::getInstance()->getValue($get_sql);
    }

    public static function getPaymentMidId($orderId)
    {
        $get_sql = 'SELECT tj_mid_id
                    FROM ' ._DB_PREFIX_.'tpay
                    WHERE tj_order_id=' .(int) $orderId;

        return Db::getInstance()->getValue($get_sql);
    }

    /**
     * @param int $orderId
     * @return array
     */
    public static function getOrderStatusHistory($orderId)
    {
        $sql = 'SELECT id_order_state
                FROM ' . _DB_PREFIX_ . 'order_history
                WHERE id_order=' . (int)$orderId;

        $rows = Db::getInstance()->executeS($sql);
        $statuses = [];
        foreach ($rows as $value) {
            foreach ($value as $statusID) {
                $statuses[]=$statusID;
            }
        }

        return $statuses;
    }

}
