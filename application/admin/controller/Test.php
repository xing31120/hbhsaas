<?php

namespace app\admin\controller;

use AllInPay\MemberService;
use AllInPay\SDK\yunClient;
use app\common\amqp\BizConsumer;
use app\common\amqp\BizProducer;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\AllInPay\AllInPayOrderService;
use app\common\service\OrderEntryService;
use app\common\service\OrderProcessService;
use app\common\service\UserFundsService;
use app\common\service\OrderRefundService;
use app\common\service\UserService;
use app\common\tools\Redis;
use app\push\service\ToolsService;
use think\Controller;
use think\Db;
use think\facade\Config;
use think\facade\Env;
use think\facade\Log;

class Test extends Controller {

    public $accountSetNo = '';

    public function __construct(){
        parent::__construct();
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->accountSetNo = $config['account_set_no'];
    }

    function test(){
        $this -> view -> assign('title', '用户接口测试');
        $this->assign('returnData', []);
        return $this->fetch();
    }

    function doTest(){
        $data = input();
        $returnData = $result= [];
        if(!isset($data['method'])){
            $this->redirect('test');
        }

        $UserService = new UserService();

        $appUid     = $data['appUid'];    //默认1000
        $bizUid     = $data['bizUid'];

        $method = $data['method'];

        if($method == 'createMember'){  //创建用户
            $result = $UserService->createMember($appUid, ['bizUid' => $bizUid]);
            $returnData = $result['data'];
        }
        else if($method == 'sendVerificationCode'){ //绑定手机发送验证码
            $result = $UserService->sendVerificationCode($appUid, $bizUid, $data['phone'] );
            $returnData = $result['data'];
        }
        else if($method == 'bindPhone'){ //绑定手机
            $result = $UserService->bindPhone($appUid, $bizUid, $data['phone'], $data['verificationCode'] );
            $returnData = $result['data'];
        }
        else if($method == 'setRealName'){ //实名认证
            $result = $UserService->setRealName($appUid,$bizUid, $data['identityName'], $data['identityNo']);
            $returnData = $result['data'];
        }
        else if($method == 'signContract'){ //电子签约
            $params = [
                'bizUid' => $bizUid,
                'jumpUrl' => 'http://devadmin-fin.zzsupei.com/Test/test.html',
            ];
            $result = $UserService->signContract($appUid, $params);
            if(!is_array($result)){
                $this->redirect($result);
            }
            $returnData = $result['data'];
        }
        else if($method == 'applyBindBankCard'){ //申请绑定银行卡
            //$bizUserId,$bankCardNo,$phone,'宋星',null,1,$identityNo
            $params = [
                'bizUid' => $bizUid,
                'cardNo' => $data['bankCardNo'],
                'phone'  => $data['phone'],
                'name'   => $data['identityName'],
                'identityNo' => $data['identityNo'],
                'validate' => null,
            ];
            $result = $UserService->applyBindBankCard($appUid, $params);
            $returnData = $result['data'];
            $data['tranceNum'] = $returnData['tranceNum'] ?? '';
        }
        else if($method == 'bindBankCard'){ //绑定银行卡
            $params = [
                'bizUid'    => $bizUid,
                'tranceNum' => $data['tranceNum'],
                'phone'     => $data['phone'],
                'verificationCode'  => $data['verificationCode'],
                'identityNo' => $data['identityNo'],
                'validate' => null,
            ];
            $result = $UserService->bindBankCard($appUid, $params);
            $returnData = $result['data'];
        }
        else if($method == 'queryBalance'){ //查询用户余额
            $userFundsService = new UserFundsService();
            $result = $userFundsService->queryBalance($appUid, $bizUid);
            $returnData = $result['data'];
        }
        else if($method == 'getMemberInfo'){ //查询用户信息
            $result = $UserService->getMemberInfo($appUid, $bizUid);
            $returnData = $result['data'];
        }
        else if($method == 'unbindBankCard'){ //解绑银行卡
            $params = [
                'bizUid'    => $bizUid,
                'cardNo' => $data['bankCardNo'],
            ];
            $result = $UserService->unbindBankCard($appUid, $params);
            $returnData = $result['data'];
        }
        else if($method == 'queryMerchantBalance'){ //查询平台账户余额
            $UserFundsService = new UserFundsService();
            $result = $UserFundsService->queryMerchantBalance();
            $returnData = $result['data'];
        }
        else if($method == 'queryReserveFundBalance'){ //查询平台头寸
            $UserFundsService = new UserFundsService();
            $fundAcctSys = $data['fundAcctSys'];
            $result = $UserFundsService->queryReserveFundBalance($fundAcctSys);
            $returnData = $result['data'];
        }

        $this->assign('result', $result);
        $this->assign('title', '用户接口测试');
        $this->assign('returnData', $returnData);
        $this->assign('data', $data);
        return $this->fetch('test/test');
    }

