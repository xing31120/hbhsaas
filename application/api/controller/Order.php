<?php


namespace app\api\controller;

use app\common\model\OrderEntry;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\AllInPay\AllInPayOrderService;
use app\common\service\OrderEntryService;
use app\common\service\OrderProcessService;
use app\common\service\OrderRefundService;
use app\common\service\OrderWithdrawService;
use app\common\service\UserFundsService;
use app\common\tools\SysEnums;
use think\facade\Config;
use app\api\controller\Base;
use think\Db;
use think\facade\Env;
use think\facade\Log;
use think\facade\Request;
use think\facade\Hook;

class Order extends Base{

    public $escrowUserId = '';
    public $accountSetNo = '';

    public function __construct(){
        parent::__construct();
//        $allInPayClient = new AllInPayClient();
//        $config = $allInPayClient->getConfig();
//        $this->accountSetNo = $config['account_set_no'];
//        $this->escrowUserId = $config['escrow_user_id'];

//        $params = input();
//        if( empty($params['bizUid']) ){
//            return apiOutError('参数错误',SysEnums::ApiParamMissing);
//        }
    }


    //支付回调
    function notifyPay(){
        $input = input();
        Hook::listen('app_init', $input);
$str = "notifyPay--------";
Log::notice($str);
pj($input);
        $randString = getRandomString(4);
        $data['merchantOrderNo']  = $input['acquireOrder ']['merchantOrderNo '] ?: "PAY".date("YmdHis").$randString;  //分账订单号

//        $orderProcessService = new OrderProcessService();
//
//        $tradeCode      = 4001; //代付消费金
//        $accountSetNo   = $this->accountSetNo;
//        $appUid         = $this->appUid;    //默认1000
//
//        $escrowUserId   = isset($data['escrowUserId']) ? $appUid.$data['escrowUserId'] : $this->escrowUserId; //收款的中间账户
//        $bizOrderProcessNo  = $data['bizOrderNo'] ?: "PRS".date("YmdHis");  //分账订单号
//        $amount             = $data['amount'];      //分账总金额
////        $collectPayList = json_decode(html_entity_decode($data['collectPayList']), true);
////        $splitRuleList  = json_decode(html_entity_decode($data['splitRuleList']), true);
//        $collectPayList = is_array($data['collectPayList']) ? $data['collectPayList'] : json_decode(html_entity_decode($data['collectPayList']), true);
//        $splitRuleList = is_array($data['splitRuleList']) ? $data['splitRuleList'] : json_decode(html_entity_decode($data['splitRuleList']), true);
//        foreach ($splitRuleList as &$row){
//            if($row['bizUserId'] == -1){
//                $row['bizUserId'] = '#yunBizUserId_B2C#';
//                $row['accountSetNo'] = '100001';
//            }
//            elseif($row['bizUserId'] == -10){
//                $row['bizUserId'] = -10;
//            }
//            else{
//                $row['bizUserId'] = $appUid.$row['bizUserId'];
//            }
//            $row['fee'] = 0;
//        }
////var_dump($splitRuleList);exit;
//
//
//        $param["collectPayList"] = $collectPayList;
//        $param["splitRuleList"] = $splitRuleList;
//        $param['bizOrderNo'] = $bizOrderProcessNo;
//        $param["tradeCode"] = $tradeCode;
//        $param["amount"] = $amount;
//        $param["fee"] = $data['fee']?? 0;
//        $param["backUrl"] = $this->domain .'AllinPay/notifyAgentPay';
//        $param["bizBackUrl"]    = $data['bizBackUrl']  ?? '';
//        $param['extendInfo'] = $appUid;
//        $param['extendParams'] = $data['extendParams'];
//        //代收的收款人的账户和账户集编号
//        $param["bizUserId"] = $escrowUserId;
//        $param["accountSetNo"] = $this->accountSetNo;
//
//        return $orderProcessService->signalAgentPay($appUid, $param, $collectPayList, $splitRuleList);

    }

