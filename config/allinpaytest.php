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
// | 应用设置
// +----------------------------------------------------------------------

return [
    //网关地址  http://test.allinpay.com/op/gateway
    'server_url'    => env('allinpaytest.server_url', 'http://test.allinpay.com/op/gateway'),
    //小程序地址
    'server_xcx_url'    => env('allinpay.server_xcx_url', 'http://116.228.64.55:6900/yungateway'),
    //应用私钥证书地址
    'path'          => env('allinpaytest.path', 'data/1581648210684.pfx'),
    //应用私钥证书密码
    'pwd'           => env('allinpaytest.pwd', '123456'),
    //版本
    'version'       => env('allinpaytest.version', '1.0'),
    //开放平台公钥证书
    'tl_cert_path'  => env('allinpaytest.tl_cert_path', 'data/TLcert-test-new.cer'),
    //开放平台appId
    'app_id'        => env('allinpaytest.app_id', '1581648210684'),
    //应用secretKey:
    'secret_key'    => env('allinpaytest.secret_key', 'WaHVZNHZYX3v4si1bBTVseIwEMPMcKzL'),
    //华通银行提供给商户的私钥证书文件
    'ht_cert_path'  => env('allinpaytest.ht_cert_path', 'data/DDW2.pfx'),
    //华通银行私钥密码
    'ht_cert_pwd'   => env('allinpaytest.ht_cert_pwd', '111111'),
    //回调域名
    'call_back_domain'   => env('allinpaytest.call_back_domain', 'http://betafin-back.zzsupei.com/'),
    //会员账户集
    'account_set_no'   => env('allinpaytest.account_set_no', '400193'),
    //企业信息检测 是否线上认证 //测试环境false
    'company_is_auth'   =>  env('allinpaytest.company_is_auth', false),
    //中间账户
    'escrow_user_id'   => env('allinpaytest.escrow_user_id', '20003'),
    //支付的小程序
    'mini_allinpay'=> env('allinpay.mini_allinpay', 'wxbf1962cac1ef7c9a'),
    //支付的公众号
    'public_allinpay'=> env('allinpay.public_allinpay', 'wxe9c791cab87b2857')
];
