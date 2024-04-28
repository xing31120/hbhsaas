<?php
namespace app\common\model;
use app\common\model\basic\Single;

class HbhShop extends Single {
    public $mcName = 'hbh_shop_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    const status_true = 1;
    const status_false = 4;

    function getAllShopList(){
        $op['where'][] = ['status','=', self::status_true];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id asc';
        $list = $this->getList($op);
        return $list['list'];
    }
}
