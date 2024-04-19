<?php


namespace app\push\service;

use app\common\model\UsersApp;
use GuzzleHttp\Client;

/**
 * Class MemberService
 * @package app\push\service
 */
class MemberService extends BaseService {

    public $backUrl = '';

    /**
     * 电子签约回调商户
     *
     * @param [type] $data
     * @return void
     * @author LX
     * @date 2020-12-09
     */
    function pushSignContract($data){
        $userAppInfo = UsersApp::get($data['appUid']);
        $this->backUrl = $backUrl = $data['bizBackUrl'];
        $params = [
            'appId' => $userAppInfo['app_id'],
            'bizUid' => $data['bizUid'],
            'contractNo'   =>  $data['contractNo'],
            'status'   =>  $data['status'],
        ];
        $result = $this->bizCurl($backUrl, $params);
var_dump($result);
        return $result == 'success';
    }

    /**
     * 企业信息结果回调商户
     *
     * @param [type] $data
     * @return void
     * @author LX
     * @date 2020-12-09
     */
    function pushCompanyResult($data){
        $userAppInfo = UsersApp::get($data['appUid']);
        $this->backUrl = $backUrl = $data['bizBackUrl'];
        $params = [
            'appId' => $userAppInfo['app_id'],
            'bizUid' => $data['bizUid'],
            'ocrRegNum'   =>  $data['ocrRegNum'],//企业信息检测
            'ocrIdCard'   =>  $data['ocrIdCard'],//法人个人信息检测
            'ocrResultInfo'   =>  $data['ocrResultInfo'],
        ];
        $result = $this->bizCurl($backUrl, $params);
var_dump($result);
        return $result == 'success';
    }

}