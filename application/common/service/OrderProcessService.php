<?php


namespace app\common\service;

use AllInPay\Log\Log;
use app\common\amqp\BizProducer;
use app\common\model\OrderEntry;
use app\common\model\OrderProcess;
use app\common\model\OrderRefund;
use app\common\model\UsersApp;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\workSendMessage\WorkSendMessageService;
use app\common\tools\SysEnums;
use app\common\service\AllInPay\AllInPayOrderService;
use think\Db;
use think\facade\Config;

//分账订单 服务
class OrderProcessService
{

    public $allInPayOrderService = null;
    private static $model = null;

    const CONFIRM_STATUS_TEXT = [
        0 => '待确认',
        1 => '入款确认'
    ];

    public function __construct()
    {
        $this->allInPayOrderService = new AllInPayOrderService();
    }

    public static function getModel($appUid)
    {
        if (self::$model == null) {
            self::$model = new OrderProcess(['app_uid' => $appUid]);
        }
        return self::$model;
    }

    /**
     * 托管代付
     * @param $appUid
     * @param $param
     * @param $collectPayList
     * @param $splitRuleList
     * @return array
     * User: 宋星 DateTime: 2020/11/19 15:10
     */
    function signalAgentPay($appUid, $param, $collectPayList, $splitRuleList)
    {

        if (empty($appUid) || empty($param["bizOrderNo"]) || empty($param['amount']) ||
            empty($param["accountSetNo"]) || empty($collectPayList) || empty($splitRuleList) ||
            empty($param["bizUserId"]) || strlen($param['bizUserId']) < 5 || empty($param['tradeCode']) || empty($param['bizBackUrl'])
        ) {
            return errorReturn('参数错误', SysEnums::ApiParamMissing);
        }

        if (empty($collectPayList) || !isset($collectPayList[0]["bizOrderNo"])) {
            return errorReturn('代收订单信息为空');
        }

        $orderEntryNo = $collectPayList[0]["bizOrderNo"];

        //收款人列表总金额
        $collectAmountArray = array_column($collectPayList, 'amount', 'bizOrderNo');
        $collectSumAmount = array_sum($collectAmountArray);
//var_dump($collectSumAmount);
//var_dump($param["amount"]);
        if ($collectSumAmount != $param["amount"]) {
            return errorReturn('代收订单总金额错误');
        }

        $collectCount = count($collectPayList);
        if ($collectCount == 1) {
            $orderEntry['count'] = null;
            $orderEntry['list'][] = model('OrderEntry')->infoByBizOrderNo($appUid, $orderEntryNo);
        } else {
            $bizOrderNoList = array_column($collectPayList, 'bizOrderNo');
            $op['where'][] = ['biz_order_no', 'in', $bizOrderNoList];
            $orderEntry = model('OrderEntry')->getList($appUid, $op);
        }

        if (empty($orderEntry) || (isset($orderEntry['list']) && $collectCount > 1 && $collectCount != count($orderEntry['list']))) {
            return errorReturn('代收订单信息数量错误');
        }


//var_dump($param);
//var_dump($collectPayList);
//var_dump($splitRuleList);
//exit;
        if (empty($splitRuleList) || !isset($splitRuleList[0])) {
            $splitRuleList[0]["bizUserId"] = $param['bizUserId'];
            $splitRuleList[0]["accountSetNo"] = $param['accountSetNo'];
            $splitRuleList[0]["amount"] = $param['amount'];
            $splitRuleList[0]["fee"] = $param['fee'];
            $splitRuleList[0]["remark"] = "中间账户直接收款,未分账";
            $processList = $splitRuleList;
        } else {
            $processList = $splitRuleList;
            foreach ($splitRuleList as &$r) {
                unset($r['bizOrderNo']);
            }
        }
        $result = [];
        //分账订单号
        $num = 0;
        $bizOrderProcessNo = $param['bizOrderNo'];
        $param['bizOrderNo'] = $bizOrderProcessNo;
        $bizBackUrl = $param["bizBackUrl"];
        unset($param["bizBackUrl"]);
//var_dump($param);exit;
//var_dump($collectPayList);
//var_dump($splitRuleList);


        Db::startTrans();

        /**
         * 增加分账处理订单
         */
        $sumAmount = 0; //分账列表总金额
        $arrProcessOrder = [];
//var_dump($processList);exit;
        foreach ($processList as $item) {
            if (!isset($item['bizUserId']) || !isset($item['amount']) || !isset($item['fee'])) {
                Db::rollback();
                return errorReturn('分账参数错误', SysEnums::ApiParamMissing);
            }
            //添加分账订单
            $data = [];
            if ($item['bizUserId'] == '#yunBizUserId_B2C#' && $item['accountSetNo'] == 100001) {
                $userInfo['id'] = -1;
                $userInfo['member_type'] = 1;   //1 转账给平台
                $data['biz_uid'] = -1;
            } elseif ($item['bizUserId'] == '-10') {
                $userInfo['id'] = -10;
                $userInfo['member_type'] = 1;   //1 转账给平台
                $data['biz_uid'] = -10;
            } else {
                $data['biz_uid'] = str_replace($appUid, "", $item['bizUserId']);
                $userInfo = model('Users')->infoByBizUid($appUid, $data['biz_uid']);
            }

//var_dump($data['biz_uid'].'@@@'.$item['bizUserId'].'###'.$userInfo['id']);
            $bizOrderNo = $item['bizOrderNo'] ?? $orderEntryNo;
            $data['uid'] = $userInfo['id'];
            $data['app_uid'] = $appUid;
            $data['biz_order_process_no'] = $bizOrderProcessNo;
            $data['process_num'] = $num;
            $data['order_entry_no'] = $bizOrderNo;
            $data['allinpay_order_no'] = '';
            $data['receiver_id'] = $param['bizUserId'];
            $data['member_type'] = $userInfo['member_type'];
            $data['account_set_no'] = $param['accountSetNo'] ?? '';
            $data['order_process_status'] = OrderProcess::WAIT_PAY;
            $data['amount'] = $item['amount'] ?? 0;
            $data['fee'] = $item['fee'] ?? 0;
            $data['trade_code'] = $param['trade_code'] ?? '4001';
            $data['back_url'] = $bizBackUrl ?? '';
            $data['source'] = $param['source'] ?? 1;
            $data['remark'] = $item['remark'] ?? '';
            $data['extend_info'] = $param['extendInfo'] ?? '';
            $data['extend_params'] = $param['extendParams'] ?? '';
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data["show_user_name"] = $item['showUserName'] ?? '';
            $data["show_order_no"] = $item['showOrderNo'] ?? '';
            //财务确认
            $confirmStatus = model('OrderEntry')->where('biz_order_no','=',$bizOrderNo)->value('confirm_status');
            $data["confirm_status"] = empty($confirmStatus) ? 0 : 1;
            if (!empty($userInfo['id'])) {
                $arrProcessOrder[] = $data;
                $sumAmount += $item['amount'];
                $num++; //分账订单序号 自增
            }
        }
//exit();
        $sumAmount += $param["fee"];
//echo json_encode($processList);exit;
        $rs = model('OrderProcess')->insertAll($appUid, $arrProcessOrder);
//var_dump($rs);
        if (!$rs) {
            Db::rollback();
            $errorMsg = $rs ?? '添加分账订单失败';
            return errorReturn($errorMsg, 11111111, $processList);
        }
        if ($sumAmount != $param["amount"]) {  //分账列表每笔的金额之和 要等于 总金额
            Db::rollback();
            return errorReturn('分账总金额错误', SysEnums::SumAmountError);
        }

        Db::commit();

        //机器人-分账确认通知key
        $is_test = config('amqp.is_test'); //正式环境 false, 测试环境true
        $app_debug = config('app.app_debug');   //正式环境 false, 测试环境true
        if (!$is_test && !$app_debug) {
            $key = '7c686d0a-7cef-48fc-b341-c70f2aea1d3d';
            $content = "有收款订单{$orderEntryNo}待确认分账!";
            (new WorkSendMessageService($key))->sendMarkDown($content);
        }

        return successReturn(['data' => ['bizOrderNo' => $bizOrderProcessNo, 'payStatus' => 'success']]);

    }

