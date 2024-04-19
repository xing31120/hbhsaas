<?php
namespace app\web\controller;

use app\common\tools\SysEnums;
use thans\jwt\facade\JWTAuth;
use think\App;
use think\Controller;
use think\Response;
use think\Request;
use think\exception\HttpResponseException;

class baseController  extends Controller{

    // 缓存key值
    const USER_LOGIN_NAME = 'web_uid_';

    //不需要token认证的路径
    protected $noAuthRoute = [
        'User/register',//注册
        'User/login',//登录
        'Api/refurbishtoken'
    ];

    public $tokenNew = '';

    function __construct(App $app = null){
        $request = $app->request;
        $controller =$request->controller();
        $action = $request->action();
        if(!config('app.app_debug')){
            //token认证
            $request = $this->tokenAuth($request,$controller,$action);
        }




        parent::__construct($app);
    }



    public function rsData($code,$msg = '',$result = []){
        $data = ['code'=>$code,'msg'=>$msg,'data'=>$result];
        return apiOut($data);
    }

    /** token认证
     * 可将token加入header，如下：Authorization:bearer token值
     * 可将token加入到url中作为参数。键名为token
     * 可将token加入到cookie。键名为token
     * 推荐header方式
     * @param $request
     * @param $controller
     * @param $action
     * @return mixed
     */
    //
    public function tokenAuth($request,$controller,$action){
        //如果是需要验证token的 路由
        if(!in_array($controller.'/'.$action,$this->noAuthRoute)){
            $payload = JWTAuth::auth();
            $uid = $payload['user_id']->getValue(); //可以继而获取payload里自定义的字段，比如uid
            if(!$uid){
                $result = errorReturn('token获取信息失败');
                $rep = Response::create($result, 'json')->header([]);
                throw new HttpResponseException($rep);
            }
            $request->uid = $uid;

//            if(!config('app.app_debug')){
//                $tokenNew = $this->refreshToken();
//                $this->tokenNew = $request->tokenNew = $tokenNew;
//            }
//            $request->loginName = Cache::get(self::USER_LOGIN_NAME.$uid);
        }
        return $request;
    }

    //获取token
    public function getToken($uid){
        if(empty($uid)){
            return $this->rsData(SysEnums::ApiParamWrong,'参数错误',[]);
        }
        $jwt = JWTAuth::builder(['user_id' => $uid]);;
        return $jwt;
    }

    //刷新token
    public function refreshToken(){
        $token = JWTAuth::refresh();
        if(!$token){
            return $this->rsData(SysEnums::IdentityWrong,'token刷新失败!',[]);
        }
        return $token;
    }


}
