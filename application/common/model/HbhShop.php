<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhShop extends SingleSubData {
    public $mcName = 'hbh_shop_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    const status_true = 1;
    const status_false = 4;


}
