<?php

namespace app\web\service;

use app\common\model\ShopUser;

class shopUserService {


    function shopUserLogin($data){
        if(empty($data['username']) || empty($data['password'])){
            return errorReturn('请输入用户名和密码');
        }

        $rs = model('ShopUser')->checkPsw($data['username'],$data['password']);
        if(!$rs['result']){
            return errorReturn('用户名或密码错误');
        }

        $shopInfo = model('ShopInfo')->getInfoByUid($rs['data']['id']);
//var_dump($shopInfo);exit;
        if(!$shopInfo['result']){
            return errorReturn($shopInfo['msg']);
        }

        $data = $rs;
        $data['shop_info'] = $shopInfo['data'];

        return $data;
    }

    function getList($data){
        $map = [];
        $keywords = isset($data['keywords']) ? $data['keywords'] : '';
        if ( !empty($keywords) ) {
            $map[] = ['username', 'like', '%'.$keywords.'%'];
        }

        // 5. 定义分页参数
        $limit = input('limit',10);
        $page = input('page',1);

        // 6. 获取到所有的用户
        $userList = ShopUser::where($map)
            -> alias('u')
            -> page($page, $limit)
            -> select();

        $total = count(ShopUser::where($map)->select());

        return successReturn(["count" => $total, "data" => $userList]);
    }
}
