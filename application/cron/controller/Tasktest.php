<?php


namespace app\cron\controller;


use think\facade\Config;
use think\facade\Env;

class Tasktest extends Base{

    function aaa(){
        pj(1111);
    }

    function order(){
        $user_id = 123;
        $partner_id = "200010213685";
        \PayBy\PayBy::$caBundle=__DIR__ . '/cert/cacert.pem';
        \PayBy\PayBy::setPartnerId($partner_id);
        $path = app()->getRootPath() . 'extend/Payby/private.pem';
//pj(file_get_contents($path));
        \PayBy\PayBy::setPrivateKeyPath($path);
        \PayBy\PayBy::setApiBase('https://api.payby.com/sgs/api');

//pj(1111);
        $input = input();
        //订单号


        $domain_api = 'http://' . Env::get('route.domain_api').'.' . Env::get('route.domain_top');
        $domain_pc = 'http://' . Env::get('route.domain_pc').'.' . Env::get('route.domain_top');
//pj($domain);
        $randString = getRandomString(4);
        $order_no  =  "PAY".date("YmdHis").$randString;
        $subject = 'subject_1';
        $amount = 3;
        $data = [
            "merchantOrderNo" => $order_no,
            "subject" => $subject,
            'totalAmount' => [
                'currency' => 'AED',
                'amount' => $amount,
            ],
            "notifyUrl" => $domain_api."/order/notifyPay",
            "paySceneCode" => "PAYPAGE",
            "redirectUrl " => $domain_pc."DYNQR",
            "customerId  " => $user_id,
//            "accessoryContent" => "aaaa_4",
        ];
        $rrr = \PayBy\Api\Order::placeOrder($data);
pj(["data" => $data ,'result' => $rrr ]);
    }
}