    //退款
    function refund(){
        $data = input();
        $orderRefundService = new OrderRefundService();

        $appUid         = $this->appUid;    //默认1000
        $bizUid         = $data['bizUid']; //付款人biz_uid
        $bizUserId      = $appUid . $bizUid;

        $param['bizOrderNo']    = $data['bizOrderNo'] ?? "RE".date("YmdHis");   //退款订单号
        $param['oriBizOrderNo'] = $data['oriBizOrderNo'] ?? "";   //原代收或者充值订单号
        $param['bizUserId']     = $bizUserId;
        $param['refundType']    = $data['refundType'] ?? '1';
        $param["amount"]        = $data['amount']  ?? 0;
        $param["backUrl"]       = $this->domain .'AllinPay/notifyRefund';
        $param["bizBackUrl"]    = $data['bizBackUrl']  ?? '';
        $param['extendInfo']    = $appUid;

        $orderEntryInfo = model('OrderEntry')->infoByBizOrderNo($appUid, $param['oriBizOrderNo']);
        if(empty($orderEntryInfo)){
            return errorReturn('该订单不存在!');
        }
        if( $orderEntryInfo['order_entry_status'] == OrderEntry::ALL_IN_PAY_COMPLETE && $orderEntryInfo['remain_amount'] < $param["amount"] ){
            return errorReturn('订单金额错误!');
        }

        if($orderEntryInfo['amount'] != $param["amount"]){
            $temp = [];
            $AllInPayOrderService = new AllInPayOrderService();
            $temp['bizUserId'] = $AllInPayOrderService->receiveBizUserId;
            $temp['amount'] = $param["amount"];

            $param['refundList'][] = $temp;
        }

        return $orderRefundService->refund($appUid, $param);
    }

    //查询订单状态
    function getOrderDetail(){
        /*
未支付	            1	整型
交易失败	        3	整型	交易过程中出现错误
交易成功	        4	整型
交易成功-发生退款	5	整型	交易成功，但是发生了退款。
关闭	            6	整型	未支付的订单，每天日终（00:30）批量关闭已创建未支付，且创建时间大于24小时的订单。
进行中	            99	整型
         */
        $data = input();
        if(empty($data['bizOrderNo'])){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }

        $allInPayOrderService = new AllInPayOrderService();
        $result =  $allInPayOrderService->getOrderDetail($data);

        if($result['result']){
            unset($result['data']['acct']);
            unset($result['data']['extendInfo']);
            unset($result['data']['orderNo']);
            $result['data']['buyerBizUserId'] = substr($result['data']['buyerBizUserId'], 4);
        }

        return $result;
    }

    //查平台头寸
    function queryReserveFundBalance(){
        $data = input();
        $UserFundsService = new UserFundsService();
        $fundAcctSys = $data['fundAcctSys'] ?? 1;
        $result = $UserFundsService->queryReserveFundBalance($fundAcctSys);
        $returnData = $result['data'];
        return $result;
    }

    function getOrderSplitRuleListDetail(){
        $data = input();
        $UserFundsService = new UserFundsService();
        $bizOrderNo = $data['bizOrderNo'] ?? '';
        $result = $UserFundsService->getOrderSplitRuleListDetail($bizOrderNo);
        $returnData = $result['data'];
        return $result;
    }

