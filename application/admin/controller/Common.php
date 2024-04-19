<?php
namespace app\admin\controller;
use app\admin\service\authService;
use think\Controller;

class Common extends Controller{

    public function login() {
//        pj(2321);
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
        $this -> success('退出成功', 'common/login','', 1);

    }
}
