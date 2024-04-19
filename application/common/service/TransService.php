<?php


namespace app\common\service;

use app\common\service\AllInPay\AllInPayClient;
use app\common\service\AllInPay\AllInPayTransferService;
use app\common\tools\SysEnums;

//转账 服务
class TransService{

    public $allInPayTransferService = null ;

    const CONFIRM_STATUS_TEXT = [
        0 => '待确认',
        1 => '入款确认'
    ];

//    public $accountSetNo = '400193';
    public $publicAccountId = '';

    public function __construct(){
        $this->allInPayTransferService = new AllInPayTransferService();
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->publicAccountId = $config['escrow_user_id'];
    }

    /**
     * 平台转账
     * @param $appUid
     * @param $param
     * @return array
     * User: cwh  DateTime:2022/2/16 16:37
     */
    function applicationTransfer($appUid, $param){
        if(empty($appUid) || empty($param['target_biz_user_id']) || empty($param["source_account_set_no"]) ||
            empty($param["target_account_set_no"]) || empty($param['amount'])
        ){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }
        $params['bizTransferNo'] = setOrderSn('TN');   //转账订单号
        $params['targetBizUserId'] = $param['target_biz_user_id'];//收款会员的BizUserId
        $params['sourceAccountSetNo'] = $param['source_account_set_no'];//源账户集编号
        $params["amount"] = $param['amount'] ?? 0;//金额 分为单位
        $params["targetAccountSetNo"] = $param['target_account_set_no'];//目标账户集编号
        $params["extendInfo"] = $data['extend_info'] ?? '';//扩展信息

        $result = $this->allInPayTransferService->applicationTransfer($params);
        if(!isset($result['result']) || $result['result'] === false){    //allinpay错误直接返回
            return $result;
        }
        $paramNew = $params;
        $paramNew['transferNo'] = $result['data']['transferNo'] ?? '';

        $rs = model('Transfer')->addTransfer($appUid, $paramNew);
        if(!$rs['result']){
            $errorMsg = $rs['msg'] ?? '新增转账订单失败';
            return errorReturn($errorMsg);
        }

        $returnData['bizUid']       = str_replace($appUid, "", $params['targetBizUserId']);
        $returnData['bizTransferNo']   = $params['bizTransferNo'];
        $returnData['amount']       = $params['amount'];
        //'payData' => $result['data'],
        return successReturn(['data' => $returnData,   'resData' => $rs['data']->toArray(), 'dataAllinPay' => $result['data']]);
    }
}