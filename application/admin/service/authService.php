<?php

namespace app\admin\service;

use app\common\model\AdminAuthGroup;
use app\common\model\AdminAuthGroupAccess;
use app\common\model\AdminUser;
use app\common\model\ManageRoles;
use think\facade\Session;

class authService
{

    //用户登录检测, 返回用户+用户角色
    function checkLogin($data)
    {

        // 查询条件
        $map[] = ['username', '=', $data['username']];
        $result = AdminUser::where($map)->find();
        $password = md5(md5($data['password']. $result['salt']));
        //比较密码盐
//        if ($result['password'] != md5(md5($data['password'] . $result['salt']))) {
//            return errorReturn('账号或者密码错误', -1);
//        }
//        if (empty($result)){
//            return errorReturn('管理员账号不存在', -1);
//        }
        if (empty($result) || $result['password'] != $password){
            return errorReturn('密码错误', -1);
        }
        if ($result['status'] != 1){
            return errorReturn('账号已禁用', -1);
        }


        $roles = AdminAuthGroupAccess::where('user_id', $result['id'])->column('role_id');
        $accesses = AdminAuthGroup::where('id', 'in', $roles)->column('rules');

        Session::set('uid', $result['id']);
        Session::set('username', $result['username']);
        Session::set('_auth_list_', $accesses);

        return successReturn(['msg' => '登录成功', 'data' => $result]);
    }

}
