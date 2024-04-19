<?php


namespace app\common\service;

use AllInPay\Log\Log;
use AllInPay\SDK\yunClient;
use app\common\model\OrderEntry;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\UserService;
use app\common\tools\SysEnums;
use app\common\service\AllInPay\AllInPayOrderService;
use think\Db;

//入账订单 服务
class OrderEntryService{

    public $mcName = 'order_entry_service_';
    public $allInPayOrderService = null ;

    const CONFIRM_STATUS_TEXT = [
        0 => '待确认',
        1 => '入款确认'
    ];

//    public $accountSetNo = '400193';
    public $publicAccountId = '';

    public function __construct(){
        $this->allInPayOrderService = new AllInPayOrderService();
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->publicAccountId = $config['escrow_user_id'];
    }



    /**
     * [payBySMS 确认支付（前台+短信验证码确认）]
     */
    function payBySMS($appUid, $param){
        if( empty($param["verificationCode"]) ){
            unset($param["verificationCode"]);
        }
        if( empty($data["bizUserId"]) || empty($data["bizOrderNo"]) || strlen($param['bizUserId']) < 5 || empty($param['consumerIp']) ){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }
        $result = $this->allInPayOrderService->payBySMS($param);
        if(isset($result['result']) && $result['result'] === false){    //allinpay错误直接返回
            return $result;
        }

        return $result;
    }

    /**
     * [payBySMS 确认支付（后台+短信验证码确认）]
     */
    function payByBackSMS($appUid, $param){
        if( empty($param["verificationCode"]) ){
            unset($param["verificationCode"]);
        }
        if(empty($appUid) || empty($param['bizUserId']) || strlen($param['bizUserId']) < 5
            || empty($param['consumerIp']) || empty($param['bizOrderNo']) || empty($param['consumerIp'])
        ){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }
        $result = '';
        $result = $this->allInPayOrderService->payByBackSMS($param);
        if(isset($result['result']) && $result['result'] === false){    //allinpay错误直接返回
            return $result;
        }

        unset($param['consumerIp']);
        unset($param['verificationCode']);
        $param['bizUid'] = str_replace($appUid,"",$param['bizUserId']);
        unset($param['bizUserId']);
        return successReturn(['data'=> $param, 'payData' => $result]);
    }


    /**
     * Notes:充值订单
     * @param $appUid
     * @param $param
     * @param $payMethod
     * @return array
     * User: SongX DateTime: 2020/11/30 9:44
     */
    function depositApply($appUid, $param, $payMethod){
        if(empty($appUid) || empty($param["backUrl"]) || empty($param['bizOrderNo'] ||
            empty($param["bizBackUrl"])  || empty($payMethod) || empty($param['bizUserId']) ||
            strlen($param['bizUserId']) < 5 || empty($param['amount']) )
        ){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }

        $publicAccountId = $param['public_account_id'] ?? $this->publicAccountId;
        $param["frontUrl"] = $bizFrontUrl = $param["bizFrontUrl"];
        $bizBackUrl = $param["bizBackUrl"];
        unset($param['public_account_id']);
        unset($param["bizFrontUrl"]);
        unset($param["bizBackUrl"]);
        $result = $this->allInPayOrderService->depositApply($param, $payMethod);
        if($result['result'] === false){    //allinpay错误直接返回
            return $result;
        }

        $paramNew = $param;
        $paramNew['payMethod'] = $payMethod;
        $paramNew['allinpay_order_no'] = $result['data']['orderNo'] ?? '';
        $paramNew['allinpay_pay_no'] = $result['data']['payInterfaceOutTradeNo'] ?? '';
        $paramNew['order_type'] = 10;
        $paramNew['public_account_id'] = $publicAccountId;
        $paramNew['front_url'] = $bizFrontUrl;
        $paramNew['back_url'] = $bizBackUrl;

        $rs = model('OrderEntry')->addOrder($appUid, $paramNew);
        if(!$rs['result']){
            $errorMsg = $rs['msg'] ?? '添加充值订单失败';
            return errorReturn($errorMsg);
        }
//        , 'payData' => $result['data']
        return successReturn(['data' => $rs['data'], 'dataAllinPay' => $result['data']]);
    }

