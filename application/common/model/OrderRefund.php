<?php
namespace app\common\model;
use app\common\model\basic\Common;
use app\common\service\OrderProcessService;

class OrderRefund extends Common{
    public $mcName = 'order_refund_';
    public $selectTime = 6;
    public $mcTimeOut = 6;

    public $refundType = [1 =>'D1',2 =>'D0'];//订单退款类型
    public $auditStatus = [0 => '等待审核', 10 =>'审核通过', 40 =>'审核驳回'];//审核状态
    public $payStatus = [0 => '申请等待中', 20 => '支付成功', 40 => '支付失败'];//支付状态 0.等待中 10.进行中 20.成功 40.失败',
    public $backStatus = [0 => '未通知', 10 => '等待通知', 20 => '通知成功', 40 => '通知失败'];

    //建行退款响应状态  不等同于退款状态
    const CCB_RESPONSE_REFUND_SUCCESS = '00';//退款成功
    const CCB_RESPONSE_REFUND_FAIL = '01';//退款失败
    const CCB_RESPONSE_REFUND_DELAY = '02';//退款延迟等待
    const CCB_RESPONSE_REFUND_UNCERTAIN = '03';//网银退款结果不确定

    const PAY_STATUS = [
        'WAIT_PAY' => 0,
        'ALL_IN_PAY_ING'=>10,
        'ALL_IN_PAY_COMPLETE' => 20,
        'ALL_IN_PAY_ERROR' => 40,
    ];

    const orderRefundStatus = [
        0=> '待退款',
        10=> '退款中',
        20=>'退款完成',
        40=> '退款失败',
    ];

