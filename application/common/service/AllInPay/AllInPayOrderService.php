<?php


namespace app\common\service\AllInPay;


use AllInPay\Log\Log;
use app\common\tools\SysEnums;
use think\facade\Config;

class AllInPayOrderService{

    public $receiveBizUserId = '';
    public $accountSetNo = '';

    public function __construct(){
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->accountSetNo = $config['account_set_no'];
        $this->receiveBizUserId = $config['escrow_user_id'];
    }

    function getAccountSetNo (){
        return $this->accountSetNo;
    }

    /**
     *  查询余额
     * @param $bizUserId
     * @param string $accountSetNo 测试环境
     * User: 宋星 DateTime: 2020/11/10 14:09
     * @return bool|mixed
     */
    public function queryBalance($bizUserId, $accountSetNo = ''){
        $client = new AllInPayClient();
        $param["bizUserId"] = $bizUserId;
        $param["accountSetNo"] = $accountSetNo ?: $this->accountSetNo;
        $method = "allinpay.yunst.orderService.queryBalance";
        $result = $client->request($method,$param);

        return $result;
    }


    /**
     * 充值申请
     * @param $param
     * @param $payMethod
     * @return array
     * User: 宋星 DateTime: 2020/11/11 10:52
     */
    public function depositApply($param, $payMethod){
        $yunClient = new AllInPayClient();

        if(empty($payMethod) || empty($param["bizOrderNo"]) || empty($param["bizUserId"]) ||
           empty($param["backUrl"])  || $param["amount"] <= 0 ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }
        //银行卡加密
        $payParamKey = key($payMethod);
        if(isset($payMethod[$payParamKey]['bankCardNo'])){
            $payMethod[$payParamKey]['bankCardNo'] = $yunClient->encryptAES($payMethod[$payParamKey]['bankCardNo']);
        }

        $param["payMethod"]     = $payMethod;
        $param["fee"]           = $param["fee"] ?? 0;
        $param["validateType"]  = $param["validateType"] ?? 0;
        $param["frontUrl"]      = $param["frontUrl"] ?? '';
        $param["industryCode"]  = $param["industryCode"] ?? '2512';
        $param["industryName"]  = $param["industryName"] ?? '家用电器';
        $param["source"]        = $param["source"] ?? 1;

        $param["accountSetNo"]  = $param["accountSetNo"] ?? $this->accountSetNo;
//var_dump($param["validateType"]);exit;

        $method = "allinpay.yunst.orderService.depositApply";
        $result = $yunClient->request($method,$param);
        return $result;
    }

    /**
     * 托管代收申请
     * @param $param
     * @param $payMethod
     * @return array
     * User: 宋星 DateTime: 2020/11/16 14:06
     */
    function agentCollectApply($param, $payMethod){
        $yunClient = new AllInPayClient();

        if(empty($payMethod) || empty($param["bizOrderNo"])  || empty($param["payerId"]) ||
           empty($param["backUrl"])  || $param["amount"] <= 0 ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }
        //银行卡加密
        $payParamKey = key($payMethod);
        if(isset($payMethod[$payParamKey]['bankCardNo'])){
            $payMethod[$payParamKey]['bankCardNo'] = $yunClient->encryptAES($payMethod[$payParamKey]['bankCardNo']);
        }
        //收款列表 JSONArray
        $receiverParam[0]["bizUserId"]  = $this->receiveBizUserId;
        $receiverParam[0]["amount"]     = $param["amount"];
        $param["recieverList"]          = $receiverParam;

        $param["payMethod"]     = $payMethod;
        $param["fee"]           = $param["fee"] ?? 0;
        $param["validateType"]  = $param["validateType"] ?? 0;
        $param["frontUrl"]      = $param["frontUrl"] ?? '';
        $param["industryCode"]  = $param["industryCode"] ?? '2512';
        $param["industryName"]  = $param["industryName"] ?? '家用电器';
        $param["tradeCode"]     = $param["tradeCode"]  ?? '3001';   //代收消费金
        $param["source"]        = $param["source"] ?? 1;    //1:手机 2:pc
//echo json_encode($param);exit;
        $method = "allinpay.yunst.orderService.agentCollectApply";
        $result = $yunClient->request($method,$param);
//return $param;
        return $result;
    }

    /**
     * [getOrderDetail 查询订单状态]
     * 未支付	            1	整型
     * 交易失败	            3	整型	交易过程中出现错误
     * 交易成功	            4	整型
     * 交易成功-发生退款	5	整型	交易成功，但是发生了退款。
     * 关闭	                6	整型	未支付的订单，每天日终（00:30）批量关闭已创建未支付，且创建时间大于24小时的订单。
     * 进行中	            99	整型
     */
    function getOrderDetail($param){
        $yunClient = new AllInPayClient();

        if( empty($param["bizOrderNo"]) ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }

        $method = "allinpay.yunst.orderService.getOrderDetail";
        $result = $yunClient->request($method,$param);
        return $result;
    }

    /**
     * [resendPaySMS 重新发送支付订单验证码]
     */
    function resendPaySMS($param){
        $yunClient = new AllInPayClient();

        if( empty($param["bizOrderNo"]) ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }

        $method = "allinpay.yunst.orderService.resendPaySMS";
        $result = $yunClient->request($method,$param);
        return $result;
    }

    /**
     * [payBySMS 确认支付（前台+短信验证码确认）]
     */
    function payBySMS($param ){
        if( empty($param["bizUserId"]) || empty($param["bizOrderNo"]) || empty($param["consumerIp"]) ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }

        $yunClient = new AllInPayClient();
        $method = "allinpay.yunst.orderService.payBySMS";
        $result = $yunClient->concatUrlParams($method,$param);
        $url = "http://test.allinpay.com/op/gateway?".$result;

        return $url;
        //header("Location:$url");
    }

