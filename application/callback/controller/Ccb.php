<?php
namespace app\callback\controller;

use AllInPay\Log\Log;
use app\common\amqp\BizProducer;
use app\common\model\MqErrorLog;
use app\common\model\NotifyData;
use app\common\model\OrderEntry;
use app\common\model\OrderRefund;
use app\common\model\reconciliation\FinanceBase;
use app\common\model\reconciliation\FinanceReconciliation;
use app\common\model\reconciliation\FinanceSummary;
use app\common\model\reconciliation\FinanceSummaryDetail;
use app\common\service\AliyunOssService;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\Ccb\CcbAccountingRulesService;
use app\common\service\Ccb\CcbClient;
use app\common\service\Ccb\CcbSdkService;
use app\common\service\Ccb\CcbShopInfoService;
use app\common\service\OrderProcessService;
use app\common\service\workSendMessage\WorkSendMessageService;
use app\common\tools\SysEnums;
use app\common\tools\Zip;
use app\push\service\SendWxWorkService;
use think\Db;
//use think\Request;
use think\facade\Config;
use think\facade\Env;
use think\facade\Hook;
use think\facade\Request;
use WeChat\Exceptions\InvalidResponseException;

class Ccb extends Base {

    const SUCCESS = '00';
    const FAIL    = '01';

    const PAYMENTCALLBACK = 'paymentCallback';//支付回调
    const REFUNDCALLBACK = 'refundCallback';//退款回调
    const FINRULECALLBACK = 'finRuleCallback';//规则回调
    const FILECALLBACK = 'FileCallback';//对账单回调

