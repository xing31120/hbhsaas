<?php
namespace app\common\model;
use app\common\model\basic\Single;
use app\common\model\basic\SingleSubData;

class HbhOrder extends SingleSubData {
    public $mcName = 'hbh_order_';
//    public $selectTime = 600;
//    public $mcTimeOut = 600;
    public $mcOpen = false;

    const order_status_wait = 10;   //待付款
    const order_status_pay = 20;    //付款完成
    const order_status_end = 30;    //订单完成
    const order_status_cancel = 40; //订单取消


}
