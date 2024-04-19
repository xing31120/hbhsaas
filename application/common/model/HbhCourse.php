<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhCourse extends SingleSubData {
    public $mcName = 'hbh_course_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;


    function getAllCourseList($shop_id = ''){
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'name asc';
        $list = $this->getList($op);
        return $list['list'];
    }
}
