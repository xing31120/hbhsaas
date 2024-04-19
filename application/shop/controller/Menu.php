<?php


namespace app\shop\controller;


use app\common\model\ShopAuthMenu as MenuModel;
use think\facade\Request;
use app\common\tools\Tree;

class Menu extends Base{

    // 菜单管理首页
    public function index(){
        $this -> view -> assign('title', '菜单管理');
        return $this -> view -> fetch('index');
    }

    // 菜单列表
    public function menuList(){

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
        $menuList = MenuModel::where($map)
            -> order('sort', 'asc')
//            -> page($page, $limit)
            -> select();

        // 调用think\facade\Tree自定义无限级分类方法
        $menuList = Tree::createTree($menuList);
        $total = count(MenuModel::where($map)->select());
        $result = array("code" => 0, "msg" => "查询成功", "count" => $total, "data" => $menuList);
        return json($result);

        // 3. 设置模板变量
        $this -> view -> assign('empty', '<span class="text-red">没有权限节点</span>');
        $this -> view -> assign('menuList', $menuList);

        // 4. 渲染模板
        return $this -> view -> fetch('index');
    }

    // 添加菜单
    public function add(){
        // 获取菜单列表，赋值给模板
        $menuList = MenuModel::where('status', '=', 1)
            -> order('sort', 'asc')
            -> select();

        // 设置模板变量
        $this -> view -> assign('title', '添加菜单');
        $this -> view -> assign('menuList', $menuList);

        // 渲染模板
        return $this -> view -> fetch('add');
    }

    // 添加菜单操作
    public function doAdd(){
        // 1. 获取的用户提交的信息
        $data = input();

        // 2. 执行新增操作
        $res = MenuModel::create($data);

        // 3. 执行成功
        if ( $res ) {
            $this -> success('菜单添加成功', 'index');
        }

        // 4. 执行失败
        $this -> error('菜单添加失败，请检查');
    }

    // 编辑菜单
    public function edit(){
        // 获取菜单主键id
        $id = input('id');

        // 查询要更新的菜单信息
        $menuInfo = MenuModel::where('id', $id) -> find();

        // 获取权限节点列表
        $menuList = MenuModel::where('status', '=', 1)
            -> order('sort', 'asc')
            -> select();
        $menuList = Tree::createTree($menuList);

        // 设置模板变量
        $this -> view -> assign('title', '编辑菜单');
        $this -> view -> assign('menuInfo', $menuInfo);
        $this -> view -> assign('menuList', $menuList);

        // 渲染模板
        return $this -> view -> fetch('edit');
    }

    // 编辑菜单操作
    public function doEdit(){
        // 1. 获取的用户提交的信息
        $data = input();

        // 2. 取出主键
        $id = $data['id'];

        // 2. 执行新增操作
        $res = MenuModel::update($data);

        // 3. 执行成功
        if ( $res ) {
            $this -> success('菜单编辑成功', 'index');
        }

        // 4. 执行失败
        $this -> error('菜单编辑失败，请检查');
    }

    // 删除菜单
    public function del(){
        // 获取主键id
        $id = input('id');

        // 删除数据
        $res = MenuModel::where('id', $id) -> delete();

        if ( $res ) {
            $this -> success('菜单删除成功', 'index');
        }
        $this -> error('菜单删除失败');
    }
}
