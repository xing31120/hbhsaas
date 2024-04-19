<?php


namespace app\common\service;

use AllInPay\Log\Log;
use AllInPay\SDK\yunClient;
use app\admin\controller\OrderProcess;
use app\common\amqp\BizProducer;
use app\common\model\OrderEntry;
use app\common\model\OrderRefund;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\Ccb\CcbOrderService;
use app\common\tools\SysEnums;
use app\common\service\AllInPay\AllInPayOrderService;
use app\push\service\SendWxWorkService;
use think\Db;

//退款服务
class OrderRefundService{

    public $mcName = 'order_refund_';
    public $allInPayOrderService = null ;


    function __construct(){
        $this->allInPayOrderService = new AllInPayOrderService();
    }

    function refund($appUid, $param){
        if(empty($appUid) || empty($param["bizUserId"]) || empty($param['bizOrderNo']) ||
            empty($param["oriBizOrderNo"])  || empty($param['bizBackUrl']) || empty($param['backUrl']) ||
            strlen($param['bizUserId']) < 5 || empty($param['amount'])
        ){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }


        Db::startTrans();

        $bizBackUrl = $param["bizBackUrl"];
        unset($param["bizBackUrl"]);

        $paramNew = $param;
        $paramNew['biz_order_no'] = $param['bizOrderNo'];
        $paramNew['ori_biz_order_no'] = $param['oriBizOrderNo'];
        $paramNew['biz_users_id'] = $param['bizUserId'];
        $paramNew['allinpay_order_no'] = '';
        $paramNew['biz_back_url'] = $bizBackUrl;
        $paramNew['refund_list'] = isset($param['refundList']) ? json_encode($param['refundList']) : '';

        $rs = model('OrderRefund')->addRefund($appUid, $paramNew);
        if(!$rs['result']){
            Db::rollback();
            $errorMsg = $rs['msg'] ?? '添加托管代收订单失败';
            return errorReturn($errorMsg);
        }
        //1: 修改代收订单状态和剩余金额,
        $orderProcessService = new OrderProcessService();
        $res = $orderProcessService->updateEntryOrderStatus($rs['data'], $rs['data']['ori_biz_order_no']);
        if(!$res['result']){
            Db::rollback();
            return errorReturn($res['msg']);
        }
        //2: 修改用户余额
        $res = $orderProcessService->updateUserFund( $rs['data']);
        if(!$res['result']){
            Db::rollback();
            return errorReturn($res['msg']);
        }

        //请求 allInPay
        $result = $this->allInPayOrderService->refund($param);
        if(!isset($result['result']) || $result['result'] === false){    //allinpay错误直接返回
            Db::rollback();
            return $result;
        }
        //修改订单表 allInPay订单号,交易单号
        $dataUpdate['allinpay_order_no']  =  $result['data']['orderNo'] ?? 'abc';
        $dataUpdate['allinpay_pay_no']    = $result['data']['payInterfaceOutTradeNo'] ?? '';
        $resAllInPay = model('OrderRefund')::where('biz_order_no',$param['bizOrderNo'])->update($dataUpdate);
        if(!$resAllInPay){
            (new AllInPayClient())->getLogIns()->logMessage("[更新单号失败]",Log::INFO,"OrderRefund表biz_order_no<{$param['bizOrderNo']}>更新allInPay订单号失败---->allinpay_order_no<{$allInPayOrderNo}>");
        }

        Db::commit();

//        $returnData['bizUid']       = $param['bizUserId'];
        $returnData['bizUid']       = str_replace($appUid, "", $param['bizUserId']);
        $returnData['bizOrderNo']   = $param['bizOrderNo'];
        $returnData['amount']       = $param['amount'];

        return successReturn(['data' => $returnData, 'resData' => $rs['data']->toArray()]);
    }

