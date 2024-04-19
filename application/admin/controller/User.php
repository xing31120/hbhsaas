<?php


namespace app\admin\controller;

use think\Db;
use think\facade\Hook;
use think\facade\Request;
use think\facade\Session;
use app\admin\service\authService;
use app\common\model\AdminAuthGroupAccess as AuthGroupAccess;
use app\common\model\AdminUser;
use app\common\model\AdminUser as UserModel;
use app\common\model\AdminAuthGroup as AuthGroup;
use app\common\model\UsersApp;
use app\web\service\shopUserService;
use app\common\tools\Random;

class User extends Base{

    // 用户登录页面
    public function login(){
        $this -> view -> assign('title', '管理员登录');
        return $this -> view -> fetch('login');
    }

    // 验证后台登录
    public function checkLogin(){
        // 获取数据
        $data = input();
        $authService = new authService();
        $res = $authService->checkLogin($data);

        if(!$res['result']){
            $this -> error($res['msg']);
        }
        $this -> success('登录成功', '/');

    }

    // 退出登录
    public function logout(){
        // 1. 清除session
        session(null);
        // 2. 退出登录并跳转到登录页面
        $this -> success('退出成功', '/login');
    }

    // 用户管理首页
    public function index(){
        $this -> view -> assign('title', '用户管理');
        return $this -> view -> fetch('index');
    }

    // 用户列表
    public function userList(){
        $data = input();
        $usersApp = new UserModel();
//        $shopUserService = new shopUserService();
        $return = $usersApp->getList($data);

        $res = ['count' => $return['count'], 'data' => $return['list']];
        return adminOut($res);
//        return json($return);

    }

    // 添加用户
    public function add(){
        $role = AuthGroup::all();
        $this -> view -> assign('title', '添加用户');
        $this -> view -> assign('roleList', $role);
        return $this -> view -> fetch('add');
    }

    //编辑用户
    public function edit(){
        if (request()->isPost()) {
            $data = input();
            $uid = $data['uid'];
            //新密码处理
            $update['salt'] = Random::alnum(6);
            $update['password'] = md5(md5($data['password'].$update['salt']));
            $map[] = ['id', '=', $uid];
            AdminUser::where($map)->where($map)->update($update);
            // 定义用户角色关联表字段
            $role['role_id'] = $data['role_id'];
            $role['user_id'] = $uid;
            // 用户角色关联表插入数据
            $res = AuthGroupAccess::where('user_id','=',$uid)->update($role);
            $this -> success('操作成功', 'index');
        }
        $id = input('id');
        $this->view->assign('uid',$id);
        $role = AuthGroup::all();
        $this ->view->assign('title', '编辑用户');
        $this ->view->assign('roleList', $role);
        return $this->view->fetch('edit');
    }


    // 执行添加用户的操作
    public function doAdd(){
        // 1. 获取的用户提交的信息
        $data = input();

        $data['logintime'] = time();
        $data['loginip'] = $this -> request -> ip();

        // 2. 执行新增操作，多表插入记录，开启事务操作
        Db::startTrans();
        try {
            //密码盐处理
            $data['salt'] = Random::alnum(6);
            $data['password'] = md5(md5($data['password'].$data['salt']));

            // 插入用户表
            $user = UserModel::create($data);

            // 定义用户角色关联表字段
            $role['role_id'] = $data['role_id'];
            $role['user_id'] = $user -> id;

            // 用户角色关联表插入数据
            $res = AuthGroupAccess::create($role);

            // 提交事务处理
            Db::commit();
        } catch ( \Exception $e ) {
            // 回滚事务
            Db::rollback();
            $this -> error('用户添加失败，请检查');
        }
        $this -> success('用户添加成功', 'index');
    }

    function password(){
        $uid = session('uid');
        $this->view->assign('uid',$uid);
        $this->view-> assign('title', '修改密码');
        return $this->view->fetch('password');
    }

    function doPassword(){
        $data = input();
        $uid = $data['uid'];
        $sourcePassword = $data['source_password'];
        $password = $data['password'];
        // 查询条件
        $map[] = ['id', '=', $uid];
        $result = AdminUser::where($map)->find();
        if(empty($password)){
            $this -> error('新密码不能为空，请检查');
        }
        //比较密码盐
        if ($result['password'] != md5(md5($sourcePassword . $result['salt']))) {
            $this -> error('原密码错误，请检查');
        }
        //新密码处理
        $update['salt'] = Random::alnum(6);
        $update['password'] = md5(md5($data['password'].$update['salt']));
        AdminUser::where($map)->where($map)->update($update);
        $this -> success('修改成功');
    }

}