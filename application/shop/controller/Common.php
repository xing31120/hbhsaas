<?php
namespace app\shop\controller;
use app\shop\service\authService;
use think\Controller;
use think\facade\Cookie;
use think\Request;

class Common extends Controller{

    public function login() {
        $this->assign('title','登录');
        return $this->fetch();
    }

    public function checkLogin() {
        // 获取数据
        $data = input();
        if(!isset($data['geetest_challenge'])){
            return errorReturn('请先点击验证码进行验证', -1);
        }
        if(!geetest_check($data,config('extend.geetest'))){
            return errorReturn('验证码错误', -1);
        };

        $authService = new authService();
        $res = $authService->checkLogin($data);
        if(!$res['result']){
            return errorReturn($res['msg'], -1);
        }
        return successReturn(['msg'=>'登录成功']);
    }
    //获取滑动验证码
    public function getLoginCaptcha(){
        return geetest(config('extend.geetest'));
    }
    public function logout(){
        // 1. 清除session
        session(null);
//        echo  123;exit;
        // 2. 退出登录并跳转到登录页面
        $this -> success('logout success', 'common/login','', 1);

    }

    function changeLang(Request $request){
        $lang = $request->param('lang');
        Cookie::set("languageName", $lang);
        switch ($lang){
            case 'zh':
                cookie('think_var','zh-cn');
                break;
            case 'en':
            default :
                cookie('think_var','en-us');
                break;
        }

        return successReturn('success');
    }
}