    /**
     * 增加退款订单
     * @param [type] $appUid
     * @param [type] $param
     * @return void
     * @date 2020-11-20
     */
    public function addRefund($appUid, $param){
        if(empty($param['biz_order_no']))  return errorReturn('订单编号错误');
        if(empty($param['biz_users_id']) )  return errorReturn('用户编号错误');
        if(empty($param['biz_back_url']) )  return errorReturn('回调地址错误');

        $orderEntryInfo = model('OrderEntry')->infoByBizOrderNo($appUid, $param['ori_biz_order_no']);
        if(empty($orderEntryInfo)){
            return errorReturn('该订单不存在!');
        }

        $orderRefundInfo = $this->infoByBizOrderNo($appUid, $param['biz_order_no']);
        if(!empty($orderRefundInfo)){
            return errorReturn('订单号重复!');
        }


        $bizUid = str_replace($appUid,"",$param['biz_users_id']);
        $userInfo = model('Users')->infoByBizUid($appUid, $bizUid);

        $data['uid']                = $userInfo['id'];
        $data['app_uid']            = $userInfo['app_uid'];
        $data['biz_uid']            = $userInfo['biz_uid'];
        $data['biz_order_no']       = $param['biz_order_no'];
        $data['ori_biz_order_no']   = $param['ori_biz_order_no'];
        $data['allinpay_order_no']  = $param['allinpay_order_no'];
        $data['refund_type']        = $param['refundType'] ?? 1;
        $data['refund_list']        = $param['refund_list'] ?? '';
        $data['amount']             = $param['amount'] ?? 0;
        $data['coupon_amount']      = $param['coupon_amount'] ?? 0;
        $data['fee_amount']         = $param['fee_amount'] ?? 0;
        $data['extend_info']        = $param['extendInfo'] ?? '';
        $data['biz_back_url']       = $param['biz_back_url'] ?? '';
        $data['type']               = $param['type'] ?? 1;
        $data['show_user_name']     = $orderEntryInfo['show_user_name'];

        $res = $this->saveData($appUid, $data);
        if(!$res){
            return errorReturn('新增退款订单失败');
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
    function allInPayComplete($appUid, $biz_order_no, $allinpayPayNo){
        $beforeStatus = self::PAY_STATUS['WAIT_PAY'];
        $upEntryStatus = self::PAY_STATUS['ALL_IN_PAY_COMPLETE'];
        $info = $this->infoByBizOrderNo($appUid, $biz_order_no);
        if(empty($info) || $info['pay_status'] != $beforeStatus){
            return errorReturn('查询订单失败!');
        }
        $data['id'] = $info['id'];
        $info['pay_status'] = $data['pay_status'] = $upEntryStatus;
        $info['allinpay_pay_no'] = $data['allinpay_pay_no'] = $allinpayPayNo;
        $upResult = $this->where('id','=',$info['id'])->update($data);
        if(!$upResult){
            return errorReturn('更新订单失败!');
        }
        return successReturn(['data' => $info]);
//        return $this->upPayStatusByNo($appUid, $biz_order_no,self::PAY_STATUS['WAIT_PAY'], self::PAY_STATUS['ALL_IN_PAY_COMPLETE']);
    }

    /**
     * 通过订单号和支付流水号查询订单
     * @param $bizOrderNO
     * @param $PyTrnNo
     * @return array|bool
     * User: cwh  DateTime:2021/9/17 17:07
     */
    function infoByBizOrderNoAndPyTrnNo($bizOrderNO,$PyTrnNo){
        if (empty($bizOrderNO) || empty($PyTrnNo)) {
            return false;
        }
        $where[] = ['biz_order_no','=',$bizOrderNO];
        $where[] = ['py_trn_no','=',$PyTrnNo];
        $where[] = ['type','=',OrderEntry::Ccb];

        //缓存开启并且命中
//        $mcKey = $this->mcName . '_' . $bizOrderNO;
//        if($this->mcOpen && cache($mcKey) !== false){
//            return cache($mcKey)->toArray();
//        }
        //查询失败直接返回false
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

    //allInPay异步支付失败
    function allInPayError($appUid, $biz_order_no){
        return $this->upPayStatusByNo($appUid, $biz_order_no,self::PAY_STATUS['WAIT_PAY'], self::PAY_STATUS['ALL_IN_PAY_ERROR']);
    }

    //0:待支付 10:allInPay异步支付完成 20:财务确认收款 30: 部分代付 40:部分代付确认 50: 分账完成 60: 财务确认订单完成
    function upPayStatusByNo($appUid, $biz_order_no, $beforeStatus, $upEntryStatus){

    }

    /**
     * ccb异步退款完成
     * @param $biz_order_no
     * @param $py_trn_no
     * @param $super_refund_no
     * @return array
     * User: cwh  DateTime:2021/9/22 17:35
     */
    function ccbComplete($biz_order_no,$py_trn_no, $super_refund_no){
        $beforeStatus = self::PAY_STATUS['ALL_IN_PAY_ING'];
        $upEntryStatus = self::PAY_STATUS['ALL_IN_PAY_COMPLETE'];
        $info = $this->infoByBizOrderNoAndPyTrnNo($biz_order_no,$py_trn_no);
        if(empty($info) || $info['pay_status'] != $beforeStatus){
            return errorReturn('查询订单失败!');
        }
        $data['id'] = $info['id'];
        $info['pay_status'] = $data['pay_status'] = $upEntryStatus;
        $info['allinpay_order_no'] = $data['allinpay_order_no'] = $super_refund_no;
        $upResult = $this->where('id','=',$info['id'])->update($data);
        if(!$upResult){
            return errorReturn('更新订单失败!');
        }
        $paramEntry['app_uid'] = $info['app_uid'];
        $paramEntry['order_entry_no'] = $info['ori_biz_order_no'];
        $paramEntry['amount'] = $info['amount'];
        $res = (new OrderProcessService())->updateCcbEntryOrderStatus($paramEntry,OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE'],1);
        return successReturn(['data' => $info]);
    }

    /**
     * ccb异步退款失败
     * @param $biz_order_no
     * @param $py_trn_no
     * @param $super_refund_no
     * @return array
     * User: cwh  DateTime:2021/9/22 18:04
     */
    function ccbError($biz_order_no,$py_trn_no,$super_refund_no){
        $beforeStatus = self::PAY_STATUS['ALL_IN_PAY_ING'];
        $upEntryStatus = self::PAY_STATUS['ALL_IN_PAY_ERROR'];
        $info = $this->infoByBizOrderNoAndPyTrnNo($biz_order_no,$py_trn_no);
        if(empty($info) || $info['pay_status'] != $beforeStatus){
            return errorReturn('查询订单失败!');
        }
        $data['id'] = $info['id'];
        $info['pay_status'] = $data['pay_status'] = $upEntryStatus;
        $info['allinpay_order_no'] = $data['allinpay_order_no'] = $super_refund_no;
        $upResult = $this->where('id','=',$info['id'])->update($data);
        if(!$upResult){
            return errorReturn('更新订单失败!');
        }
        $paramEntry['app_uid'] = $info['app_uid'];
        $paramEntry['order_entry_no'] = $info['ori_biz_order_no'];
        $paramEntry['amount'] = $info['amount'];
        $res = (new OrderProcessService())->updateCcbEntryOrderStatus($paramEntry,OrderRefund::PAY_STATUS['ALL_IN_PAY_ERROR'],1);
        return successReturn(['data' => $info]);
    }



}
