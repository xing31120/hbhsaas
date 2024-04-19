<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

use think\facade\Env;

return [
    'is_test' => Env::get('amqp.is_test', false), //false 是正式环境
    'host' => Env::get('amqp.host', '172.26.35.168'),
    'port' => Env::get('amqp.port', '5672'),
    'user' => Env::get('amqp.user', 'betafinance'),
    'password' => Env::get('amqp.password', 'BovF1FsnxlDKEB57'),
    'vhost' => Env::get('amqp.vhost', '/'),
    'prefix' => Env::get('amqp.prefix', 'beta_finance_'),
];
