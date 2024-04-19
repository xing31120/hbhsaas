<?php
namespace app\callback\controller;

use AllInPay\Log\Log;
use app\common\amqp\BizProducer;
use app\common\model\MqErrorLog;
use app\common\model\NotifyData;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\OrderProcessService;
use app\common\service\workSendMessage\WorkSendMessageService;
use app\common\tools\SysEnums;
use think\Db;
//use think\Request;
use think\facade\Config;
use think\facade\Hook;
use think\facade\Request;
use WeChat\Exceptions\InvalidResponseException;

class AllinPay extends Base {

    function index(){
        $data = input();
        var_dump($data);
        echo 123;
    }

    function notifyCheck(){
        $yunClient = new AllInPayClient();
        $data = input();
        Hook::listen('app_init', $data);
        if(!isset($data['sign'])){
            return false;
        }
        $data['sign'] = str_replace(' ','+',$data['sign']);
        unset($data['signType']);

        $checkData = $yunClient->checkSign($data);
        if(!$checkData['result']){  //sign 验证失败
            return false;
        }

//$row = NotifyData::get(742)->toArray();
//$data = json_decode($row['notify_data'], true);
//echo json_encode($data);exit;

        $data['bizContent'] = isset($data['bizContent']) ? html_entity_decode($data['bizContent']) : [];
        $data['bizContent'] = isset($data['bizContent']) ? json_decode($data['bizContent'], true) : [];
        return $data;
    }

    //0:待支付 10:allinpay异步支付完成 20:财务确认收款 30: 部分代付 40:部分代付确认 50: 分账完成 60: 财务确认订单完成',
    //托管代收or充值订单  请求回调
    function notifyDepositApply(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error');
        }
        if(empty($data['bizContent'])){
            exit('error');
        }
        $bizContent = $data['bizContent'];
        $appUid = $bizContent['extendInfo'];
        $bizOrderNo = $bizContent['bizOrderNo'];
        $allinpayPayNo = $bizContent['payInterfaceOutTradeNo'] ?? '';
        $orderNo = $bizContent['orderNo'] ?? '';

        Db::startTrans();
        //修改订单状态,
        $modelOrder = model('OrderEntry')->allInPayComplete($appUid, $bizOrderNo, $allinpayPayNo,$orderNo);
        if(!$modelOrder['result'])  {
            Db::rollback();
            exit('errorUp');
        }

        $modelOrder = $modelOrder['data'];
        //推消息入MQ , MQ消费消息队列通知业务系统
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'OrderService',
            'fun' =>  'depositApply',   //fun 必填, 值是 Service 的方法名
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

        Db::commit();
        exit('success');


    }

    //托管代付回调
    function notifyAgentPay(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error1');
        }
        if(empty($data['bizContent'])){
            exit('error2');
        }

        Db::startTrans();
        $orderProcessService = new OrderProcessService();
        $res = $orderProcessService->notifyAgentPay($data['bizContent']);
        if(!$res['result'])  {
            Db::rollback();
            exit($res['msg']);
        }

        $allinpayPayNo = $res['data'][0]['payInterfaceOutTradeNo'] ?? '';

        //推消息入MQ , MQ消费消息队列通知业务系统
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'OrderService',
            'fun' =>  'agentPay',   //fun 必填, 值是 Service 的方法名
            'appId' => $data['bizContent']['extendInfo'],
            'bizOrderNo' => $data['bizContent']['bizOrderNo'],
            'allinpayPayNo' => $allinpayPayNo,
        ];
        $result = $producer->publish($arrMsg);
        if($result !== null){
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }

        Db::commit();
        exit('success');

    }

    function frontDepositApply(){
    }

    function notifyRefund(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error1');
        }
        if(empty($data['bizContent'])){
            exit('error2');
        }
        $bizContent = $data['bizContent'];
        $appUid = $bizContent['extendInfo'];
        $bizOrderNo = $bizContent['bizOrderNo'];
        $status = $bizContent['status'];
        $allinpayPayNo = $bizContent['payInterfaceOutTradeNo'] ?? '';
