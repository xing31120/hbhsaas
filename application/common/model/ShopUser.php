<?php
namespace app\common\model;
use app\common\model\basic\Single;
use app\common\model\basic\SingleSubData;

class ShopUser extends SingleSubData{
    public $mcName = 'shop_user_';

    function checkPsw($username, $psw) {
        $password = md5($psw . config('extend.SALT'));
        $rs = $this->where(['username' => $username, 'password' => $password])->find();
        if ($rs == false) {
            return errorReturn('用户名或密码错误', -1);
        }
        unset($rs['password']); //密码字段unset
        return $this->afterLogin($rs);
    }

    function getAllList($shop_id = ''){
        $shop_id && $op['where'][] = ['shop_id','=', $shop_id];
        $op['where'][] = ['status','=', 1];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = $this->getList($op);
        return $list['list'];
    }
}
