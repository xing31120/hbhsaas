<?php
namespace app\common\model;
use app\common\model\basic\Single;
use app\common\model\basic\SingleSubData;
use think\Db;

class HbhOrderPay extends SingleSubData {
    public $mcName = 'hbh_order_pay_';
//    public $selectTime = 600;
//    public $mcTimeOut = 600;
    public $mcOpen = false;

    const order_status_wait = 10;   //待付款
    const order_status_pay = 20;    //付款完成
    const order_status_end = 30;    //订单完成
    const order_status_cancel = 40; //订单取消


    function infoByBizOrderNo($payOrderNO){
        if (empty($payOrderNO)) {
            return false;
        }

        $where[] = ['order_sn','=',$payOrderNO];
        $rs = $this->where($where)->find();
        if(empty($rs)){
            return false;
        }
        //设置缓存
//        if($this->mcOpen){
//            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
//            cache($mcKey, $rs, $time);
//        }
        return $rs->toArray();

    }

    /**
     * 异步支付完成
     * @param $pay_order_no string 业务系统订单号
     * @param $threeOrderNo string 通商云订单号
     * @return array
     */
    function payComplete($pay_order_no, $threeOrderNo = ''){
        $beforeStatus = self::order_status_wait;
        $upStatus = self::order_status_pay;
        // 查询支付订单
        $info = $this->infoByBizOrderNo( $pay_order_no);
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
        $info['three_order_no'] = $data['three_order_no'] = $threeOrderNo;
//        $upResult = $this->where('id','=',$info['id'])->update($data);
        $upResult = $this->updateById($info['id'], $data);
        if(!$upResult){
            return errorReturn('update order error!');
        }

        //更新用户钱包, 增加钱包日志
        $remark = "Online Recharge ({$class_num})";
        $action_all = 'Order/notifyPay' ;
        $bizType = HbhUserWalletDetail::bizTypeRecharge;
        $payPassageway = HbhUserWalletDetail::pay_passageway_online;
        $resDetail = (new HbhUserWalletDetail())->updateUserWalletAndDetail($info['user_id'], $class_num, HbhUserWalletDetail::RECHARGE,
            HbhUserWalletDetail::wallet_type_class, $remark, $info['id'], $action_all,$bizType, $payPassageway);
        if (!$resDetail['result']) {
            Db::rollback();
            return $resDetail;
        }


        return successReturn(['data' => $info]);

    }

}