//$status = 'error';

        //修改订单状态,
        if($status == 'OK'){
            $modelOrder = model('OrderRefund')->allInPayComplete($appUid, $bizOrderNo, $allinpayPayNo);
        }else{
            $modelOrder = model('OrderRefund')->allInPayError($appUid, $bizOrderNo);
        }

        if(!$modelOrder['result']) {
            Db::rollback();
            exit('error3');
        }
        $modelOrder = $modelOrder['data'];
        //推消息入MQ , MQ消费消息队列通知业务系统
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'OrderService',
            'fun' =>  'refund',   //fun 必填, 值是 Service 的方法名
            'id' => $modelOrder['id'],
            'appId' => $modelOrder['app_uid'],
            'bizOrderNo' => $bizOrderNo,
            'amount' => $modelOrder['amount'],
            'allinpayPayNo' => $modelOrder['allinpay_pay_no'],
            'bizUid' => $modelOrder['biz_uid'],
            'status' => $status
        ];
        $result = $producer->publish($arrMsg);
        if($result !== null){
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }

        Db::commit();
        exit('success');

    }

    function notifyWithdraw(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error1');
        }
        if(empty($data['bizContent'])){
            exit('error2');
        }
        $bizContent = $data['bizContent'];
        $appUid = $bizContent['extendInfo'];
        $bizOrderNo = $bizContent['bizOrderNo'];
        $status = $bizContent['status'];
        $allinpayPayNo = $bizContent['payInterfaceOutTradeNo'] ?? '';
        $orderNo = $bizContent['orderNo'] ?? '';
        //修改订单状态,