    //allinpay 回调修改状态
    function notifyAgentPay($bizContent)
    {
        $appUid = $bizContent['extendInfo'];
        $bizOrderNo = $bizContent['bizOrderNo'];

        //修改订单状态,
        $orderProcessList = model('OrderProcess')->allInPayCompleteByNo($appUid, $bizOrderNo);
        if (!isset($orderProcessList['result']) || !$orderProcessList['result']) {
            return errorReturn('托管订单分配失败!');
        }
        //修改用户余额
        foreach ($orderProcessList['data'] as $row) {
            $userInfo = model('Users')->plusUserFund($row['app_uid'], $row['biz_uid'], $row['amount']);
            if (!isset($userInfo['result']) || !$userInfo['result']) {
                return errorReturn('托管订单余额更新失败!');
            }
        }
        return successReturn(['data' => $orderProcessList]);


    }

    function getList($appUid, $op = [])
    {
        if (empty($op)) {
            $op['where'] = [];
            $op['field'] = 'id, uid, app_uid, biz_uid, biz_order_process_no, allinpay_order_no, order_entry_no, process_num, amount, receiver_id, ' .
                ' order_process_status, remark, create_time, update_time,show_user_name,confirm_status,refund_status';
            $op['order'] = 'id desc';
        }
        $op['group'] = 'biz_order_process_no';
        $usersAppList = model('UsersApp')->getAllList();
        $data = model('OrderProcess')->getList($appUid, $op);
        if (!isset($data['list'])) {
            return [];
        }
        $bizOrderProcessNos = [];
        foreach ($data['list'] as &$item) {
            $bizOrderProcessNos[] = $item['biz_order_process_no'];
        }
        $bizUids = model('OrderProcess')->whereIn('biz_order_process_no',$bizOrderProcessNos)->column('biz_uid');
        $userInfos = UserService::getSaasUserInfo($bizUids);
        foreach ($data['list'] as $k => &$v) {
            $v['appName'] = isset($usersAppList[$v['app_uid']]['app_name']) ? $usersAppList[$v['app_uid']]['app_name'] : "";
            $v['orderProcessStatusVal'] = OrderProcess::orderProcessStatus[$v['order_process_status']];

            $v['amount'] = bcdiv($v['amount'] , 100,2);
            $v['remain_amount'] = bcdiv($v['remain_amount'] , 100,2);
            $v['refunded_amount'] = bcdiv($v['refunded_amount'] , 100,2);
            $v['refunding_amount'] = bcdiv($v['refunding_amount'] , 100,2);
            $v['ccb_reconciliation_amount'] = bcdiv($v['ccb_reconciliation_amount'] , 100,2);
            $v['fee']                       = $v['fee'] / 100;

            $bizOrderProcessNo = model('OrderProcess')->where('biz_order_process_no', '=', $v['biz_order_process_no'])->select()->toArray();
            $v['user_info'] = '';
            $v['amount'] = '';
            foreach ($bizOrderProcessNo as $key => $value) {
                $nameStr = $value['show_user_name'] . '</br>';
                $amountStr = ' '.($value['amount'] / 100).' 元'.'</br>';
                $amountAllStr = empty($value['show_user_name']) ?  $amountStr : '( '.$value['show_user_name'].' )' .$amountStr;
                if (isset($userInfos['data'][$value['biz_uid']])) {
                    $userInfo = $userInfos['data'][$value['biz_uid']];
                    $nameStr = '' . $userInfo['real_name'] . ' ( ' . $userInfo['mobile'] . ' )</br>';
                    $amountAllStr = '' . $userInfo['real_name'] . ' ( ' . $userInfo['mobile'] ." ) ,". $amountStr;
                }
                if ($value['biz_uid'] == -1) {
                    $nameStr = '深圳装速配科技有限公司' . '</br>';
                    $amountAllStr = '( 深圳装速配科技有限公司 )' . $amountAllStr;
                } else if ($value['biz_uid'] == -10) {
                    $nameStr = '海南中装速配科技有限公司' . '</br>';
                    $amountAllStr = '( 海南中装速配科技有限公司 )' . $amountAllStr;
                }
                $v['user_info'] = $v['user_info'] . $nameStr;
                $v['amount'] = $v['amount'] .$amountAllStr;
                $v['confirm_status_text'] = self::CONFIRM_STATUS_TEXT[$v['confirm_status']];
                $v['refund_status_txt'] = OrderEntry::REFUND_STATUS_TXT[$v['refund_status']] ?? '';
                if($v['refund_status'] == OrderEntry::NO_REFUND && $v['ccb_reconciliation_amount'] ==0){
                    $v['dim_status_txt'] = '待分账';
                }else if($v['refund_status'] != OrderEntry::ALL_REFUND && $v['ccb_reconciliation_amount'] >0){
                    //已分账
                    //没有全部退款  并且有分账  是已分账
                    $v['dim_status_txt'] = '已分账';
                }else{
                    //全部退款   未分账
                    $v['dim_status_txt'] = '不分账';
                }
            }

        }
        return $data;
    }

