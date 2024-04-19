<?php


namespace app\common\service;

use app\common\model\reconciliation\FinanceReconciliation;

//入账订单 服务
class FinanceReconciliationService{

    const RCNCL_RSLT_STCD = [
        '00' => '初始',
        '01' => '对平',
        '03' => '交易金额不一致',
        '04' => '平台多',
        '05' => '收单多',
    ];

    const ORDR_TPCD = [
        '1' => '支付',
        '2' => '退款'
    ];


    function getList($op = []){
        $data = (new FinanceReconciliation())->getList($op);
        if(!isset($data['list'])){
            return [];
        }
        foreach($data['list'] as $k=>$v){
            $data['list'][$k]['hdcg'] = bcdiv($v['hdcg'],100,2);
            $data['list'][$k]['txnamt'] = bcdiv($v['txnamt'],100,2);
            $data['list'][$k]['rcncl_rslt_stcd_txt'] = self::RCNCL_RSLT_STCD[$v['rcncl_rslt_stcd']];
            $data['list'][$k]['ordr_tpcd_txt'] = self::ORDR_TPCD[$v['ordr_tpcd']];
            $data['list'][$k]['txn_date'] = (new FinanceReconciliation())->formatData($v['txn_dt']);
            $data['list'][$k]['txn_time'] = (new FinanceReconciliation())->formatData($v['txn_tm'],2);
        }

        return $data;
    }


}