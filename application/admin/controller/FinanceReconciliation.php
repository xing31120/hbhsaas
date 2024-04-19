<?php

namespace app\admin\controller;

use app\common\service\FinanceReconciliationService;
use app\common\service\FinanceSummaryDetailService;
use app\common\service\OrderEntryService;
use app\common\model\OrderEntry as OrderEntryModel;
use app\common\service\UserService;
use app\common\model\UsersApp;
use app\common\tools\Http;
use think\App;

class FinanceReconciliation extends Base
{

    private $service = null;


    /**
     * FinanceReconciliation constructor.
     * @param App|null $app
     * @param OrderEntryService $service
     */
    public function __construct(App $app = null, FinanceReconciliationService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    //数据列表
    public function dataList()
    {
        $ordr_tpcd = FinanceReconciliationService::ORDR_TPCD;
        $this->assign('ordr_tpcd', $ordr_tpcd);
        return $this->fetch();
    }

    //设置搜索的where条件
    private function setWhere($data)
    {
        $where = [];
        if (isset($data['ordr_tpcd']) && $data['ordr_tpcd'] != '' ) $where[] = ['ordr_tpcd', '=', $data['ordr_tpcd']];
        if (isset($data['main_ordr_no']) && $data['main_ordr_no'] != '') $where[] = ['main_ordr_no', '=', trim($data['main_ordr_no'])];
        if (isset($data['ordr_no']) && $data['ordr_no'] != '') $where[] = ['ordr_no', '=', trim($data['ordr_no'])];
        if (isset($data['txn_dt']) && $data['txn_dt'] != ''){
            $time = explode(' ~ ', $data['txn_dt']);
            $time[0] = str_replace("-","",$time[0]);
            $time[1] = str_replace("-","",$time[1]);
            $where[] = ['txn_dt', 'between', [$time[0], $time[1]]];
        }
        return $where;
    }

    //异步获取列表数据
    public function ajaxList()
    {
        $data = input();
        $limit = 10;//每页显示的数量
        $op['where'] = $this->setWhere($data);
        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['doPage'] = true;
        $op['field'] = '*';
        $op['limit'] = $data['limit'] ?? $limit;
        $op['order'] = 'txn_dt desc, id desc';
        $list = $this->service->getList($op);
        $res = ['count' => $list['count'], 'data' => $list['list']];
        return adminOut($res);
    }

}