    /**
     * 支付回调
     * User: cwh  DateTime:2021/9/16 11:54
     */
    public function paymentCallback(){
        $data = input();
        if(empty($data)){
            $data = file_get_contents("php://input");
        }
        $notifyData['type'] = 1;
        $notifyData['json_msg'] = json_encode($data);
        $rs = model('CcbNotifyData')->saveData($notifyData);

        //缺少验签
        $py_trn_no = $data['Py_Trn_No'];
        $bizOrderNo = $data['Main_Ordr_No'];

        $res = $this->checkSign($bizOrderNo,$py_trn_no,$data,self::PAYMENTCALLBACK);
        if(!$res){
            $returnData['Svc_Rsp_St'] = self::FAIL;
            return json($returnData);
        }

        if($data['Ordr_Stcd'] != OrderEntry::CCB_COMPLETE && $data['Ordr_Stcd'] != OrderEntry::CCB_FAILURE){
            //不是成功或者失败状态，不更新订单
            $returnData['Svc_Rsp_St'] = self::SUCCESS;
            return json($returnData);
        }
        $modelOrder = model('OrderEntry')->CcbComplete($bizOrderNo, $py_trn_no,$data['Ordr_Stcd'],$data['Pay_Time']);
        if(!$modelOrder['result'])  {
            Db::rollback();
//            exit('errorUp');
            $returnData['Svc_Rsp_St'] = self::SUCCESS;
            return json($returnData);
        }
        if($data['Ordr_Stcd'] !=2){
            //不是成功状态，不通知业务方
            $returnData['Svc_Rsp_St'] = self::SUCCESS;
            return json($returnData);
        }
        $modelOrder = $modelOrder['data'];
        //推消息入MQ , MQ消费消息队列通知业务系统
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'CcbOrderService',
            'fun' =>  'paymentCallback',   //fun 必填, 值是 Service 的方法名
            'id' => $modelOrder['id'],
            'appId' => $modelOrder['app_uid'],
            'bizOrderNo' => $bizOrderNo,
            'allinpayPayNo' => $modelOrder['allinpay_pay_no'],
            'bizUid' => $modelOrder['biz_uid'],
            'amount' => $modelOrder['amount']
        ];
        $result = $producer->publish($arrMsg);
        if($result !== null){
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }
        //mq通知企业微信
        $producerone = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'SendWxWorkService',
            'fun' =>  'sendWxWork',   //fun 必填, 值是 Service 的方法名
            'appId' => $modelOrder['app_uid'],
            'bizOrderNo' => $bizOrderNo,
            'type'      =>SendWxWorkService::ADD_PAY_SUCCESS,
            'workKey'  =>env('SAASWORK.HSB_PUSH', '')
        ];
        $result = $producerone->publish($arrMsg);
        if($result !== null){
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }
        //返回给建行的信息
        $returnData['Svc_Rsp_St'] = self::SUCCESS;
        return json($returnData);
    }

    /**
     * 回调验签
     * @param $biz_order_no
     * @param $py_trn_no 支付流水号
     * @param $data
     * @return bool
     * User: cwh  DateTime:2021/11/3 17:07
     */
    public function checkSign($biz_order_no,$py_trn_no,$data,$type =''){
        switch($type){
            case self::PAYMENTCALLBACK://下单回调
                $info = model('OrderEntry')->infoByBizOrderNoAndPyTrnNo($biz_order_no,$py_trn_no);
                $pay_method = $info['pay_method'];
                break;
            case self::FINRULECALLBACK://规则回调
                $ccb_client = new CcbClient();
                $mktIds = $ccb_client->mktIdBypayMethod();
                $mktIds = array_flip($mktIds);
                $pay_method = $mktIds[$data['Mkt_Id']];
                break;
            case self::REFUNDCALLBACK://退款回调
                $return_info = model('OrderRefund')->infoByBizOrderNoAndPyTrnNo($biz_order_no,$py_trn_no);
                $info = model('OrderEntry')->infoByBizOrderNoAndPyTrnNo($return_info['ori_biz_order_no'],$py_trn_no);
                $pay_method = $info['pay_method'];
                break;
            default:
                return true;
                break;
        }
        $ccb = new CcbSdkService();
        return $ccb->verify($data,$pay_method);
    }

    /**
     * 退款回调通知
     * User: cwh  DateTime:2021/9/16 14:38
     */
    public function refundCallback(){
        $data = input();
        $notifyData['type'] = 2;
        $notifyData['json_msg'] = json_encode($data);
        $rs = model('CcbNotifyData')->saveData($notifyData);

        //缺少验签
        $py_trn_no = $data['Py_Trn_No'];
        $bizOrderNo = $data['Cust_Rfnd_Trcno'];
        $super_refund_no = $data['Super_Refund_No'] ?? '';

        $res = $this->checkSign($bizOrderNo,$py_trn_no,$data,self::REFUNDCALLBACK);
        if(!$res){
            $returnData['Svc_Rsp_St'] = self::FAIL;
            return json($returnData);
        }
        if($data['Refund_Rsp_St'] == self::SUCCESS){
            //退款成功
            $pay_status = OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE'];
            $modelOrder = model('OrderRefund')->CcbComplete($bizOrderNo, $py_trn_no,$super_refund_no);
        }else{
            //退款失败
            $pay_status = OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE'];
            $modelOrder = model('OrderRefund')->ccbError($bizOrderNo, $py_trn_no,$super_refund_no);
        }
        if(!$modelOrder['result'])  {
            Db::rollback();
//            exit('errorUp');
            $returnData['Svc_Rsp_St'] = self::SUCCESS;
            return json($returnData);
        }
        $modelOrder = $modelOrder['data'];
        //推消息入MQ , MQ消费消息队列通知业务系统
        $producer = new BizProducer();

        $arrMsg = [
            'serviceClass' => 'CcbOrderService',
            'fun' =>  'refund',   //fun 必填, 值是 Service 的方法名
            'id' => $modelOrder['id'],
            'appId' => $modelOrder['app_uid'],
            'bizOrderNo' => $bizOrderNo,
            'allinpayPayNo' => $modelOrder['allinpay_pay_no'],
            'bizUid' => $modelOrder['biz_uid'],
            'amount' => $modelOrder['amount'],
            'pay_status' => $pay_status
        ];
        $result = $producer->publish($arrMsg);
        if($result !== null){
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }

        //mq通知企业微信
        $producerone = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'SendWxWorkService',
            'fun' =>  'sendWxWork',   //fun 必填, 值是 Service 的方法名
            'appId' => $modelOrder['app_uid'],
            'bizOrderNo' => $bizOrderNo,
            'type'      =>SendWxWorkService::ADD_PAY_REFUND_SUCCESS,
            'workKey'  =>env('SAASWORK.HSB_PUSH', '')
        ];
        $result = $producerone->publish($arrMsg);
        if($result !== null){
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }
        //返回给建行的信息
        $returnData['Svc_Rsp_St'] = self::SUCCESS;
        return json($returnData);
    }

    /**
     * 分账规则回调通知
     * User: cwh  DateTime:2021/9/16 15:18
     */
    public function finRuleCallback(){
        $data = input();
        $notifyData['type'] = 3;
        $notifyData['json_msg'] = json_encode($data);
        $rs = model('CcbNotifyData')->saveData($notifyData);
//$rrr = model('CcbNotifyData')::get(293);
//$data = json_decode($rrr['json_msg'], 1);
//pr($data);

        $biz_order_no = '';
        $py_trn_no ='';
        $res = $this->checkSign($biz_order_no,$py_trn_no,$data,self::FINRULECALLBACK);
        if(!$res){
            $returnData['Svc_Rsp_St'] = self::FAIL;
            return json($returnData);
        }
        $returnData = (new CcbAccountingRulesService())->ruleFun($data);
        return json($returnData);
    }

    /**
     * 商家推送地址
     * User: cwh  DateTime:2021/9/17 11:02
     */
    public function shopCallback(){
        $data = input();
        $notifyData['type'] = 4;
        $notifyData['json_msg'] = json_encode($data);
        $rs = model('CcbNotifyData')->saveData($notifyData);
//$rrr = model('CcbNotifyData')::get(70);
//$data = json_decode($rrr['json_msg'], 1);
//pj($data);
        $actionType = $data['Opr_Tp'] ?? '01';  //01新增,02删除,03 修改

        $returnData['Svc_Rsp_St'] = self::SUCCESS;
        if($actionType == '01'){
            $res = (new CcbShopInfoService())->addShop($data);
        }elseif($actionType == '02'){
            $res = (new CcbShopInfoService())->delShop($data);
        }elseif($actionType == '03'){
            $res = (new CcbShopInfoService())->editShop($data);
        }
//pj($data);
//$res = (new CcbShopInfoService())->editShop($data);
//pr($res);

        if(!$res['result'] && $res['code'] != \app\common\tools\SysEnums::CcbAccountingRulesAlreadyExist)  {
            $returnData['Svc_Rsp_St'] = self::FAIL;
            $logIns = (new AllInPayClient())->getLogIns();
            $logIns->LogMessage("[shopCallback]",Log::INFO,json_encode($res));
            return json($returnData);
        }

        //推送saas系统
        $shopInfo = $res['data'];
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'CcbOrderService',
            'fun' =>  'shopCallBack',   //fun 必填, 值是 Service 的方法名
            'data' => $shopInfo,
            'actionType' => $actionType,
            'appUid' => $shopInfo['app_uid'],
        ];
        $result = $producer->publish($arrMsg);
        if($result !== null){
            $returnData['Svc_Rsp_St'] = self::FAIL;
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }
        return json($returnData);
    }



    public function finRuleCallbackTest(){
        $input = input();
        $actionType = $input['Mnt_Type'] ?? '';  //00新增,01修改
        if($actionType == '00'){
            $rrr = model('CcbNotifyData')::get(86);
        }elseif($actionType == '01'){
            $rrr = model('CcbNotifyData')::get(87);
        }
        $data = json_decode($rrr['json_msg'], 1);

        $returnData = (new CcbAccountingRulesService())->ruleFun($data);
        return json($returnData);

    }

    function shopCallbackTest(){
        $input = input();
        $actionType = $input['Opr_Tp'] ?? '01';  //01新增,02删除,03 修改
        if($actionType == '01'){
            $rrr = model('CcbNotifyData')::get(70);
        }elseif($actionType == '02'){
            $rrr = model('CcbNotifyData')::get(71);
        }elseif($actionType == '03'){
            $rrr = model('CcbNotifyData')::get(72);
        }
        $data = json_decode($rrr['json_msg'], 1);

        $returnData['Svc_Rsp_St'] = self::SUCCESS;
        if($actionType == '01'){
            $res = (new CcbShopInfoService())->addShop($data);
        }elseif($actionType == '02'){
            $res = (new CcbShopInfoService())->delShop($data);
        }elseif($actionType == '03'){
            $data = array_merge($data, $input);
            $res = (new CcbShopInfoService())->editShop($data);
        }

        if(!$res['result'])  {
            $returnData['Svc_Rsp_St'] = self::FAIL;
            $logIns = (new AllInPayClient())->getLogIns();
            $logIns->LogMessage("[shopCallback]",Log::INFO,json_encode($res));
            return json($res);
        }

        //推送saas系统
//        $shopInfo = $res['data'];
//        $producer = new BizProducer();
//        $arrMsg = [
//            'serviceClass' => 'CcbOrderService',
//            'fun' =>  'shopCallBack',   //fun 必填, 值是 Service 的方法名
//            'data' => $shopInfo,
//            'actionType' => $actionType,
//            'appUid' => $shopInfo['app_uid'],
//        ];
//        $result = $producer->publish($arrMsg);
//        if($result !== null){
//            $returnData['Svc_Rsp_St'] = self::FAIL;
//            $mqErrorLog = new MqErrorLog();
//            $mqErrorLog->isUpdate(false)->save($arrMsg);
//        }
        return json($returnData);
    }

    /**
     * 对账单推送地址
     * User: cwh  DateTime:2021/9/17 11:02
     */
    public function FileCallback(){
        $saveData = input();
        $dataFiles = $_FILES;
//        $dataFiles = array(
//            "41060860800345-20211009_zip"=>array(
//                "name"=> "41060860800345-20211009.zip",
//                "type"=> "application\/octet-stream",
//                "tmp_name"=> "/tmp/phpahvzUo",
//                "error"=> 0,
//                "size"=> 1798
//            )
//    );
        $notifyData['type'] = 5;
        $notifyData['json_msg'] = json_encode($dataFiles);
        $rs = model('CcbNotifyData')->saveData($notifyData);

        $runtime_path = Env::get('runtime_path');
        $runtime_path.'file/zip';
        if(!file_exists($runtime_path.'file/zip')){
            mkdir( $runtime_path.'file/zip',0777,true);
        }
        if(!file_exists($runtime_path.'file/package')){
            mkdir( $runtime_path.'file/package',0777,true);
        }
        Db::startTrans();
        foreach ($dataFiles as $key => $val){
            $file = basename($val['name']);
            $temp = explode('.',$file);
            $move_path = $filepath= $runtime_path.'file/zip'."/".$file;
            $move_result = rename($val['tmp_name'],$move_path);//移动文件
            if(!$move_result){
                write_logs('移动文件失败：'.$move_path, 'ccb/import','ccb');
                continue;
            }
//            $yun_path = 'fin/upload/' . date('Ymd') . '/' . md5($file.time()) . '.' . $temp[count($temp)-1];
//            $local_path = $val['tmp_name'];
//            $AliyunOssService = new AliyunOssService();
//            $bool = $AliyunOssService->upload($yun_path,$local_path);
//            if($bool){
//                $yun_path = '/' . $yun_path;
//                $saveData['file'][] = $yun_path;
//            }

            $file_name = $val['name'];
            $filepath= $runtime_path.'file/zip'."/".$file_name;
            $path = $runtime_path.'file'."/package";
            $zipResult = Zip::unzip($filepath,$path);
            if($zipResult){
                //解压成功
                //导入对账明细表
                $path_dir = $path."/".$temp[0];
                $res = $financeReconciliationResult = (new FinanceReconciliation())->addReconciliationByFile($path_dir,$temp[0]);
                if(!$res['result']){
                    Db::rollback();
                    $returnData['Svc_Rsp_St'] = self::FAIL;
                    return json($returnData);
                }
                $res = $financeReconciliationResult = (new FinanceSummary())->addSummaryByFile($path_dir,$temp[0]);
                if(!$res['result']){
                    Db::rollback();
                    $returnData['Svc_Rsp_St'] = self::FAIL;
                    return json($returnData);
                }
                $res = $financeReconciliationResult = (new FinanceSummaryDetail())->addSummaryDetailByFile($path_dir,$temp[0]);
                if(!$res['result']){
                    Db::rollback();
                    $returnData['Svc_Rsp_St'] = self::FAIL;
                    return json($returnData);
                }
            }
        }
        Db::commit();

        $returnData['Svc_Rsp_St'] = self::SUCCESS;
        return json($returnData);
//        $rumtime_path = Env::get('runtime_path');
//        $file_name = "41060860800345-20211009.zip";
//        $filepath= $rumtime_path.'file'."/".$file_name;
//        $path = $rumtime_path.'file'."/package";
//        $a= Zip::unzip($filepath,$path);
//        pr($a);
    }
}