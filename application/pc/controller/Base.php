<?php
namespace app\pc\controller;

use app\common\model\HbhCourse;
use app\common\model\HbhCourseCat;
use app\common\model\HbhSeoConfig;
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
    protected $hbh_user;

    /**
     * 初始化方法
     * 创建常量、公共方法
     * 在所有的方法之前被调用
     */
    protected function initialize(){
        $session_shop_id = session('shop_id');
        if(empty(session('shop_id'))){
            $session_shop_id = 1;
        }
        $this->shop_id = $session_shop_id;//初始化应用ID
        //正式环境 要做菜单权限验证
//        if(!config('app.app_debug')){
//            $this->authMenu();
//        }
        $domain= Request::domain();
        $this->assign('domain', $domain);

        $controller = request()->controller();
        $action = request()->action();
        $path = "{$controller}/{$action}";
        $seoInfo = (new HbhSeoConfig())->infoByPath($path);
        $default_description = 'Early childhood education &amp; After School Care Institute in Dubai, UAE, English &amp; Chinese language courses, T.nt dance Studio, Arts &amp; Math Classes,';
        $this->assign('title', $seoInfo['seo_title'] ?: 'Early childhood education- Dance Classes for Adults in Dubai');
        $this->assign('keywords', $seoInfo['seo_keywords'] ?: 'Hand By Hand Institute');
        $this->assign('description', $seoInfo['seo_description'] ?: $default_description);

        // 课程分类
        $cat_list = (new HbhCourseCat())->getAllCourseCatList($this->shop_id);
        $this->assign('course_cat_list', $cat_list);
        $course_list = (new HbhCourse())->getAllCourseList($this->shop_id);
        $this->assign('course_list', $course_list);

//        $this->shop_id = session('shop_id');//初始化应用ID
        $this->hbh_user = json_decode(session('hbh_user'), 1);//初始化应用ID

        $this->assign('shop_id', $this->shop_id);
        $this->assign('userInfo', $this->hbh_user);
        $this->assign('userName', session('hbh_name'));

        $lang = input('lang', 'en-us');
        setcookie('think_var', $lang);
        Lang::saveToCookie($lang);
        $this->assign('language', $lang);
//pj(session('hbh_name'));
//pj($this->hbh_user);
        // 显示侧边导航菜单
//        if($controller=='Index' && $action == 'index'){
//            $this -> showNav();
//        }
    }

    function authMenu(){
        $controller = request()->controller();
        $action = request()->action();
        //ajax不做验证, 不验证的路由地址
        if(!in_array($controller.'/'.$action,$this->noAuthRoute) &&  !Request::isAjax()){
            // 检测用户是否登录
//            $this -> isLogin();
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
        $this->assign('username', session('username'));
        $lang = input('lang', 'zh-cn');
        setcookie('think_var', $lang);
        $this->assign('language', $lang);

        // 3. 渲染菜单
        return $this -> view -> fetch('index');
    }
}
