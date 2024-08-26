<?php
namespace app\common\model;
use app\common\model\basic\Single;
use app\common\model\basic\SingleSubData;
use think\Db;
use think\facade\Lang;

class HbhOrderPay extends SingleSubData {
    public $mcName = 'hbh_order_pay_';
//    public $selectTime = 600;
//    public $mcTimeOut = 600;
    public $mcOpen = false;
    public $selectTime = 0;

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
            return errorReturn('search pay order error!');
        }
        // 查询订单商品
        $product_info = (new HbhProduct())->info($info['product_id']);
        $class_num = $product_info['class_num'] ?? -99;
        if($class_num == -99){
            return errorReturn('search product error!');
        }
        //查询订单
        $info_order = (new HbhOrder())->info($info['order_id']);
        if(empty($info_order) || $info_order['order_status'] != $beforeStatus){
            return errorReturn('search order error!');
        }

        // 更新支付订单状态
        $data_order['id'] = $info_order['id'];
        $info_order['order_status'] = $data['order_status'] = $upStatus;
        $upResult_order = $this->updateById($info_order['id'], $data_order);
        if(!$upResult_order){
            return errorReturn('update order error!');
        }

        // 更新支付订单状态
        $data['id'] = $info['id'];
        $info['order_status'] = $data['order_status'] = $upStatus;
        $info['three_order_no'] = $data['three_order_no'] = $threeOrderNo;
//        $upResult = $this->where('id','=',$info['id'])->update($data);
        $upResult = $this->updateById($info['id'], $data);
        if(!$upResult){
            return errorReturn('update pay order error!');
        }

        //更新用户余额
        $userInfo = (new HbhUsers())->where('id', $info['user_id'])->findOrEmpty()->toArray();
        $userInfo['residue_quantity'] = $userInfo['residue_quantity'] + $class_num;
        unset($userInfo['create_time']);
        unset($userInfo['update_time']);
        $res = (new HbhUsers())->saveData($userInfo);
        if(!$res){
            Db::rollback();
            return errorReturn(Lang::get('FailedToDeductUserBalance'));
        }

        //更新用户钱包, 增加钱包日志
        $remark = "Online Recharge ({$class_num})";
        $action_all = 'Order/notifyPay' ;
        $bizType = HbhUserWalletDetail::bizTypeRecharge;
        $payPassageway = HbhUserWalletDetail::pay_passageway_online;
        $resDetail = (new HbhUserWalletDetail())->updateUserWalletAndDetail($info['user_id'], $class_num, HbhUserWalletDetail::RECHARGE,
            HbhUserWalletDetail::wallet_type_class, $remark, $info['id'], $action_all,$bizType, $payPassageway);
        if (!$resDetail['result']) {
            return $resDetail;
        }


        return successReturn(['data' => $info]);

    }

}
