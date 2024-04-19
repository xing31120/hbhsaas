<?php

namespace app\admin\controller;

use app\common\service\OrderEntryService;
use app\common\model\OrderEntry as OrderEntryModel;
use app\common\service\PhpExcelService;
use app\common\service\UserService;
use app\common\model\UsersApp;
use app\common\tools\Http;
use think\App;

class OrderEntryHsb extends Base
{

    private $service = null;


    /**
     * JobStyle constructor.
     * @param App|null $app
     * @param GoodsBrandService $service
     */
    public function __construct(App $app = null, OrderEntryService $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    //数据列表
    public function dataList()
    {
        $dim_status = OrderEntryModel::dimStatus;
        $this->assign('dim_status', $dim_status);
        $payMethod = OrderEntryModel::HsbPayMethod;
        $orderEntryStatus = OrderEntryModel::orderEntryStatus;
//        unset($orderEntryStatus['0']);
        unset($orderEntryStatus['20']);
//        unset($orderEntryStatus['30']);
        $orderEntryStatus['30'] = '部分分账';
        $this->assign('order_entry_status', $orderEntryStatus);
        $confirmStatus = [
            0 => '待确认',
            1 => '入款确认'
        ];
        $this->assign('confirm_status', $confirmStatus);
        $this->assign('pay_method', $payMethod);
        $this->assign('funName', 'entry_hsb');
        $refund_status = \app\common\model\OrderEntry::REFUND_STATUS_TXT;
        $this->assign('refund_status', $refund_status);

        return $this->fetch();
    }

    //设置搜索的where条件
    private function setWhere($data)
    {
        $where = [];
        $where[] = ['order_type', '=', \app\common\model\OrderEntry::orderType['agentCollect']];
//        $where[] = ['order_entry_status', 'not in', [0,20]];
        $where[] = ['type', '=', 2];
        $where[] = ['order_entry_status','=',OrderEntryModel::ALL_IN_PAY_COMPLETE];
        if (isset($data['refund_status']) && $data['refund_status'] != '') $where[] = ['refund_status', '=', trim($data['refund_status'])];
        if (isset($data['confirm_status']) && $data['confirm_status'] != '' ) $where[] = ['confirm_status', '=', $data['confirm_status']];
        if (isset($data['biz_uid']) && $data['biz_uid'] != '') $where[] = ['biz_uid', '=', trim($data['biz_uid'])];
        if (isset($data['biz_order_no']) && $data['biz_order_no'] != '') $where[] = ['biz_order_no', '=', trim($data['biz_order_no'])];
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
        if (isset($data['pay_method']) && $data['pay_method'] != ''){
            if($data['pay_method'] == 'WECHAT_PUBLIC_ORG'){
                $where[] = ['pay_method', 'in', ['WECHAT_PUBLIC_ORG','WECHATPAY_MINIPROGRAM_ORG']];
            }else{
                $where[] = ['pay_method', '=', $data['pay_method']];
            }
        }
        if (isset($data['order_entry_status']) && $data['order_entry_status'] != '') $where[] = ['order_entry_status', '=', trim($data['order_entry_status'])];
        if (isset($data['allinpay_order_no']) && $data['allinpay_order_no'] != '') $where[] = ['allinpay_order_no', '=', trim($data['allinpay_order_no'])];
        if (isset($data['allinpay_pay_no']) && $data['allinpay_pay_no'] != '') $where[] = ['allinpay_pay_no', '=', trim($data['allinpay_pay_no'])];
        if (isset($data['public_account_id']) && $data['public_account_id'] != '') $where[] = ['public_account_id', '=', trim($data['public_account_id'])];
        if (isset($data['show_order_no']) && $data['show_order_no'] != '') $where[] = ['show_order_no', '=', trim($data['show_order_no'])];
        if (isset($data['show_user_name'])) $where[] = ['show_user_name', 'like', '%' . trim($data['show_user_name']) . '%'];
        if(isset($data['shop_search'])&&$data['shop_search']!=""){
            $appUids = model('UsersApp')->where('app_name','like', '%' . trim($data['shop_search']) . '%')->column('app_uid');
            if(!empty($appUids)){
                $where[] = ['app_uid', 'in', $appUids];
            }else{
                $where[] = ['app_uid', 'in', 0];
            }
        }
        if(isset($data['user_search'])&&$data['user_search']!=''){
//            $result = UserService::getFinMemberIdBySearch($data['user_search']);
//            if(!empty($result['data'])) $where[] = ['biz_uid', 'in', $result['data']];
            $where[] = ['show_user_name','like','%' . trim($data['user_search']) . '%'];
        }
        if (isset($data['update_time']) && $data['update_time'] != ''){
            $time = explode('~', $data['update_time']);
            $where[] = ['update_time', 'between', [strtotime($time[0]), strtotime($time[1]) + 3600 * 24]];
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
//pj($op);
        $list = $this->service->getList($this->appUid, $op);


        $res = ['count' => $list['count'], 'data' => $list['list']];
        return adminOut($res);
    }

    /**
     * 财务确认入款单
     */
    public function confirmStatus(){
        $data = input();
        $id = $data['id'];
        $result = model('OrderEntry')->updateById($id,0,['confirm_status'=>1]);
        $orderEntryInfo = model('OrderEntry')->info($id,0);
        model('OrderProcess')->where('order_entry_no','=',$orderEntryInfo['biz_order_no'])->update(['confirm_status'=>1]);
        if($result){
            return adminOut(['msg' => '操作成功', 'data' => []]);
        }
        return adminOut(['msg' => '操作失败']);
    }

    function exportEntry(){
        $data = input();
        $op['where'] = $this->setWhere($data);
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'create_time desc, id desc';
        $list = $this->service->getList($this->appUid, $op);

        $config = [
            ['width'=>10,'title'=>'ID','column'=>'id'],
            ['width'=>15,'title'=>'商家','column'=>'appName'],
            ['width'=>20,'title'=>'付款会员','column'=>'user_info'],
            ['width'=>30,'title'=>'商家交易单号','column'=>'biz_order_no'],
            ['width'=>30,'title'=>'渠道交易流水号','column'=>'allinpay_pay_no'],
            ['width'=>35,'title'=>'通商云订单号','column'=>'allinpay_order_no'],
            ['width'=>30,'title'=>'业务订单号','column'=>'show_order_no'],
            ['width'=>15,'title'=>'交易金额','column'=>'amount'],
            ['width'=>15,'title'=>'交易手续费','column'=>'fee'],
            ['width'=>15,'title'=>'未分账金额','column'=>'remain_amount'],
            ['width'=>15,'title'=>'已分账金额','column'=>'ccb_reconciliation_amount'],
            ['width'=>15,'title'=>'退款中金额','column'=>'refunding_amount'],
            ['width'=>15,'title'=>'已退款金额','column'=>'refunding_amount'],
            ['width'=>15,'title'=>'退款状态','column'=>'refund_status_txt'],
            ['width'=>15,'title'=>'分账状态','column'=>'dim_status_txt'],
            ['width'=>25,'title'=>'支付方式','column'=>'payMethodVal'],
            ['width'=>20,'title'=>'创建时间','column'=>'create_time'],
            ['width'=>20,'title'=>'交易时间','column'=>'pay_time'],
        ];
        $title = '惠市宝代收订单';
        $res = PhpExcelService::exportNormal($list['list'],$config,$title);
        PhpExcelService::excelOut($title,$res);
    }

}
