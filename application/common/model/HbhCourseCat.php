<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhCourseCat extends SingleSubData {
    public $mcName = 'hbh_course_cat_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    const status_true = 1;
    const status_false = 4;


    function getAllCourseCatList($shop_id = ''){
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = $this->getList($op);
        return $list['list'];
    }
}
