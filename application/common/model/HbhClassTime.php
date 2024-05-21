<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhClassTime extends SingleSubData {
    public $mcName = 'hbh_class_time_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    function getAllList($shop_id = ''){
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'sort asc,id asc';
        $list = $this->getList($op, 0);
        return $list['list'];
    }


}
