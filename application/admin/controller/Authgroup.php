<?php


namespace app\admin\controller;


use app\common\model\AdminAuthRule as AuthRule;
use app\common\model\AdminAuthGroup as AuthGroupModel;

use app\common\tools\Tree;

class Authgroup extends Base{

    // 角色管理首页
    public function index(){
        // 2. 定义模板变量
        $this -> view -> assign('title', '角色管理');
        // 3. 渲染模板
        return $this -> view -> fetch('index');
    }

    // 角色列表
    public function roleList()
    {
        // 1. 检测用户是否登录
//        $this -> isLogin();

        // 2. 全局查询条件
        $map = []; // 将所有的查询条件封装到这个数组中

        // 条件1
//        $map[] = ['status', '=', 1]; // 这里的等号不允许省略

        // 实现搜索功能
        $keywords = input('keywords');
        if ( !empty($keywords) ) {
            // 条件2
            $map[] = ['name', 'like', '%'.$keywords.'%'];
        }

        // 定义分页参数
        $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
        $page = isset($_GET['page']) ? $_GET['page'] : 1;

        // 2. 获取到所有的分类
        $RoleList = AuthGroupModel::where($map)
            -> page($page, $limit)
            -> select();
        $total = count(AuthGroupModel::where($map)->select());
        $result = array("code" => 0, "msg" => "查询成功", "count" => $total, "data" => $RoleList);
        return json($result);

        // 3. 设置模板变量
        $this -> view -> assign('empty', '<span class="text-red">没有角色</span>');
        $this -> view -> assign('roleList', $RoleList);

        // 4. 渲染模板
        return $this -> view -> fetch('index');
    }

    // 添加角色页面
    public function add()
    {
        return $this -> view -> fetch('add', ['title' => '添加角色']);
    }

    // 执行角色添加
    public function doAdd()
    {
        // 1. 获取的用户提交的信息
        $data = input();

        // 2. 执行新增操作
        $res = AuthGroupModel::create($data);

        // 3. 执行成功
        if ( $res ) {
            $this -> success('角色添加成功', 'index');
        }

        // 4. 执行失败
        $this -> error('角色添加失败，请检查');
    }

    // 编辑角色页面
    public function edit()
    {
        // 获取角色id
        $roleId = input('id');

        // 根据角色id查询要更新的角色信息
        $roleInfo = AuthGroupModel::where('id', $roleId) -> find();

        // 设置模板变量
        $this -> view -> assign('title', '编辑角色');
        $this -> view -> assign('roleInfo', $roleInfo);

        // 渲染模板
        return $this -> view -> fetch('edit');
    }

    // 执行编辑角色操作
    public function doEdit()
    {
        // 1. 获取的用户提交的信息
        $data = input();

        // 2. 取出主键
        $id = $data['id'];

        // 2. 执行新增操作
        $res = AuthGroupModel::update($data);

        // 3. 执行成功
        if ( $res ) {
            $this -> success('角色编辑成功', 'index');
        }

        // 4. 执行失败
        $this -> error('角色编辑失败，请检查');
    }

    // 删除角色
    public function del()
    {
        $id = input('id');


        $res = model('AdminAuthGroup')->del($id);
        if ( $res ) {
            $this -> success('角色删除成功', 'index');
        }
        $this -> error('角色删除失败，请检查');
    }

    // 角色授权页面
    public function access(){

        // 2. 获取角色id
        $roleId = input('id');

        // 3. 根据角色id查询角色信息
        $roleInfo = AuthGroupModel::where('id', $roleId) -> find();

        // 4. 获取权限列表
        $nodes = AuthRule::order('sort', 'asc') -> select();
        // 调用think\facade\Tree自定义无限级分类方法
        $nodes = Tree::createTree($nodes);

        $json = array();  // $json用户存放最新数组，里面包含当前用户组是否有相应的权限
        $access = AuthRule::all();
        $rules = explode(',', $roleInfo['rules']);
        foreach ($nodes as $node) {
            $res = in_array($node['id'], $rules);
            $data = array(
                'nid' => $node['id'],
                'checked' => $node['id'],
                'parentid' => $node['pid'],
                'name' => $node['title'],
                'id' => $node['id'] . '_' . $node['level'],
                'checked' => $res ? true : false
            );
            $json[] = $data;
        }

        // 5. 设置模板变量
        $this -> view -> assign('title', '角色授权');
        $this -> view -> assign('roleInfo', $roleInfo);
        $this -> view -> assign('json', json_encode($json));
        $this -> view -> assign('roleId', $roleId);

        // 渲染模板
        return $this -> view -> fetch('access');
    }

    // 处理角色授权 添加角色-权限表
    public function doAccess()
    {
        if ( request()->isAjax() ) {
            // 1. 获取的用户提交的信息
            $data = input('post.');

            // 2. 取出数据
            $id = $data['id'];
            $rules = $data['rules'];

            // 3. 变更当前角色拥有的权限规则
            if ( isset($rules) ) {
                $datas = '';
                foreach ( $rules as $value ) {
                    $tmp = explode('_', $value);
                    $datas .= ',';
                    $datas .= $tmp[0];
                }
                $datas = substr($datas, 1);
//$aaa = AuthGroupModel::where('id', $id) ->find();
//var_dump($aaa);
                $res = model("AdminAuthGroup")->updateById($id,['rules' => $datas]);
                if ( true == $res ) {
                    return ['status' => 1, 'message' => '角色授权操作成功', 'url' => 'index'];
                }
                return ['status' => 0, 'message' => '角色授权操作失败，请检查'];
            } else {
                return ['status' => 0, 'message' => '未接收到权限节点数据，请检查'];
            }
        } else {
            $this -> error("请求类型错误");
        }

    }
}