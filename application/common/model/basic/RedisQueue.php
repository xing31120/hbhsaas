<?php

namespace app\common\model\basic;
use app\common\model\mongo\AdminActionLogMongo;
use think\Model;

/**
 * Redis队列统一操作模型
 * @author lfcheng
 * @date 4/29/21 5:50 PM
 */
class RedisQueue extends Model{

    const ADMIN_ACTION_LOG_QUEUE = 'admin_action_log_queue';//操作日志队列

    public static function viewAdminActionLogQueue(){
        $redis = redis();
        $key_name = self::ADMIN_ACTION_LOG_QUEUE;
        $len = $redis->lLen($key_name);
        if($len<=0){
            return [];
        }
        $data = $redis->lRange($key_name,0,-1);
        return empty($data) ? [] : $data;
    }

    public static function setAdminActionLogQueue($type,$data,$shop_uid){
        return true;//定时任务没有开启，暂时不记录日志
        $module = request()->module();
        if (in_array($module, ['admin', 'shop'])) {
            $controller = request()->controller();
            $action = request()->action();
            $ip = request()->ip();
            $data = [
                'shop_uid' => $shop_uid,
                'admin_user_id' => $module == 'admin' ? session('uid') : session('login_uid'),
                'type' => $module == 'admin' ? AdminActionLogMongo::PLATFORM : AdminActionLogMongo::SHOP,
                'admin_user_name' => session('username') ?? '',
                'content' => is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data,
                'module_name' => $module,
                'controller_name' => $controller,
                'action_name' => $action,
                'ip' => $ip,
                'operate_type' => $type,
                'create_time' => time(),
                'update_time' => time()
            ];
            $redis = redis();
            $key_name = self::ADMIN_ACTION_LOG_QUEUE;
            $redis->lPush($key_name,json_encode($data,JSON_UNESCAPED_UNICODE));
        }
    }

    public static function getAdminActionLogQueue($limit = 100){
        $redis = redis();
        $key_name = self::ADMIN_ACTION_LOG_QUEUE;
        $len = $redis->lLen($key_name);
        if($len<=0){
            return [];
        }
        $data = [];
        for ($i = 1; $i <= $limit; $i ++) {
            $res = $redis->rPop($key_name);
            if(empty($res)){
                continue;
            }
            $rs = is_object($res) ? get_object_vars($res) : $res;
            $data[] = json_decode($rs);
        }
        return $data;
    }

}