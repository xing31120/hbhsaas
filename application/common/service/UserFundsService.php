<?php


namespace app\common\service;

use AllInPay\Log\Log;
use AllInPay\SDK\yunClient;
use app\common\service\AllInPay\AllInPayClient;
use app\common\tools\SysEnums;
use app\common\service\AllInPay\AllInPayOrderService;

//会员资金 服务
class UserFundsService{

    public $accountSetNo = '';
    public $mcTimeOut = 6;
    public $mcName = 'user_funds_service_';
    public $allInPayOrderService = null ;

    public function __construct(){
        $this->allInPayOrderService = new AllInPayOrderService();

        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->accountSetNo = $config['account_set_no'];
    }

    /**
     *  查询余额
     * @param $appUid
     * @param $bizUid
     * @param string $accountSetNo 测试环境
     * User: 宋星 DateTime: 2020/11/10 16:46
     * @return bool|mixed
     */
    public function queryBalance($appUid, $bizUid,$accountSetNo = ''){
        $accountSetNo = $accountSetNo ?: $this->accountSetNo;
        $bizUserId = $bizUid > 0 ? $appUid . $bizUid : $bizUid;
        $mcKey = $this->mcName . 'query_Balance_' . $bizUserId;
        //余额缓存
        $rs = cache($mcKey);
        if ($rs !== false) {
            return successReturn(['data' => $rs['data']]);
        }
        //缓存 未命中or过期, 查allinpay
        $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
        $rs = $this->allInPayOrderService->queryBalance($bizUserId, $accountSetNo);
        if($rs['result'] === false){    //allinpay错误直接返回
            return $rs;
        }
//        cache($mcKey, $rs, $time);

        //入库
//        $userInfo = model('Users')->infoByBizUid($appUid, $bizUid);
        //自动判断是否 修改余额
        $userInfo = model('Users')->upUsersFunds($appUid, $bizUid, $rs['data']['allAmount'], $rs['data']['freezenAmount']);


        if(!$userInfo['result']){
            return errorReturn('查询余额失败');
        }

        return successReturn(['data' => $rs['data']]);
    }

    //直接请求通联
    public function queryBalanceAllInPay($appUid,$bizUid,$accountSetNo = ''){
        $accountSetNo = $accountSetNo ?: $this->accountSetNo;
        $bizUserId = $appUid . $bizUid;
        $rs = $this->allInPayOrderService->queryBalance($bizUserId, $accountSetNo);
        if($rs['result'] === false){    //allinpay错误直接返回
            return $rs;
        }
        return successReturn(['data' => $rs['data']]);
    }




    function queryMerchantBalance(){

        return $this->allInPayOrderService->queryMerchantBalance();
    }

    public function queryReserveFundBalance($fundAcctSys = 1){
        return $this->allInPayOrderService->queryReserveFundBalance($fundAcctSys);
    }

    public function getOrderSplitRuleListDetail($bizOrderNo){
        return $this->allInPayOrderService->getOrderSplitRuleListDetail($bizOrderNo);
    }
}