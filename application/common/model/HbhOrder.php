<?php
namespace app\common\model;
use app\common\model\basic\Single;
use app\common\model\basic\SingleSubData;
use think\Db;

class HbhOrder extends SingleSubData {
    public $mcName = 'hbh_order_';
//    public $selectTime = 600;
//    public $mcTimeOut = 600;
    public $mcOpen = false;

    const order_status_wait = 10;   //待付款
    const order_status_pay = 20;    //付款完成
    const order_status_end = 30;    //订单完成
    const order_status_cancel = 40; //订单取消


    function infoByBizOrderNo($orderNO){
        if (empty($orderNO)) {
            return false;
        }

        $where[] = ['order_sn','=',$orderNO];
        $rs = $this->where($where)->find();
        if(empty($rs)){
            return false;
        }
        return $rs->toArray();

    }

    /**
     * 支付完成
     * @param $order_no string 业务系统订单号
     * @return array
     */
    function orderComplete($order_no){
        $beforeStatus = self::order_status_wait;
        $upStatus = self::order_status_pay;
        // 查询支付订单
        $info = $this->infoByBizOrderNo( $order_no);
        if(empty($info) || $info['order_status'] != $beforeStatus){
            return errorReturn('search order error!');
        }
        // 查询订单商品
        $product_info = (new HbhProduct())->info($info['product_id']);
        $class_num = $product_info['class_num'] ?? -99;
        if($class_num == -99){
            return errorReturn('search product error!');
        }


        // 更新支付订单状态
        $data['id'] = $info['id'];
        $info['order_status'] = $data['order_status'] = $upStatus;
        $upResult = $this->updateById($info['id'], $data);
        if(!$upResult){
            return errorReturn('update order error!');
        }

        return successReturn(['data' => $info]);

    }

}