    //财务确认单条明细,
    function confirmProcessById($appUid, $id)
    {

        Db::startTrans();

        $info = self::getModel($appUid)->info($id, $appUid);
        unset($info['create_time']);
        $info['order_process_status'] = OrderProcess::FIN_CONFIRM_PROCESS;
        $info['update_time'] = time();
        $res = self::getModel($appUid)->saveData($appUid, $info);
        if (!$res) {
            return errorReturn('更新订单失败!');
        }

        //结束分账订单
        $res = $this->endProcessOrder($info);
        if (!$res['result']) {
            Db::rollback();
            return errorReturn($res['msg']);
        }
        Db::commit();
        $res['msg'] = '操作成功!';
        return $res;
    }

    function confirmProcessAll($appUid, $id)
    {

        Db::startTrans();

        $info = self::getModel($appUid)->info($id, $appUid);
//        unset($info['create_time']);
//        $info['order_process_status'] = OrderProcess::FIN_CONFIRM_PROCESS;
//        $info['update_time'] = time();
//        $res = self::getModel($appUid)->saveData($appUid, $info);
        $dataUpdate['order_process_status'] = OrderProcess::FIN_CONFIRM_PROCESS;
        $dataUpdate['update_time'] = time();
        $res = model('OrderProcess')::where('biz_order_process_no', $info['biz_order_process_no'])->update($dataUpdate);
        if (!$res) {
            return errorReturn('更新订单失败!');
        }

        //结束分账订单
        $res = $this->endProcessOrder($info);
        if (!$res['result']) {
            Db::rollback();
            return errorReturn($res['msg']);
        }
        Db::commit();
        $res['msg'] = '操作成功!';
        return $res;
    }

