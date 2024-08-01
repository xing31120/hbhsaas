<?php


namespace app\cron\controller;


class Tasktest extends Base{

    function aaa(){
        pj(1111);
    }

    function order(){
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
        $randString = getRandomString(4);
        $order_no  =  "PAY".date("YmdHis").$randString;
        $subject = 'subject_1';
        $amount = 0.3;


        $data = [
            "merchantOrderNo" => $order_no,
            "subject" => $subject,
            'totalAmount' => [
                'currency' => 'AED',
                'amount' => $amount,
            ],
            "paySceneCode" => "DYNQR",
            "notifyUrl" => "/order/notifyPay",
            "accessoryContent" => "aaaa_4",
        ];
        $rrr = \PayBy\Api\Order::placeOrder($data);
pj([$rrr, $data, ]);
    }
}
