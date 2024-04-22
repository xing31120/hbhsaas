<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhShopCampus extends SingleSubData {
    public $mcName = 'hbh_shop_campus_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    const status_true = 1;
    const status_false = 4;

    function getAllCampusList($shop_id = false){
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = $this->getList($op, $shop_id);
        return $list['list'];
    }

    function getAllCampusShopList($shop_id = false){
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'shop_id desc';
        $list = $this->getList($op, $shop_id);
        $shop_list = (new HbhShop())->all()->toArray();
        $shop_list = array_column($shop_list, null, 'id');
//pj($shop_list);
        $return_data = [];
        foreach ($list['list'] as $item) {
            $shop_id = $item['shop_id'];
            $return_data[$shop_id]['name_en'] = $shop_list[$shop_id]['shop_name_en'];
            $return_data[$shop_id]['name'] = $shop_list[$shop_id]['shop_name'];
            $return_data[$shop_id]['url'] = $shop_list[$shop_id]['url'];
            $return_data[$shop_id]['data'][] = $item;
        }


        return $return_data;
    }
}
