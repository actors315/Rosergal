<?php
namespace App\Library\Model\Mysql;
 
use App\Library\Base\MysqlBase as MysqlBase;


class EasterShareModel extends MysqlBase 
{
    public $tableName = 'eload_easter_game_sharelist';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function insertData($data)
    {
        $this->masterDb->insert($this->tableName, $data);
    }
    
    
    
}