<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhClassTimeDetail extends SingleSubData {
    public $mcName = 'hbh_class_time_detail_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

//    function getAllList($uid){
//        $op['where'][] = ['uid','=', $uid];
//        $op['doPage'] = false;
//        $op['field'] = '*';
//        $op['order'] = 'id asc';
//        $list = $this->getList($op);
//        return $list['list'];
//    }

}
