<?php


namespace app\push\service;

use app\common\model\OrderEntry;
use app\common\model\OrderProcess;

use app\common\model\OrderRefund;
use app\common\model\OrderWithdraw;
use app\common\model\UsersApp;
use GuzzleHttp\Client;

/**
 * Class OrderService
 * @package app\push\service
 */
class CcbOrderService extends BaseService {

    public $backUrl = '';

    /**
     * 支付回调 通知业务系统
     * @param $data
     * @return bool
     * User: cwh  DateTime:2021/9/17 16:11
     */
    function paymentCallback($data){
        $orderEntry = new OrderEntry();
        $userAppInfo = UsersApp::get($data['appId']);
        //获取业务系统回调地址
//        $info = $orderEntry->info($data['id'], $data['appId']);
        $info = $orderEntry->where(['id' => $data['id'], 'app_uid' => $data['appId']])->findOrEmpty()->toArray();
        if(!$info){
            return false;
        }
        $this->backUrl = $backUrl = $info['back_url'];
//$backUrl = 'http://devapi-saas.zzsupei.com/FinApi/depositApplyCallback';
        //整合业务系统回调地址需要的参数
        $params = [
            'appId' => $userAppInfo['app_id'],
            'bizOrderNo' => $data['bizOrderNo'],
            'bizUid' => $data['bizUid'],
            'amount' => $data['amount'],
            'allinpayPayNo' => $data['allinpayPayNo'] ?? '',
        ];
        $result = $this->bizCurl($backUrl, $params);
var_dump(substr($result, 0, 10));
        return $result == 'success';
    }

    /**
     * Notes: 退款回调处理  推送业务系统
     * @param $data
     * @return bool
     * User: SongX DateTime: 2020-12-14 18:13
     */
    function refund($data){

        $orderRefund = new OrderRefund();
        $userAppInfo = UsersApp::get($data['appId']);
        //获取业务系统回调地址
        $info = $orderRefund->where(['id' => $data['id'], 'app_uid' => $data['appId']])->findOrEmpty()->toArray();
        if(!$info){
            return false;
        }
        $this->backUrl = $backUrl = $info['biz_back_url'];

        //整合业务系统回调地址需要的参数
        $params = [
            'appId' => $userAppInfo['app_id'],
            'bizOrderNo' => $data['bizOrderNo'],
            'bizUid' => $data['bizUid'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'allinpayPayNo' => $data['allinpayPayNo'] ?? '',
        ];
        $result = $this->bizCurl($backUrl, $params);
        var_dump(substr($result, 0, 10));
        return $result == 'success';
    }

    function shopCallBack($data){
        $apiUrl = config('saas.saas_api_server');
        $backUrl = $apiUrl.'/HsbFinPay/updateShop';
        $userAppInfo = UsersApp::get($data['appUid']);

        $params = [
            'appId' => $userAppInfo['app_id'],
            'data' => $data['data'],
            'actionType' => $data['actionType'],
        ];
        $result = $this->bizCurl($backUrl, $params);
var_dump(substr($result, 0, 10));
        return $result == 'success';
    }

    function rulesCallBack($data){
        $apiUrl = config('saas.saas_api_server');
        $backUrl = $apiUrl.'/HsbFinPay/updateRules';
        $userAppInfo = UsersApp::get($data['appUid']);

        $params = [
            'appId' => $userAppInfo['app_id'],
            'data' => $data['data'],
            'actionType' => $data['actionType'],
        ];
        $result = $this->bizCurl($backUrl, $params);
var_dump(substr($result, 0, 10));
        return $result == 'success';
    }

}