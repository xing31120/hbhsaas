<?php

namespace app\admin\controller;

use app\common\model\reconciliation\FinanceBase;
use app\common\service\FinanceSummaryDetailService;
use app\common\service\FinanceSummaryService;
use app\common\service\OrderEntryService;
use app\common\model\OrderEntry as OrderEntryModel;
use app\common\service\UserService;
use app\common\model\UsersApp;
use app\common\tools\Http;
use think\App;

class FinanceSummaryDetail extends Base
{

    private $service = null;


    /**
     * FinanceSummaryDetail constructor.
     * @param App|null $app
     * @param OrderEntryService $service
     */
    public function __construct(App $app = null, FinanceSummaryDetailService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    //数据列表
    public function dataList()
    {
        $clrg_stcd = FinanceBase::CLRG_STCD;
        $this->assign('clrg_stcd', $clrg_stcd);
        return $this->fetch();
    }

    //设置搜索的where条件
    private function setWhere($data)
    {
        $where = [];
        if (isset($data['main_ordr_no']) && $data['main_ordr_no'] != '' ) $where[] = ['main_ordr_no', '=', $data['main_ordr_no']];
        if (isset($data['py_ordr_no']) && $data['py_ordr_no'] != '') $where[] = ['py_ordr_no', '=', trim($data['py_ordr_no'])];

        if (isset($data['rcvprt_mkt_mrch_nm'])) $where[] = ['rcvprt_mkt_mrch_nm', 'like', '%' . trim($data['rcvprt_mkt_mrch_nm']) . '%'];
        if (isset($data['clrg_stcd']) && $data['clrg_stcd'] != '') $where[] = ['clrg_stcd', '=', trim($data['clrg_stcd'])];
        if (isset($data['clrg_dt']) && $data['clrg_dt'] != ''){
            $time = explode(' ~ ', $data['clrg_dt']);
            $time[0] = str_replace("-","",$time[0]);
            $time[1] = str_replace("-","",$time[1]);
            $where[] = ['clrg_dt', 'between', [$time[0], $time[1]]];
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
        $op['order'] = 'clrg_dt desc, id desc';
        $list = $this->service->getList($op);
        $res = ['count' => $list['count'], 'data' => $list['list']];
        return adminOut($res);
    }

}
