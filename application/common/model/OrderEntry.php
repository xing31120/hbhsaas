<?php
namespace app\common\model;
use app\common\model\basic\Common;
use app\common\model\reconciliation\FinanceReconciliation;
use app\common\model\reconciliation\FinanceSummaryDetail;
use app\common\service\FinanceSummaryDetailService;
use app\common\service\FinanceSummaryService;
use app\common\service\workSendMessage\WorkSendMessageService;
use app\push\service\BaseService;
use app\push\service\SendWxWorkService;

class OrderEntry extends Common {
    public $mcName = 'order_entry_';
    public $selectTime = 6;
    public $mcTimeOut = 6;

    const WAIT_PAY                  = 0;  // 0: 待支付
    const ALL_IN_PAY_COMPLETE       = 10; //10: allInPay异步支付完成
    const FIN_CONFIRM_DEPOSIT       = 20; //20: 财务确认收款
    const AGENT_PAY_PART            = 30; //30: 部分代付
    const AGENT_PAY_PART_CONFIRM    = 40; //40: 部分代付财务确认
    const AGENT_PAY_COMPLETE        = 50; //50: 分账完成
    const AGENT_PAY_COMPLETE_ORDER  = 60; //60: 财务确认订单完成
    const FAILURE                   = 70; //70: 失效

    //建行惠市宝支付状态
    const CCB_WAIT_PAY              = 1;  // 0: 待支付
    const CCB_COMPLETE              = 2; //成功
    const CCB_FAILURE               = 6; //失效

    const CHANGE_TYPE = [
        self::CCB_WAIT_PAY=>self::WAIT_PAY,
        self::CCB_COMPLETE=>self::ALL_IN_PAY_COMPLETE,
        self::CCB_FAILURE=>self::FAILURE,
    ];

    const NO_REFUND = 1;//未退款
    const PART_REFUND = 2;//部分退款
    const ALL_REFUND = 3;//全部退款

    const REFUND_STATUS_TXT = [
        self::NO_REFUND=>'未退款',
        self::PART_REFUND=>'部分退款',
        self::ALL_REFUND=>'全部退款',
    ];

    const FEE = 0.002;//手续费  千分之二


    const AllInPay = 1;//通联
    const Ccb = 2;//建行惠市宝

    const orderType = [
        'recharge' => 10,       //充值
        'agentCollect' => 20,   //托管代收
    ];

    //0:待支付 10:allInPay异步支付完成 20:财务确认收款 30: 部分代付 40:部分代付确认 50: 分账完成 60: 财务确认订单完成
    const orderEntryStatus = [
        0=> '待支付',
        10=> '支付完成',
        20=>'财务确认收款',
//        30=> '部分代付待确认',
//        40=>'部分代付确认完成',
//        50=> '分账完成待确认',
//        60=>'分账确认完成'
        30=> '部分代付',
        50=> '分账完成',
    ];

    const payMethod = [
        'QUICKPAY_VSP' => '快捷支付',
        'GATEWAY_VSP' => '网关支付',
        'WECHATPAY_MINIPROGRAM_ORG' => '微信小程序支付',
        'BALANCE' => '余额支付',
        'WECHAT_PUBLIC_ORG' => '微信支付',
    ];

    const HsbPayMethod = [
        '01'=>'对公支付',
        '02'=>'线下支付（无收银台）',
        '03'=>'移动端H5页面 (app)',
        '05'=>'微信小程序（无收银台）',
        '06'=>'对私网银（无收银台）',
        '07'=>'聚合二维码（无收银台）',
//        '08'=>'龙支付（无收银台）',
    ];

    //分账状态
    const DIM_WAIT = 0;//待分账
    const DIM_OK = 1;//待分账
    const DIM_NO = 2;//待分账
    const dimStatus = [
        self::DIM_WAIT=>'待分账',
        self::DIM_OK=>'已分账',
        self::DIM_NO=>'不分账',
    ];

