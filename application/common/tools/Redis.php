<?php

namespace app\common\tools;

/**
 * redis类
 * @author lfcheng
 * @date 11/27/20 4:52 PM
 */
class Redis extends \Redis{

    public static function redis() {
        $config = config('cache.redis');
        $con = new \Redis();
        $con->connect($config['host'],$config['port'], 15);
        if($config['password'] != ''){
            $con->auth($config['password']);
        }
        return $con;
    }

}