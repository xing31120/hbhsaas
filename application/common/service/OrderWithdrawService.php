<?php


namespace app\common\service;

use AllInPay\Log\Log;
use AllInPay\SDK\yunClient;
use app\common\model\OrderEntry;
use app\common\service\AllInPay\AllInPayClient;
use app\common\tools\SysEnums;
use app\common\service\AllInPay\AllInPayOrderService;
use think\Db;

//提现服务
class OrderWithdrawService{

    public $mcName = 'order_withdraw_';
    public $allInPayOrderService = null ;


    function __construct(){
        $this->allInPayOrderService = new AllInPayOrderService();
    }

    function withdraw($appUid, $param){
//var_dump(empty($appUid));exit;
        if( empty($param["bizUserId"]) || empty($param['bizOrderNo']) ||
            empty($param["amount"])  || empty($param['bizBackUrl']) || empty($param['backUrl']) ||
            empty($param['bankCardNo'])
        ){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }

        $bizBackUrl = $param["bizBackUrl"];

        $allInPayOrderNo = $result['data']['orderNo'] ?? 'abc';
        $allInPayPayNo   = $result['data']['payInterfaceOutTradeNo'] ?? '';
//var_dump($result);exit;
        $paramNew = $param;
        $paramNew['biz_order_no'] = $param['bizOrderNo'];
        $paramNew['extend_params'] = $param['extendParams'] ?? '';
        $paramNew['biz_users_id'] = $param['bizUserId'];
        $paramNew['allinpay_order_no'] = $allInPayOrderNo;
        $paramNew['allinpay_pay_no'] = $allInPayPayNo;
        $paramNew['biz_back_url'] = $bizBackUrl;

        $rs = model('OrderWithdraw')->addWithdraw($appUid, $paramNew);
        if(!$rs['result']){
            $errorMsg = $rs['msg'] ?? '添加托管代收订单失败';
            return errorReturn($errorMsg);
        }

        unset($param["bizBackUrl"]);
        if($param['bizUserId'] == -1){
            $param['bizUserId'] = '#yunBizUserId_B2C#';
            $param['accountSetNo'] = '100001';
            $param['bankCode'] = '4105840';
        }
        $result = $this->allInPayOrderService->withdraw($param);
        if(!isset($result['result']) || $result['result'] === false){    //allinpay错误直接返回
            return $result;
        }


//        $returnData['bizUid']       = $param['bizUserId'];
        $returnData['bizUid']       = str_replace($appUid, "", $param['bizUserId']);
        $returnData['bizOrderNo']   = $param['bizOrderNo'];
        $returnData['amount']       = $param['amount'];
        $returnData['payStatus']    = 0;


        $userInfo = model('Users')->subUserFund($appUid, $param['bizUid'], $param['amount']);

        if(!isset($userInfo['result']) || !$userInfo['result']){
            return errorReturn('用户余额更新失败!');
        }

        return successReturn(['data' => $returnData, 'resData' => $rs['data']->toArray()]);
    }


}