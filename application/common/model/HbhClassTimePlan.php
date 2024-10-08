<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhClassTimePlan extends SingleSubData {
    public $mcName = 'hbh_class_time_plan_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;


    function getAllCourseList($shop_id = ''){
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = $this->getList($op);
        return $list['list'];
    }
}
