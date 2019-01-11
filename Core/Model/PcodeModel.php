<?php
/**
 * Model
 *
 * DB模型
 *
 * @author Python Luo <laifaluo@126.com>
 *
 * */
 
namespace App\Library\Model\Mysql;
 
use App\Library\Base\MysqlBase as MysqlBase;
use App\Library\Framework\Container;


class PcodeModel extends MysqlBase 
{
    public $tableName = 'eload_promotion_code';
    
    public function __construct(Container $app = null)
    {
        parent::__construct($app);
    }
    
    public function updatePcodeTimesByCode($code)
    {
        
        $sql = "update " . PCODE . " SET cishu = cishu + 1 where code = ?";
        $object = $this->masterDb->executeQuery($sql, [$code]);
        
        return $object->rowCount();
    }
    
    public function addUserCouponByCandles($data)
    {
        $this->masterDb->insert($this->tableName, $data);
    }
    

}