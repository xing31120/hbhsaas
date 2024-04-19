<?php


namespace app\api\controller;

use app\common\model\OrderEntry;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\AllInPay\AllInPayOrderService;
use app\common\service\OrderEntryService;
use app\common\service\OrderProcessService;
use app\common\service\OrderRefundService;
use app\common\service\OrderWithdrawService;
use app\common\service\TransService;
use app\common\service\UserFundsService;
use app\common\tools\AllInPayEnums;
use app\common\tools\SysEnums;
use think\facade\Config;
use app\api\controller\Base;
use think\Db;
use think\facade\Env;
use think\facade\Request;

class Transfer extends Base
{

    public $escrowUserId = '';
    public $accountSetNo = '';

    public function __construct()
    {
        parent::__construct();
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->accountSetNo = $config['account_set_no'];
        $this->escrowUserId = $config['escrow_user_id'];

        $params = input();
        if (empty($params['bizUid'])) {
            return apiOutError('参数错误', SysEnums::ApiParamMissing);
        }
    }

    /**
     * 转账
     * @return array
     * User: cwh  DateTime:2022/2/16 11:20
     */
    function applicationTransfer()
    {
        $data = input();
        $transService = new TransService();

        $appUid = $this->appUid;    //默认1000
        $bizUid = $data['bizUid']; //收款人biz_uid
        $bizUserId = $appUid . $bizUid;

        $param['target_biz_user_id'] = $bizUserId;
        $param['source_account_set_no'] = AllInPayEnums::STANDARD_BALANCE_ACCOUNT_SET;
        $param["amount"] = $data['amount'] ?? 0;
        $param["target_account_set_no"] = $this->accountSetNo;
        $param["extendInfo"] = $data['extendInfo'] ?? '';

        return $transService->applicationTransfer($appUid, $param);
    }


}