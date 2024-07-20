<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;
use app\common\service\workSendMessage\WorkSendMessageService;
use think\facade\Lang;

class HbhUserWallet extends SingleSubData {
    public $mcName = 'hbh_user_wallet_';
//    public $selectTime = 600;
    public $mcOpen = false;

    //钱包是否启用
    const STATUS_TRUE = 1;
    const STATUS_FALSE = 2;
    //默认支付密码
    const PAY_PASSWORD_DEFAULT = '123456';

    // 检查用户钱包
    public function checkWalletInfo($userId = 0, $id = 0){
        if(empty($id) && empty($userId)){
            return errorReturn(Lang::get('ParameterError').'-checkWallet');
        }
        $row = null;
        if($id){
            $row = self::where("id", $id)->findOrEmpty()->toArray();
        }
        if($userId){
            $row = self::where("user_id", $userId)->findOrEmpty()->toArray();
        }
        if(empty($row)){
            return errorReturn(Lang::get('CheckWalletError'));
        }

        unset($row['pay_password']);
        return successReturn(['data' => $row]);
    }

    public function createUserWallet($userId){
        if(empty($userId)){
            return errorReturn(Lang::get('CreateWalletError'));
        }

    }

    public function getWalletInfo($userId){
        $row = $this->checkWalletInfo($userId);
//pj([111,$row]);
        if($row['result']){
            return $row;
        }
        $userInfo = (new HbhUsers())->info($userId);
        if(empty($userInfo)){
            return errorReturn(Lang::get('UserError'));
        }

        //如果没有钱包, 创建钱包
        $module = request()->module();
        $date = date("Y-m-d H:i:s");
        $param = [
            'user_id'               => $userId,
            'balance'               => 0,
            'class_num'             => $userInfo['residue_quantity'],
            'frozen_balance'        => 0,
            'total_recharge_amount' => 0,
            'total_recharge_class'  => $userInfo['residue_quantity'],
            'total_used_amount'     => 0,
            'status'                => self::STATUS_TRUE,
            'pay_password'          => self::PAY_PASSWORD_DEFAULT,
            'shop_id'               => $module == 'shop' ? session('shop_id') : session('hbh_shop_id'),
            'created_at'            => $date,
            'updated_at'            => $date,
        ];
        $res = self::create($param);
        $param['id'] = $res->id;
        unset($param['pay_password']);
        return successReturn(['data' => $param]);

    }

    /**
     * Notes: 更新用户钱包-课时
     * @param $uid
     * @param float $amount 更新金额(正数), 根据$fundType决定 + 或者 -
     * @param $fundType
     * @return array
     * User: songX
     * Date: 2024/2/20 17:36:09
     */
    function updateUserWallet($uid, $fundType, $amount){
        $walletResult = $this->getWalletInfo($uid);
        $userFund = $walletResult['data'];

        //是支出类型的, 需要扣余额, 判断余额是否足够
        if (HbhUserWalletDetail::fundType[$fundType]['is_income'] == 0 && $amount > $userFund['class_num']) {
            return errorReturn(Lang::get('BalanceIsNotEnough'));
        }

        if (HbhUserWalletDetail::fundType[$fundType]['is_income'] == 1){
            //需要修改用户余额, 并且是收入类型的
            $userFund['class_num'] = bcadd($userFund['class_num'], $amount, 2);
        } else {
            //需要修改用户余额, 是支出类型的, 需要扣余额,目前只有提现支出
            $userFund['class_num'] = bcsub($userFund['class_num'], $amount, 2);
        }

        //提现支出的, 需要增加冻结金额
        if ($fundType == HbhUserWalletDetail::WITHDRAWAL_OUT) {
            $userFund['frozen_balance'] = bcadd($userFund['frozen_balance'], $amount, 2);
        }
        //提现收入的, 需要扣除冻结金额, 增加余额(上面已经增加)
        if ($fundType == HbhUserWalletDetail::WITHDRAWAL_IN) {
            $userFund['frozen_balance'] = bcsub($userFund['frozen_balance'], $amount, 2);
        }

        if(in_array($fundType, [HbhUserWalletDetail::RECHARGE])){
            // todo 增加累计充值
            $userFund['total_recharge_amount'] = bcadd($userFund['total_recharge_amount'], $amount, 2);
        }
        if(in_array($fundType, [HbhUserWalletDetail::DEDUCTION])){
            // todo 后台扣款扣除累计充值
            $userFund['total_recharge_amount'] = bcsub($userFund['total_recharge_amount'], $amount, 2);
        }
        if(in_array($fundType, [HbhUserWalletDetail::CONSUME, HbhUserWalletDetail::BALANCE_CONSUME])){
            // todo 增加累计消费
            $userFund['total_used_amount'] = bcadd($userFund['total_used_amount'], $amount, 2);
        }

        $userFund['updated_at'] = date('Y-m-d H:i:s');
        $userFund['update_time'] = time();
        unset($userFund['created_at']);
        unset($userFund['create_time']);
        //$updateUserFund  是否更新用户钱包余额, 有些方式是不需要更新用户钱包的, 例如在线支付直接消费的就不用调整钱包
        $updateUserFund = HbhUserWalletDetail::fundType[$fundType]['is_update_user_fund'] ?? 1;
//pj($userFund);
        if ($updateUserFund) {
            $resUserFund = $this->where('id',$userFund['id'])->update($userFund);
            if (!$resUserFund) {
                return errorReturn(Lang::get('UpdateWalletError'));
            }
        }
        return successReturn(['data' => $userFund]);
    }


}
