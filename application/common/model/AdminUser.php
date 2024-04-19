<?php
namespace app\common\model;
use app\common\model\basic\Single;

class AdminUser extends Single{
    public $mcName = 'admin_user_';

    function checkPsw($username, $psw) {
        $password = md5($psw . config('extend.SALT'));
        $rs = $this->where(['username' => $username, 'password' => $password])->find();
        if ($rs == false) {
            return errorReturn('用户名或密码错误', -1);
        }
        unset($rs['password']); //密码字段unset
        return $this->afterLogin($rs);
    }
}
