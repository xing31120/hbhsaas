<?php
namespace app\pc\controller;



use app\common\model\HbhUsers;
use app\common\model\ShopAuthGroup;
use app\common\model\ShopAuthGroupAccess;
use app\common\model\ShopUser;
use think\facade\Session;

class Auth extends Base {

    function login() {
        return $this->fetch();
    }

    function reg() {
        return $this->fetch();
    }

    function forgot() {
        return $this->fetch();
    }

    function logincheck() {
        $data = input();
        return $this->login_data($data);
    }

    public function logout(){
        // 1. 清除session
        session(null);
        // 2. 退出登录并跳转到首面
        $this -> success('logout success', '/index','', 1);
    }

    function login_data($data){
        if (empty($data['login_name'])){
            return errorReturn('username is empty', -1);
        }
        if (empty($data['login_password'])){
            return errorReturn('password is empty', -1);
        }
        $data['login_name'] = trim($data['login_name']);

        // 查询条件
        $where[] = function ($query) use ($data) {
            $query->whereRaw("name = :name OR email = :email", ['name' => $data['login_name'], 'email'=> $data['login_name']]);
        };
        $result = HbhUsers::where($where)->find();
        if (empty($result) || $result['password'] != $data['login_password']){
            return errorReturn('password error', -1);
        }
        if ($result['status'] != 1){
            return errorReturn('account is disabled', -1);
        }


        if ( $result ) {
            Session::set('hbh_shop_id', $result['shop_id']);
            Session::set('hbh_uid', $result['id']);
            Session::set('hbh_name', $result['name']);
            Session::set('hbh_email', $result['email']);
            Session::set('hbh_role', $result['role']);
            Session::set('hbh_user', json_encode($result));
            return successReturn(['msg'=> 'login success','data'=> $result, 'url' => '/index' ] );
        }
        return errorReturn(['msg'=> 'login error','data'=> $result, 'url' =>  url('auth/login') ]);

    }

    function register() {
        $data = input();
        $where[] = function ($query) use ($data) {
            $query->whereRaw("name = :name OR email = :email", ['name' => $data['name'], 'email'=> $data['email']]);
        };
        $result = HbhUsers::where($where)->find();
        if (!empty($result) && $result['email'] == $data['email']){
            return errorReturn(['msg'=> 'email occupied','data'=> $result, 'url' => url('auth/reg') ]);
        }
        if (!empty($result) && $result['name'] == $data['name']){
            return errorReturn(['msg'=> 'name occupied','data'=> $result, 'url' => url('auth/reg') ]);
        }
        if(empty($data['password'])){
            return errorReturn(['msg'=> 'password is empty','data'=> $result, 'url' => url('auth/reg') ]);
        }
        if(empty($data['confirm_password'])){
            return errorReturn(['msg'=> 'confirm password is empty','data'=> $result, 'url' => url('auth/reg') ]);
        }

        if($data['confirm_password'] != $data['password']){
            return errorReturn(['msg'=> 'password inconsistency','data'=> $result, 'url' => url('auth/reg') ]);
        }

        unset($data['confirm_password']);
$data['shop_id'] = 1;
        $card = '000'.getRandomCode(7);
        $time = time();
        $data['role'] = 'student';
        $data['watch_history'] = '[]';
        $data['address'] = '';
        $data['card_number'] = $data['serial_num'] = $card;
        $data['expiry_date'] = date("Y-m-d", $time + 30 * 86400);
        $data['residue_quantity'] = 1;
        $data['class_details'] = $data['second_class'] = $data['third_class'] = '';
//pj($data);
        HbhUsers::create($data);

        $data['login_name'] = $data['name'];
        $data['login_password'] = $data['password'];
        return $this->login_data($data);
    }


}
