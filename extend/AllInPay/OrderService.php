<?php

namespace AllInPay;

use AllInPay\SDK\yunClient;
use AllInPay\Log\Log;
use AllInPay\Config\conf;

$demo = new OrderDemo();
$demo->batchAgentPay();

class OrderService{

    private  $logIns;
    public function __construct(){
        $this->logIns = Log::getInstance();
    }

    /**
     * [depositApply 充值申请]
     */
	public function depositApply($bizUserId,$accountSetNo,$amount,$fee,$validateType,$frontUrl,$backUrl,$industryCode,$industryName,$source)
	{

		$yunClient = new yunClient();
		$param["bizOrderNo"] = "TL".date("Ymdhis");
		$param["bizUserId"] = $bizUserId;
		$param["accountSetNo"] = $accountSetNo;
		$param["amount"] = $amount;
		$param["fee"] =$fee;
		$param["validateType"] = $validateType;
		$param["frontUrl"] = $frontUrl;
		$param["backUrl"] = $backUrl;
		//快捷支付
		// $payParam["amount"] = 1;
		// $payParam["bankCardNo"] = $yunClient->encryptAES("6227000267060250071");
		// $payMethod["QUICKPAY_VSP"] = $payParam;
		//收银宝网关
		// $payParam["amount"] = 1;
		// $payParam["paytype"] = "B2C,B2B";
		// $payMethod["GATEWAY_VSP"] = $payParam;
		//微信小程序
		// $payParam["amount"] = 1;
		// $payParam["limitPay"] = "no_credit";
		// $payParam["acct"] = "oUU99wefa2BWRDmoIqUjMTFrxMGY";
		// $payMethod["WECHATPAY_MINIPROGRAM"] = $payParam;
		//收银宝正扫
		// $payParam["amount"] = $amount;
		// $payParam["limitPay"] = "no_credit";
		// $payMethod["SCAN_ALIPAY"] = $payParam;
		// 微信原生小程序
		$payParam["amount"] = 1;
		$payParam["wxAppId"] = "wx806d3df873b1e9fc";
		$payParam["wxMchtId"] = "1550008971";
		$payParam["limitPay"] = "no_credit";
		$payParam["acct"] = "olRPt4pZRC04UilIX8GehfLj-vyI";
		$payParam["cusip"] = "10.168.1.70";
		$payMethod["WECHATPAY_MINIPROGRAM_OPEN"] = $payParam;		

		$param["payMethod"] = $payMethod;
		$param["industryCode"] = $industryCode;
		$param["industryName"] = $industryName;
		$param["source"] = $source;
		$method = "allinpay.yunst.orderService.depositApply";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	/**
	 * [payByBackSMS 确认支付（后台+短信验证码确认）]
	 */
	public function payByBackSMS($bizUserId,$bizOrderNo,$verificationCode,$consumerIp,$tradeNo=null)
	{
		$yunClient = new yunClient();
		$param["bizUserId"] = $bizUserId;
		$param["bizOrderNo"] = $bizOrderNo;
		$param["tradeNo"] = $tradeNo;
		$param["verificationCode"] = $verificationCode;
		$param["consumerIp"] = $consumerIp;
		$method = "allinpay.yunst.orderService.payByBackSMS";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	public function queryReserveFundBalance()
	{
		$yunClient = new yunClient();
		$param["sysid"] = "2002141050372732927";
		$method = "allinpay.yunst.merchantService.queryReserveFundBalance";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}
	/**
	 * [payBySMS 确认支付（前台+短信验证码确认）]
	 */
	public function payBySMS($bizUserId,$bizOrderNo,$consumerIp,$verificationCode=null)
	{
		$yunClient = new yunClient();
		$param["bizUserId"] = $bizUserId;
		$param["bizOrderNo"] = $bizOrderNo;
		$param["verificationCode"] = $verificationCode;
		$param["consumerIp"] = $consumerIp;
		$method = "allinpay.yunst.orderService.payBySMS";
		$result = $yunClient->concatUrlParams($method,$param);
		$url = "http://test.allinpay.com/op/gateway?".$result;
		$this->logIns->logMessage("[前台+短信验证码确认URL]",Log::INFO,$url);
		//header("Location:$url");
	}

	/**
	 * [withdrawApply 提现申请]
	 */
	public function withdrawApply($bizUserId,$accountSetNo,$amount,$fee,$validateType,$backUrl,$bankCardNo,$industryCode,$industryName,$source)
	{
		$yunClient = new yunClient();
		$param["bizOrderNo"] = "TL".date("Ymdhis");
		$param["bizUserId"] = $bizUserId;
		$param["accountSetNo"] = $accountSetNo;
		$param["amount"] = $amount;
		$param["fee"] = $fee;
		$param["validateType"] = $validateType;
		$param["backUrl"] = $backUrl;
		$param["withdrawType"] = "D1";
		$param["bankCardNo"] = $yunClient->encryptAES($bankCardNo);
		$param["industryCode"] = $industryCode;
		$param["industryName"] = $industryName;
		$param["source"] = $source;
		$method = "allinpay.yunst.orderService.withdrawApply";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}


	/**
	 * [consumeApply 消费申请]
	 */
	public function consumeApply($payerId,$recieverId,$amount,$fee,$validateType,$backUrl,$industryCode,$industryName,$source)
	{
		$yunClient = new yunClient();
		$param["payerId"] = $payerId;
		$param["recieverId"] = $recieverId;
		$param["amount"] = $amount;
		$param["fee"] = $fee;
		$param["bizOrderNo"] = "TL".date("Ymdhis");
		$param["validateType"] = $validateType;
		$param["backUrl"] = $backUrl;
		$param["frontUrl"] = "https://www.baidu.com";

		//余额支付 JSONArray
		// $payParam[0]["accountSetNo"]="400193";
		// $payParam[0]["amount"]=$amount;
		// $payMethod["BALANCE"] = $payParam;
		//快捷支付
		// $payParam["amount"] = $amount;
		// $payParam["bankCardNo"] = $yunClient->encryptAES("6227000267060250071");
		// $payMethod["QUICKPAY_VSP"] = $payParam;
		//收银宝网关
		// $payParam["amount"] = 1;
		// $payParam["paytype"] = "B2B";
		// $payMethod["GATEWAY_VSP"] = $payParam;
		//微信小程序
		// $payParam["amount"] = 1;
		// $payParam["limitPay"] = "no_credit";
		// $payParam["acct"] = "oUU99wefa2BWRDmoIqUjMTFrxMGY";
		// $payMethod["WECHATPAY_MINIPROGRAM"] = $payParam;
		// 微信原生小程序
		$payParam["amount"] = 1;
		$payParam["wxAppId"] = "wx806d3df873b1e9fc";
		$payParam["wxMchtId"] = "1550008971";
		$payParam["limitPay"] = "no_credit";
		$payParam["acct"] = "oUU99wefa2BWRDmoIqUjMTFrxMGY";
		$payParam["cusip"] = "10.168.1.70";
		$payMethod["WECHATPAY_MINIPROGRAM_OPEN"] = $payParam;
		// 刷卡支付
		// $payParam["amount"] = $amount;
		// $payParam["limitPay"] = "no_credit";
		// $payParam["authcode"] = "135022012029210261";
		// $payMethod["CODEPAY_VSP"] = $payParam;
		// H5支付
		// $payParam["amount"] = $amount;
		// $payParam["limitPay"] = "no_credit";
		// $payMethod["H5_CASHIER_VSP"] = $payParam;		
		// // 二级分账
		// $splitRuleList[0]["bizUserId"]="testtlzf02";
		// $splitRuleList[0]["accountSetNo"]="400193";
		// $splitRuleList[0]["amount"]=1;
		// $splitRuleList[0]["fee"]=0;
		// $splitRuleList[0]["remark"]="消费二级分账";

		// //一级分账
		// $splitParam[0]["bizUserId"]="testtlzf01";
		// $splitParam[0]["accountSetNo"]="400193";
		// $splitParam[0]["amount"]=1;
		// $splitParam[0]["fee"]=0;
		// $splitParam[0]["remark"]="消费一级分账";
		// $splitParam[0]["splitRuleList"]=$splitRuleList;
		// $param["splitRule"] = $splitParam;
		$param["payMethod"] = $payMethod;
		$param["industryCode"] = $industryCode;
		$param["industryName"] = $industryName;
		$param["source"] = $source;
		$method = "allinpay.yunst.orderService.consumeApply";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	/**
	 * [agentCollectApply 托管代收]
	 */
	public function agentCollectApply($payerId,$tradeCode,$amount,$fee,$backUrl,$validateType,$industryCode,$industryName,$source)
	{
		$yunClient = new yunClient();
		$param["bizOrderNo"] = "TL".date("Ymdhis");
		$param["payerId"] = $payerId;
		//收款列表 JSONArray
		$recievParam[0]["bizUserId"]="testtlzf02";
		$recievParam[0]["amount"]=$amount;
		$param["recieverList"] = $recievParam;
		$param["tradeCode"] = $tradeCode;
		$param["amount"] = $amount;
		$param["fee"] = $fee;
		$param["backUrl"] = $backUrl;
		$param["validateType"] = $validateType;
		//余额支付 JSONArray
		$payParam[0]["accountSetNo"]="400193";
		$payParam[0]["amount"]=$amount;
		$payMethod["BALANCE"] = $payParam;
		//快捷支付
		// $payParam["amount"] = $amount;
		// $payParam["bankCardNo"] = $yunClient->encryptAES("6227000267060250071");
		// $payMethod["QUICKPAY_VSP"] = $payParam;
		//收银宝网关
		// $payParam["amount"] = 1;
		// $payParam["paytype"] = "B2B";
		// $payMethod["GATEWAY_VSP"] = $payParam;
		//微信小程序
		// $payParam["amount"] = 1;
		// $payParam["limitPay"] = "no_credit";
		// $payParam["acct"] = "oUU99wefa2BWRDmoIqUjMTFrxMGY";
		// $payMethod["WECHATPAY_MINIPROGRAM"] = $payParam;
		$param["payMethod"] = $payMethod;
		$param["industryCode"] = $industryCode;
		$param["industryName"] = $industryName;
		$param["source"] = $source;
		$method = "allinpay.yunst.orderService.agentCollectApply";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	/**
	 * [signalAgentPay 单笔托管代付]
	 */
	public function signalAgentPay($bizUserId,$bizOrderNo,$tradeCode,$amount,$fee,$backUrl)
	{
		$yunClient = new yunClient();
		$param["bizOrderNo"] = "TL".date("Ymdhis");
		//托管代收中的付款人列表
		$collectPayList[0]["bizOrderNo"] = $bizOrderNo;
		$collectPayList[0]["amount"] = 3;
		$param["collectPayList"] = $collectPayList;
		//收款账户bizUserId
		$param["bizUserId"] = $bizUserId;

		$splitParam[0]["bizUserId"]="ORGCASH30003";
		$splitParam[0]["accountSetNo"]="400193";
		$splitParam[0]["amount"]=1;
		$splitParam[0]["fee"]=0;
		$splitParam[0]["remark"]="消费一级分账";
		$param["splitRuleList"] = $splitParam;
		// 
		$param["accountSetNo"] = "400193";
		$param["tradeCode"] = $tradeCode;
		$param["amount"] = 3;
		$param["fee"] = 0;
		$param["backUrl"] = $backUrl;
		$method = "allinpay.yunst.orderService.signalAgentPay";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	public function batchAgentPay()
	{
		$yunClient = new yunClient();
		$param["bizBatchNo"] = "1596708057721ba";
		//托管代收中的付款人列表
		// $collectPayList1[0]["bizOrderNo"] = "TL20200806044959";
		// $collectPayList1[0]["amount"] = 3;
		// $batchPayList[0]["bizOrderNo"] = "BatchTL111111111";
		// $batchPayList[0]["bizUserId"] = "testtlzf01";
		// $batchPayList[0]["accountSetNo"] = "400193";
		// $batchPayList[0]["collectPayList"] = $collectPayList1;
		// $batchPayList[0]["backUrl"] = "https://www.baidu.com";
		// $batchPayList[0]["amount"] = 3;
		// $batchPayList[0]["fee"] = 0;

		// $collectPayList2[0]["bizOrderNo"] = "TL20200806045405";
		// $collectPayList2[0]["amount"] = 2;
		// $batchPayList[1]["bizOrderNo"] = "BatchTL2222222222";
		// $batchPayList[1]["bizUserId"] = "testtlzf02";
		// $batchPayList[1]["accountSetNo"] = "400193";
		// $batchPayList[1]["collectPayList"] = $collectPayList2;
		// $batchPayList[1]["backUrl"] = "https://www.baidu.com";
		// $batchPayList[1]["amount"] = 2;
		// $batchPayList[1]["fee"] = 0;
		// 
		$collectPayList1[0]["bizOrderNo"] = "1596695571119ds";
		$collectPayList1[0]["amount"] = 2;
		$batchPayList[0]["bizOrderNo"] = "1596700984548lyz";
		$batchPayList[0]["bizUserId"] = "ORGCASH30001";
		$batchPayList[0]["accountSetNo"] = "400193";
		$batchPayList[0]["collectPayList"] = $collectPayList1;
		$batchPayList[0]["backUrl"] = "https://www.baidu.com";
		$batchPayList[0]["amount"] = 2;
		$batchPayList[0]["fee"] = 0;
		$splitRuleparam[0]["accountSetNo"] = "400193";
		$splitRuleparam[0]["amount"] = 2;
		$splitRuleparam[0]["bizUserId"] = "f7229622-1dfd-4444-a018-6daece0203d6";
		$splitRuleparam[0]["fee"] = 0;
		$splitRuleparam[0]["remark"] = " 消费一级分账";
		$batchPayList[0]["splitRuleList"] = $splitRuleparam;

		$collectPayList2[0]["bizOrderNo"] = "1596695643171ds";
		$collectPayList2[0]["amount"] = 2;
		$batchPayList[1]["bizOrderNo"] = "1596700984549lyz";
		$batchPayList[1]["bizUserId"] = "ORGCASH30001";
		$batchPayList[1]["accountSetNo"] = "400193";
		$batchPayList[1]["collectPayList"] = $collectPayList2;
		$batchPayList[1]["backUrl"] = "https://www.baidu.com";
		$batchPayList[1]["amount"] = 2;
		$batchPayList[1]["fee"] = 0;
		$splitRuleparam1[0]["accountSetNo"] = "400193";
		$splitRuleparam1[0]["amount"] = 2;
		$splitRuleparam1[0]["bizUserId"] = "f7229622-1dfd-4444-a018-6daece0203d6";
		$splitRuleparam1[0]["fee"] = 0;
		$splitRuleparam1[0]["remark"] = " 消费一级分账";
		$batchPayList[1]["splitRuleList"] = $splitRuleparam1;
 


		$param["batchPayList"] = $batchPayList;
		//收款账户bizUserId
		$param["tradeCode"] = "4001";
		$method = "allinpay.yunst.orderService.batchAgentPay";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}	

	/**
	 * [freezeMoney 冻结金额]
	 */
	public function freezeMoney($bizUserId,$amount)
	{
		$yunClient = new yunClient();
		$param["bizFreezenNo"] = "TL".date("Ymdhis");
		$param["bizUserId"] = $bizUserId;
		$param["accountSetNo"] = "400193";
		$param["amount"] = $amount;
		$method = "allinpay.yunst.orderService.freezeMoney";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	/**
	 * [unfreezeMoney 解冻金额]
	 */
	public function unfreezeMoney($bizUserId,$bizFreezenNo,$amount)
	{
		$yunClient = new yunClient();
		$param["bizFreezenNo"] = $bizFreezenNo;
		$param["bizUserId"] = $bizUserId;
		$param["accountSetNo"] = "400193";
		$param["amount"] = $amount;
		$method = "allinpay.yunst.orderService.unfreezeMoney";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	/**
	 * [refund 退款申请]
	 */
	public function refund($oriBizOrderNo,$bizUserId,$refundType,$amount,$backUrl)
	{
		$yunClient = new yunClient();
		$param["bizOrderNo"] = "TL".date("Ymdhis");
		$param["oriBizOrderNo"] = $oriBizOrderNo;
		$param["bizUserId"] = $bizUserId;
		$param["refundType"] = $refundType;
		$param["amount"] = $amount;
		$param["backUrl"] = $backUrl;
		$method = "allinpay.yunst.orderService.refund";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	/**
	 * [applicationTransfer 平台转账]
	 */
	public function applicationTransfer($targetBizUserId,$amount)
	{
		$yunClient = new yunClient();
		$param["bizTransferNo"] = "TL".date("Ymdhis");
		$param["sourceAccountSetNo"] = "100001";
		$param["targetBizUserId"] = $targetBizUserId;
		$param["targetAccountSetNo"] = "400193";
		$param["amount"] = $amount;
		$param["backUrl"] = "https://www.baidu.com";
		$method = "allinpay.yunst.orderService.applicationTransfer";
		$result = $yunClient->request($method,$param);
		var_dump($result);

	}

	/**
	 * [queryBalance 查询余额]
	 */
	public function queryBalance($bizUserId,$accountSetNo)
	{
		$yunClient = new yunClient();
		$param["bizUserId"] = $bizUserId;
		$param["accountSetNo"] = $accountSetNo;
		$method = "allinpay.yunst.orderService.queryBalance";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	public function queryMerchantBalance($accountSetNo)
	{
		$yunClient = new yunClient();
		$param["accountSetNo"] = $accountSetNo;
		$method = "allinpay.yunst.merchantService.queryMerchantBalance";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}	

	/**
	 * [getOrderDetail 查询订单状态]
	 */
	public function getOrderDetail($bizOrderNo)
	{
		$yunClient = new yunClient();
		$param["bizOrderNo"] = $bizOrderNo;
		$method = "allinpay.yunst.orderService.getOrderDetail";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	/**
	 * [queryInExpDetail 查询账户收支明细]
	 */
	public function queryInExpDetail($timestart,$timeend,$bizUserId,$accountSetNo,$startPosition,$queryNum)
	{
		$yunClient = new yunClient();
		$param["bizUserId"] = $bizUserId;
		$param["accountSetNo"] = $accountSetNo;
		$param["dateStart"] = date("Y-m-d",strtotime($timestart));
		$param["dateEnd"] = date("Y-m-d",strtotime($timeend));
		$param["startPosition"] = $startPosition;
		$param["queryNum"] = $queryNum;
		$method = "allinpay.yunst.orderService.queryInExpDetail";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	/**
	 * [getCheckAccountFile 平台集合对账下载]
	 */
	public function getCheckAccountFile($timestart,$fileType)
	{
		$yunClient = new yunClient();
		$param["date"] = date("Ymd",strtotime($timestart));
		$param["fileType"] = $fileType;
		$method = "allinpay.yunst.orderService.getCheckAccountFile";
		$result = $yunClient->request($method,$param);
		set_time_limit(0); 
		$file = file_get_contents($url);
		file_put_contents("./DownFile/$data.txt", $file);
	}

	public function agentCollectProtocolApply($bizUserId,$protocolNo)
	{
		$yunClient = new yunClient();
		$param["payerId"] = $bizUserId;
		$param["protocolNo"] = $protocolNo;
		$param["tradeCode"] = "3001";
		$param["amount"] = 1;
		$param["backUrl"] = "https://www.baidu.com";
		$param["industryCode"] = $queryNum;
		$param["industryName"] = $queryNum;
		$param["source"] = "2";
		$method = "allinpay.yunst.orderService.queryInExpDetail";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}

	public function withdrawApply_HT()
	{
		$yunClient = new yunClient();
		$param["bizOrderNo"] = "TL".date("Ymdhis");
		$param["bizUserId"] = "1294229449063051265";
		$param["accountSetNo"] = "400193";
		$param["amount"] = 1;
		$param["validateType"] = 0;
		$param["backUrl"] = "https://www.baidu.com";
		$param["bankCardNo"] = $yunClient->encryptAES("6217002870069115069");
		$param["withdrawType"] = "D0";
		//$param["bankCardPro"] = 1;
		$param["fee"] =0;
		$param["industryCode"] = "1413";
		$param["industryName"] = "汽车售后";
		$param["source"] = 2;

		$payparam["subAcctNo"] = "9120001009798353840";
		$payparam["PAYEE_ACCT_NO"] = "6217002870069115069";
		$payparam["PAYEE_ACCT_NAME"] = "测试";
		$payparam["AMOUNT"] = "1";
		$payparam["SUMMARY"] = "佣金提现";
		$Htarray = array();
		$Htarray['PAYEE_ACCT_NO'] = "6217002870069115069";
		$Htarray['PAYEE_ACCT_NAME'] = "测试";
		$Htarray['AMOUNT'] = "1";
		$Htarray["SUMMARY"] = "佣金提现";
		ksort($Htarray);
		$json = json_encode($Htarray,JSON_UNESCAPED_UNICODE);
		$payparam["SIGNED_MSG_MER"] = $yunClient->htBankWithdrawSign($json);
		$payMethod["WITHDRAW_HTBANK"] = $payparam;
		$param["payMethod"] = $payMethod;
		$method = "allinpay.yunst.orderService.withdrawApply";
		$result = $yunClient->request($method,$param);
		var_dump($result);
	}	

}
