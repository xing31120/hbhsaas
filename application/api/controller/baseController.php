<?php
namespace app\api\controller;

use app\common\tools\SysEnums;
// use thans\jwt\facade\JWTAuth;
use think\App;
use think\Controller;
use think\Response;
use think\Request;
use think\exception\HttpResponseException;

class baseController  extends Controller{

    // 缓存key值
    const USER_LOGIN_NAME = 'api_uid_';

    //不需要token认证的路径
    protected $noAuthRoute = [
        // 'Api/refurbishtoken'
    ];

    public $tokenNew = '';

    
    function __construct(){
        $request = request();
        $controller =$request->controller();
        $action = $request->action();
        //正式环境才做token 认证
        if(!config('app.app_debug')){
            //token认证
            $request = $this->tokenAuth($request->param(),$controller,$action);
        }
        $request = $this->tokenAuth($request->param(),$controller,$action);
    }

    public function rsData($code,$msg = '',$result = []){
        $data = ['code'=>$code,'msg'=>$msg,'data'=>$result];
        return apiOut($data);
    }

    public function tokenAuth($request,$controller,$action){
        //如果是需要验证token的 路由
        if(!in_array($controller.'/'.$action,$this->noAuthRoute)){
            if( empty($request['sign'])){
                return $this->rsData(SysEnums::ApiParamWrong,'参数错误',[]);
            }
            $sign = $this->sign($request);
            // echo $sign;
            if( $sign != $request['sign']){
               return $this->rsData(SysEnums::ApiParamWrong,'参数错误',[]);
            }        
        }
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
        ksort($strRequest);
        $sb = '';
        foreach ($strRequest as $entry_key => $entry_value) {
            $sb .= $entry_key . '=' . $entry_value . '&';
        }
        $userAppInfo = model('UsersApp')->getInfoyAppID($strRequest['appId']);
        $sb = trim($sb, "&");
        $sign = md5( $sb . $userAppInfo['secret_key']);
        return $sign;
    }

    // //获取token
    // public function getToken($uid){
    //     if(empty($uid)){
    //         return $this->rsData(SysEnums::ApiParamWrong,'参数错误',[]);
    //     }
    //     $jwt = JWTAuth::builder(['user_id' => $uid]);;
    //     return $jwt;
    // }

    // //刷新token
    // public function refreshToken(){
    //     $token = JWTAuth::refresh();
    //     if(!$token){
    //         return $this->rsData(SysEnums::IdentityWrong,'token刷新失败!',[]);
    //     }
    //     return $token;
    // }


}
