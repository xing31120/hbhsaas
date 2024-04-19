<?php

namespace app\api\controller;

use app\common\amqp\BizProducer;
use app\common\model\OrderEntry;
use app\common\model\OrderProcess;
use app\common\model\OrderRefund;
use app\common\model\reconciliation\FinanceReconciliation;
use app\common\model\reconciliation\FinanceSummary;
use app\common\model\reconciliation\FinanceSummaryDetail;
use app\common\service\OrderProcessService;
use app\push\service\SendWxWorkService;
use think\Controller;

class CronReconciliation extends Controller {

    /**
     * 增加分账汇总
     * @return |null
     * User: cwh  DateTime:2021/9/23 16:00
     */
//    public function addSummaryByFile(){
//        $result = (new FinanceSummary())->addSummaryByFile();
//        return apiOut($result);
//    }

    /**
     * 增加分账明细
     * @return |null
     * User: cwh  DateTime:2021/9/23 16:01
     */
//    public function addSummaryDetailByFile(){
//        $result = (new FinanceSummaryDetail())->addSummaryDetailByFile('123','321');
//        return apiOut($result);
//    }

    /**
     * 增加对账明细
     * @return |null
     * User: cwh  DateTime:2021/9/23 19:43
     */
//    public function addReconciliationByFile(){
//        $result = (new FinanceReconciliation())->addReconciliationByFile();
//        return apiOut($result);
//    }

    /**
     * 自动对账
     * @return |null
     * User: cwh  DateTime:2021/9/23 19:43
     */
    public function autoReconciliation(){
        $result = (new FinanceSummaryDetail())->autoReconciliation();
        return apiOut($result);
    }

    /**
     * 推送业务系统
     * @return |null
     * User: cwh  DateTime:2021/10/19 16:34
     */
    public function sendFinanceSummaryDetail(){
        $result = (new OrderEntry())->sendFinanceSummaryDetail();
        return apiOut($result);
    }

//    public function test(){
////        $data['app_uid'] = 2020;
//////        $data['bizOrderNo'] = '21191620173846260179';
////        $data['bizOrderNo'] = 'RE21191620173846260175';
////        $data['type'] = SendWxWorkService::ADD_PAY_REFUND;
////        (new SendWxWorkService)->sendWxWork($data);
////mq通知企业微信
//        $producer = new BizProducer();
//        $arrMsg = [
//            'serviceClass' => 'SendWxWorkService',
//            'fun' =>  'sendWxWork',   //fun 必填, 值是 Service 的方法名
//            'appId' => 2020,
//            'bizOrderNo' => '21191620173846260179',
//            'type'      =>SendWxWorkService::ADD_PAY_ORDER
//        ];
//        $result = $producer->publish($arrMsg);
//    }

//    public function test1(){
////        $paramEntry['app_uid'] = 2020;
////        $paramEntry['order_entry_no'] = 'PA20211104151934639627';
////        $paramEntry['amount'] = "100";
////        $res = (new OrderProcessService())->updateCcbOrderProcessStatus($paramEntry['app_uid'],$paramEntry['order_entry_no']);
//        $orderEntryNo ="21191620173846260111";
//        $orderProcess = model('OrderProcess')->getOrderProcessListByMainOrdrNo($orderEntryNo);
//        $appUid = "2020";
//        foreach($orderProcess as $v){
//            $updateData['id'] = $v['id'];
//            $updateData['refund_status'] = 2;
////            $arrEntry['id'] = $v['id'];
////            dump($arrEntry);
//            $res = model('OrderProcess')->updateData($appUid,$updateData);
//            dump($res);
//        }
////        $appUid = "2020";
////        $res = model('OrderProcess')->saveData($appUid,$updateData);
//        exit;
//    }
}