<?php
namespace app\common\model;
use app\common\model\basic\Single;
use app\common\model\basic\SingleSubData;

class HbhPointProductCategory extends SingleSubData {
    public $mcName = 'hbh_point_product_category_';
//    public $selectTime = 600;
//    public $mcTimeOut = 600;
    public $mcOpen = true;

    function getAllPointProductCatList($shop_id = ''){
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = $this->getList($op);
        return $list['list'];
    }
}
