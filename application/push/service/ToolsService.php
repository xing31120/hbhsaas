<?php


namespace app\push\service;

use app\common\model\UsersApp;

class ToolsService{

    public static function Asciisort(&$ar) {
        if(is_array($ar)) {
            ksort($ar);
            foreach($ar as &$v) self::Asciisort($v);
        }
    }

    public static function sign($strRequest){
        unset($strRequest['sign']);
        $strRequest = array_filter($strRequest);//剔除值为空的参数
        self::Asciisort($strRequest);
//        $sign = md5(urldecode(http_build_query($strRequest) . $userAppInfo['secret_key']));


//        $sb = '';
//        $sign = '';
//        foreach ($strRequest as $entry_key => $entry_value) {
//            $entry_value = html_entity_decode($entry_value);
//            $sb .= $entry_key . '=' . $entry_value . '&';
//        }
        if(!isset($strRequest['appId'])){
            return false;
        }
        $UsersApp = new UsersApp();
        $userAppInfo = $UsersApp->getInfoyAppID($strRequest['appId']);
        if(empty($userAppInfo)){
            return false;
        }
        $sign = md5(urldecode(http_build_query($strRequest) . $userAppInfo['secret_key']));
        return $sign;
    }
}