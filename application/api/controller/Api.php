<?php


namespace app\api\controller;

use app\common\tools\SysEnums;
use app\web\service\shopUserService;
use app\api\controller\baseController;
use app\common\service\UserService;
use think\Db;

class Api extends baseController{

    function refurbishToken(){
        $uid = input('uid', 0);
        if($uid <= 0 ){
            return apiOutError('参数错误', SysEnums::ApiParamWrong);
        }
        $data = [
            'token' => $this->getToken($uid),
            'msg' => 'abcd',
        ];

        return apiOut($data);
    }


}