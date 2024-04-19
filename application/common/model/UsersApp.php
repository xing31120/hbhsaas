<?php
namespace app\common\model;
use app\common\model\basic\Single;

class UsersApp extends Single {

    public $pk = 'app_uid';
    public $mcName = 'users_app_';
    public $selectTime = 600;
    public $mcTimeOut = 600; 
    public $mcOpen = true; 
    public $status = [10=> '正常', 40=>'已锁定/禁用'];

    function info($id, $field = ''){
        return parent::info($id, $field);
    }
    
    function getInfoyAppID( $appId, $field = ''){
        if (empty($appId)) {
            return false;
        }

        $where[] = ['app_id','=',$appId];
        if ($this->mcOpen) {
            $mcKey = $this->mcName . '_appId_' . $appId;
            $rs = cache($mcKey);
            if ($rs === false) {

                $rs = $this->where($where)->find();
                $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
                cache($mcKey, $rs, $time);
            }
        } else {
            $rs = $this->where($where)->find();
        }
        return $field ? $rs[$field] : $rs;
    }

    function getAllList(){

        $usersAppList = $this->selectTime > 0 ?
            $this->cache(true, $this->selectTime)->select()->toArray() :
            $this->select()->toArray();

        $usersAppList = array_column($usersAppList, null, 'app_uid');

        return $usersAppList;
    }
}