    function weChatPayTest(){
        $data = input();
        $returnData = [];
        $orderEntryService =new OrderEntryService();
        $method         = $data['method'];

        $consumerIp     = '192.168.1.144';
        $tradeCode      = 3001; //代收消费金
        $accountSetNo   = $this->accountSetNo;
        $bizOrderNo     = $data['bizOrderNo'] ?? "SX".date("YmdHis");
        $appUid         = $data['appUid'];    //默认1000
        $payerBizUid    = $data['payerBizUid'] ?? 3; //付款人biz_uid
        $amount         = $data['amount'];
        $payerId        = $appUid . $payerBizUid;
        $bankCardNo     = $data['bankCardNo'] ?? '6228480078086570476';    //农行-6228480078086570476 建行-6217001930038760865
        $payMethodKey   = $data['payMethodKey'] ?? 'WECHATPAY_MINIPROGRAM_ORG';   //支付方式  QUICKPAY_VSP 快捷支付
        $data['bizOrderNo'] = $bizOrderNo;
        $data['verificationCode'] = '';

        if($method == 'agentCollectApply'){  //托管代收申请
            $param['bizOrderNo'] = $bizOrderNo;
            $param['payerId'] = $payerId;;
            $param["tradeCode"] = $tradeCode;
            $param["amount"] = $amount;
            $param["bizFrontUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/frontDepositApply';
            $param["bizBackUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/notifyDepositApply';
            $param["backUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/notifyDepositApply';
            $param['extendInfo'] = $appUid;
            //收银宝子商户号, 需要走线下流程申请  测试环境目前有3个 //56039305714Z6HU   56039305714Z6HV     56039305714Z6J3


            //小程序支付
            if(isset($data['subAppid']) && $data['subAppid']){
                $payParam["subAppid"] = $data['subAppid'];
            }
            $payParam["vspCusid"] = '56039305714Z6HU';
            $payParam["subAppid"] = 'wxa503087871bcbc55';
            $payParam["limitPay"] = "no_credit";
            $payParam["acct"] = $data['acct'] ?: 'o4e7H5QK3LuqnEdrBT6vLGaOUwfk';
            $payParam["amount"] = $amount;
            $payMethod[$payMethodKey] = $payParam;

            $result = $orderEntryService->agentCollectApply($appUid, $param, $payMethod);
            $result['param'] = $param;
            $result['payMethod'] = $payMethod;
            $result['payInfo'] = $result['payData']['payInfo'] ?? '';
        }

        return json($result);
    }

    private function payMethod($payMethodKey, $param){
        $payMethod = [];
        $payParam["amount"] = $param['amount'];
        $openId = $param['acct'] ?? '';
        $bankCardNo = $param['bankCardNo'] ?? '';
        $accountSetNo =  (new AllInPayOrderService())->getAccountSetNo();

        $is_test = Config::get('amqp.is_test');
        if($is_test){
            $miniprogramSubAppid = Config::pull('allinpaytest')['mini_allinpay'];
            $publicSubAppid = Config::pull('allinpaytest')['public_allinpay'];
        }else{
            $miniprogramSubAppid = Config::pull('allinpay')['mini_allinpay'];
            $publicSubAppid = Config::pull('allinpay')['public_allinpay'];
        }

        if(isset($param['wechatAppId']) && !empty($param['wechatAppId'])){
            $publicSubAppid = $param['wechatAppId'];
        }

        if($payMethodKey == 'WECHATPAY_MINIPROGRAM_ORG'){   //微信小程序支付
            //$payParam["subAppid"] = 'wxbf1962cac1ef7c9a';
            $payParam["subAppid"] = $miniprogramSubAppid;
            $payParam['limitPay'] = $param['limitPay'] ?? '';
            $payParam['vspCusid'] = $param['vspCusid'] ?? '56039305714Z6HU';
            $payParam["acct"] = $openId;
            $payParam['extendParams'] = $param['extendParams'] ?? '';
            $payMethod[$payMethodKey] = $payParam;
        }
        else if($payMethodKey == 'WECHAT_PUBLIC_ORG'){  //微信支付
            //$payParam["subAppid"] = 'wxa9693ec0a2f63ee6';
            $payParam["subAppid"] = $publicSubAppid;
            $payParam['limitPay'] = $param['limitPay'] ?? '';
            $payParam['vspCusid'] = $param['vspCusid'] ?? '56039305714Z6HU';
            $payParam["acct"] = $openId;
            $payParam['extendParams'] = $param['extendParams'] ?? '';
            $payMethod[$payMethodKey] = $payParam;
        }

        else if($payMethodKey == 'QUICKPAY_VSP'){
            $payParam["bankCardNo"] = $bankCardNo;
            $payMethod[$payMethodKey] = $payParam;
        }
        else if($payMethodKey == 'GATEWAY_VSP_ORG'){
            $payParam["paytype"] = "B2C,B2B";
            $payMethod[$payMethodKey] = $payParam;
        }
        else if($payMethodKey == 'BALANCE'){
            $payParam["accountSetNo"] = $accountSetNo;
            $temp[] = $payParam;
            $payMethod[$payMethodKey] = $temp;
        }
        return $payMethod;
    }


}
