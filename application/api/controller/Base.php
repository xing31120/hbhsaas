<?php
namespace app\api\controller;

use AllInPay\Log\Log;
use app\common\service\AllInPay\AllInPayClient;
use app\common\tools\SysEnums;
// use thans\jwt\facade\JWTAuth;
use app\push\service\ToolsService;
use think\App;
use think\Controller;
use think\facade\Env;
use think\Response;
use think\Request;
use think\exception\HttpResponseException;
use think\facade\Config;

class Base  extends Controller{

    // 缓存key值
    const USER_LOGIN_NAME = 'api_uid_';

    //不需要token认证的路径
    protected $noAuthRoute = [
        'Order/wechatpaytest'
    ];

    //不需要检测用户的路径
    protected $noCheckUserRoute = [
        'Member/createmember',
        'Member/getbankcardbin',
        'Order/signalagentpay',
        'Order/getorderdetail',
        'Order/queryreservefundbalance',
        'Order/getordersplitrulelistdetail',
        'CcbOrder/accountingruleslist',
        'CcbOrder/getrules',
        'CcbOrder/getshopinfo'
    ];

    public $userAppInfo = '';
    public $appUid = '';
    public $domain = '';

    function __construct(){
        $request = request();
        $controller =$request->controller();
        $action = $request->action();

        //正式环境才做sign 认证
//        if(!config('app.app_debug')){
//            //sign 认证
//            $request = $this->signAuth($request->param(),$controller,$action);
//        }
//        $request = $this->signAuth($request->param(),$controller,$action);
    }

    public function signAuth($request,$controller,$action){
        //如果是需要验证token的 路由
        if(!in_array($controller.'/'.$action,$this->noAuthRoute)){
            if( empty($request['appId']) || empty($request['sign']) ){
                $result = errorReturn('appId和sign获取信息失败');
                abort(response($result,200,[],'json'));
            }
            $AllInPayClient = new AllInPayClient();
            $this->userAppInfo = model('UsersApp')->getInfoyAppID($request['appId']);
            $this->appUid = $this->userAppInfo['app_uid'];
            $this->domain = $AllInPayClient->getCallBackDomain();
            $this->checkUser($request,$controller,$action);
            $sign = ToolsService::sign($request);
            $logIns = (new AllInPayClient())->getLogIns();
            $logIns->LogMessage("[signAuth]",Log::INFO,json_encode($request).'----'.$sign);
            if(!$sign || $sign != $request['sign']){
                // echo $sign;//测试用
                $result = errorReturn('sign验证失败',SysEnums::AffairError, $sign);
                abort(response($result,200,[],'json'));
            }
        }
        return $request;
    }

    //检测用户
    public function checkUser($request,$controller,$action){
        if(!in_array($controller.'/'.$action,$this->noCheckUserRoute)){
            // var_dump($controller.'/'.$action);
            // var_dump($request);
            if(!isset($request['bizUid'])){
                $result = errorReturn('用户错误-bizUid参数未配置',SysEnums::DataNotExist);
                abort(response($result,200,[],'json'));
            }
            $userInfo = model('Users')->infoByBizUid( $this->appUid, $request['bizUid']);
            if( empty($userInfo)){
                $result = errorReturn('用户错误-找不到该用户:'.$request['bizUid'],SysEnums::DataNotExist);
                abort(response($result,200,[],'json'));
            }
        }
        // return $request;
    }

    /**
     * 签名算法
     * @param [type] $request
     * @return void
     * @date 2020-11-19
     */
    public function sign($strRequest){
        unset($strRequest['sign']);
        $strRequest = array_filter($strRequest);//剔除值为空的参数
        $this->Asciisort($strRequest);
        $sb = '';
        foreach ($strRequest as $entry_key => $entry_value) {
            $sb .= $entry_key . '=' . $entry_value . '&';
        }
        $userAppInfo = model('UsersApp')->getInfoyAppID($strRequest['appId']);
        if(empty($userAppInfo)){
            $result = errorReturn('appId错误');
            abort(response($result,200,[],'json'));
        }
        $sb = trim($sb, "&");
        $sign = md5( $sb . $userAppInfo['secret_key']);
        return $sign;
    }

    public function Asciisort(&$ar) {
        if(is_array($ar)) {
            ksort($ar);
            foreach($ar as &$v) $this->Asciisort($v);
        }
    }

}
