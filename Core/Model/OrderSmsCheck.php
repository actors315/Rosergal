<?php
/**
 * Created by PhpStorm.
 * User: xiehuanjin
 * Date: 2018/10/14
 * Time: 14:46
 */

namespace App\Library\Model\Mysql;


use App\Library\Base\MysqlBase;

class OrderSmsCheck extends MysqlBase
{

    public $tableName = 'eload_order_sms_check';

    public function checkOrderSign($orderSn, $token)
    {
        $sql = 'SELECT osc.is_check,oi.order_id,oi.user_id FROM ' . $this->tableName . ' osc ' .
            ' INNER JOIN ' . ORDERINFO . ' oi on oi.order_sn = osc.order_sn' .
            ' WHERE osc.order_sn = ? AND osc.token = ?';

        return $this->slaveDb->execQueryString($sql, [$orderSn, $token])->fetchInto(self::FETCH_ASSOC);

    }

    public function updateCheckByToken($token, $data)
    {
        $query = $this->masterDb->update($this->tableName, $data, 'token = ? AND is_check = 0', [$token]);

        return $query->rowCount();
    }

}