    /**
     * 转化orderList数据
     * @param $data
     * @return mixed
     * User: cwh  DateTime:2021/9/16 22:11
     */
    public function checkFormatRefundOrderList($data,$amount,$sub_ordr_id){
        if(empty($data)){
            return $data;
        }
        $total_rfnd_Amt = 0;
        $total_par_rfnd_Amt = 0;
        foreach($data as &$v){
            $total_rfnd_Amt = bcadd($total_rfnd_Amt,$v['rfnd_Amt'],2);
            $v['rfnd_Amt'] = bcdiv($v['rfnd_Amt'],100,2);
            $v['sub_Ordr_Id'] = $sub_ordr_id;
            if(!empty($v['parlist']) ){
                foreach ($v['parlist'] as &$item) {
                    $total_par_rfnd_Amt = bcadd($total_par_rfnd_Amt,$item['rfnd_Amt'],2);
                    $item['rfnd_Amt'] = bcdiv($item['rfnd_Amt'],100,2);
                }
            }
        }

        if($amount != $total_rfnd_Amt ){
            return errorReturn("订单总价和订单子价格不一致");
        }
        if($total_rfnd_Amt != $total_par_rfnd_Amt){
            return errorReturn("订单列表分账价格加起来不等于总价格");
        }
        $returnData['msg'] = '成功';
        $returnData['data'] = $data;
        return successReturn($returnData);
    }

