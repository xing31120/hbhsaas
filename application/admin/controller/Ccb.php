<?php

namespace app\admin\controller;

use app\common\service\AllInPay\AllInPayClient;
use app\common\service\Ccb\CcbAccountingRulesService;
use app\common\service\OrderProcessService;
use app\common\service\OrderWithdrawService;
use app\common\service\UserFundsService;
use think\facade\Hook;

class Ccb extends Base{

    //分账周期模式
    const sub_acc_cyc = [
        1 => ['label' => '日'],
        2 => ['label' => '月'],
        3 => ['label' => '月末'],
    ];
    //汇总计算订单金额后分账 0: 不汇总  1:汇总
    const is_gather = [
        0 => ['label' => '不汇总'],
        1 => ['label' => '汇总'],
    ];
    //分账模式  1-翻盘式, 2-滚动式；3-时间自定义式
    const clrg_mode = [
        1 => ['label' => '翻盘式'],
        2 => ['label' => '滚动式'],
        3 => ['label' => '时间自定义式'],
    ];

    //数据列表
    public function dataList(){
        return $this->fetch();
    }

    //异步获取列表数据
    public function ajaxList(){
        $data = input();
        $where[] = ['seq_no', '=', '2'];
        $where[] = ['app_uid', '<', '4000'];
        $where[] = ['mkt_mrch_id', 'like', '44200001617128%'];
        $list =  model("CcbAccountingRules")->where($where)->select()->toArray();;

        foreach ($list as &$item) {
            $item['sub_acc_cyc'] = self::sub_acc_cyc[$item['sub_acc_cyc']]['label'];
            $item['is_gather'] = self::is_gather[$item['is_gather']]['label'];
            $item['clrg_mode'] = self::clrg_mode[$item['clrg_mode']]['label'];
        }

//        $userFundsService = new UserFundsService();
//        $result = $userFundsService->queryBalance(0, -10);
//        $rs = $userFundsService->queryMerchantBalance();
////pj($rs);
//        $userInfo = model('Users')->upUsersFunds(0, -1, $rs['data']['allAmount'], $rs['data']['freezeAmount']);
//
//        $list = model('RealAuth')
//            ->field("r.id, r.name, u.mobile, u.all_amount, r.member_type")
//            ->alias('r')
//            ->where('r.biz_uid', 'in', [-1,-10])
//            ->leftJoin('users u', 'r.uid = u.id')
//            ->select()
//            ->toArray();

        $count = count($list);
        $result = ['count' => $count, 'data' => $list];
        return adminOut($result);

    }


}
