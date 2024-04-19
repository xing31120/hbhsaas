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
    'server_url'    => env('allinpay.server_url', 'https://cloud.allinpay.com/gateway'),
    //小程序地址
    'server_xcx_url'    => env('allinpay.server_xcx_url', 'https://fintech.allinpay.com/yungateway'),
    //应用私钥证书地址
    'path'          => env('allinpay.path', 'line/pfxfin.pfx'),
    //应用私钥证书密码
    'pwd'           => env('allinpay.pwd', 'Pfx2020'),
    //版本
    'version'       => env('allinpay.version', '1.0'),
    //开放平台公钥证书
    'tl_cert_path'  => env('allinpay.tl_cert_path', 'line/TLCert.cer'),
    //开放平台appId
    'app_id'        => env('allinpay.app_id', '1333951950931640321'),
    // 'app_id'        => env('allinpay.app_id', '00201474'),
    
    //应用secretKey:
    'secret_key'    => env('allinpay.secret_key', 'PTYkrFORkLIJL8ZsOKpWPArtej5ifdla'),
    //华通银行提供给商户的私钥证书文件
    'ht_cert_path'  => env('allinpay.ht_cert_path', ''),
    //华通银行私钥密码
    'ht_cert_pwd'   => env('allinpay.ht_cert_pwd', ''),
    //回调域名
    'call_back_domain'   => env('allinpay.call_back_domain', 'http://fin-back.zzsupei.com/'),
    //会员账户集
    'account_set_no'   => env('allinpay.account_set_no', '400743'),
    //企业信息检测 是否线上认证 // 正式环境 true
    'company_is_auth'   =>  env('allinpaytest.company_is_auth', true),
    //中间账户
    'escrow_user_id'   => env('allinpaytest.escrow_user_id', '2002721'),
    //支付的小程序
    'mini_allinpay'=> env('allinpay.mini_allinpay', 'wx6d6aeeac383f637f'),
    //支付的公众号
    'public_allinpay'=> env('allinpay.public_allinpay', 'wxe9c791cab87b2857')
];
