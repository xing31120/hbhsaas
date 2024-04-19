<?php


namespace app\shop\controller;

use app\common\model\AdminUser;
use app\common\model\ShopAuthGroup;
use app\common\model\Users;
use think\Db;
use think\facade\Hook;
use think\facade\Request;
use think\facade\Session;
use app\admin\service\authService;
use app\common\model\ShopAuthGroupAccess ;
use app\common\model\ShopAuthGroupAccess as AuthGroupAccess;
use app\common\model\ShopUser;
use app\common\model\ShopUser as UserModel;
use app\common\model\ShopAuthGroup as AuthGroup;
use app\common\model\UsersApp;
use app\web\service\shopUserService;
use app\common\tools\Random;

class User extends Base{

    // 用户管理首页
    public function index(){
        $this -> view -> assign('title', '用户管理');
        return $this -> view -> fetch('index');
    }

    // 用户列表
    public function userList(){
        $data = input();
        $usersApp = new UserModel();
        $return = $usersApp->getList($data);
        $groupAccess = AuthGroupAccess::all()->toArray();
        $groupAccess = array_column($groupAccess, 'role_id', 'user_id');
        $authGroup = AuthGroup::all()->toArray();
        $authGroup = array_column($authGroup, 'name', 'id');
        foreach ($return['list'] as &$row){
            $row['role_name'] = $authGroup[$groupAccess[$row['id']] ?? ''] ?? '';
            $row['logintime'] = $row['logintime']>0?date('Y-m-d H:i:s',$row['logintime']):'-';
        }


        $res = ['count' => $return['count'], 'data' => $return['list']];
        return adminOut($res);

    }

    // 添加用户
    public function add(){
        $role = AuthGroup::all();
        $this -> view -> assign('title', '添加用户');
        $this -> view -> assign('roleList', $role);
        return $this -> view -> fetch('add');
    }

    function doDelete(){
        $id = input('id', 0);
        $res = (new UserModel())->del($id);
        $res2 = (new ShopAuthGroupAccess())->where('user_id','=', $id)->delete();
        $this -> success('操作成功', 'index');

    }

    //编辑用户
    public function edit(){
        if (request()->isPost()) {
            $data = input();
            $uid = $data['uid'];
            //新密码处理
            $update['salt'] = Random::alnum(6);
//            $update['password'] = md5(md5($data['password'].$update['salt']));
            $update['password'] = $data['password'];
            $map[] = ['id', '=', $uid];
            UserModel::where($map)->where($map)->update($update);
            // 定义用户角色关联表字段
            $role['role_id'] = $data['role_id'];
            $role['user_id'] = $uid;
            // 用户角色关联表插入数据
            $res = AuthGroupAccess::where('user_id','=',$uid)->update($role);
            $this -> success('操作成功', 'index');
        }
        $id = input('id');
        $access = AuthGroupAccess::where('user_id','=',$id)->findOrEmpty()->toArray();
        $this->view->assign('uid',$id);
        $this->view->assign('access',$access);
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
//            $data['password'] = md5(md5($data['password'].$data['salt']));

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
        $result = ShopUser::where($map)->find();
        if(empty($password)){
            $this -> error('新密码不能为空，请检查');
        }
        //比较密码盐
        if ($result['password'] != $sourcePassword) {
            $this -> error('原密码错误，请检查');
        }
        //新密码处理
        $update['salt'] = Random::alnum(6);
//        $update['password'] = md5(md5($data['password'].$update['salt']));
        $update['password'] = $data['password'];
        ShopUser::where($map)->where($map)->update($update);
        $this -> success('修改成功');
    }

}
