<?php


namespace app\common\service\Ccb;

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

    public $mcName = 'ccb_order_entry_service_';
    public $allInPayOrderService = null ;

    const CONFIRM_STATUS_TEXT = [
        0 => '待确认',
        1 => '入款确认'
    ];

    public function __construct(){
        $this->allInPayOrderService = new AllInPayOrderService();
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->publicAccountId = $config['escrow_user_id'];
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
            'public_account_id, trade_code, pay_method, create_time, update_time,allinpay_pay_no,show_user_name,confirm_status';
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

        foreach ($data['list'] as $k => &$v) {
            $v['appName'] = isset($usersAppList[$v['app_uid']]['app_name'])?$usersAppList[$v['app_uid']]['app_name']:"";
            $v['orderEntryStatusVal'] = OrderEntry::orderEntryStatus[$v['order_entry_status']];
            $v['amount'] = $v['amount'] / 100;
            $v['remain_amount'] = $v['remain_amount'] / 100;
            $v['payMethodVal'] = $v['pay_method']=='WECHATPAY_MINIPROGRAM_ORG'?"微信支付":OrderEntry::payMethod[$v['pay_method']];
            $v['user_info'] = $v['show_user_name'];
            if(isset($userInfos['data'][$v['biz_uid']])){
                $userInfo = $userInfos['data'][$v['biz_uid']];
                $v['user_info'] =  '姓名:' . $userInfo['real_name'] . '</br>手机号:' . $userInfo['mobile'];
            }
            $v['confirm_status_text'] = self::CONFIRM_STATUS_TEXT[$v['confirm_status']];
        }

//var_dump($data['list']);exit;
        return $data;
    }


}