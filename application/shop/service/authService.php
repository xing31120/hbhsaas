<?php
namespace app\shop\service;

use app\common\model\ShopAuthGroup;
use app\common\model\ShopAuthGroupAccess ;
use app\common\model\ShopUser;
use think\facade\Session;

class authService{

    //用户登录检测, 返回用户+用户角色
    function checkLogin($data){

        // 查询条件
        $where[] = ['username', '=', $data['username']];
        $where[] = ['password', '=', $data['password']];
//        $where[] = function ($query) use ($data) {
//            $query->whereRaw("username = :name OR email = :email", ['name' => $data['username'], 'email'=> $data['username']]);
//        };
        $result = ShopUser::where($where) -> find();
        if ( $result ) {
            $roles = ShopAuthGroupAccess::where('user_id', $result['id']) -> column('role_id');
            $accesses = ShopAuthGroup::where('id', 'in', $roles) -> column('rules');

            Session::set('shop_id', $result['shop_id']);
            Session::set('uid', $result['id']);
            Session::set('username', $result['username']);
//            Session::set('role', $result['role']);
            Session::set('_auth_list_', $accesses);

            return successReturn(['msg'=> 'login success','data'=> $result] );
        }
        return errorReturn('login error', -1);


    }

}
