<?php
namespace app\common\model;
use app\common\model\basic\Common;

class OrderWithdraw extends Common{
    public $mcName = 'order_withdraw_';
    public $selectTime = 6;
    public $mcTimeOut = 6;

    public $withdrawType = [1 =>'D1',2 =>'D0'];//订单提现类型
    public $auditStatus = [0 => '等待审核', 10 =>'审核通过', 40 =>'审核驳回'];//审核状态
    public $payStatus = [0 => '申请等待中', 20 => '支付成功', 40 => '支付失败'];//支付状态 0.等待中 10.进行中 20.成功 40.失败',
    public $backStatus = [0 => '未通知', 10 => '等待通知', 20 => '通知成功', 40 => '通知失败'];

    const PAY_STATUS = [
        'WAIT_PAY' => 0,
        'ALL_IN_PAY_COMPLETE' => 20,
        'ALL_IN_PAY_ERROR' => 40,
    ];

    /**
     * 增加提现订单
     * @param [type] $appUid
     * @param [type] $param
     * @return void
     * @date 2020-11-20
     */
    public function addWithdraw($appUid, $param){
        if(empty($param['biz_order_no']))  return errorReturn('订单编号错误');
        if(empty($param['biz_users_id']) )  return errorReturn('用户编号错误');
        if(empty($param['biz_back_url']) )  return errorReturn('回调地址错误');
        if(empty($param['bankCardNo']) )  return errorReturn('银行卡信息错误');

        $bizUid = str_replace($appUid,"",$param['biz_users_id']);
        $userInfo = model('Users')->infoByBizUid($appUid, $bizUid);

        $data['uid']                = $userInfo['id'];
        $data['app_uid']            = $userInfo['app_uid'];
        $data['biz_uid']            = $userInfo['biz_uid'];
        $data['biz_order_no']       = $param['biz_order_no'];
        $data['allinpay_order_no']  = $param['allinpay_order_no'];
        $data['withdraw_type']      = $param['withdrawType'] ?? 1;
        $data['amount']             = $param['amount'] ?? 0;
        $data['fee_amount']         = $param['fee'] ?? 0;
        $data['bank_card_no']       = $param['bankCardNo'] ?? 0;
        $data['bank_card_pro']      = $param['bankCardPro'] ?? 0;
        $data['extend_info']        = $param['extendInfo'] ?? '';
        $data['biz_back_url']       = $param['biz_back_url'] ?? '';

        $res = $this->saveData($appUid, $data);
        if(!$res){
            return errorReturn('新增提现订单失败');
        }

        return successReturn(['data' => $res]);
    }

    function infoByBizOrderNo($appUid, $bizOrderNO){
        if (empty($bizOrderNO)) {
            return false;
        }

        $this->submeter($appUid);
        $where[] = ['biz_order_no','=',$bizOrderNO];
        $mcKey = $this->mcName . '_' . $bizOrderNO;

        //缓存开启并且命中
        if($this->mcOpen && cache($mcKey) !== false){
            return cache($mcKey)->toArray();
        }
        //查询失败直接返回false
        $rs = $this->where($where)->find();
        if(empty($rs)){
            return false;
        }
        //设置缓存
        if($this->mcOpen){
            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
            cache($mcKey, $rs, $time);
        }
        return $rs->toArray();

    }

    //allInPay异步支付完成
    function allInPayComplete($appUid, $biz_order_no, $allinpayPayNo,$orderNo = ''){
        $beforeStatus = self::PAY_STATUS['WAIT_PAY'];
        $upEntryStatus = self::PAY_STATUS['ALL_IN_PAY_COMPLETE'];
        $info = $this->infoByBizOrderNo($appUid, $biz_order_no);
        if(empty($info) || $info['pay_status'] != $beforeStatus){
            return errorReturn('查询订单失败!');
        }
        $data['id'] = $info['id'];
        $info['pay_status'] = $data['pay_status'] = $upEntryStatus;
        $info['allinpay_pay_no'] = $data['allinpay_pay_no'] = $allinpayPayNo;
        if(!empty($orderNo)){
            $data['allinpay_order_no'] = $orderNo;
        }
        $upResult = $this->where('id','=',$info['id'])->update($data);
        if(!$upResult){
            return errorReturn('更新订单失败!');
        }
        return successReturn(['data' => $info]);
//        return $this->upPayStatusByNo($appUid, $biz_order_no,self::PAY_STATUS['WAIT_PAY'], self::PAY_STATUS['ALL_IN_PAY_COMPLETE']);
    }

    //allInPay异步支付失败
    function allInPayError($appUid, $biz_order_no){
        return $this->upPayStatusByNo($appUid, $biz_order_no,self::PAY_STATUS['WAIT_PAY'], self::PAY_STATUS['ALL_IN_PAY_ERROR']);
    }

    //0:申请等待中 20:支付成功 40:支付失败
    function upPayStatusByNo($appUid, $biz_order_no, $beforeStatus, $upEntryStatus){

    }



}
