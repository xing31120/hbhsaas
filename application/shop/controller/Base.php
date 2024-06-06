<?php
namespace app\shop\controller;

use app\common\model\HbhSjLog;
use app\common\model\ShopAuthGroup;
use app\common\model\ShopAuthGroupAccess;
use app\common\model\ShopUser;
use phpseclib3\Crypt\EC;
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
    protected $lang;

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
        $this->lang = input('lang', 'zh-cn');
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
        $role_list = (new ShopAuthGroup())->whereIn('id', $role_id_arr)->select()->toArray();
        $role_name = array_column($role_list,'name');
//pj($role_list);
        if(in_array('superadmin', $role_name)){
            return true;
        }
        return false;
//pj($access);
//        if()
    }

    /**
     * @param array $old_info     新增时 $old_info 为空
     * @param array $new_info     删除时 $new_info 为空
     * @param int   $type         操作类型, 1: 新增, 2:修改, 4:删除
     * @return void
     */
    function add_log(array $old_info, array $new_info, int $type = 2){
//        $this->shop_id = session('shop_id');//初始化应用ID
        $row['module']      = 'shop';
        $row['controller']  = request()->controller();
        $row['action']      = request()->action();
        $row['ip']          = request()->ip();
        $row['shop_id']     = session('shop_id');
        $row['create_time'] = time();
        $row['type']        = $type;
        $row['create_at']   = date("Y-m-d H:i:s");
        $row['admin_id']    = session('uid');
        $row['admin_name']  = session('username');

        $ignore_key = ['password', 'create_time', 'update_time'];

        $before_data = $after_data = [];
        if($type == HbhSjLog::type_update){
            foreach ($new_info as $new_key => $new_val) {
                $old_val = $old_info[$new_key] ?? '-@';
                if($old_val === '-@'){  //旧数据异常的, 跳过
                    continue;
                }

                // 忽略某些key的 变化
                if(in_array($new_key, $ignore_key)) continue;

                if($old_val != $new_val){
                    $before_data[$new_key] = $old_val;
                    $after_data[$new_key] = $new_val;
                }
            }
        }else{
            $before_data = $old_info;
            $after_data = $new_info;
        }

        if(empty($before_data) && empty($after_data)){
            return;
        }

        $row['before_data'] = json_encode($before_data);
        $row['after_data'] = json_encode($after_data);
        HbhSjLog::create($row);
    }
}
