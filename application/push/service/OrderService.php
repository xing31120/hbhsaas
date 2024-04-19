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
class OrderService extends BaseService {

    public $backUrl = '';

    /**
     * Notes: 托管代收+充值 推送业务系统
     * @param $data
     * @return bool
     * User: SongX DateTime: 2020-12-8 10:01
     */
    function depositApply($data){
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
     * Notes: 托管代付 推送业务系统
     * @param $data
     * @return bool|string
     * User: SongX DateTime: 2020-12-9 18:13
     */
    function agentPay($data){
        $userAppInfo = UsersApp::get($data['appId']);
        $orderProcess = new OrderProcess();
        //获取业务系统回调地址
        $list = $orderProcess->infoByBizOrderProcessNo($data['appId'], $data['bizOrderNo']);
        if(!$list){
            return false;
        }
        $this->backUrl = $backUrl = $list[0]['back_url'];
        $extendParams = $list[0]['extend_params'];
//var_dump($backUrl);
//exit;
        //整合业务系统回调地址需要的参数
        $params = [
            'appId' => $userAppInfo['app_id'],
            'bizOrderNo' => $data['bizOrderNo'],
            'extendParams' => $extendParams,
            'allinpayPayNo' => $data['allinpayPayNo'] ?? '',
        ];
        $result = $this->bizCurl($backUrl, $params);
//var_dump($result);exit;
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

    /**
     * Notes: 提现回调 推送业务系统
     * @param $data
     * @return bool|string
     * User: SongX DateTime: 2020-12-18 19:52
     */
    function withdraw($data){

        $orderRefund = new OrderWithdraw();
        $userAppInfo = UsersApp::get($data['appId']);
        //获取业务系统回调地址
        $info = $orderRefund->where(['id' => $data['id'], 'app_uid' => $data['appId']])->findOrEmpty()->toArray();
        if(!$info){
            return false;
        }
        $this->backUrl = $backUrl = $info['biz_back_url'];
        $extendParams = $info['extend_params'];
//$backUrl = 'http://devapi-saas.zzsupei.com/FinApi/withdrawCallback';
        //整合业务系统回调地址需要的参数
        $params = [
            'appId' => $userAppInfo['app_id'],
            'bizOrderNo' => $data['bizOrderNo'],
            'bizUid' => $data['bizUid'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'extendParams' => $extendParams,
            'allinpayPayNo' => $data['allinpayPayNo'] ?? '',
        ];
        $result = $this->bizCurl($backUrl, $params);
//var_dump($result);
var_dump(substr($result, 0, 10));
        return $result == 'success';
    }

}