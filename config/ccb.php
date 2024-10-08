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
    //网关地址  http://marketpaykone.dev.jh:8028/
    'server_url' => env('ccb.server_url', 'https://marketpay.ccb.com/'),
    //回调域名
    'call_back_domain' => env('ccb.call_back_domain', 'http://fin-back.zzsupei.com/'),
    //公钥证书路径
    'pub_cert_path' => env('ccb.pub_cert_path', 'data/pub'),
    //私钥证书路径
    'private_cert_path' => env('ccb.private_cert_path', 'data/private.cer'),
    //允许的支付方式
    'pymd_cd' => env('ccb.pymd_cd','01,03,05,06,07,08'),
    
    //订单类型,04 普通订单
    'Py_Ordr_Tpcd' => env('ccb.py_ordr_tpcd', '04'),
    //156 人民币
    'Ccy' => env('ccb.ccy', '156'),
    //小程序的APPID.“Pymd_Cd（支付方式代码）”为“05-微信小程序”时必输
    'Sub_Appid' => env('ccb.sub_appid', '1581648210684'),
    //单位：秒，订单的默认超时时间为30分钟，目前允许的范围为0-1800秒
    'Order_Time_Out' => env('ccb.order_time_out', '900'),
    
    //接口公共参数
    'commonParams' =>[
        //发起渠道编号,默认送5个0
        'Ittparty_Stm_Id' => env('ccb.ittparty_stm_id', '00000'),
        //支付渠道代码,默认送25个0
        'Py_Chnl_Cd' => env('ccb.py_chnl_cd', '0000000000000000000000000'),
        //市场编号,14位市场编号,由银行在正式上线前提供
    ],
    'mktIdBypayMethod'=>[
        '01' => '44200001617129',
        '02' => '44200001617128',
        '03' => '44200001617128',
        '04' => '44200001617128',
        '05' => '44200001617128',
        '06' => '44200001617130',
        '07' => '44200001617128',
        '08' => '44200001617128',
    ],

    'platform_mkt_id' => env('ccb.platform_mkt_id', '41060860800294000000'),
];