    /**
     * Notes:同一分账订单好下都已经财务确认过时,触发分账完成操作
     * 1: 修改代收订单状态和剩余金额,
     * 2: 修改用户余额
     * 3: 请求allinpay
     * 4: 生成消息 入mq
     * @param $appUid
     * @param $info
     * @return array
     * User: SongX DateTime: 2020-12-24 20:03
     */
    function endProcessOrder($info)
    {
        //同一个分账订单号下的所有订单, 是否全都财务确认
        $result = $this->processSnNotConfirm($info);
        if (empty($result) && $result !== false) {
            //1: 修改代收订单状态和剩余金额,
//            $res = $this->updateEntryOrderStatus($info);
//            if(!$res['result']){
//                return errorReturn($res['msg']);
//            }
//            //2: 修改用户余额
//            $res = $this->updateUserFund( $info);
//            if(!$res['result']){
//                return errorReturn($res['msg']);
//            }
            //说明都已经分账确认完成, 需要通知业务系统,
            //3: 请求allinpay
            $resAllInPay = $this->requestAllInPay($info);
            if (!$resAllInPay['result']) {
                return errorReturn($resAllInPay['msg']);
            }
            //4: 生成消息 入mq
//            $res = $this->pushMqMsg($info);
//            if(!$res['result']){
//                return errorReturn($res['msg']);
//            }

            return $resAllInPay;
        }

        return successReturn(['msg' => '操作成功', 'data' => $info]);
    }