    /**
     * 托管代收申请
     * @param $appUid
     * @param $param
     * @param $payMethod
     * @return array
     * User: 宋星 DateTime: 2020/11/19 15:10
     */
    function agentCollectApply($appUid, $param, $payMethod){
        //|| empty($param["bizFrontUrl"])
        if(empty($appUid) || empty($param['bizOrderNo'] || empty($param["backUrl"]) ||
            empty($param["bizBackUrl"])  || empty($payMethod) || empty($param['payerId']) ||
            strlen($param['payerId']) < 5 || empty($param['amount']) )
        ){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }

        $publicAccountId = $param['escrowUserId'] ?? $this->publicAccountId;
        $param["frontUrl"] = $bizFrontUrl = $param["bizFrontUrl"];
        $bizBackUrl = $param["bizBackUrl"];
        unset($param['public_account_id']);
        unset($param["bizFrontUrl"]);
        unset($param["bizBackUrl"]);
        $result = $this->allInPayOrderService->agentCollectApply($param, $payMethod);
        if(!isset($result['result']) || $result['result'] === false){    //allinpay错误直接返回
            return $result;
        }
//var_dump($result);exit;
        $paramNew = $param;
        $paramNew['payMethod'] = $payMethod;
        $paramNew['allinpay_order_no'] = $result['data']['orderNo'] ?? '';
        $paramNew['allinpay_pay_no'] = $result['data']['payInterfaceOutTradeNo'] ?? '';
        $paramNew['order_type'] = 20;
        $paramNew['public_account_id'] = $publicAccountId;
        $paramNew['front_url'] = $bizFrontUrl;
        $paramNew['back_url'] = $bizBackUrl;

        $rs = model('OrderEntry')->addOrder($appUid, $paramNew);
        if(!$rs['result']){
            $errorMsg = $rs['msg'] ?? '添加托管代收订单失败';
            return errorReturn($errorMsg);
        }

//        $returnData['bizUid']       = $param['payerId'];
        $returnData['bizUid']       = str_replace($appUid, "", $param['payerId']);
        $returnData['bizOrderNo']   = $param['bizOrderNo'];
        $returnData['amount']       = $param['amount'];
        $returnData['payMethodKey'] = key($paramNew['payMethod']);
        //'payData' => $result['data'],
        return successReturn(['data' => $returnData,   'resData' => $rs['data']->toArray(), 'dataAllinPay' => $result['data']]);
    }

    function getList($appUid, $op = []){
        if (empty($op)) {
            $where = [];
            $where[] = ['order_type', '=', OrderEntry::orderType['agentCollect']];
            $op['where'] = $where;
            $op['field'] = 'id, uid, app_uid, biz_uid, biz_order_no, allinpay_order_no, payer_id, order_entry_status, amount, remain_amount, '.
            'public_account_id, trade_code, pay_method, create_time, update_time,allinpay_pay_no,show_user_name,confirm_status,refund_status';
            $op['order'] = 'id desc';
        }

        $usersAppList = model('UsersApp')->getAllList();
        $data = model('OrderEntry')->getList($appUid, $op);
        if(!isset($data['list'])){
            return [];
        }
        $bizUids = [];
        foreach ($data['list'] as &$item) {
            $bizUids[] = $item['biz_uid'];
        }
        $userInfos = UserService::getSaasUserInfo($bizUids);

        $data = $this->formatList($data, $usersAppList, $userInfos);
//var_dump($data['list']);exit;
        return $data;
    }

    function formatList($data, $usersAppList, $userInfos){
        foreach ($data['list'] as $k => &$v) {
            if($v['type'] == 1){
                $v['payMethodVal'] = $v['pay_method']=='WECHATPAY_MINIPROGRAM_ORG'? "微信支付" : OrderEntry::payMethod[$v['pay_method']];
            }else if($v['type'] == 2){
                $v['payMethodVal'] =  OrderEntry::HsbPayMethod[$v['pay_method']] ?? '';
            }


            $v['appName'] = isset($usersAppList[$v['app_uid']]['app_name'])?$usersAppList[$v['app_uid']]['app_name']:"";
            $v['orderEntryStatusVal'] = OrderEntry::orderEntryStatus[$v['order_entry_status']];
            $v['amount'] = $v['amount'] / 100;
            $v['fee'] = $v['fee'] / 100;
            $v['remain_amount'] = $v['remain_amount'] / 100;
            $v['refunded_amount'] = $v['refunded_amount'] / 100;
            $v['refunding_amount'] = $v['refunding_amount'] / 100;
            $v['ccb_reconciliation_amount'] = $v['ccb_reconciliation_amount'] / 100;
            $v['user_info'] = $v['show_user_name'];
//            if(isset($userInfos['data'][$v['biz_uid']])){
//                $userInfo = $userInfos['data'][$v['biz_uid']];
//                $v['user_info'] =  '姓名:' . $userInfo['real_name'] . '</br>手机号:' . $userInfo['mobile'];
//            }
            $v['confirm_status_text'] = self::CONFIRM_STATUS_TEXT[$v['confirm_status']];
            $v['refund_status_txt'] = OrderEntry::REFUND_STATUS_TXT[$v['refund_status']] ?? '';

            $v['pay_time'] = empty($v['pay_time']) ? "-": date('Y-m-d H:i:s',$v['pay_time']);


            if($v['refund_status'] == OrderEntry::NO_REFUND && $v['ccb_reconciliation_amount'] ==0){
                $v['dim_status_txt'] = '待分账';
            }else if($v['refund_status'] != OrderEntry::ALL_REFUND && $v['ccb_reconciliation_amount'] >0){
                //已分账
                //没有全部退款  并且有分账  是已分账
                $v['dim_status_txt'] = '已分账';
            }else{
                //全部退款   未分账
                $v['dim_status_txt'] = '不分账';
            }
        }

        return $data;
    }
}