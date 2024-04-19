<?php


namespace app\common\service;

use app\common\model\reconciliation\FinanceBase;
use app\common\model\reconciliation\FinanceSummaryDetail;

//入账订单 服务
class FinanceSummaryDetailService{

    function getList($op = []){
        $data = (new FinanceSummaryDetail())->getList($op);
        if(!isset($data['list'])){
            return [];
        }
        foreach($data['list'] as $k=>$v){
            $data['list'][$k]['clrgamt'] = bcdiv($v['clrgamt'],100,2);
            $data['list'][$k]['hdcg_amt'] = bcdiv($v['hdcg_amt'],100,2);
            $data['list'][$k]['shld_subacc_amt'] = bcdiv($v['shld_subacc_amt'],100,2);
            $data['list'][$k]['clrg_stcd_txt'] = FinanceBase::CLRG_STCD[$v['clrg_stcd']];
            $data['list'][$k]['clrg_date'] = (new FinanceSummaryDetail())->formatData($v['clrg_dt']);
        }
        return $data;
    }


}