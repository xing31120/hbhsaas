<?php
namespace app\common\model;
use app\common\model\basic\Common;

class UsersPubKey extends Common {
    public $mcName = 'users_pub_key_';
    public $selectTime = 6;
    public $mcTimeOut = 6;


    /**
     * 根据appuid和支付方式获取公钥
     * @param $app_uid
     * @param $method
     * @return array|bool
     * User: cwh  DateTime:2021/10/29 15:47
     */
    function infoByAppUidAndMethod($app_uid,$method){
        if (empty($app_uid) || empty($method)) {
            return false;
        }
        $where[] = ['app_uid','=',$app_uid];
        $where[] = ['pay_method','=',$method];

        //缓存开启并且命中
//        $mcKey = $this->mcName . '_' . $bizOrderNO;
//        if($this->mcOpen && cache($mcKey) !== false){
//            return cache($mcKey)->toArray();
//        }
        //查询失败直接返回false
        $rs = $this->where($where)->find();
        if(empty($rs)){
            return false;
        }
        //设置缓存
//        if($this->mcOpen){
//            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
//            cache($mcKey, $rs, $time);
//        }
        return $rs->toArray();
    }
}
