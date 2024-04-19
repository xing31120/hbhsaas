<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhEmailDetail extends SingleSubData {
    public $mcName = 'hbh_email_detail_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    const status_wait = 0;
    const status_delete = 4;
    const status_end = 10;


    function getListByStatus($status = [], $shop_id = ''){
        !empty($status) && $op['where'][] = ['status','in', $status];
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = $this->getList($op);
        return $list['list'];
    }

    function addRow($data){

    }

}
