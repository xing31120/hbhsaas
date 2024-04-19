<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhContactForm extends SingleSubData {
    public $mcName = 'hbh_contact_form_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    const IS_CALL_WAIT = 0;
    const IS_CALL_END = 1;

    function getAllList($is_call = -1){
        if($is_call == 0 || $is_call == 1){
            $op['where'][] = ['is_call', '=', $is_call];
        }

        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = $this->getList($op, 0);
        return $list['list'];
    }

}
