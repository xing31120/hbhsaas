<?php
namespace app\admin\controller;

use think\Controller;
use auth\Auth;
use app\common\model\AdminAuthMenu as Menu;
use think\facade\Request;

class Base  extends Controller{

    //不验证的路由地址, 控制器大写, 方法名全小写
    protected $noAuthRoute = [
        'Common/login',//登录
        'Common/checklogin',//登录验证
        'Index/swagger',
        'OrderProcess/ajaxlist',
    ];

    protected $returnData = [
        'result' => true,
        'msg' => '',
        'code' => 0
    ];

    protected $appUid;

    /**
     * 初始化方法
     * 创建常量、公共方法
     * 在所有的方法之前被调用
     */
    protected function initialize(){
        $this->appUid = config('extend.app_uid');//初始化应用ID
        //正式环境 要做菜单权限验证
//        if(!config('app.app_debug')){
            $this->authMenu();
//        }
        $controller = request()->controller();
        $action = request()->action();
        // 显示侧边导航菜单
        if($controller=='Index' && $action == 'index'){
            $this -> showNav();
        }
    }

    function authMenu(){
        $controller = request()->controller();
        $action = request()->action();
        //ajax不做验证, 不验证的路由地址
        if(!in_array($controller.'/'.$action,$this->noAuthRoute) &&  !Request::isAjax()){
            // 检测用户是否登录
            $this -> isLogin();
            // 权限检测
            $auth = Auth::instance();

            if ( $action == '' ) {
                $node =  '/' .$controller;
            } else {
                $node = '/' .$controller . '/' . $action;
            }
            $uid = session('uid');

            if ( !$auth -> check($node, $uid) ) {
//                $this -> error('您没有权限访问，请联系管理员'.$node, 'admin/Index/console');
            }
//            exit;
        }
    }

    /**
     * 检测用户是否登录
     * 调用位置：后台入口 admin.php/index/index
     */
    protected function isLogin(){
        if ( !session('?uid') ) {
            $this -> redirect('common/login');
        }
    }

    /**
     * 显示侧边栏导航
     */
    public function showNav(){

        $menus = Menu::where([
            'status' => 1,
        ]) -> select() -> toArray();
        $menu =  makeTree($menus);
//var_dump($menu);exit;
        $menuSort = array_column($menu,'sort');
        array_multisort($menuSort,SORT_ASC,$menu);
        // 2. 将分类信息赋值给模板 nav.html
        $this -> view -> assign('menu', $menu);
        $this -> view -> assign('title', '中装SAAS总后台');

        // 3. 渲染菜单
        return $this -> view -> fetch('index');
    }
}
