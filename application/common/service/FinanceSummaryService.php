<?php


namespace app\common\service;

use app\common\model\reconciliation\FinanceSummary;

//入账订单 服务
class FinanceSummaryService{

    const CLRG_STCD = [
        1 => '未分账',
        2 => '分账成功',
        4 => '分账异常'
    ];


    function getList($op = []){
        $data = (new FinanceSummary())->getList($op);
        if(!isset($data['list'])){
            return [];
        }
        foreach($data['list'] as $k=>$v){
            $data['list'][$k]['to_clrgamt'] = bcdiv($v['to_clrgamt'],100,2);
            $data['list'][$k]['clrg_stcd_txt'] = self::CLRG_STCD[$v['clrg_stcd']];
            $data['list'][$k]['clrg_date'] = (new FinanceSummary())->formatData($v['clrg_dt']);
        }
        return $data;
    }


}