    //1: 修改代收订单状态和剩余金额,
    function updateEntryOrderStatus($info, $entryNo = null)
    {
        $orderEntryNo = $info['order_entry_no'] ?? $entryNo;
        if (empty($orderEntryNo)) {
            return errorReturn('代收订单号错误');
        }
        $appUid = $info['app_uid'];
        $rowEntry = model('OrderEntry')->infoByBizOrderNo($appUid, $orderEntryNo);
        $arrEntry['order_entry_status'] = $info['amount'] == $rowEntry['remain_amount'] ? OrderEntry::AGENT_PAY_COMPLETE : OrderEntry::AGENT_PAY_PART;
        $arrEntry['remain_amount'] = $rowEntry['remain_amount'] - $info['amount'];
        $arrEntry['id'] = $rowEntry['id'];
        $res = model('OrderEntry')->saveData($appUid, $arrEntry);
        if (!$res) {
            return errorReturn('代收订单修改失败');
        }
        return successReturn(['msg' => '代收订单修改成功']);
    }

    /**
     * 退款处理
     *
     * 建行返回退款失败，不做退款失败处理，通知业务系统，丢到退款异常订单，由财务确认，等待财务通知退款状态
     * 所以建行同步回来的退款失败当成 退款进行中处理，异步回来的退款失败，不做处理，财务回来的退款失败或者退款成功才开始接受处理
     *
     * 修改代收订单状态和剩余可退款金额
     * @param $info
     * @param $status
     * @param $callback 是否是回调回来 0否  1 建行回调 2 财务通知
     * @return array
     * User: cwh  DateTime:2021/11/5 18:20
     */
    function updateCcbEntryOrderStatus($info, $status,$callback = 0)
    {
        $orderEntryNo = $info['order_entry_no'];
        if (empty($orderEntryNo)) {
            return errorReturn('代收订单号错误');
        }
        $appUid = $info['app_uid'];
        $rowEntry = model('OrderEntry')->infoByBizOrderNo($appUid, $orderEntryNo);
//        $arrEntry['order_entry_status'] = $info['amount'] == $rowEntry['remain_amount'] ? OrderEntry::AGENT_PAY_COMPLETE : OrderEntry::AGENT_PAY_PART;
        if($callback == 0 && $status ==OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE']){
            //同步回来的退款     成功退款
            $arrEntry['remain_amount'] = bcsub($rowEntry['remain_amount'] , $info['amount']);//剩余可分账金额
            $arrEntry['refunded_amount'] = bcadd($info['amount'] , $rowEntry['refunded_amount']);//已经退款的金额
        }else if($callback ==0 && $status == OrderRefund::PAY_STATUS['ALL_IN_PAY_ING']){
            //同步回来的退款     退款进行中
            $arrEntry['remain_amount'] = bcsub($rowEntry['remain_amount'] , $info['amount']);//剩余可分账金额
            $arrEntry['refunding_amount'] = bcadd($info['amount'] , $rowEntry['refunding_amount']);//退款中的金额
        }else if($callback ==0 && $status == OrderRefund::PAY_STATUS['ALL_IN_PAY_ERROR']){
            //同步回来的退款     退款失败，退款失败也当退款进行中处理
            $arrEntry['remain_amount'] = bcsub($rowEntry['remain_amount'] , $info['amount']);//剩余可分账金额
            $arrEntry['refunding_amount'] = bcadd($info['amount'] , $rowEntry['refunding_amount']);//退款中的金额
        }else if($callback == 1 && $status == OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE']){
            //异步回来的退款     退款回调成功   三方退款成功   退款中的金额 扣掉退款的金额  已退款金额加上当前退款金额
            $arrEntry['refunded_amount'] = bcadd($info['amount'] , $rowEntry['refunded_amount']);//已经退款的金额
            $arrEntry['refunding_amount'] =  bcsub($rowEntry['refunding_amount'] , $info['amount']);//退款中的金额
        }else if($callback == 1 && $status == OrderRefund::PAY_STATUS['ALL_IN_PAY_ERROR']){
            //异步回来的退款     退款回调成功   三方退款失败   退款中的金额 扣掉当前退款的金额   退款失败也当退款进行中处理 异步回来的退款不做处理
//            $arrEntry['refunding_amount'] =  bcsub($rowEntry['refunding_amount'] , $info['amount']);//退款中的金额
//            $arrEntry['remain_amount'] = bcsub($rowEntry['remain_amount'] , $info['amount']);//剩余可分账金额
        }else if($callback == 2 && $status == OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE']){
            //异步回来的退款     退款回调成功   财务确认退款成功   退款中的金额 扣掉退款的金额  已退款金额加上当前退款金额
            $arrEntry['refunded_amount'] = bcadd($info['amount'] , $rowEntry['refunded_amount']);//已经退款的金额
            $arrEntry['refunding_amount'] =  bcsub($rowEntry['refunding_amount'] , $info['amount']);//退款中的金额
        }else if($callback == 2 && $status == OrderRefund::PAY_STATUS['ALL_IN_PAY_ERROR']){
            //异步回来的退款     退款回调成功   财务确认退款失败   退款中的金额 扣掉当前退款的金额
            $arrEntry['refunding_amount'] =  bcsub($rowEntry['refunding_amount'] , $info['amount']);//退款中的金额
            $arrEntry['remain_amount'] = bcadd($rowEntry['remain_amount'] , $info['amount']);//剩余可分账金额
        }

        $arrEntry['id'] = $rowEntry['id'];
        $res = model('OrderEntry')->saveData($appUid, $arrEntry);
        if (!$res) {
            return errorReturn('代收订单修改失败');
        }
        $res = $this->updateCcbOrderProcessStatus($appUid,$orderEntryNo);
        $res = $this->updateCcbEntryRefundStatus($appUid,$orderEntryNo);
        if (!$res) {
            return errorReturn('代收订单修改失败');
        }
        return successReturn(['msg' => '代收订单修改成功']);
    }

