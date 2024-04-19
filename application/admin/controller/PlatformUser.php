<?php

namespace app\admin\controller;

use app\common\service\AllInPay\AllInPayClient;
use app\common\service\OrderProcessService;
use app\common\model\OrderEntry as OrderEntryModel;
use app\common\model\OrderProcess as OrderProcessModel;
use app\common\service\OrderWithdrawService;
use app\common\service\UserFundsService;
use app\common\service\UserService;
use app\common\tools\Http;
use think\App;
use think\facade\Hook;

class PlatformUser extends Base{


    //数据列表
    public function dataList(){
        return $this->fetch();
    }

    //异步获取列表数据
    public function ajaxList(){
        $data = input();
        $userFundsService = new UserFundsService();
        $result = $userFundsService->queryBalance(0, -10);
        $rs = $userFundsService->queryMerchantBalance();
//pj($rs);
        $userInfo = model('Users')->upUsersFunds(0, -1, $rs['data']['allAmount'], $rs['data']['freezeAmount']);

        $list = model('RealAuth')
            ->field("r.id, r.name, u.mobile, u.all_amount, r.member_type")
            ->alias('r')
            ->where('r.biz_uid', 'in', [-1,-10])
            ->leftJoin('users u', 'r.uid = u.id')
            ->select()
            ->toArray();
        $count = count($list);
        $res = ['count' => $count, 'data' => $list];
        return adminOut($res);

    }

    //查看详情, 平台提现
    public function details(){
        $data = input();
        $id = $data['id'];
        if (empty($id)) {
            return adminOut(['msg' => '参数错误']);
        }
//var_dump($id);

        $info = model('RealAuth')
            ->field("r.id, r.app_uid, r.biz_uid, r.name, u.mobile, u.all_amount, r.member_type, r.account_no, r.parent_bank_name")
            ->alias('r')
            ->where('r.id', '=', $id)
            ->leftJoin('users u', 'r.uid = u.id')
            ->findOrEmpty()
            ->toArray();

        if ($this->request->isPost()) {
            $amount = $data['amount'];
            if ($amount <= 0)   return $this -> error('请输入正确的提现金额！', null, '', 1);
            if ($amount > ($info['all_amount'] / 100))   $this -> error('余额不足！', null, '', 1);

            $orderWithdrawService = new OrderWithdrawService();
            $AllInPayClient = new AllInPayClient();
            $config = $AllInPayClient->getConfig();

            $accountSetNo = $config['account_set_no'];
            $domain = $AllInPayClient->getCallBackDomain();

//var_dump($id);
//exit;

            $param['bizUid']        = $info['biz_uid'];
            $param['bizOrderNo']    = setOrderSn('PTX');   //提现订单号
            $param['bizUserId']     = $info['biz_uid'];
            $param['accountSetNo']  = $accountSetNo;
            $param["amount"]        = $amount * 100;
            $param["fee"]           = 0;
            $param["backUrl"]       = $domain . 'AllinPay/notifyWithdraw';
            $param["bizBackUrl"]    = $domain . 'AllinPay/notifyWithdrawPlatform';
            $param["bankCardNo"]    = $info['account_no'];
            $param["bankCardPro"]   = 1;    //0：个人银行卡,1：企业对公账户, 如果不传默认为0, 平台提现，必填1
            $param['withdrawType']  = 0;
            $param["source"]        = 1;
            $param['extendInfo']    = $info['app_uid'];;
            $param['validateType']  = 0;    //0:无验证 1:短信 2:支付密码
            $param['extendParams']  = '';
//echo json_encode($param); exit;

            $result = $orderWithdrawService->withdraw($info['app_uid'], $param);
            if ($result['result']) {
                $this -> success('操作成功', 'dataList','',1);
            }
            $this -> error($result['msg']);
        }


//var_dump($info);exit;
        $this->assign('info', $info);
        return $this->fetch();
    }

    function withdraw(){
        $id = input('id', 0);
        if ($id <= 0) {
            return adminOut(['msg' => '参数错误']);
        }
        Hook::listen('app_init', input());
        $OrderProcessService = new OrderProcessService();
        $res = $OrderProcessService->confirmProcessAll($this->appUid, $id);
        return adminOut(['msg' => $res['msg'], 'data' => $res['data']]);
    }


}
