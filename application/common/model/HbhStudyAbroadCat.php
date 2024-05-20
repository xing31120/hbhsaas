<?php
namespace app\common\model;
use app\common\model\basic\Single;

class HbhStudyAbroadCat extends Single {
    public $mcName = 'hbh_study_abroad_cat_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    const status_true = 1;
    const status_false = 4;

    function getAllList(){
        $op['where'][] = ['status','=', self::status_true];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'sort asc, id asc';
        $res = $this->getList($op);

        return $res['list'];
    }
}
