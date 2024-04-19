<?php
namespace app\shop\controller;

use app\common\model\ShopAuthGroup;
use app\common\model\ShopAuthGroupAccess;
use app\common\model\ShopUser;
use think\Controller;
use auth\Auth;
use app\common\model\ShopAuthMenu as Menu;
use think\facade\Lang;
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

    protected $shop_id;

    /**
     * 初始化方法
     * 创建常量、公共方法
     * 在所有的方法之前被调用
     */
    protected function initialize(){
        $this->shop_id = session('shop_id');//初始化应用ID
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
        $uid = session('uid');
        $roles = ShopAuthGroupAccess::where('user_id', $uid) -> column('role_id');
        $accesses = ShopAuthGroup::where('id', 'in', $roles) -> column('rules');

        $menus = Menu::where([
            'status' => 1,
        ]) ->whereIn("id", explode(',', $accesses[0]))-> select() -> toArray();
//pj([$accesses, $menus]);
        $menu =  makeTree($menus);
//var_dump($menu);exit;
        $menuSort = array_column($menu,'sort');
        array_multisort($menuSort,SORT_ASC,$menu);
        // 2. 将分类信息赋值给模板 nav.html
        $this -> view -> assign('menu', $menu);
        $this -> view -> assign('title', '中装SAAS总后台');
        $this->assign('username', session('username'));
        $lang = input('lang', 'zh-cn');
        setcookie('think_var', $lang);
        $this->assign('language', $lang);

        // 3. 渲染菜单
        return $this -> view -> fetch('index');
    }

    /**
     * Notes: 是否超管
     * @return bool
     * User: songX
     * Date: 2024/4/3 17:12:07
     */
    function isAdmin()
    {
        $uid = session('uid');
        $userInfo = (new ShopUser())->info($uid);
        $access = (new ShopAuthGroupAccess())->where('user_id', $uid)->select()->toArray();
        $role_id_arr = array_column($access, 'role_id', 'user_id');

        if(in_array(1, $role_id_arr)){
            return true;
        }
        return false;
//pj($access);
//        if()
    }
}