    /**
     * [payByBackSMS 确认支付（后台+短信验证码确认）]
     */
    function payByBackSMS($param){
        $yunClient = new AllInPayClient();
        if( empty($param["bizUserId"]) || empty($param["bizOrderNo"]) || empty($param["consumerIp"]) || empty($param["verificationCode"]) ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }

        $method = "allinpay.yunst.orderService.payByBackSMS";
        $result = $yunClient->request($method,$param);
        return $result;
    }

    /**
     * 单笔托管代付
     * @param $param
     * @param $collectPayList
     * @param $splitRuleList
     * @return array
     * User: SongX DateTime: 2020/11/19 17:34
     */
    function signalAgentPay($param, $collectPayList, $splitRuleList){
        $yunClient = new AllInPayClient();

        if(empty($param["bizOrderNo"]) || empty($param["bizUserId"]) || empty($param["backUrl"])  || $param["amount"] <= 0 ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }

        if(empty($collectPayList) || !isset($collectPayList[0]['amount']) ||  !isset($collectPayList[0]["bizOrderNo"]) || $collectPayList[0]['amount'] <= 0 ){
            return errorReturn('代收订单参数错误',SysEnums::ApiParamMissing);
        }

        if(!empty($splitRuleList) && (!isset($splitRuleList[0]['bizUserId']) ||
           !isset($splitRuleList[0]["amount"]) || $splitRuleList[0]['amount'] < 0 ||
           !isset($splitRuleList[0]["fee"]) || $splitRuleList[0]['fee'] < 0
        )){
            return errorReturn('分账订单参数错误',SysEnums::ApiParamMissing);
        }

        //收款列表 JSONArray
        $param["collectPayList"]          = $collectPayList;
        $collectAmountArray = array_column($collectPayList, 'amount');
        //收款人列表总金额
        $collectSumAmount = array_sum($collectAmountArray);

        $param["bizUserId"]     =  $this->receiveBizUserId; //收款账户
        //$param["bizOrderNo"]      托管代收订单号
        $param["amount"]        = $param["amount"] ?? $collectSumAmount;
        $param["fee"]           = $param["fee"] ?? 0;
        $param["tradeCode"]     = $param["tradeCode"]  ?? '4001';   //代付购买金
        $param["accountSetNo"]  = $param["accountSetNo"] ?? $this->accountSetNo;
        //如果有分账规则 列表, 每笔的金额之和要小于收款人的总金额
        if(!empty($splitRuleList)){
            $param["splitRuleList"] = $splitRuleList;
        }

        $sumAmount = 0; //分账列表总金额
        foreach ($splitRuleList as $item){
            if(!isset($item['bizUserId']) || empty($item['bizUserId']) || !isset($item['amount']) || !isset($item['fee'])){
                return errorReturn('分账参数错误',SysEnums::ApiParamMissing);
            }
            $sumAmount += $item['amount'];
        }
        if($sumAmount > $param["amount"]){  //分账列表每笔的金额之和要小于
            return errorReturn('分账总金额错误',SysEnums::SumAmountError);
        }

//echo json_encode($param);exit;

        $method = "allinpay.yunst.orderService.signalAgentPay";
        $result = $yunClient->request($method,$param);

        return $result;
    }


    function refund($param){
        $yunClient = new AllInPayClient();

        if(empty($param["bizOrderNo"]) || empty($param["oriBizOrderNo"]) || empty($param["bizUserId"]) || empty($param["backUrl"])  || $param["amount"] <= 0 ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }

        $param["refundType"] = $param["refundType"] == 1 ? 'D1':'D0';
        $method = "allinpay.yunst.orderService.refund";
        $result = $yunClient->request($method,$param);
        return $result;
    }

    function withdraw($param){
        $yunClient = new AllInPayClient();

        if(empty($param["bizOrderNo"]) || empty($param["accountSetNo"]) || empty($param["bizUserId"]) ||
            empty($param["backUrl"])  || $param["amount"] <= 0 || empty($param["bankCardNo"])
        ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }

        $param["industryCode"]  = $param["industryCode"] ?? '2512';
        $param["industryName"]  = $param["industryName"] ?? '家用电器';
        $param["bankCardNo"] = $yunClient->encryptAES($param["bankCardNo"]);
        $param["withdrawType"] = $param["withdrawType"] == 1 ? 'D1':'D0';
        $method = "allinpay.yunst.orderService.withdrawApply";
//pj($param);
        $result = $yunClient->request($method,$param);
        return $result;
    }

    //平台账户集余额查询
     function queryMerchantBalance(){
        $client = new AllInPayClient();
        $param["accountSetNo"] = 100001;
        $method = "allinpay.yunst.merchantService.queryMerchantBalance";
        $result = $client->request($method,$param);

        return $result;
    }

    //头寸查询
    function queryReserveFundBalance($fundAcctSys){
        $client = new AllInPayClient();
        $param["sysid"] = '1333951950931640321';
        $param["fundAcctSys"] = $fundAcctSys;
        $method = "allinpay.yunst.merchantService.queryReserveFundBalance";
        $result = $client->request($method,$param);

        return $result;
    }

    function getOrderSplitRuleListDetail($bizOrderNo){
        $client = new AllInPayClient();
        $param["bizOrderNo"] = $bizOrderNo;
        $method = "allinpay.yunst.orderService.getOrderSplitRuleListDetail";
        $result = $client->request($method,$param);

        return $result;
    }
}