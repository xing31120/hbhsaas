<?php
namespace app\common\model;
use app\common\amqp\BizProducer;
use app\common\model\basic\Common;
use app\common\model\reconciliation\FinanceSummaryDetail;
use app\common\service\workSendMessage\WorkSendMessageService;
use app\push\service\BaseService;
use app\push\service\SendWxWorkService;
use think\Db;

class OrderProcess extends Common {
    public $mcName = 'order_process_';
    public $selectTime = 0;
    public $mcTimeOut = 0;
    protected $mcOpen = false;   //缓存开关

    const WAIT_PAY                  = 0;  // 0: 待支付
    const FIN_CONFIRM_PROCESS       = 20; //20: 财务确认分账
    const ALL_IN_PAY_COMPLETE       = 30; //30: allInPay异步支付完成

    const RECONCILIATION_ERROR = 2;//对账异常
    const RECONCILIATION_NORMAL = 1;//对账异常
    const NO_RECONCILIATION_NORMAL = 0;//未对账

    //0:待支付 10:allInPay异步支付完成 20:财务确认分账
    const orderProcessStatus = [
        0=> '待确认',
        10=> '错误数据',
        20=>'财务已确认',
        30=>'分账完成',
    ];

    function infoByBizOrderProcessNo($appUid, $bizOrderProcessNo){
        if (empty($bizOrderProcessNo)) {
            return false;
        }

        $this->submeter($appUid);
        $where[] = ['biz_order_process_no','=',$bizOrderProcessNo];

        //缓存开启并且命中
//        $mcKey = $this->mcName . '_' . $appUid. '_' . $bizOrderProcessNo;
//        if($this->mcOpen &&  ($data=cache($mcKey)) !== false){
//            return $data->toArray();
//        }
        //查询失败直接返回false
        $rs = $this->where($where)->select();
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

    function infoByBizOrderAllInPayNo($appUid, $allInPayPrderNo){
        if (empty($allInPayPrderNo)) {
            return false;
        }

        $this->submeter($appUid);
        $where[] = ['allinpay_order_no','=',$allInPayPrderNo];

        //缓存开启并且命中
//        $mcKey = $this->mcName . '_' . $allInPayPrderNo;
//        if($this->mcOpen && $data=json_decode(cache($mcKey), true)){
//            return $data;
//        }
        //查询失败直接返回false
        $rs = $this->where($where)->select();
        if(empty($rs)){
            return false;
        }
        //设置缓存
//        if($this->mcOpen){
//            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
//            cache($mcKey, json_encode($rs->toArray()), $time);
//        }
        return $rs->toArray();

    }


    function addProcessOrder($appUid, $param){
        if(empty($param['bizOrderNo'])) return errorReturn('分账订单编号错误');
        if(empty($param['bizUserId']) ) return errorReturn('用户编号错误');

        $info = $this->infoByBizOrderProcessNo($appUid, $param['bizOrderNo']);
        if($info){
            return errorReturn('分账订单已经存在!');
        }

        $data['biz_uid'] = str_replace($appUid,"",$param['bizUserId']);
        $userInfo = model('Users')->infoByBizUid($appUid, $data['biz_uid']);
        if(empty($userInfo)){
            return errorReturn('用户信息错误!');
        }

        $data['receiver_id']        = $param['receiver_id'];
        $data['uid']                = $userInfo['id'];
        $data['app_uid']            = $appUid;
        $data['biz_order_process_no']= $param['bizOrderNo'];
        $data['allinpay_order_no']  = $param['allinpay_order_no'] ?? '';
        $data['allinpay_pay_no']  = $param['allinpay_pay_no'] ?? '';
        $data['order_entry_no']     = $param['order_entry_no'] ?? '';
        $data['member_type']        = $userInfo['member_type'];
        $data['account_set_no']     = $param['accountSetNo'] ?? '';
        $data['order_process_status']= self::WAIT_PAY;
        $data['amount']             = $param['amount'] ?? 0;
        $data['fee']                = $param['fee'] ?? '';
        $data['front_url']          = $param['front_url'] ?? '';
        $data['back_url']           = $param['back_url'] ?? '';
        $data['source']             = $param['source'] ?? 1;
        $data['remark']             = $param['remark'] ?? '';
        $data['extend_info']        = $param['extendInfo'] ?? '';

        $res = $this->saveData($appUid, $data);

        if(!$res){
            return errorReturn('新增分账订单失败');
        }

        return successReturn(['data' => $res]);

    }

    /**
     * 创建建行订单之后创建订单明细
     * User: cwh  DateTime:2021/9/16 20:51
     */
    function addProcessOrderByBbc($appUid,$param)
    {
        $insertAllData = [];
        $time = time();
        foreach ($param['orderlist'] as $v) {
            $data['payer_id']           = $param['bizUserId'] ?? $param['payerId'];
            $insertData['biz_uid'] = str_replace($appUid,"",$data['payer_id']);
            $userInfo = model('Users')->infoByBizUid($appUid, $insertData['biz_uid']);
            if(empty($userInfo)){
                return errorReturn('用户信息错误!');
            }
            $insertData['type'] = $param['type'] ?? 1;
            $insertData['allinpay_order_no'] = $param['allinpay_order_no'] ?? '';
            $insertData['order_entry_no'] = $param['bizOrderNo'] ?? '';
            $insertData['uid'] = $userInfo['id'] ?? '';
            $insertData['app_uid'] = $appUid;
            $insertData['show_order_no'] = $param['showOrderNo'];//业务系统订单号
            $insertData['show_user_name'] = $param['showUserName'];//业务系统名称或者手机号
            $insertData['back_url'] = $param['processBackUrl'];
            $insertData['extend_info'] = $param['extendInfo'];
            foreach ($v['parlist'] as $v1) {
                $insertData['biz_order_process_no'] = $v['cmdtyOrdrNo'] . $v1['seqNo'];
                $insertData['clrg_rule_id'] = $v['clrgRuleId'] ?? '';//分账规则编号
                $insertData['cmdty_ordr_no'] = $v['cmdtyOrdrNo'];//客户方子订单流水号,不允许重复  VarChar 40
                $insertData['mkt_mrch_id'] = $v1['mktMrchId'];//商家编号,20位商家编号该字段由银行在正式上线前提供，测试阶段有测试数
                $insertData['amount'] = $v1['amt'];//应付金额  分为单位
                $insertData['txnamt'] = $v1['amt'];//实付金额 分为单位
                $insertData['remain_amount'] = $v1['amt'];//可以分账的金额
                $insertData['remark'] = $v1['cmdtyDsc'] ?? '';
                $insertData['fee']    = 0;
                $insertData['rate'] = $v1['rate'];
                $insertData['seq_no'] = $v1['seqNo'];
                $insertData['create_time'] = $time;
                $insertData['update_time'] = $time;
                $insertAllData[] = $insertData;
            }
        }
        if(empty($insertAllData)){
            return errorReturn("明细为空");
        }
        $res = $this->insertAll($appUid,$insertAllData);
        if(!$res){
            return errorReturn("插入明细失败");
        }
        return successReturn(["msg"=>'插入明细成功']);
    }


    //allInPay异步支付完成,根据支付订单号
//    function allInPayCompleteProcess($appUid, $biz_order_no){
//        return $this->upOrderStatusByNo($appUid, $biz_order_no,self::WAIT_PAY, self::ALL_IN_PAY_COMPLETE);
//    }
//    function upOrderStatusByNo($appUid, $biz_order_no, $beforeStatus, $upEntryStatus){
//        $info = $this->infoByBizOrderProcessNo($appUid, $biz_order_no);
//        if(empty($info) || $info['order_process_status'] != $beforeStatus){
//            return errorReturn('查询订单失败!');
//        }
//        $data['id'] = $info['id'];
//        $info['order_process_status'] = $data['order_process_status'] = $upEntryStatus;
//        $res =  $this->saveData($appUid, $data);
//        if(!$res){
//            return errorReturn('更新订单失败!');
//        }
//
//        return successReturn(['data' => $info]);
//    }


    //allInPay异步支付完成,根据 All In Pay订单号
    function allInPayCompleteByNo($appUid, $order_no){
        return $this->upOrderStatusByAllInPayNo($appUid, $order_no,self::FIN_CONFIRM_PROCESS, self::ALL_IN_PAY_COMPLETE);
    }

    function upOrderStatusByAllInPayNo($appUid, $order_no, $beforeStatus, $upEntryStatus){
        $list = $this->infoByBizOrderProcessNo($appUid, $order_no);
        if(empty($list) || !isset($list[0])){
            return errorReturn('查询订单失败!');
        }

        $data = [];
        foreach ($list as $info){
            $temp = [];
            $temp['id']         = $info['id'];
            $temp['app_uid']    = $info['app_uid'];
            $temp['biz_uid']    = $info['biz_uid'];
            $temp['amount']     = $info['amount'];
            $temp['order_process_status'] = $upEntryStatus;
            $data[] = $temp;

        }
        $res = $this->updateAll($appUid, $data);
        if(!$res){
            return errorReturn('更新订单失败!');
        }

        return successReturn(['data' => $data]);
    }


    /**
     * 根据订单号获取分账订单列表 分组
     * @param $main_ordr_no
     * User: cwh  DateTime:2021/10/18 15:27
     */
    public function getOrderListByMainOrdrNoList($main_ordr_no){
        $list = $this->whereIn('order_entry_no',$main_ordr_no)->select()->toArray();
        //组装数据
        $arr = [];
        foreach($list as $v){
            $key = $v['order_entry_no']."_".$v['mkt_mrch_id'];
            $arr[$key] = $v;
        }
        return $arr;
    }

    /**
     * 根据订单号获取分账订单列表 根据分账规则排序
     * @param $main_ordr_no
     * User: cwh  DateTime:2021/10/18 15:27
     */
    public function getOrderProcessListByMainOrdrNo($main_ordr_no,$order ="seq_no desc"){
        $where[] = ['order_entry_no','in',$main_ordr_no];
        $list = $this->whereIn('order_entry_no',$main_ordr_no)->order($order)->column("*","seq_no");
        return $list;
    }

    /**
     * 获取已经对账未推送业务系统的订单号
     * User: cwh  DateTime:2021/10/19 16:27
     */
    public function getListByWhere($where,$field="*"){
        return $this->where($where)->field($field)->select()->toArray();
    }

}