    /**
     * 修改分账订单的退款金额和可分账金额
     * @param $appUid
     * @param $orderEntryNo
     * @return array|bool
     * User: cwh  DateTime:2021/11/6 14:59
     */
    function updateCcbOrderProcessStatus($appUid,$orderEntryNo){
        if (empty($orderEntryNo)) {
            return errorReturn('代收订单号错误');
        }
        $rowEntry = model('OrderEntry')->infoByBizOrderNo($appUid, $orderEntryNo);
        //已退款金额 根据比例算
        //退款金额  根据比例算
        //剩余金额  根据比例算
        $orderProcess = model('OrderProcess')->getOrderProcessListByMainOrdrNo($orderEntryNo);
        //先算商家   总的-商家 = 中装
        $orderProcess[2]['refunded_amount'] = round(bcdiv(bcmul($rowEntry['refunded_amount'] , $orderProcess[2]['rate']) , 100,2));//已退款金额
        $orderProcess[2]['refunding_amount'] = round(bcdiv(bcmul($rowEntry['refunding_amount'] , $orderProcess[2]['rate']) , 100,2));//退款中金额
        $orderProcess[2]['remain_amount'] = bcsub(bcsub($orderProcess[2]['amount'] , $orderProcess[2]['refunded_amount']) , $orderProcess[2]['refunding_amount']);//可分账金额

        $orderProcess[1]['refunded_amount'] = bcsub($rowEntry['refunded_amount'] , $orderProcess[2]['refunded_amount']);
        $orderProcess[1]['refunding_amount'] = bcsub($rowEntry['refunding_amount'] , $orderProcess[2]['refunding_amount']);
        $orderProcess[1]['remain_amount'] = bcsub(bcsub($orderProcess[1]['amount'] , $orderProcess[1]['refunded_amount']) , $orderProcess[1]['refunding_amount']);
        foreach($orderProcess as $k=>$v){
            //四舍五入（（本金 * 比例） 除以 100）
            $updateData['remain_amount'] = $v['remain_amount'];
            $updateData['refunded_amount'] = $v['refunded_amount'];
            $updateData['refunding_amount'] = $v['refunding_amount'];
            model('OrderProcess')->where("id",$v['id'])->update($updateData);
        }

        return true;
    }