    function infoByBizOrderNo($appUid, $bizOrderNO){
        if (empty($bizOrderNO)) {
            return false;
        }

        $this->submeter($appUid);
        $where[] = ['biz_order_no','=',$bizOrderNO];

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

    /**
     *  增加充值订单or托管代收订单
     * @param $appUid
     * @param $param
     * @return bool|mixed|null
     * User: 宋星 DateTime: 2020/11/12 11:48
     */
    function addOrder($appUid, $param){
        if(empty($param['bizOrderNo']))                             return errorReturn('订单编号错误');
        if(empty($param['bizUserId']) && empty($param['payerId']))  return errorReturn('用户编号错误');

        $info = $this->infoByBizOrderNo($appUid, $param['bizOrderNo']);
        if($info){
            return errorReturn('订单已经存在!');
        }
        $data['payer_id']           = $param['bizUserId'] ?? $param['payerId'];
        $data['biz_uid'] = str_replace($appUid,"",$data['payer_id']);
        $userInfo = model('Users')->infoByBizUid($appUid, $data['biz_uid']);

        $data['uid']                = $userInfo['id'];
        $data['app_uid']            = $appUid;
        $data['biz_order_no']       = $param['bizOrderNo'];
        $data['allinpay_order_no']  = $param['allinpay_order_no'] ?? '';
        $data['member_type']        = $userInfo['member_type'];
        $data['public_account_id']  = $param['public_account_id'] ?? '';
        $data['order_type']         = $param['order_type'] ?? 20;   //10: 充值订单  20:托管代收订单
        $data['account_set_no']     = $param['accountSetNo'] ?? '';
        $data['biz_goods_no']       = $param['bizGoodsNo'] ?? '';
        $data['order_entry_status'] = self::WAIT_PAY;
        $data['goods_type']         = $param['goodsType'] ?? '';
        $data['amount']             = $param['amount'] ?? 0;
        $data['remain_amount']      = $param['amount'] ?? 0;
        $data['processing_amount']  = 0;
        $data['fee']                = $param['fee'] ?? '';
        $data['trade_code']         = '3001';   //代收消费金
        $data['validate_type']      = $param['validateType'] ?? 0;  //0:不验证 1:短信验证 2:支付密码验证
        $data['front_url']          = $param['front_url'] ?? '';
        $data['back_url']           = $param['back_url'] ?? '';
        $data['order_expire_datetime'] = $param['orderExpireDatetime'] ?? '';
        $data['pay_method']         = isset($param['payMethod']) ? key($param['payMethod']) : '';
        $data["industry_code"]      = $param["industryCode"] ?? '2512';
        $data["industry_name"]      = $param["industryName"] ?? '家用电器';
        $data['source']             = $param['source'] ?? 1;
        $data['goods_name']         = $param['goodsName'] ?? '';
        $data['goods_desc']         = $param['goodsDesc'] ?? '';
        $data['summary']            = $param['summary'] ?? '';
        $data["show_user_name"]     = $param['showUserName']  ?? '';
        $data["show_order_no"]      = $param['showOrderNo']  ?? '';
        $data['type']               = $param['type'] ?? 1;
        $data['result_msg']         = $param['result_msg'] ?? json_encode([]);
        $data['ittparty_jrnl_no']   = $param['ittparty_jrnl_no'] ?? '';
        $data['py_trn_no']          = $param['py_trn_no'] ?? '';
        $data['pay_qr_code']        = $param['pay_qr_code'] ?? '';
        $data['open_id']            = $param['open_id'] ?? '';
        $data['sub_ordr_id']        = $param['sub_ordr_id'] ?? '';
        if(isset($param['extendInfo']) ){
            $data['extend_info']    = is_array($param['extendInfo']) ? json_encode($param['extendInfo']) : $param['extendInfo'];
        }


        $res = $this->saveData($appUid, $data);

        if(!$res){
            return errorReturn('新增订单失败');
        }

        return successReturn(['data' => $res]);
    }

    /**
     * allInPay异步支付完成
     * @param $appUid int 应用会员表id
     * @param $biz_order_no string 业务系统订单号
     * @param $allinpayPayNo string 收银宝相关支付渠道单号
     * @param $orderNo string 通商云订单号
     * @return array
     */
    function allInPayComplete($appUid, $biz_order_no, $allinpayPayNo,$orderNo = ''){
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


//        return $this->upOrderStatusByNo($appUid, $biz_order_no,self::WAIT_PAY, self::ALL_IN_PAY_COMPLETE);
    }

    /**
     * 建行CCB异步支付完成
     * @param $biz_order_no 业务系统订单号
     * @param $py_trn_no 惠市宝生成的订单号
     * @return array
     * User: cwh  DateTime:2021/9/17 17:19
     */
    function CcbComplete($biz_order_no,$py_trn_no,$status = self::WAIT_PAY,$pay_time = 0){
        $beforeStatus = self::WAIT_PAY;
        $upEntryStatus = self::CHANGE_TYPE[$status] ?? 0;
        $info = $this->infoByBizOrderNoAndPyTrnNo($biz_order_no,$py_trn_no);
        if(empty($info) || $info['order_entry_status'] != $beforeStatus){
            return errorReturn('查询订单失败!');
        }
        $data['id'] = $info['id'];
        $info['order_entry_status'] = $data['order_entry_status'] = $upEntryStatus;
        $data['pay_time'] = $pay_time ==0 ? time(): strtotime((new FinanceReconciliation())->formatData($pay_time,3));
        $upResult = $this->where('id','=',$info['id'])->where('type',self::Ccb)->update($data);
        if(!$upResult){
            return errorReturn('更新订单失败!');
        }
        return successReturn(['data' => $info]);
    }

    //财务确认收款
    function confirmDeposit($appUid, $biz_order_no){
        return $this->upOrderStatusByNo($appUid, $biz_order_no, self::FIN_CONFIRM_DEPOSIT);
    }

    //部分 代付/分账
    function agentPayPart($appUid, $biz_order_no){
        return $this->upOrderStatusByNo($appUid, $biz_order_no, self::AGENT_PAY_PART);
    }

    //部分确认 代付/分账
    function agentPayConfirm($appUid, $biz_order_no){
        return $this->upOrderStatusByNo($appUid, $biz_order_no, self::AGENT_PAY_PART_CONFIRM);
    }

    //代付/分账  完成
    function agentPayComplete($appUid, $biz_order_no){
        return $this->upOrderStatusByNo($appUid, $biz_order_no, self::AGENT_PAY_COMPLETE);
    }

    //财务确认订单完成
    function confirmOrder($appUid, $biz_order_no){
        return $this->upOrderStatusByNo($appUid, $biz_order_no, self::AGENT_PAY_COMPLETE_ORDER);
    }

    //0:待支付 10:allInPay异步支付完成 20:财务确认收款 30: 部分代付 40:部分代付确认 50: 分账完成 60: 财务确认订单完成
    function upOrderStatusByNo($appUid, $biz_order_no, $beforeStatus, $upEntryStatus){

    }

    /**
     * 获取已经对账未推送业务系统的订单号
     * User: cwh  DateTime:2021/10/19 16:27
     */
    public function getListByWhere($where,$limit=10){
        return $this->where($where)->limit($limit)->select()->toArray();
    }

    /**
     * 获取已经对账未推送业务系统的订单号
     * User: cwh  DateTime:2021/10/19 16:27
     */
    public function getListByWhereKey($where,$field,$index_key,$limit=10){
        return $this->where($where)->limit($limit)->column($field,$index_key);
    }

    /**
     * 推送分账明细异常和业务系统订单推送
     * User: cwh  DateTime:2021/10/19 17:25
     */
    public function sendFinanceSummaryDetail(){
        $model = model('OrderEntry');
        $where[] = ['is_reconciliation' ,'=', FinanceSummaryDetail::RECONCILIATION_OK];
        $where[] = ['is_push', '=', FinanceSummaryDetail::NO_PUSH];
        $field ="id,biz_order_no,ccb_reconciliation_amount,reconciliation_status,is_reconciliation,remain_amount,app_uid,biz_uid,fee";
        $orderEntryList = $model->getListByWhereKey($where,$field,"biz_order_no");

        $main_order_Nos = array_column($orderEntryList,"biz_order_no");
        $whereProcess[] = ['order_entry_no','in',$main_order_Nos];
        $fieldProcess = "id,order_entry_no,ccb_reconciliation_amount,reconciliation_status,is_reconciliation,seq_no,mkt_mrch_id,remain_amount,biz_order_process_no,back_url,biz_uid,fee";
        $orderProcessList = (new OrderProcess())->getListByWhere($whereProcess,$fieldProcess);
        $list = $this->OrderEntryMergeOrderProcessList($orderEntryList,$orderProcessList);
        foreach($list as $v){
            if($v['reconciliation_status'] == OrderProcess::RECONCILIATION_ERROR){
                $v['type'] = SendWxWorkService::ADD_PAY_RECONCILIATION;
                $v['workKey'] = env('SAASWORK.HSB_PUSH', '');
                (new SendWxWorkService)->sendWxWork($v);
            }
            $updateData['is_push'] = FinanceSummaryDetail::YES_PUSH;
            $res = $this->sendBusiness($v);
            if($res=='success'){
                $this->updateById($v['id'],$v['app_uid'],$updateData);
            }
        }
        $result['msg'] ='推送完成';
        return successReturn($result);
    }

    /**
     * 组合分账订单和分账明细订单拿数据
     * @param $orderEntryList
     * @param $orderProcessList
     * @return mixed
     * User: cwh  DateTime:2021/11/10 15:13
     */
    public function OrderEntryMergeOrderProcessList($orderEntryList,$orderProcessList){
        foreach($orderProcessList as $v){
            if(isset($orderEntryList[$v['order_entry_no']])){
                $orderEntryList[$v['order_entry_no']]['orderProcessList'][] = $v;
            }
        }
        return $orderEntryList;
    }

    /**
     * 推送业务系统通知
     * @return bool
     * User: cwh  DateTime:2021/10/19 18:58
     */
    public function sendBusiness($data){
        $userAppInfo = UsersApp::get($data['app_uid']);

        $backUrl = $data['orderProcessList']['0']['back_url'];
//        $backUrl = $info['back_url'];
//$backUrl = 'http://devapi-saas.zzsupei.com/FinApi/depositApplyCallback';
        //整合业务系统回调地址需要的参数
        $params = [
            'appId' => $userAppInfo['app_id'],
            'bizOrderNo' => $data['biz_order_no'],
            'bizUid' => $data['biz_uid'],
            'ccbReconciliationAmount'=>$data['ccb_reconciliation_amount'],
            'reconciliationStatus' => $data['reconciliation_status'],
            'fee'                  => $data['fee'],
            'orderProcessList' => $data['orderProcessList'],
        ];
        $result = (new BaseService())->bizCurl($backUrl, $params);
        return $result;
    }

    /**
     * 发送对账异常到企业微信
     * @param $order_process_info
     * User: cwh  DateTime:2021/10/19 18:33
     */
    public function sendReconciliationError($order_process_info){
        $time = date("Y-m-d H:i:s");
        $content = <<<EOF
### <font color="warning">慧市宝对账单，对账异常，请查看</font>
> 主订单：{$order_process_info['order_entry_no']}
> 分账明细订单：{$order_process_info['biz_order_process_no']}
> 商家编号：{$order_process_info['mkt_mrch_id']}
> 分账系统分账金额：{$order_process_info['amount']} 
> 建行分账金额：{$order_process_info['ccb_reconciliation_amount']}
> 时间：{$time}
EOF;
        (new WorkSendMessageService(env('SAASWORK.SAAS_ERROR_PUSH', '')))->sendMarkDown($content);
    }
}
