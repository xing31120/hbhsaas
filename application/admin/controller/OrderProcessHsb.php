<?php

namespace app\admin\controller;

use app\common\model\reconciliation\FinanceBase;
use app\common\service\Ccb\CcbShopInfoService;
use app\common\service\FinanceReconciliationService;
use app\common\service\FinanceSummaryDetailService;
use app\common\service\FinanceSummaryService;
use app\common\service\OrderProcessService;
use app\common\model\OrderEntry as OrderEntryModel;
use app\common\model\OrderProcess as OrderProcessModel;
use app\common\service\PhpExcelService;
use app\common\service\UserService;
use app\common\tools\Http;
use think\App;
use think\facade\Hook;
use app\common\service\AllInPay\AllInPayOrderService;
use think\facade\cache;
class OrderProcessHsb extends Base
{

    private $service = null;

    /**
     * JobStyle constructor.
     * @param App|null $app
     * @param GoodsBrandService $service
     */
    public function __construct(App $app = null, OrderProcessService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    //数据列表
    public function dataList()
    {
        $data = input();
        $dim_status = OrderEntryModel::dimStatus;
        $this->assign('dim_status', $dim_status);
        $clrg_stcd = FinanceBase::CLRG_STCD;
        $this->assign('clrg_stcd', $clrg_stcd);
        $orderEntryStatus = OrderProcessModel::orderProcessStatus;
        unset($orderEntryStatus[10]);
        unset($orderEntryStatus[20]);

        $this->assign('order_process_status', $orderEntryStatus);
        $this->assign('funName', 'process_hsb');

        $refund_status = \app\common\model\OrderEntry::REFUND_STATUS_TXT;
        $this->assign('refund_status', $refund_status);
        $order_entry_no = $data['order_entry_no'] ?? '';
        $this->assign('order_entry_no',$order_entry_no);
        return $this->fetch();
    }

    //设置搜索的where条件
    private function setWhere($data)
    {
        $where = [];
        $where[] = ['order_process_status', '<>', 10];
        $where[] = ['delete_time', '=', 0];
        $where[] = ['id', '>', 119];
        $where[] = ['type', '=', 2];

        if (isset($data['clrg_stcd']) && $data['clrg_stcd'] != '') $where[] = ['clrg_stcd', '=', trim($data['clrg_stcd'])];
        if (isset($data['refund_status']) && $data['refund_status'] != '') $where[] = ['refund_status', '=', trim($data['refund_status'])];
        if (isset($data['id']) && $data['id'] > 0) $where[] = ['id', '=', $data['id']];
        if (isset($data['biz_uid']) && $data['biz_uid'] != '') $where[] = ['biz_uid', '=', trim($data['biz_uid'])];
        if (isset($data['receiver_id']) && $data['receiver_id'] != '') $where[] = ['receiver_id', '=', trim($data['receiver_id'])];
        if (isset($data['biz_order_process_no']) && $data['biz_order_process_no'] != '') $where[] = ['biz_order_process_no', '=', trim($data['biz_order_process_no'])];
        if (isset($data['order_entry_no']) && $data['order_entry_no'] != '') $where[] = ['order_entry_no', '=', trim($data['order_entry_no'])];
        if (isset($data['allinpay_order_no']) && $data['allinpay_order_no'] != '') $where[] = ['allinpay_order_no', '=', trim($data['allinpay_order_no'])];
        if (isset($data['allinpay_pay_no']) && $data['allinpay_pay_no'] != '') $where[] = ['allinpay_pay_no', '=', trim($data['allinpay_pay_no'])];
        if (isset($data['show_order_no']) && $data['show_order_no'] != '') $where[] = ['show_order_no', '=', trim($data['show_order_no'])];
        if (isset($data['order_process_status']) && $data['order_process_status'] != '') $where[] = ['order_process_status', '=', trim($data['order_process_status'])];
        if (isset($data['show_user_name'])) $where[] = ['show_user_name', 'like', '%' . trim($data['show_user_name']) . '%'];
        if (isset($data['split_time']) && $data['split_time'] != '') {
            $time = explode('~', $data['split_time']);
            $where[] = ['split_time', 'between', [strtotime($time[0]), strtotime($time[1]) + 3600 * 24]];
        }
        if (isset($data['dim_status']) && $data['dim_status'] != ''){
            if($data['dim_status'] == OrderEntryModel::DIM_WAIT){
                //待分账 没有退款 并且还没分账
                $where[] = ['refund_status', '=', OrderEntryModel::NO_REFUND];
                $where[] = ['ccb_reconciliation_amount', '=', 0];
            }else if($data['dim_status'] == OrderEntryModel::DIM_OK){
                //已分账
                //没有全部退款  并且有分账  是已分账
                $where[] = ['refund_status', 'in', [OrderEntryModel::NO_REFUND,OrderEntryModel::PART_REFUND]];
                $where[] = ['ccb_reconciliation_amount', '>', 0];
            }else{
                //全部退款   未分账
                $where[] = ['refund_status', '=', OrderEntryModel::ALL_REFUND];
            }
        }
        if (isset($data['shop_search']) && $data['shop_search'] != "") {
            $appUids = model("CcbShopInfo")->where('mkt_mrch_nm', 'like', '%' . trim($data['shop_search']) . '%')->column('mkt_mrch_id');
//            $appUids = model('UsersApp')->where('app_name', 'like', '%' . trim($data['shop_search']) . '%')->column('app_uid');
            if (!empty($appUids)) {
                $where[] = ['mkt_mrch_id', 'in', $appUids];
            } else {
                $where[] = ['mkt_mrch_id', 'in', 0];
            }
        }
        if (isset($data['user_search']) && $data['user_search'] != '') {
            if(trim($data['user_search']) == '深圳装速配科技有限公司'){
                $where[] = ['biz_uid', 'in', '-1'];
            } else if (trim($data['user_search']) == "平台") {
                $where[] = ['biz_uid', 'in', '-1'];
            } else if (trim($data['user_search']) == '海南中装速配科技有限公司'){
                $where[] = ['biz_uid', 'in', '-10'];
            }else {
                $result = UserService::getFinMemberIdBySearch($data['user_search']);
                if (!empty($result['data'])) $where[] = ['biz_uid', 'in', $result['data']];
            }
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
        $op['order'] = 'create_time desc, id desc';
        $list = $this->service->getList($this->appUid, $op);

        $shopList = (new CcbShopInfoService())->getAll('');
        $shopList = array_column($shopList['data'], null, 'mkt_mrch_id');
        foreach ($list['list'] as &$item) {
            $mkt_mrch_id = $item['mkt_mrch_id'] ?? -1;
            $shopInfo = $shopList[$mkt_mrch_id] ?? [];

            $item['appName'] = $shopInfo['mkt_mrch_nm'] ?? '';
            $item['clrg_stcd_txt'] = FinanceBase::CLRG_STCD[$item['clrg_stcd']] ?? '';
            $item['split_time'] = empty($item['split_time']) ? '-' : date('Y-m-d',$item['split_time']);
        }

        $res = ['count' => $list['count'], 'data' => $list['list']];
        return adminOut($res);

    }

    function exportProcess(){
        $data = input();
        $op['where'] = $this->setWhere($data);
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'create_time desc, id desc';
        $list = $this->service->getList($this->appUid, $op);

        foreach ($list['list'] as &$item) {
            $item['amount'] = str_replace('</br>', PHP_EOL, $item['amount']);
        }
        $config = [
            ['width'=>10,'title'=>'ID','column'=>'id'],
            ['width'=>15,'title'=>'商家','column'=>'appName'],
            ['width'=>30,'title'=>'分账订单号','column'=>'biz_order_process_no'],
            ['width'=>35,'title'=>'通商云订单号','column'=>'allinpay_order_no'],
            ['width'=>30,'title'=>'原商家交易单号','column'=>'order_entry_no'],
            ['width'=>30,'title'=>'业务订单号','column'=>'show_order_no'],
            ['width'=>35,'title'=>'分账金额','column'=>'remain_amount'],
            ['width'=>15,'title'=>'退款中金额','column'=>'refunding_amount'],
            ['width'=>15,'title'=>'已退款金额','column'=>'refunded_amount'],
            ['width'=>15,'title'=>'退款状态','column'=>'refund_status_txt'],
            ['width'=>15,'title'=>'手续费','column'=>'fee'],
            ['width'=>15,'title'=>'分账状态','column'=>'dim_status_txt'],
            ['width'=>20,'title'=>'创建时间','column'=>'create_time'],
            ['width'=>20,'title'=>'分账时间','column'=>'split_time'],
        ];
        $title = '惠市宝分账订单';
        $res = PhpExcelService::exportNormal($list['list'],$config,$title);
        PhpExcelService::excelOut($title,$res);
    }

}