    /**
     * 归订单的退款状态
     * @param $appUid
     * @param $orderEntryNo
     * User: cwh  DateTime:2021/11/6 10:12
     */
    function updateCcbEntryRefundStatus($appUid,$orderEntryNo){
        //获取订单信息
        $order_entry_info = model('OrderEntry')->infoByBizOrderNo($appUid,$orderEntryNo);
        //剩余金额 大于0
        if($order_entry_info['refunded_amount'] ==0 && $order_entry_info['refunding_amount'] == 0){
            //已退款金额= 0 退款中金额 = 0 未退款
            $arrEntry['refund_status'] = OrderEntry::NO_REFUND;
        } else if($order_entry_info['refunding_amount'] > 0){
            //退款中中 金额 大于0
            $arrEntry['refund_status'] = OrderEntry::PART_REFUND;
        }else if($order_entry_info['refunded_amount'] == $order_entry_info['amount']){
            //已退款金额 = 所有金额    全部退款
            $arrEntry['refund_status'] = OrderEntry::ALL_REFUND;
        }else if($order_entry_info['refunded_amount'] < $order_entry_info['amount']){
            //已退款金额 < 所有金额    部分退款
            $arrEntry['refund_status'] = OrderEntry::PART_REFUND;
        }
        $arrEntry['id'] = $order_entry_info['id'];
        $res = model('OrderEntry')->saveData($appUid, $arrEntry);
        $orderProcess = model('OrderProcess')->getOrderProcessListByMainOrdrNo($orderEntryNo);
        foreach($orderProcess as $v){
            $updateData['id'] = $v['id'];
            $updateData['refund_status'] = $arrEntry['refund_status'];
            model('OrderProcess')->where('id',$v['id'])->update($updateData);
        }
        return $res;
    }

    //2: 修改用户余额
    function updateUserFund($info)
    {
        $userInfo = model('Users')->plusUserFund($info['app_uid'], $info['biz_uid'], $info['amount']);

        if (!isset($userInfo['result']) || !$userInfo['result']) {
            return errorReturn('用户余额更新失败!');
        }
        return successReturn(['msg' => '修改余额成功']);
    }

    //同一个分账订单号下的所有订单, 是否全都财务确认
    function processSnNotConfirm($info)
    {
        $bizOrderProcessNo = $info['biz_order_process_no'] ?? '';
        if (empty($bizOrderProcessNo)) {
            return false;
        }

//        $this->submeter($info['app_uid']);
        $where[] = ['biz_order_process_no', '=', $bizOrderProcessNo];
        $where[] = ['order_process_status', '<', OrderProcess::FIN_CONFIRM_PROCESS];

        //查询失败直接返回false
        $rs = self::getModel($info['app_uid'])->where($where)->select()->toArray();
        if (empty($rs)) {
            return [];
        }
        return $rs;
    }

