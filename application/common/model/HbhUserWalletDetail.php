<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;
use app\common\service\workSendMessage\WorkSendMessageService;
use think\facade\Lang;

class HbhUserWalletDetail extends SingleSubData {
    public $mcName = 'hbh_user_wallet_detail_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;


    const wallet_type_class = 1;    //课时类型
    const wallet_type_balance = 2;  //余额类型
    const wallet_type_score = 3;    //积分类型

    const pay_passageway_recharge = 1;
    const pay_passageway_balance = 2;


    const fundPassageway = [
        self::pay_passageway_recharge => [
            'value' => self::pay_passageway_recharge,
            'label' => 'Recharge',
            'label_cn' => '充值',
        ],
        self::pay_passageway_balance => [
            'value' => self::pay_passageway_balance,
            'label' => 'Balance',
            'label_cn' => '余额',
        ],
    ];

    const bizTypeRecharge = 10;     //充值订单
    const bizTypeDeduction = 40;    //消费订单
    const bizType = [
        self::bizTypeRecharge => [
            'value' => self::bizTypeRecharge,
            'label' => 'Recharge Order',
            'label_cn' => '充值订单',
        ],
        self::bizTypeDeduction => [
            'value' => self::bizTypeDeduction,
            'label' => 'Deduction Order',
            'label_cn' => '消费订单',
        ],
    ];

    // fundType 类型详解
    const RECHARGE = 10;        //充值
    const RECHARGE_ADMIN = 15;  //后台充值
    const DEDUCTION = 20;       //扣款
    const CONSUME = 30;         //消费支出
    const WITHDRAWAL_OUT = 40;  //提现支出
    const WITHDRAWAL_IN = 50;   //提现收入
    const REFUND = 60;          //退款收入
    const BALANCE_CONSUME = 70; //余额支出
    const fundType = [
        self::RECHARGE => [
            'value' => self::RECHARGE,
            'label' => 'Recharge',
            'label_cn'  => '充值',
            'is_income' => 1,
            'is_update_user_fund' => 1,
        ],
        self::RECHARGE_ADMIN => [
            'value' => self::RECHARGE_ADMIN,
            'label' => 'Backend Recharge',
            'label_cn'  => '后台充值',
            'is_income' => 1,
            'is_update_user_fund' => 1,
        ],
        self::DEDUCTION => [
            'value' => self::DEDUCTION,
            'label' => 'Deduction',
            'label_cn'  => '扣款',
            'is_income' => 2,
            'is_update_user_fund' => 1,
        ],
        self::CONSUME => [
            'value' => self::CONSUME,
            'label' => 'Consume',
            'label_cn'  => '消费',
            'is_income' => 2,
            'is_update_user_fund' => 0,
        ],
        self::WITHDRAWAL_OUT => [
            'value' => self::WITHDRAWAL_OUT,
            'label' => 'WithdrawalD Deduction',
            'label_cn'  => '提现支出',
            'is_income' => 2,
            'is_update_user_fund' => 1,
        ],
        self::WITHDRAWAL_IN => [
            'value' => self::WITHDRAWAL_IN,
            'label' => 'Withdrawal Cancel',
            'label_cn'  => '提现收入',
            'is_income' => 1,
            'is_update_user_fund' => 1,
        ],
        self::REFUND => [
            'value' => self::REFUND,
            'label' => 'Refund Income',
            'label_cn'  => '退款收入',
            'is_income' => 1,
            'is_update_user_fund' => 1,
        ],
        self::BALANCE_CONSUME => [
            'value' => self::BALANCE_CONSUME,
            'label' => 'Balance Disburse',
            'label_cn'  => '余额支出',
            'is_income' => 2,
            'is_update_user_fund' => 1,
        ],
    ];



    function addDetail($uid, $amount, $fundType,$walletType=self::wallet_type_class, $remark = '', $beforeBalance = 0, $afterBalance = 0, $bizId='', $bizType=self::bizTypeDeduction, $payPassageway = self::pay_passageway_balance){
        $admin_id = session('uid');
        $admin_id = $admin_id ?: 0;
        $state = self::fundType[$fundType]['is_income'] ?? -1;
        if($state == -1){
            return errorReturn(Lang::get('WrongFundType'));
        }
        $walletResult = (new HbhUserWallet())->getWalletInfo($uid);
        $userFund = $walletResult['data'];
        if (self::fundType[$fundType]['is_income'] == 1){
            //需要修改用户余额, 并且是收入类型的
            $change_amount = $amount;
//            $state = 1;
        } else {
            //需要修改用户余额, 是支出类型的, 需要扣余额
            $change_amount = -$amount;
        }
        $module = request()->module();
        $timeStr = date('Y-m-d H:i:s');
        $detail['user_id'] = $uid;
        $detail['wallet_type'] = $walletType;
        $detail['fund_type'] = $fundType;
        $detail['before_amount'] = $beforeBalance;
        $detail['after_amount'] = $afterBalance;
        $detail['change_amount'] = $change_amount;
        $detail['change_money'] = $amount;
        $detail['add_time'] = time();
        $detail['remark'] = $remark;
        $detail['state'] = $state;
        $detail['user_wallet_id'] = $userFund['id'] ?? 0;
        $detail['pay_passageway'] = $payPassageway;
        $detail['biz_type'] = $bizType;
        $detail['biz_id'] = $bizId;
        $detail['admin_id'] = $admin_id;
        $detail['shop_id'] = $module == 'shop' ? session('shop_id') : session('hbh_shop_id') ;
        $detail['created_at'] = $timeStr;
        $detail['updated_at'] = $timeStr;

        $resInsertId = self::insert($detail);
        if (!$resInsertId) {
            return errorReturn(Lang::get('AddWalletDetailError'));
        }
        $detail['id'] = $resInsertId;

        return successReturn(['data' => $detail]);
    }

    /**
     * Notes: 更新用户钱包和钱包明细
     * @param $uid
     * @param $fundType
     * @param null $amount
     * @param string $remark
     * @param string $bizOrderSn
     * @param int $bizType
     * @param int $payPassageway
     * @return array
     * User: songX
     * Date: 2024/2/20 17:35:26
     */
    function updateUserWalletAndDetail($uid, $amount, $fundType, $walletType=self::wallet_type_class, $remark = '', $bizOrderSn='', $bizType=self::bizTypeDeduction, $payPassageway= self::pay_passageway_balance){
        if($amount === null){
            return errorReturn(Lang::get('PleaseEnterTheAmount'));
        }

        $afterBalance = 0;
        $walletResult = (new HbhUserWallet())->getWalletInfo($uid);
        $userFund = $walletResult['data'];
        $beforeBalance = $userFund['class_num'] ?? 0;
        $res = (new HbhUserWallet())->updateUserWallet($uid, $fundType, $amount);
        if (!$res) {
            return $res;
        }
        $userFundAfter = $res['data'];
        $afterBalance = $userFundAfter['class_num'] ?? 0;
        $resDetail = $this->addDetail($uid, $amount, $fundType, $walletType, $remark, $beforeBalance, $afterBalance, $bizOrderSn, $bizType, $payPassageway);
        if (!$resDetail['result']) {
            return $resDetail;
        }

        return $res;

    }

}
