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


    function infoByBizOrderNo($payOrderNO){
        if (empty($payOrderNO)) {
            return false;
        }

        $where[] = ['biz_order_no','=',$payOrderNO];
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
     * @param $allinpayPayNo string 收银宝相关支付渠道单号
     * @param $orderNo string 通商云订单号
     * @return array
     */
    function allInPayComplete($appUid, $pay_order_no, $allinpayPayNo,$orderNo = ''){
        $beforeStatus = self::WAIT_PAY;
        $upEntryStatus = self::ALL_IN_PAY_COMPLETE;
        $info = $this->infoByBizOrderNo($appUid, $biz_order_no);
        if(empty($info) || $info['order_entry_status'] != $beforeStatus){
            return errorReturn('查询订单失败!');
        }
        $data['id'] = $info['id'];
        $info['order_entry_status'] = $data['order_entry_status'] = $upEntryStatus;
        $info['allinpay_pay_no'] = $data['allinpay_pay_no'] = $allinpayPayNo;
        if(!empty($orderNo)){
            $data['allinpay_order_no'] = $orderNo;
        }
        $upResult = $this->where('id','=',$info['id'])->update($data);
        if(!$upResult){
            return errorReturn('更新订单失败!');
        }

        if($info['order_type'] == self::orderType['recharge']){
            $resPlusUserFund = model('Users')->plusUserFund($appUid, $info['biz_uid'], $info['amount']);
            if(!$resPlusUserFund){
                return errorReturn('更新用户余额失败!');
            }
        }

        return successReturn(['data' => $info]);

    }

}