    /**
     * ccb订单退款
     * @param $appUid
     * @param $param
     * @return mixed
     * User: cwh  DateTime:2021/9/18 17:54
     */
    function ccbRefund($appUid, $param,$config = []){
        if(empty($appUid) || empty($param["bizUserId"]) || empty($param['bizOrderNo']) ||
            empty($param["oriBizOrderNo"])  || empty($param['bizBackUrl']) ||
            strlen($param['bizUserId']) < 5 || empty($param['amount'])
        ){
            return errorReturn('参数错误',SysEnums::ApiParamMissing);
        }

        $rs = model('OrderRefund')->infoByBizOrderNo($appUid, $param['bizOrderNo']);
        if($rs['pay_status'] != OrderRefund::PAY_STATUS['WAIT_PAY']){
            //退款单已经存在，业务系统重复请求，直接返回结果
            $returnData['bizUid']       = str_replace($appUid, "", $rs['biz_uid']);
            $returnData['bizOrderNo']   = $rs['biz_order_no'];
            $returnData['amount']       = $rs['amount'];
            $returnData['pay_status']   = $rs['pay_status'];
            return successReturn(['data' => $returnData, 'resData' => $rs]);
        }
        $order_entry = model('OrderEntry')->infoByBizOrderNo($appUid,$param['oriBizOrderNo']);
        if(empty($order_entry)){
            return errorReturn('订单不存在',SysEnums::ApiParamMissing);
        }
        //请求 建行ccb
        $ccbOrder = new CcbOrderService();
        if(!empty($rs['biz_order_no'])){
            $sn = $ccbOrder->getSn();
            $paramsa = [
                'ittpartyTms' => $ccbOrder->getTms(),     //发起方时间戳,毫秒时间 年月日, 时分秒，毫秒
                'ittpartyJrnlNo' => $sn,   //该笔直连交易的客户方流水号（不允许重复） VarChar 32
                //业务端必传参数
                'custRfndTrcno' => $rs['biz_order_no'],     //该字段由发起方生成，请求退款时不允许重复（当出现请求超时情况时，客户可凭借此字段重复发起退款，对于同一笔客户方退款流水号，惠市宝确保只发生一次退款）。重复流水号可查询该流水号退款请求结果；不同流水号为发起新请求。
//            'rfndTrcno' => $rs['allinpay_order_no'],//惠市宝生成，与该订单的支付动作唯一匹配
                'vno'     =>4,
            ];

            $res = $ccbOrder->enquireRefundOrder($paramsa,$appUid,$order_entry['pay_method']);
            if($res['Refund_Rsp_St'] !='05'){
                //05 是未查询到退款订单 不等于是已经存在退款订单，不让退款
                return errorReturn('退款单已经存在',SysEnums::ApiParamMissing);
            }
        }

        Db::startTrans();
        $bizBackUrl = $param["bizBackUrl"];
        unset($param["bizBackUrl"]);
        $paramNew = $param;
        $paramNew['biz_order_no'] = $param['bizOrderNo'];
        $paramNew['ori_biz_order_no'] = $param['oriBizOrderNo'];
        $paramNew['biz_users_id'] = $param['bizUserId'];
        $paramNew['allinpay_order_no'] = '';
        $paramNew['biz_back_url'] = $bizBackUrl;
        $paramNew['py_trn_no'] = $param['py_trn_no'];
        $paramNew['refund_list'] = isset($param['refundList']) ? json_encode($param['refundList']) : '';
        $paramNew['type']          = 2;
        $rs = model('OrderRefund')->addRefund($appUid, $paramNew);
        if(!$rs['result']){
            Db::rollback();
            $errorMsg = $rs['msg'] ?? '添加托管代收订单失败';
            return errorReturn($errorMsg);
        }


        $sn = $ccbOrder->getSn();
        $params = [
            'pyOrdrTpcd' => $config['Py_Ordr_Tpcd'],    //订单类型,固定04
            'ccy' => $config['Ccy'],  //币种,固定156
            'ittpartyTms' => $ccbOrder->getTms(),     //发起方时间戳,毫秒时间 年月日, 时分秒，毫秒
            'ittpartyJrnlNo' => $sn,   //该笔直连交易的客户方流水号（不允许重复） VarChar 32
            //业务端必传参数
            'custRfndTrcno' => $param['bizOrderNo'],     //该字段由发起方生成，请求退款时不允许重复（当出现请求超时情况时，客户可凭借此字段重复发起退款，对于同一笔客户方退款流水号，惠市宝确保只发生一次退款）。重复流水号可查询该流水号退款请求结果；不同流水号为发起新请求。
            'pyTrnNo' => $param['py_trn_no'],//惠市宝生成，与该订单的支付动作唯一匹配
            'rfndAmt' => bcdiv($param['amount'],100,2),         //订单全额退款时不需要送订单部分退款时必须送此值，且值等于所有子订单的退款金额之和
            'vno'     =>3,
            'subOrdrList'=>$param['subOrdrList'],
        ];
        $result = $ccbOrder->refundOrder($params,$appUid,$order_entry['pay_method']);
//        $result['result'] = true;
//        $result['data']['refund_rsp_st'] = '01';
        if(!$result['result']){
            //退款失败
            Db::rollback();
            return errorReturn('退款失败，系统异常');
        }
        $dataUpdate['pay_status'] = OrderRefund::PAY_STATUS['WAIT_PAY'];
        if($result['data']['refund_rsp_st'] ==OrderRefund::CCB_RESPONSE_REFUND_SUCCESS){
            //退款成功
            $dataUpdate['pay_status'] = OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE'];
        }else if($result['data']['refund_rsp_st'] ==OrderRefund::CCB_RESPONSE_REFUND_FAIL){
            //退款失败
            $dataUpdate['pay_status'] = OrderRefund::PAY_STATUS['ALL_IN_PAY_ERROR'];
        }else{
            //退款延迟
            $dataUpdate['pay_status'] = OrderRefund::PAY_STATUS['ALL_IN_PAY_ING'];
        }
        //修改订单表 allInPay订单号,交易单号
        $dataUpdate['allinpay_order_no']    = $result['data']['rfnd_trcno'] ?? '';
        $dataUpdate['py_trn_no']    = $param['py_trn_no'] ?? '';
        $resAllInPay = model('OrderRefund')::where('biz_order_no',$param['bizOrderNo'])->update($dataUpdate);
        if(!$resAllInPay){
            (new AllInPayClient())->getLogIns()->logMessage("[更新单号失败]",Log::INFO,"OrderRefund表biz_order_no<{$param['bizOrderNo']}>更新allInPay订单号失败---->allinpay_order_no<{$allInPayOrderNo}>");
        }
        Db::commit();

        //1: 修改代收订单状态和剩余金额,
        $paramEntry['app_uid'] = $appUid;
        $paramEntry['order_entry_no'] = $param['oriBizOrderNo'];
        $paramEntry['amount'] = $param['amount'];
        $res = (new OrderProcessService())->updateCcbEntryOrderStatus($paramEntry,$dataUpdate['pay_status']);
        if (!$res) {
            return errorReturn('代收订单修改失败');
        }

        //mq通知
        //mq通知企业微信
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'SendWxWorkService',
            'fun' =>  'sendWxWork',   //fun 必填, 值是 Service 的方法名
            'appId' => $appUid,
            'bizOrderNo' => $param['bizOrderNo'],
            'type'      =>SendWxWorkService::ADD_PAY_REFUND,
            'workKey'  =>env('SAASWORK.HSB_PUSH', '')
        ];
        $result1 = $producer->publish($arrMsg);

//        $returnData['bizUid']       = $param['bizUserId'];
        $returnData['bizUid']       = str_replace($appUid, "", $param['bizUserId']);
        $returnData['bizOrderNo']   = $param['bizOrderNo'];
        $returnData['amount']       = $param['amount'];
        $returnData['pay_status']   = $dataUpdate['pay_status'];