//        $modelOrder = model('OrderWithdraw')->allInPayComplete($appUid, $bizOrderNo);
//        if(!$modelOrder['result'])  exit('error');
//        $modelOrder = $modelOrder['data'];

        //推消息入MQ , MQ消费消息队列通知业务系统
        //修改订单状态,
        if($status == 'OK'){
            $modelOrder = model('OrderWithdraw')->allInPayComplete($appUid, $bizOrderNo, $allinpayPayNo,$orderNo);
        }else{
            $modelOrder = model('OrderWithdraw')->allInPayError($appUid, $bizOrderNo);
        }

        if(!$modelOrder['result']) {
            Db::rollback();
            exit('error3');
        }
        $modelOrder = $modelOrder['data'];
        //推消息入MQ , MQ消费消息队列通知业务系统
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'OrderService',
            'fun' =>  'withdraw',   //fun 必填, 值是 Service 的方法名
            'id' => $modelOrder['id'],
            'appId' => $modelOrder['app_uid'],
            'bizOrderNo' => $bizOrderNo,
            'amount' => $modelOrder['amount'],
            'allinpayPayNo' => $modelOrder['allinpay_pay_no'],
            'bizUid' => $modelOrder['biz_uid'],
            'status' => $status
        ];
        $result = $producer->publish($arrMsg);

        if($result !== null){
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }

        Db::commit();
        exit('success');

    }

    function checkWeChat(){

    }


    function getPaySign(array $data, $signType = 'MD5', $buff = '')
    {
        ksort($data);
        if (isset($data['sign'])) unset($data['sign']);
        foreach ($data as $k => $v) $buff .= "{$k}={$v}&";
//        $buff .= ("key=" . $this->config->get('mch_key'));
        $buff .= ("key=ZzSUPei3MI6Yao8Kill5LBVerOdwqcvG");

        if (strtoupper($signType) === 'MD5') {
            return strtoupper(md5($buff));
        }
        return strtoupper(hash_hmac('SHA256', $buff, $this->config->get('mch_key')));
    }

    function notifyWeChat(){
        $data = input()?: xml2arr(file_get_contents('php://input'));
        if (isset($data['sign']) && $this->getPaySign($data) === $data['sign']) {
            return $data;
        }

var_dump($data);exit;

        echo arr2xml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']); exit;
    }

    function test(){
        $yunClient = new AllInPayClient();
        $data = input();
        $sign = $data['sign'] ?? '';
        $data['sign'] = str_replace(' ', '+', $sign);
        unset($data['signType']);

//$yunClient->Asciisort($data);
//$data = '{"appId":"1581648210684","bizContent":"{&quot;payInterfaceOutTradeNo&quot;:&quot;112014550000834694&quot;,&quot;buyerBizUserId&quot;:&quot;10003&quot;,&quot;amount&quot;:3,&quot;orderNo&quot;:&quot;1329706326858698752&quot;,&quot;extendInfo&quot;:&quot;&quot;,&quot;payDatetime&quot;:&quot;2020-11-20 16:42:15&quot;,&quot;acct&quot;:&quot;622848******0476&quot;,&quot;bizOrderNo&quot;:&quot;SX20201120164056&quot;,&quot;status&quot;:&quot;OK&quot;}","charset":"utf-8","notifyId":"1329706609315319809","notifyTime":"2020-11-20 16:49:30","notifyType":"allinpay.yunst.orderService.pay","sign":"T7l9X6aQ4K30zK8HnMby2PMhv4EIP7u5qQdjic57XVI+TWo33rZG9oOg3ugQXH0Yae964kEDJNvNP9oIqOuzWhfoYgPMTUub5hAAKRpclCv547sz25KmPNIAWh8ObQj8Tgz1\/Fu6ifwGYBsQbNXozISw0Hu\/PponGoclkHZpDKxdqoStafdUFNbL7CotTeBxtSEDJp\/llp5Q2flXNnDAGd2oJykpZcMOBCeipPcJ2ndXNwndtabN2+A8BiLTVfuS3H5oVs9jCcx0agbzCrWs4TRLqDOruF2V\/7QR+ZSkBiULIvkWVzSdhrXt0qvTAC4\/BxKNqYABbevB+2RI5525iw==","version":"1.0"}';
//$data = json_decode($data,true);
//var_dump($data['bizContent']);
//var_dump($data);
//$data['bizContent'] = html_entity_decode($data['bizContent']);
//return $data;
//var_dump(html_entity_decode($data['bizContent']));
//exit;
//        $data['bizContent'] = json_decode(html_entity_decode($data['bizContent']), true);
////return $data['bizContent'];
//        $data = json_encode($data);
//        $checkData = $yunClient->checkResult(json_encode($data));
//        return $checkData;
//        $checkData = $yunClient->checkSign($data);
//$checkData = ['buyerBizUserId' => 'f0ca63f0-d37c-471a-aa42-0ec403d8ab15'];

//        return $checkData;

        //机器人-分账确认通知key
//        $is_test = config('amqp.is_test'); //正式环境 false, 测试环境true
//        $app_debug = config('app.app_debug');   //正式环境 false, 测试环境true
//        if(!$is_test && !$app_debug){
//            echo 123;
//            $key = '7c686d0a-7cef-48fc-b341-c70f2aea1d3d';
//            $content = '有待确认分账通知!';
//            (new WorkSendMessageService($key))->sendMarkDown($content);
//        }

    }


    /**
     * 电子协议签约回调
     * @return void
     * @author LX
     * @date 2020-11-24
     */
    public function notifySignContract(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error');
        }
        if(empty($data['bizContent'])){
            exit('error');
        }
        $bizContent = $data['bizContent'];
        //存日志

        //结果入库
        $bizUserId = $bizContent['bizUserId'];
        $signContractInfo = model('SignContract')->infoByBizUserId($bizUserId);
        $data['sign_status'] = $bizContent['status'] == 'OK' ? 10 : 40;
        $data['contract_no'] = $bizContent['ContractNo'] ?? '';
        $update = model('SignContract')->updateById($signContractInfo['id'], $signContractInfo['app_uid'], $data);

        $userData['sign_contract_status'] = $bizContent['status'] == 'OK' ? 30 : 40;
        $updateUser = model('Users')->updateById($signContractInfo['uid'],$signContractInfo['app_uid'],$userData);
        if( $update && $updateUser){
            if( isset($signContractInfo['biz_back_url'])){
                $producer = new BizProducer();
                $arrMsg = [
                    'serviceClass' => 'MemberService',
                    'fun' =>  'pushSignContract',
                    'appUid'    => $signContractInfo['app_uid'],
                    'bizUid' => $signContractInfo['biz_uid'],
                    'contractNo'   =>  $data['contract_no']??'',//电子协议编号
                    'status'   =>  $data['sign_status'] == 10 ? 1 : 2,//签约结果 1成功 2失败
                    'bizBackUrl'   => $signContractInfo['biz_back_url'],
                ];
                $producer->publish($arrMsg);
            }
            
            exit('success');
        }else{
            exit('error');
        }
    }

    /**
     * 更换绑定手机回调
     * @return void
     * @author LX
     * @date 2020-11-24
     */
    public function notifyUpdatePhoneByPayPwd(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error');
        }
        if(empty($data['bizContent'])){
            exit('error');
        }
        $bizContent = $data['bizContent'];
        $bizUserId = $bizContent['bizUserId'];
        if( $bizContent['status'] == 'OK'){
            $userInfo = model("Users")->infoByBizUserId($bizUserId);
            $data['mobile'] = $bizContent['newPhone'];
            $update = model("Users")->updateById($userInfo['id'],$userInfo['app_uid'],$data);
            if( $update){
                exit('success');
            }else{
                exit('error');
            }
        }
        exit('success');
    }

    /**
     * 设置支付密码回调
     *
     * @return void
     * @author LX
     * @date 2020-11-24
     */
    public function notifySetPayPwd(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error');
        }
        if(empty($data['bizContent'])){
            exit('error');
        }
        $bizContent = $data['bizContent'];
        $bizUserId = $bizContent['bizUserId'];
        //存日志
        exit('success');
    }

    /**
     * 修改支付密码回调
     *
     * @return void
     * @author LX
     * @date 2020-11-24
     */
    public function notifyUpdatePayPwd(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error');
        }
        if(empty($data['bizContent'])){
            exit('error');
        }
        $bizContent = $data['bizContent'];
        $bizUserId = $bizContent['bizUserId'];
        //存日志
        // $bizContent['status'] == 'OK' ? '' :'';
        exit('success');
    }
        
    /**
     * 重置支付密码回调
     *
     * @return void
     * @author LX
     * @date 2020-11-24
     */
    public function notifyResetPayPwd(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error');
        }
        if(empty($data['bizContent'])){
            exit('error');
        }
        $bizContent = $data['bizContent'];
        $bizUserId = $bizContent['bizUserId'];
        //存日志
        // $bizContent['status'] == 'OK' ? '' :'';
        exit('success');
    }

    /**
     * 企业影印件信息采集验证回调
     *
     * @return void
     * @author LX
     * @date 2020-11-24
     */
    public function notifyIdcardCollect(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error');
        }
        if(empty($data['bizContent'])){
            exit('error');
        }
        $bizContent = $data['bizContent'];
        $bizUserId = $bizContent['bizUserId'];

        $realAuthInfo = model("RealAuth")->infoByBizUserId($bizUserId);
        if( empty($realAuthInfo)){
            exit('error');
        }
        $data['ocr_reg_num'] = $realAuthInfo['ocr_reg_num'];
        $data['ocr_id_card'] = $realAuthInfo['ocr_id_card'];

        if( isset($bizContent['ocrRegnumComparisonResult'])){
            $data['ocr_reg_num'] = $bizContent['ocrRegnumComparisonResult'] == 1 ? 1 : 2;
        }
        if( isset($bizContent['ocrIdcardComparisonResult'])){
            $data['ocr_id_card'] = $bizContent['ocrIdcardComparisonResult'] == 1 ? 1 : 2;
        }
        if( isset($bizContent['resultInfo'])){
            $data['ocr_result_info'] = ($realAuthInfo['ocr_result_info']??'').'-'.$bizContent['resultInfo'];
        }
        $update = model("RealAuth")->updateById($realAuthInfo['id'],$realAuthInfo['app_uid'],$data);

        if( $realAuthInfo['status'] == 10 && $data['ocr_reg_num'] == 1 && $data['ocr_id_card'] == 1){
            $userData['real_auth_status']  = 30;
            $update = model('Users')->updateById($realAuthInfo['uid'], $realAuthInfo['app_uid'], $userData);
        }
        
        if( $update){
            //推送
            if( isset($realAuthInfo['biz_back_url']) && $data['ocr_reg_num'] != 0 && $data['ocr_id_card'] != 0){
                $producer = new BizProducer();
                $arrMsg = [
                    'serviceClass' => 'MemberService',
                    'fun' =>  'pushCompanyResult',
                    'appUid' => $realAuthInfo['app_uid'],
                    'bizUid' => $realAuthInfo['biz_uid'],
                    'ocrRegNum'   =>  $data['ocr_reg_num'],//企业信息检测
                    'ocrIdCard'   =>  $data['ocr_id_card'],//法人个人信息检测
                    'ocrResultInfo'   =>  $data['ocr_result_info'],
                    'bizBackUrl'   => $realAuthInfo['biz_back_url'],
                ];
                $producer->publish($arrMsg);
            }
            exit('success');
        }else{
            exit('error');
        }
    }

    /**
     * 设置企业信息回调
     *
     * @return void
     * @author LX
     * @date 2020-11-25
     */
    public function notifySetCompanyInfo(){
        $data = $this->notifyCheck();
        if(!$data){
            exit('error');
        }
        if(empty($data['bizContent'])){
            exit('error');
        }
        $bizContent = $data['bizContent'];
        $bizUserId = $bizContent['bizUserId'];

        $realAuthInfo = model("RealAuth")->infoByBizUserId($bizUserId);
        if( empty($realAuthInfo)){
            exit('error');
        }

        $status = $bizContent['result'];
        $data['company_info_status'] = $status == 2 ? 1 : 2;
        $data['allinpay_fail_reason'] = $bizContent['failReason'] ?? '';
        $data['allinpay_remark'] = $bizContent['remark'] ?? '';

        $update = model("RealAuth")->updateById($realAuthInfo['id'],$realAuthInfo['app_uid'],$data);
        if( $update){
            exit('success');
        }else{
            exit('error');
        }
    }
        





    

}