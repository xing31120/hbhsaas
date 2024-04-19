<?php


namespace app\shop\controller;


use app\common\model\ShopAuthRule as AuthRuleModel;
use app\common\tools\Tree;

class Authrule extends Base
{
    // 权限节点管理首页
    public function index()
    {
        // 2. 定义模板变量
        $this -> view -> assign('title', '权限节点管理');
        // 3. 渲染模板
        return $this -> view -> fetch('index');
    }

    // 权限节点列表
    public function nodeList()
    {

        // 2. 全局查询条件
        $map = []; // 将所有的查询条件封装到这个数组中

        // 3. 条件1
//        $map[] = ['status', '=', 1]; // 这里的等号不允许省略

        // 4. 实现搜索功能
        $keywords = input('keywords');
        if ( !empty($keywords) ) {
            // 条件2
            $map[] = ['name', 'like', '%'.$keywords.'%'];
        }

        // 5. 定义分页参数
//        $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
//        $page = isset($_GET['page']) ? $_GET['page'] : 1;

        // 6. 获取到所有的权限节点
        $nodeList = AuthRuleModel::where($map)
            -> order('sort', 'asc')
//            -> page($page, $limit)
            -> select();

        // 调用think\facade\Tree自定义无限级分类方法
        $nodeList = Tree::createTree($nodeList);
        $total = count(AuthRuleModel::where($map)->select());
        $result = array("code" => 0, "msg" => "查询成功", "count" => $total, "data" => $nodeList);
        return json($result);

        // 3. 设置模板变量
        $this -> view -> assign('empty', '<span class="text-red">没有权限节点</span>');
        $this -> view -> assign('nodeList', $nodeList);

        // 4. 渲染模板
        return $this -> view -> fetch('index');
    }

    // 添加权限节点
    public function add()
    {
        // 获取权限节点列表，赋值给模板
        $nodeList = AuthRuleModel::where('level', '<', 3)
            -> order('sort', 'asc')
            -> select();

        // 设置模板变量
        $this -> view -> assign('title', '添加权限节点');
        $this -> view -> assign('nodeList', $nodeList);

        // 渲染模板
        return $this -> view -> fetch('add');
    }

    // 执行权限节点添加功能
    public function doAdd()
    {
        // 1. 获取的用户提交的信息
        $data = input();

        // 2. 执行新增操作
        $res = AuthRuleModel::create($data);

        // 3. 执行成功
        if ( $res ) {
            $this -> success('权限节点添加成功', 'index');
        }

        // 4. 执行失败
        $this -> error('权限节点添加失败，请检查');
    }

    // 编辑权限节点
    public function edit()
    {
        // 获取权限id
        $nodeId = input('id');

        // 根据权限id查询要更新的权限信息
        $nodeInfo = AuthRuleModel::where('id', $nodeId) -> find();

        // 获取权限节点列表，赋值给模板
        $nodeList = AuthRuleModel::where('level', '<', 3)
            -> order('sort', 'asc')
            -> select();

        // 设置模板变量
        $this -> view -> assign('title', '编辑权限节点');
        $this -> view -> assign('nodeInfo', $nodeInfo);
        $this -> view -> assign('nodeList', $nodeList);

        // 渲染模板
        return $this -> view -> fetch('edit');
    }

    // 执行权限编辑操作
    public function doEdit()
    {
        // 1. 获取的用户提交的信息
        $data = input();

        // 2. 取出主键
//        $id = $data['id'];

        // 2. 执行新增操作
        $res = AuthRuleModel::update($data);

        // 3. 执行成功
        if ( $res ) {
            $this -> success('权限编辑成功', 'index');
        }

        // 4. 执行失败
        $this -> error('权限编辑失败，请检查');
    }

    // 设置权限显示状态
    public function setStatus()
    {
        // 1. 获取用户提交的数据
        $data = input();

        // 2. 取出数据
        $id = $data['id'];
        $status = $data['status'];

        // 3. 更新数据，判断显示状态，如果为1则更改为0，如果为0则更改为1
        if ( $status == 1 ) {
            $res = AuthRuleModel::where('id', $id)
                -> data('status', 0)
                -> update();
            if ( $res ) {
                return ['status' => 1, 'message' => '<i class="iconfont">&#xe645;</i> 状态变更成功'];
            } else {
                return ['status' => 0, 'message' => '<i class="iconfont">&#xe646;</i> 操作失败，请检查'];
            }
        } else {
            $res = AuthRuleModel::where('id', $id)
                -> data('status', 1)
                -> update();
            if ( $res ) {
                return ['status' => 1, 'message' => '<i class="iconfont">&#xe645;</i> 状态变更成功'];
            } else {
                return ['status' => 0, 'message' => '<i class="iconfont">&#xe646;</i> 操作失败，请检查'];
            }
        }
    }

    // 执行权限的删除操作
    public function doDelete()
    {
        // 1. 获取到要删除的主键ID
        $id = input('id');

        // 2. 执行删除操作
        $res = AuthRuleModel::where('id', $id) -> delete();

        // 3. 判断操作是否成功
        if ( $res ) {
            $this -> success('权限删除成功', 'index');
        } else {
            $this -> error('权限删除失败', 'index');
        }
    }
}
