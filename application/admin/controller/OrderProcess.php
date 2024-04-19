<?php

namespace app\admin\controller;

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
class OrderProcess extends Base
{

    const INSPECT_COMPLETED_ORDERS =  'inspect_completed_orders_';

    const INSPECT_COMPLETED_ORDERS_PROCESS =  'inspect_completed_orders_process';

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
        $orderEntryStatus = OrderProcessModel::orderProcessStatus;
        unset($orderEntryStatus[10]);
        unset($orderEntryStatus[20]);

        $confirmStatus = [
            0 => '待确认',
            1 => '入款确认'
        ];

        $this->assign('order_process_status', $orderEntryStatus);
        $this->assign('confirm_status', $confirmStatus);
        $this->assign('funName', 'process');
        return $this->fetch();
    }

    //设置搜索的where条件
    private function setWhere($data)
    {
        $where = [];
        $where[] = ['order_process_status', '<>', 10];
        $where[] = ['delete_time', '=', 0];
        $where[] = ['id', '>', 119];
        $where[] = ['type', '=', 1];
        if (isset($data['confirm_status']) && $data['confirm_status'] != '' ) $where[] = ['confirm_status', '=', $data['confirm_status']];
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
        if (isset($data['update_time']) && $data['update_time'] != '') {
            $time = explode('~', $data['update_time']);
            $where[] = ['update_time', 'between', [strtotime($time[0]), strtotime($time[1]) + 3600 * 24]];
        }
        if (isset($data['shop_search']) && $data['shop_search'] != "") {
            $appUids = model('UsersApp')->where('app_name', 'like', '%' . trim($data['shop_search']) . '%')->column('app_uid');
            if (!empty($appUids)) {
                $where[] = ['app_uid', 'in', $appUids];
            } else {
                $where[] = ['app_uid', 'in', 0];
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
        $res = ['count' => $list['count'], 'data' => $list['list']];
        return adminOut($res);

    }

    //查看
    public function read()
    {
        $data = input();
        $id = $data['id'];
        $info = model('Users')->info($id, $this->appUid);
        $info['member_type_name'] = $info['member_type'] == 2 ? '企业会员' : '个人会员';
        if ($info['sign_contract_status'] == 30) {
            $info['contract_no'] = model('SignContract')->infoByBizUserId($info['biz_user_id'])['contract_no'];
        }

        $this->assign('status', model('Users')->status);
        $this->assign('realAuthStatus', model('Users')->realAuthStatus);
        $this->assign('signContractStatus', model('Users')->signContractStatus);
        $this->assign('info', $info);
        return $this->fetch();
    }

    function confirmProcess()
    {
        $id = input('id', 0);
        if ($id <= 0) {
            return adminOut(['msg' => '参数错误']);
        }
        Hook::listen('app_init', input());
        $OrderProcessService = new OrderProcessService();
        $res = $OrderProcessService->confirmProcessAll($this->appUid, $id);
        return adminOut(['msg' => $res['msg'], 'data' => $res['data']]);
    }

    /**
     * show_order_no 业务系统的业务订单号 校准
     */
    public function repair()
    {
        $orderEntry = model('OrderProcess')->where('show_order_no', '=', '')->select();
        if (!empty($orderEntry) && !is_array($orderEntry)) {
            $orderEntry = $orderEntry->toArray();
        }
        echo '需修复的数据:' . '</br>';
        print_r(json_encode($orderEntry, JSON_UNESCAPED_UNICODE));
        echo '</br>';
        $backUrl = config('saas.saas_api_server') . "RepairFin/init";
        $params = [
            'fin_order' => json_encode($orderEntry, JSON_UNESCAPED_UNICODE)
        ];
        $result = Http::post($backUrl, $params);
        $result = is_array($result) ? $result : json_decode($result, true);
        echo 'SAAS返回:' . '</br>';
        print_r(json_encode($result, JSON_UNESCAPED_UNICODE));
        echo '</br>';
        if (!empty($result['data'])) {
            $list = $result['data'];
            foreach ($list as $k => $v) {
                model('OrderProcess')
                    ->where('order_entry_no', '=', $v['pay_order_sn'])
                    ->where('show_order_no', '=', '')
                    ->update(['show_order_no' => $v['biz_order_sn']]);
            }
        }
        print_r('业务系统的业务订单号 校准 操作成功');
        die();
    }

    /**
     * 寻找 包含未实名的orderProcess 记录
     */
    public function getOrderProcessByNoAuth()
    {
        $orderProcessUids = model('OrderProcess')
            ->where('order_process_status', '=', 0)
            ->group('biz_uid')
            ->column('biz_uid');
        if (!empty($orderProcessUids)) {
            $noAuthUids = model('Users')
                ->where('biz_uid', 'in', $orderProcessUids)
                ->where('real_auth_status', '<>', 30)
                ->column('biz_uid');
            $userInfos = UserService::getSaasUserInfo($noAuthUids);
            $orders = model('OrderProcess')
                ->where('order_process_status', '=', 0)
                ->where('biz_uid', 'in', $noAuthUids)
                ->select();
            $i = 0 ;
            foreach ($orders as $k => $v) {
                if($v['biz_uid'] != -1){
                    $v['user_info'] = "biz_uid:".$v['biz_uid'];
                    if(isset($userInfos['data'][$v['biz_uid']])){
                        $userInfo = $userInfos['data'][$v['biz_uid']];
                        $v['user_info'] = 'biz_uid:'.$v['biz_uid'].',昵称:' . $userInfo['nick_name'] . ',姓名:' . $userInfo['real_name'] . ',手机号:' . $userInfo['mobile'];
                    }
                    $i ++ ;
                    echo '商户分账单号:'.$v['biz_order_process_no'].'存在未实名认证用户:'.$v['user_info'].'</br>';
                }
            }
            if($i == 0){
                echo '暂未有 未实名 需要确认的分账订单。';
            }
        }
        die();
    }

    public static function getAllInPayOrder($param){
        $cacheKey = self::INSPECT_COMPLETED_ORDERS.$param["bizOrderNo"];
        if(!empty(cache($cacheKey))){
            return json_decode($cacheKey,true);
        }
        $allInPayOrderService = new AllInPayOrderService();
        $result = $allInPayOrderService->getOrderDetail($param);
        if(!empty($result)){
            cache($cacheKey,json_encode($result));
        }
        return $result;
    }

    public static function getAllInPayOrderProcess($bizOrderNo){
        $cacheKey = self::INSPECT_COMPLETED_ORDERS_PROCESS.$bizOrderNo;
        if(!empty(cache($cacheKey))){
            return json_decode($cacheKey,true);
        }
        $allInPayOrderService = new AllInPayOrderService();
        $result = $allInPayOrderService->getOrderSplitRuleListDetail($bizOrderNo);
        if(!empty($result)){
            cache($cacheKey,json_encode($result));
        }
        return $result;
    }

    /**
     * 检查已分账的订单是否有异常
     */
    public function inspectCompletedOrders(){
        $orderProcessUids = model('OrderProcess')
            ->where('order_process_status', '=', 30)
            ->group('biz_uid')
            ->column('biz_uid');
        $userInfos = UserService::getSaasUserInfo($orderProcessUids);
        $orderProcessList = model('OrderProcess')
            ->where('order_process_status', '=', 30)
            ->select();
        foreach ($orderProcessList as $k=>$v){
            if(empty($v['order_entry_no'])){
                continue;
            }
            $param = ["bizOrderNo"=>$v['order_entry_no']] ;
            $result = self::getAllInPayOrder($param);
            $bizOrderInfo = $result['data'];
            if($v['biz_uid'] == -1){
                $v['user_info'] = '平台';
            }else{
                $v['user_info'] = "biz_uid:".$v['biz_uid'];
                if(isset($userInfos['data'][$v['biz_uid']])){
                    $userInfo = $userInfos['data'][$v['biz_uid']];
                    $v['user_info'] = 'biz_uid:'.$v['biz_uid'].',昵称:' . $userInfo['nick_name'] . ',姓名:' . $userInfo['real_name'] . ',手机号:' . $userInfo['mobile'];
                }
            }
            if(!$result['result']){
               echo '商户分账订单号'.$v['biz_order_process_no'].',用户信息:'.$v['user_info'].',错误消息:<span style="color:red;">'.$result['msg'].'</span></br></br>';
            }
            $orderStr = '';
            //入款单
            if(!empty($bizOrderInfo['orderStatus'])){
                $orderStr = '该订单在分账系统入款单状态 <span style="color:green;font-weight:700">已支付</span>。商户分账订单号'.$v['biz_order_process_no'].',业务单号:'.$v['order_entry_no'].',通商云订单状态:';
                $errAmount = '';
                $orderEntry = model('OrderEntry')->where('biz_order_no','=',$v['order_entry_no'])->find();
                if(!empty($orderEntry)&&$bizOrderInfo['amount']!=$orderEntry['amount']){
                     $errAmount = '<span style="color:red;">分账订单金额错误!系统中订单金额为'.bcdiv($v['amount'],100,2).'元</span>';
                }
                switch ($bizOrderInfo['orderStatus']){
                    case 1:
                        $orderStr = $orderStr.'<span style="color:yellow;font-weight:700">未支付</span>。'.'收款人:'.$v['user_info'].'</br></br>';
                        break;
                    case 3:
                        $orderStr = $orderStr.'<span style="color:yellow;font-weight:700">交易过程中出现错误</span>。错误原因:'.$bizOrderInfo['errorMessage'].',收款人:'.$v['user_info'];
                        break;
                    case 4:
                        $orderStr = $orderStr.'<span style="color:green;font-weight:700">交易成功</span>,收款人:'.$v['user_info'].',订单金额:'.bcdiv($bizOrderInfo['amount'],100,2).'元。'.$errAmount;
                        break;
                    case 5:
                        $orderStr = $orderStr.'<span style="color:green;font-weight:700">交易成功 发生退款</span>,收款人:'.$v['user_info'].',退款单号:'.$bizOrderInfo['oriOrderNo'].',商户订单号:'.$bizOrderInfo['bizOrderNo'].',订单金额:'.bcdiv($bizOrderInfo['amount'],100,2).'元。'.$errAmount;
                        break;
                    case 6:
                        $orderStr = $orderStr.'<span style="color:red;font-weight:700">订单关闭</span>,收款人:'.$v['user_info'];
                        break;
                    case 99:
                        $orderStr = $orderStr.'<span style="color:red;font-weight:700">交易进行中</span>,收款人:'.$v['user_info'];
                        break;
                }
            }
            if(!empty($orderStr)){
                echo  $orderStr.'</br></br>';
            }
            //分账单
            $result = self::getAllInPayOrderProcess($param['bizOrderNo']);
            var_dump(json_encode($result));
            echo '</br></br>';
        }
        echo  '检查所有已支付分账订单完毕'.'</br>';
        die();
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
            ['width'=>35,'title'=>'分账金额','column'=>'amount'],
            ['width'=>15,'title'=>'订单状态','column'=>'orderProcessStatusVal'],
            ['width'=>15,'title'=>'入款状态','column'=>'confirm_status_text'],
            ['width'=>20,'title'=>'创建时间','column'=>'create_time'],
            ['width'=>20,'title'=>'交易时间','column'=>'pay_time'],
        ];
        $title = '通联分账订单';
        $res = PhpExcelService::exportNormal($list['list'],$config,$title);
        PhpExcelService::excelOut($title,$res);
    }
}
