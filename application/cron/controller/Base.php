<?php
/**
 * cron控制器基类
 */
namespace app\cron\controller;

use app\common\service\shop\ShopInfoService;
use app\common\service\ShopProductVersionService;
use think\Controller;
use think\facade\Env;

class Base extends Controller
{
    /**
     * 初始化方法
     * 创建常量、公共方法
     * 在所有的方法之前被调用
     */
    protected function initialize(){

    }

    public function _empty(){
        return view('admin@demo/tips',[],404);//空操作跳转404
    }

    /**
     * Notes:获取cron域名
     * @return string
     * User: qc DateTime: 2021/9/1 11:36
     */
    protected function getCronDomain()
    {
        return 'http://' . Env::get('route.domain_cron') . '.' . Env::get('route.domain_top');
    }

    /**
     * Notes:商家同步权限验证
     * @param int $shop_uid
     * @return bool
     * User: qc DateTime: 2021/7/9 22:27
     */
    protected function syncAuthShop(int $shop_uid)
    {
        $shop_version = ShopInfoService::getShopVersion($shop_uid);
        if ($shop_uid != config('extend.platform_uid') && $shop_version != ShopProductVersionService::VERSION_FACTORY) {
            return false;
        }
        return true;
    }
}
