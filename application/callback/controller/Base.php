<?php
namespace app\callback\controller;

use think\Controller;

class Base  extends Controller{


    protected $returnData = [
        'result' => true,
        'msg' => '',
        'code' => 0
    ];

    protected $appUid;

    /**
     * 初始化方法
     * 创建常量、公共方法
     * 在所有的方法之前被调用
     */
    protected function initialize(){
        $this->appUid = config('extend.app_uid');//初始化应用ID
    }


    /**
     * 检测用户是否登录
     * 调用位置：后台入口 admin.php/index/index
     */
    protected function isLogin(){
        if ( !session('?uid') ) {
            $this -> redirect('/login');
        }
    }

}
