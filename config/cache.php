<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 驱动方式
    'type'   => 'memcache',
    // 缓存保存目录
    'path'   => '../runtime/cache/',
    // 缓存前缀
    'prefix' => 'saas_',
    // 缓存有效期 0表示永久缓存
    'expire' => 0,
    'host' => env('memcache.memcache_host','127.0.0.1'),
    'port' => env('memcache.memcache_port','11211'),
    //使用方式：use think\cache\driver\Redis;
    'redis' => [
        'type' => 'Redis',
        'host' => env('redis.redis_host','127.0.0.1'),
        'port' => env('redis.redis_port','6379'),
        'password' => env('redis.redis_password',''),
        'expire' => 0,
    ],
];