    //3: 请求allinpay
    function requestAllInPay($info)
    {
        $bizOrderProcessNo = $info['biz_order_process_no'] ?? '';
        $orderEntryNo = $info['order_entry_no'] ?? '';
        if (empty($bizOrderProcessNo) || empty($orderEntryNo)) {
            return false;
        }
        $result = successReturn([]);
        $appUid = $info['app_uid'];
        $AllInPayClient = new AllInPayClient();

        //allinpay分账参数 splitRuleList
        $splitRuleList = [];
        $processSumAmount = 0;  //分账列表总金额
        $processList = model('OrderProcess')->infoByBizOrderProcessNo($appUid, $bizOrderProcessNo);
        foreach ($processList as $prRow) {
            $temp = [];

            if ($prRow['biz_uid'] == -1) {
                $temp['bizUserId'] = '#yunBizUserId_B2C#';
                $temp['accountSetNo'] = '100001';
            } elseif ($prRow['biz_uid'] == -10) {
                $temp['bizUserId'] = -10;
            } else {
                $temp['bizUserId'] = $prRow['app_uid'] . $prRow['biz_uid'];
            }
            $temp['bizOrderNo'] = $prRow['order_entry_no'];
            $temp['amount'] = $prRow['amount'];
            $temp['fee'] = $prRow['fee'];
            $temp['remark'] = $prRow['remark'];
            $processSumAmount += $prRow['amount'];
            $splitRuleList[] = $temp;
        }
        //allinpay分账参数  collectPayList
        $collectPayList[0]['bizOrderNo'] = $orderEntryNo;
        $collectPayList[0]['amount'] = $processSumAmount;

        //1: 修改代收订单状态和剩余金额,
        $paramEntry['app_uid'] = $appUid;
        $paramEntry['order_entry_no'] = $orderEntryNo;
        $paramEntry['amount'] = $processSumAmount;
        $res = $this->updateEntryOrderStatus($paramEntry);
        if (!$res) {
            return errorReturn('代收订单修改失败');
        }
        //2: 修改用户余额
        foreach ($processList as $prRow2) {
            $res = $this->updateUserFund($prRow2);
            if (!$res['result']) {
                return errorReturn($res['msg']);
            }
        }

        //3: 请求 allInPay,修改OrderProcess状态
        $param['bizOrderNo'] = $bizOrderProcessNo;
        $param["tradeCode"] = $processList[0]['trade_code'] ?? '4001';
        $param["amount"] = $processSumAmount;
        $param["fee"] = 0;
        $param["backUrl"] = $AllInPayClient->getCallBackDomain() . 'AllinPay/notifyAgentPay';
        $param['extendInfo'] = $appUid;
        //代收的收款人的账户和账户集编号
        $param["bizUserId"] = $this->allInPayOrderService->receiveBizUserId;
        $param["accountSetNo"] = $processList[0]['account_set_no'];
//var_dump($splitRuleList[0]["amount"]);
//echo json_encode($splitRuleList);exit;
        //请求 allInPay
        $result = $this->allInPayOrderService->signalAgentPay($param, $collectPayList, $splitRuleList);
        if ($result['result'] === false) {    // allInPay 错误直接返回
            return $result;
        }
        //修改订单表 allInPay订单号,交易单号
        $dataUpdate['allinpay_order_no'] = $result['data']['orderNo'] ?? 'abc';
        $dataUpdate['allinpay_pay_no'] = $result['data']['payInterfaceOutTradeNo'] ?? '';
        $resAllInPay = model('OrderProcess')::where('biz_order_process_no', $bizOrderProcessNo)->update($dataUpdate);
        if (!$resAllInPay) {
            (new AllInPayClient())->getLogIns()->logMessage("[更新单号失败]", Log::INFO, "biz_order_process_no<{$bizOrderProcessNo}>更新allInPay订单号失败---->allinpay_order_no<{$allInPayOrderNo}>");
        }
        unset($result['data']['extendInfo']);
        unset($result['data']['orderNo']);
        unset($result['data']['payWhereabouts']);

        return $result;

    }

    //4: 生成消息 入mq
    function pushMqMsg($info)
    {
//var_dump(1111);exit;
        $appUid = $info['app_uid'] ?? '';
        if (empty($appUid)) {
            return errorReturn('分账信息错误');
        }
        //推消息入MQ , MQ消费消息队列通知业务系统
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'OrderService',
            'fun' => 'agentPay',   //fun 必填, 值是 Service 的方法名
            'appId' => $appUid,
            'extendParams' => $info['extend_params'],
            'bizOrderNo' => $info['biz_order_process_no']
        ];
        $result = $producer->publish($arrMsg);
        if ($result === null) {
            return successReturn(['msg' => '生产消息成功']);
        }
        return errorReturn('生产消息未知错误!');
    }


}