    function testPay(){
        $this->assign('title', '托管代收+充值订单接口测试');
        $data['bizOrderNo'] = "SX".date("YmdHis");
        $this->assign('returnData', []);
        $this->assign('data', $data);
        return $this->fetch();
    }

    function doTestPay(){
        $data = input();
        $returnData = $result= [];
        if(!isset($data['method'])){
            $this->redirect('testPay');
        }

        $orderEntryService =new OrderEntryService();
        $orderProcessService = new OrderProcessService();
        $method         = $data['method'];

        $consumerIp     = '192.168.1.144';
        $tradeCode      = 3001; //代收消费金
        $accountSetNo   = $this->accountSetNo ?: '400193';
        $bizOrderNo     = $data['bizOrderNo'] ?? "SX".date("YmdHis");
        $appUid         = $data['appUid'];    //默认1000
        $payerBizUid    = $data['payerBizUid']; //付款人biz_uid
        $payerId        = $appUid . $payerBizUid;
        $amount         = $data['amount'];
        $bankCardNo     = $data['bankCardNo'] ?? '6228480078086570476';    //农行-6228480078086570476 建行-6217001930038760865
        $payMethodKey   = $data['payMethodKey'] ?? 'QUICKPAY_VSP';   //支付方式  QUICKPAY_VSP 快捷支付
        $verificationCode = $data['verificationCode'];
        $data['bizOrderNo'] = $bizOrderNo;
        $data['verificationCode'] = '';

        if($method == 'agentCollectApply'){  //托管代收申请
            $param['bizOrderNo'] = $bizOrderNo;
            $param['payerId'] = $payerId;;
            $param["tradeCode"] = $tradeCode;
            $param["amount"] = $amount;
            $param["backUrl"] = Config::get('allinpay.call_back_domain') .'AllinPay/notifyDepositApply';
            $param["bizFrontUrl"] = '';
            $param["bizBackUrl"] = 'https://shop1.meijiabang.com/api/v4/payment/notify/bank_allinpay';
            $param['extendInfo'] = $appUid;
            //收银宝子商户号, 需要走线下流程申请  测试环境目前有3个 //56039305714Z6HU   56039305714Z6HV     56039305714Z6J3
            $payParam["amount"] = $amount;
            $payMethod =[];
            if($payMethodKey == 'WECHATPAY_MINIPROGRAM_ORG'){
                $payParam["vspCusid"] = '56039305714Z6HU';
                $payParam["subAppid"] = 'wxa503087871bcbc55';
                $payParam["limitPay"] = "no_credit";
                $payParam["acct"] = "oZmSm5F_U95QSttGfWd52IzUB_30";
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
//var_dump($payMethod);

            $result = $orderEntryService->agentCollectApply($appUid, $param, $payMethod);
            $returnData = $result['data'];
        }
        else if($method == 'depositApply') { //充值

        }
        else if($method == 'payByBackSMS'){ //确认支付（后台+短信验证码确认)
            $tradeNo = '';
            $param['bizOrderNo'] = $bizOrderNo;
            $param['tradeNo'] = $tradeNo;
            $param['bizUserId'] = $payerId;;
            $param['consumerIp'] = $consumerIp;
            $param['verificationCode'] = $verificationCode;
            //确认支付（后台+短信验证码确认)
            $result = $orderEntryService->payByBackSMS($appUid, $param);
            $returnData = $result['data'];
//var_dump($result);exit;
        }
        else if($method == 'payBySMS') { //确认支付（前台+短信验证码确认)
            $tradeNo = '';
            $param['tradeNo'] = $tradeNo;
            $param['bizUserId'] = $payerId;;
            $param['bizOrderNo'] = $bizOrderNo;;
            $param['consumerIp'] = $consumerIp;
            $param['verificationCode'] = $verificationCode;
            //确认支付（前台+短信验证码确认)
            $result = $orderEntryService->payBySMS($appUid, $param);

        }
        else if($method == 'signalAgentPay') { //单笔托管代付

            $bizOrderProcessNo  = $data['bizOrderProcessNo'] ?: "PRS".date("YmdHis");  //出款订单号
            $payOrderNo         = $data['bizOrderNo'];  //入款订单号
            $splitBizUserId     = $appUid . $data['splitBizUid'];
            $splitBizUserId2    = $appUid . $data['splitBizUid2'];
            $splitAmount        = $data['splitAmount'];
            $splitAmount2       = $data['splitAmount2'];
//            $splitBizUserId = ;

                //托管代收中的付款人列表
            $collectPayList[0]["bizOrderNo"] = $payOrderNo;
            $collectPayList[0]["amount"] = $amount;
//        $collectPayList[1]["bizOrderNo"] = $payOrderNo2;
//        $collectPayList[1]["amount"] = 2;
//        $param["collectPayList"] = $collectPayList;
            //收款账户bizUserId

            $splitRuleList[0]["bizUserId"] = $splitBizUserId;
            $splitRuleList[0]["bizOrderNo"] = $payOrderNo;
            $splitRuleList[0]["accountSetNo"]= $accountSetNo;
            $splitRuleList[0]["amount"]= $splitAmount;
            $splitRuleList[0]["fee"] = 0;
            $splitRuleList[0]["remark"] = "消费一级分账";
            $splitRuleList[1]["bizUserId"] = $splitBizUserId2;
            $splitRuleList[1]["bizOrderNo"] = $payOrderNo;
            $splitRuleList[1]["accountSetNo"]= $accountSetNo;
            $splitRuleList[1]["amount"]= $splitAmount2;
            $splitRuleList[1]["fee"] = 0;
            $splitRuleList[1]["remark"] = "消费一级分账";
//        $param["splitRuleList"] = $splitRuleList;
//return json($splitRuleList);
            $param['bizOrderNo'] = $bizOrderProcessNo;
            $param["tradeCode"] = 4001;
            $param["amount"] = $amount;
            $param["fee"] = 0;
            $param["backUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/notifyAgentPay';
            $param["bizBackUrl"] = 'https://shop1.meijiabang.com/api/wallet/shareMoneyCallback';
            $param['extendInfo'] = $appUid;
            //收款人的账户和账户集编号, 创建代收订单的用户
            $param["bizUserId"] = 20003;
            $param["accountSetNo"] = $accountSetNo;

            $result = $orderProcessService->signalAgentPay($appUid, $param, $collectPayList, $splitRuleList);
//            $returnData = $result['data'];

        }
//        else if($method == 'payByBackSMS') { //确认支付（后台+短信验证码确认)
//
//        }

//var_dump("<br><br>@@@@@@@@@@@<br><br>");
//var_dump($data);
        $this->assign('result', $result);
        $this->assign('title', '托管代收+充值订单接口测试');
        $this->assign('returnData', $returnData);
        $this->assign('data', $data);
        return $this->fetch('test/test_pay');
    }

    function index() {

        $yunClient = new yunClient();
        $MemberService = new MemberService();
        $userService = new UserService();
        $userFundsService = new UserFundsService();
        $orderEntryService =new OrderEntryService();
        $allInPayOrderService = new AllInPayOrderService();


        //c0b4a32d-9bd5-4a2e-922e-47d836d5160d
//        $data = $MemberService->createMember('sx3');
        //ff00bdbd-2e7e-4fb5-be3b-67547cec82ea
//        $data = $MemberService->createMember('sx4');

        //sx3       c0b4a32d-9bd5-4a2e-922e-47d836d5160d
        //sx4       ff00bdbd-2e7e-4fb5-be3b-67547cec82ea
        //10003     82fa6cac-915f-4cb0-bb33-6562887fa012

        $appUid             = 1000;    //默认1000
        $bizUid             = '3';
        $bizUserId          = 1000 . $bizUid;
        $accountSetNo       = '400193';
        $amount             = 1;
        $payMethodKey       = 'QUICKPAY_VSP';   //QUICKPAY_VSP 快捷支付
        $bizOrderNo         = "SX".date("YmdHis");
        $consumerIp         = '192.168.1.144';
        $bankCardNo         = '6228480078086570476';    //农行-6228480078086570476 建行-6217001930038760865
        $phone              = 18559212359;
        $identityNo         = '350481198607166514';
        $verificationCode   = '592481';


//        $data = $MemberService->getMemberInfo($bizUserId);
        /*
//        $data = $MemberService->createMember($bizUserId);

//        $data = $MemberService->sendVerificationCode($bizUserId,'18559212359', 9);
//        $data = $MemberService->bindPhone($bizUserId,'18559212359', '669159');
//        $data = $MemberService->sendVerificationCode($bizUserId,'18559212359', 6);
//        $data = $MemberService->unbindPhone($bizUserId,'18559212359', '817409');

//        $identityNo = '350481198607166514';
//        $data = $MemberService->setRealName($bizUserId,'宋星', $identityNo);
//        $data = $MemberService->signContract($bizUserId,'', 'https://www.zzsupei.com');

//        $data = $yunClient->decryptAES('901C49B3512528F9BE8E4A2C0975BF6E7CC6868108414F8494DB017050F18120');
//        $data = $userService->checkRealUser('sx', 3);
//        $data = Config::pull('allinpay');

//        $appInfo = model('UsersApp')->info(1);
//        $data = $userService->checkUser($appInfo,'7');
        */

//        $data = $MemberService->applyBindBankCard($bizUserId,$bankCardNo,$phone,'宋星',null,1,$identityNo );
//        $data = $MemberService->bindBankCard($bizUserId,'192757994663',$phone,$verificationCode);

        //充值社区收银宝, 集团

//        $param['bizOrderNo'] = $bizOrderNo;
//        $param['bizUserId'] = $bizUserId;;
//        $param["accountSetNo"] = $accountSetNo;
//        $param["amount"] = $amount;
//        $param["frontUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/frontDepositApply';
//        $param["backUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/notifyDepositApply';
//        //收银宝子商户号, 需要走线下流程申请  测试环境目前有3个 //56039305714Z6HU   56039305714Z6HV     56039305714Z6J3
////        $payParam["vspCusid"] = '56039305714Z6HU';
////        $payParam["paytype"] = "B2C,B2B";
//        $payParam["bankCardNo"] = $bankCardNo;
//        $payParam["amount"] = $amount;
//        $payMethod[$payMethodKey] = $payParam;
//
//        $data = $orderEntryService->depositApply($appUid, $param, $payMethod);
//        $data['param'] = $param;
//        $data['payMethod'] = $payMethod;
////


        $param['bizOrderNo'] = 'SX20201124165037';


        //查询订单状态    orderStatus
        //未支付	        1	整型
        //交易失败	        3	整型	交易过程中出现错误
        //交易成功	        4	整型
        //交易成功-发生退款	5	整型	交易成功，但是发生了退款。
        //关闭	            6	整型	未支付的订单，每天日终（00:30）批量关闭已创建未支付，且创建时间大于24小时的订单。
        //进行中	        99
//        $data = $allInPayOrderService->getOrderDetail($param);
        //重新发送支付订单验证码
//        $data = $allInPayOrderService->resendPaySMS($param);

        $tradeNo = '';
        $param['tradeNo'] = $tradeNo;

        $param['bizUserId'] = $bizUserId;;
        $param['consumerIp'] = $consumerIp;
        $param['verificationCode'] = $verificationCode;
        //确认支付（前台+短信验证码确认)
//        $data = $orderEntryService->payBySMS($appUid, $param);    echo $data;exit;
        //确认支付（后台+短信验证码确认)
//        $data = $orderEntryService->payByBackSMS($appUid, $param);


        //查询余额
//        $data = $userFundsService->queryBalance($appUid, $bizUid);
        $data = $userFundsService->queryBalance(1005, 977);

        return json($data);

    }

    public function wu(){
        $userService = new UserService();
        $OrderRefundService = new OrderRefundService();
        
        // $shopUid  = $this->shop_uid;
        // $appInfo = model('UsersApp')->info(1005);
        // echo '<pre>';
        // var_dump($appInfo);
        // $res = $userService->checkUser($shopUid,$appInfo,876,2);
        // $res = $userService->bindPhone(1005105,15606973760,769455);
        // $res = $userService->setRealName($appInfo,198,'吴鹭',360622199405053031);
        // $res = $userService->signContractQuery($this->shop_uid,$appInfo,198,'');
        // header("Location:$res");
     
        // $companyBasicInfo['name'] = '测试wu';
        // $companyBasicInfo['legal_name'] = '测试wu';
        // $companyBasicInfo['identity_no'] = '140724199411111111';
        // $companyBasicInfo['telephone'] = '18734893146';
        // $companyBasicInfo['account_no'] = '6227000267060250071';
        // $companyBasicInfo['parent_bank_name'] = '建设银行';
        // $companyBasicInfo['business_license'] = '123456789';
        // $companyBasicInfo['organization_code'] = '123456789';
        // $companyBasicInfo['tax_register'] = '123456789';
        // $companyBasicInfo['auth_type'] = 1;
        // $companyBasicInfo['bank_name'] = '建设银行厦门支行';
        // $companyBasicInfo['union_bank'] = '123333';

        // $res = $userService->setCompanyInfo($this->shop_uid,$appInfo,876,$companyBasicInfo);
        // $res = $userService->applyBindBankCard($this->shop_uid,$appInfo,198,6228490267060250071,13306973760,'吴鹭',360622199405053031);
        // var_dump($res);
        // $info = model('Users')->infoByBizUserId(1005105);
        // $info = $userService->createMember(1005,977,3);
        // $info = $userService->sendVerificationCode(1005,977,15606973760);
        // $info = $userService->bindPhone(1005,977,15606973760,'065977');
        // $info = $userService->setRealName(1005,977,'吴鹭翔',350622199405053031);
        // $info = $userService->getMemberInfo(1005,977);
        // $info = $userService->signContractQuery(1005,977,'http://devfin.zzsupei.com/Member/');
        // $info = $userService->getBankCardBin('6212264100030415388');
        // $info = $userService->getBankCardBin('6214855921668626');

$param['biz_users_id'] = 10003;  
$param['biz_order_no'] = 'SX20201119180446';
$param['allinpay_order_no'] = '1329365034391867392';
$param['refund_type'] = 1;
$param['refund_list'] = [];
$param['amount'] = 3;
$param['coupon_amount'] = 0;
$param['fee_amount'] = 0;
$param['extend_info'] = [];
$param['biz_back_url'] = "http://2222.com";

        $info = $OrderRefundService->refund(1000,3,1);
        // $info = model("OrderRefund")->addRefund(1000,$param);
        // header("Location:$info");
        // signContract
        echo "<pre>";
        var_dump($info);

    }

    //托管代收
    function agentApply(){
        $orderEntryService =new OrderEntryService();
        $allInPayOrderService = new AllInPayOrderService();

        $appUid             = 1000;     //默认1000
        $bizUid             = '3';
        $bizUserId          = $appUid . $bizUid;
        $tradeCode          = 3001; //代收消费金
        $accountSetNo       = '400193';
        $amount             = 3;
        $payMethodKey       = 'QUICKPAY_VSP';   //QUICKPAY_VSP 快捷支付
        $bizOrderNo         = "SX".date("YmdHis");
        $bankCardNo         = '6228480078086570476';    //农行-6228480078086570476 建行-6217001930038760865


        $param['bizOrderNo'] = $bizOrderNo;
        $param['payerId'] = $bizUserId;;
//        $param["accountSetNo"] = $accountSetNo;
        $param["tradeCode"] = $tradeCode;
        $param["amount"] = $amount;
        $param["frontUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/frontDepositApply';
        $param["backUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/notifyDepositApply';
        $param['extendInfo'] = $appUid;
        //收银宝子商户号, 需要走线下流程申请  测试环境目前有3个 //56039305714Z6HU   56039305714Z6HV     56039305714Z6J3
        $payParam["bankCardNo"] = $bankCardNo;
        $payParam["amount"] = $amount;
        $payMethod[$payMethodKey] = $payParam;

        $data = $orderEntryService->agentCollectApply($appUid, $param, $payMethod);
//        $data['param'] = $param;
//        $data['payMethod'] = $payMethod;

        return json($data);
    }
    //托管代付 or 分账
    function agentPay(){

        $orderEntryService =new OrderEntryService();
        $orderProcessService = new OrderProcessService();
        $allInPayOrderService = new AllInPayOrderService();

        $appUid             = 1000;     //默认1000
        $bizUid             = '3';
        $bizUserId          = $appUid . $bizUid;  //收款的中间账户的bizUserId
        $splitBizUserId     = '10009';  //实际收款人账户
        $splitBizUserId2    = '100050'; //实际收款人账户
        $tradeCode          = 4001; //代付购买金  3001 对应 4001
        $accountSetNo       = '400193';
        $amountSum          = 2;
        $payMethodKey       = 'QUICKPAY_VSP';   //QUICKPAY_VSP 快捷支付
        $bizOrderNo         = "SX".date("YmdHis");
        $bizOrderProcessNo  = "PRS".date("YmdHis");
        $bankCardNo         = '6228480078086570476';    //农行-6228480078086570476 建行-6217001930038760865
        $payOrderNo         = 'SX20201124165037';
        $payOrderNo2        = 'SX20201116153718';

//        $param['bizOrderNo'] = $payOrderNo;
//        $param['payerId'] = $bizUserId;;
////        $param["accountSetNo"] = $accountSetNo;
//        $param["amount"] = $amount;
//        $param["frontUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/frontDepositApply';
//        $param["backUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/notifyDepositApply';

        //托管代收中的付款人列表
        $collectPayList[0]["bizOrderNo"] = $payOrderNo;
        $collectPayList[0]["amount"] = $amountSum;
//        $collectPayList[1]["bizOrderNo"] = $payOrderNo2;
//        $collectPayList[1]["amount"] = 2;
//        $param["collectPayList"] = $collectPayList;
        //收款账户bizUserId

        $splitRuleList[0]["bizUserId"] = $splitBizUserId;
        $splitRuleList[0]["bizOrderNo"] = $payOrderNo;
        $splitRuleList[0]["accountSetNo"]= $accountSetNo;
        $splitRuleList[0]["amount"]= 1;
        $splitRuleList[0]["fee"] = 0;
        $splitRuleList[0]["remark"] = "消费一级分账";
        $splitRuleList[1]["bizUserId"] = $splitBizUserId2;
        $splitRuleList[1]["bizOrderNo"] = $payOrderNo;
        $splitRuleList[1]["accountSetNo"]= $accountSetNo;
        $splitRuleList[1]["amount"]= 1;
        $splitRuleList[1]["fee"] = 0;
        $splitRuleList[1]["remark"] = "消费一级分账";
//        $param["splitRuleList"] = $splitRuleList;

        $param['bizOrderNo'] = $bizOrderProcessNo;
        $param["tradeCode"] = $tradeCode;
        $param["amount"] = $amountSum;
        $param["fee"] = 0;
        $param["backUrl"] = 'http://betafin-back.zzsupei.com/AllinPay/notifyAgentPay';
        $param['extendInfo'] = $appUid;
        //中间收款人的账户和账户集编号, 创建代收订单的用户
        $param["bizUserId"] = $bizUserId;
        $param["accountSetNo"] = $accountSetNo;

        $data = $orderProcessService->signalAgentPay($appUid, $param, $collectPayList, $splitRuleList);
//        $data['param'] = $param;
//        $data['payMethod'] = $payMethod;

        return json($data);
    }

    function mq(){
        $producer = new BizProducer();

        $arrMsg = [
            'fun' =>  'signContract',   //fun 必填, 值是 ConsumeService 的方法名
            'data' => 'time:'.date('H:i:s').' your mother call you back home '
        ];

        $producer->publish($arrMsg);

    }

    function signC(){
        //allinpay 回调给你,
        // 确认搞定了,


        // 推消息给业务系统
        $producer = new BizProducer();
        $arrMsg = [
            'fun' =>  'signContract',   //fun 必填, 值是 ConsumeService 的方法名
            'url' => 'baidu.com',
            'result' => 'success',
            'time' =>  date('H:i:s'),
        ];

        $producer->publish($arrMsg);
    }

    function push(){
        $producer = new BizProducer();

        $arrMsg = [
            'serviceClass' => 'MemberService',
            'fun' =>  'signContract',   //fun 必填, 值是 ConsumeService 的方法名
            'appId' => '666666',
            'bizUid' => '3',
        ];

        $producer->publish($arrMsg);
    }

    function pushOrder(){
        $producer = new BizProducer();

        $arrMsg = [
            'serviceClass' => 'OrderService',
            'fun' =>  'agentPay',   //fun 必填, 值是 ConsumeService 的方法名
            'appId' => '1000',
            'bizOrderNo' => 'PRO20201119181305',
        ];

        $producer->publish($arrMsg);
    }

    function biz(){
        $str = '{"sign":"8ad864063d76e8381fbfb42455d7932f","appId":"20201002","bizOrderNo":"65","amount":"5","bizBackUrl":"https:\/\/shop1.meijiabang.com\/api\/wallet\/shareMoneyCallback","collectPayList":"[{&quot;bizOrderNo&quot;:1900,&quot;amount&quot;:5}]","splitRuleList":"[{&quot;bizUserId&quot;:168,&quot;bizOrderNo&quot;:1900,&quot;amount&quot;:2,&quot;remark&quot;:&quot;&quot;},{&quot;bizUserId&quot;:166,&quot;bizOrderNo&quot;:1900,&quot;amount&quot;:3,&quot;remark&quot;:&quot;&quot;}]"}';
        $str = html_entity_decode($str);
        $str = stripslashes($str);
        var_dump($str);
        $data = json_decode($str, true);
        var_dump($data);

        var_dump(ToolsService::sign($data));
    }

    function resend(){
        $bizConsumer = new BizConsumer();

        $str = '{"id":443,"appId":1009,"bizOrderNo":"PA20210116144736247090","bizUid":"1_30","amount":1}';
        $data = json_decode($str, true);

        if(!isset($data['resendNum']) || empty($data['resendNum'])){
            $data['resendNum'] = 1;
        }else{
            $data['resendNum']++;
        }
        $data['resendTime'] = time() + BizConsumer::ResendTimeInterval[$data['resendNum']];

        $data['serviceClass'] = 'OrderService';
        $data['fun'] = 'depositApply';

        $bizConsumer->resendQueue($data);
    }

    function resendCron(){
        $bizConsumer = new BizConsumer();
        $key = BizConsumer::RedisKey;
        $time = time();
        $msg = '补推送开始：' . date('Y-m-d H:i:s', $time);
        Log::info($msg);

        $redis = Redis::redis();
        $list = $redis->zrange($key, 0, -1);

        foreach ($list  as $k => $v) {
            $v = json_decode($v, true);

            Log::info($msg);
            if(!isset($v['resendTime'])){
                continue;
            }
            if ($v['resendTime'] <= $time) {
                $tempData = $v;
                unset($tempData['serviceClass']);
                unset($tempData['fun']);

                //调用 push模块的服务 (例如: MemberService) 的指定方法 ($v['fun'])
                $res = false;
                $className = '\\app\\push\\service\\'.$v['serviceClass'];
                $ConsumeService = new $className();
                if (isset($v['fun'])) {
                    $res = call_user_func([$ConsumeService, $v['fun']], $tempData);
                }

                $redis->zRem($key, json_encode($v));
                if(!$res) { //消费失败, 重新放回队列
                    $bizConsumer->resendQueue($v);
                }


                $msg = date('Y-m-d H:i:s', time())."--{$k}--{$res}--".json_encode($v);
                Log::info($msg);

echo $msg;
            }
        }
        $msg = '补推送结束：' . date('Y-m-d H:i:s', time());
        Log::info($msg);
        return json('end');

    }


}