        if($result['data']['refund_rsp_st'] ==OrderRefund::CCB_RESPONSE_REFUND_FAIL){
            //退款失败，直接返回
            $producer = new BizProducer();
            $arrMsg = [
                'serviceClass' => 'SendWxWorkService',
                'fun' =>  'sendWxWork',   //fun 必填, 值是 Service 的方法名
                'appId' => $appUid,
                'bizOrderNo' => $param['bizOrderNo'],
                'type'      =>SendWxWorkService::PAY_REFUND_ERROR,
                'workKey'  =>env('SAASWORK.HSB_REFUND_PUSH', '')
            ];
            $result2 = $producer->publish($arrMsg);
            return errorReturn($result['data']['refund_rsp_inf']);
        }

        return successReturn(['data' => $returnData, 'resData' => $rs['data']->toArray()]);
    }

    /**
     * 获取订单可以退款的金额
     * @param $order_entry_no
     * User: cwh  DateTime:2021/10/21 11:59
     */
    public function canReturnAmountByOrder($order_entry_no,$order_amount){
        $refund_amount = model('OrderRefund')->where("ori_biz_order_no",$order_entry_no)->whereIn("pay_status",[OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE'],OrderRefund::PAY_STATUS['ALL_IN_PAY_ING']])->sum("amount");
        $amount = $order_amount - $refund_amount;
        return $amount;
    }


    function getList($appUid, $op = []){
        if (empty($op)) {
//            $where[] = ['pay_status', '=', \app\common\model\OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE']];
            $op['where'] = [];
            $op['field'] = '*';
            $op['order'] = 'create_time desc, id desc';
        }

        $usersAppList = model('UsersApp')->getAllList();
        $data = model('OrderRefund')->getList($appUid, $op);
        if(!isset($data['list'])){
            return [];
        }
        $bizUids = [];
        foreach ($data['list'] as &$item) {
            $bizUids[] = $item['biz_uid'];
        }
        $bizUids = array_unique($bizUids);
        $userInfos = UserService::getSaasUserInfo($bizUids);
//pj($bizUids);
        foreach ($data['list'] as $k => &$v) {
            $v['appName'] = isset($usersAppList[$v['app_uid']]['app_name']) ? $usersAppList[$v['app_uid']]['app_name'] : "";
            $v['orderPayStatusVal'] = OrderRefund::orderRefundStatus[$v['pay_status']];
            $v['amount'] = $v['amount'] / 100;
            $v['sysName'] = $v['type'] == 1 ? '通联' : '建行';

            $v['user_info'] = $v['show_user_name'];
//            if(isset($userInfos['data'][$v['biz_uid']])){
//                $userInfo = $userInfos['data'][$v['biz_uid']];
//                $v['user_info'] =  '姓名:' . $userInfo['real_name'] . '</br>手机号:' . $userInfo['mobile'];
//            }
        }

        return $data;
    }
}