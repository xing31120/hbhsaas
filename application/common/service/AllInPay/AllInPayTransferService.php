<?php


namespace app\common\service\AllInPay;


use app\common\tools\SysEnums;

class AllInPayTransferService{

    public $receiveBizUserId = '';
    public $accountSetNo = '';

    public function __construct(){
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->accountSetNo = $config['account_set_no'];
        $this->receiveBizUserId = $config['escrow_user_id'];
    }

    /**
     * 平台转账
     * @param $param
     * @return array
     * User: cwh  DateTime:2022/2/16 16:34
     */
    function applicationTransfer($param){
        $yunClient = new AllInPayClient();
        if(empty($param["bizTransferNo"])  || empty($param["sourceAccountSetNo"]) ||
            empty($param["targetBizUserId"]) || empty($param['targetAccountSetNo'])  || $param["amount"] <= 0 ){
            return errorReturn('请求参数错误',SysEnums::ApiParamMissing);
        }
        $method = "allinpay.yunst.orderService.applicationTransfer";
        $result = $yunClient->request($method,$param);
        return $result;
    }
}