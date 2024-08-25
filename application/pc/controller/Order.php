<?php
namespace app\pc\controller;



use app\common\model\HbhOrder;
use app\common\model\HbhOrderPay;
use app\common\model\HbhProduct;
use app\common\model\HbhUsers;
use think\Db;
use think\facade\Env;

class Order extends Base {

    private $partner_id = "200010213685";

    function ajaxSubmit(){
        if(empty($this->hbh_user)){
            $this->redirect('Auth/login');
        }
//        $data = input();
        $product_id = input('product_id', '');
        if(empty($product_id)){
            return errorReturn('Please Select a Product');
        }
        $product_info = (new HbhProduct())->info($product_id);


        $user_id = $this->hbh_user['id'];
        $shop_id = $this->shop_id;
        \PayBy\PayBy::setPartnerId($this->partner_id);
        $path = app()->getRootPath() . 'extend/Payby/private.pem';
//pj(file_get_contents($path));
        \PayBy\PayBy::setPrivateKeyPath($path);
        \PayBy\PayBy::setApiBase('https://api.payby.com/sgs/api');
        $domain_api = 'http://' . Env::get('route.domain_api').'.' . Env::get('route.domain_top');
        $domain_pc = 'http://' . Env::get('route.domain_pc').'.' . Env::get('route.domain_top');
//pj($domain);
        $amount = $product_info['amount'] ?? 0;

        //插入订单表
        $randString = getRandomString(4);
        $order_sn  =  "MA".date("YmdHis").$randString;
        $order['shop_id']  = $shop_id;
        $order['order_sn']  = $order_sn;
        $order['order_status']  = HbhOrderPay::order_status_wait;
        $order['user_id']  = $user_id;
        $order['product_id']  = $product_id;
        $order['remarks']  = "";
        $order['total_amount']  = $amount;
        $order['pay_time'] = $order['create_time'] = $order['update_time'] = time();
        $order['id'] = (new HbhOrder())->insert($order);

        //插入支付订单
        $randString = getRandomString(4);
        $order_sn_pay  =  "PAY".date("YmdHis").$randString;
        $subject = $product_info['product_name'] ?? '';
        $order_data['shop_id']  = $shop_id;
        $order_data['order_sn']  = $order_sn_pay;
        $order_data['order_status']  = HbhOrderPay::order_status_wait;
        $order_data['user_id']  = $user_id;
        $order_data['product_id']  = $product_id;
        $order_data['pay_channel']  = 'PAYPAGE';
        $order_data['remarks']  = "sid_".$shop_id;
        $order_data['total_amount']  = $amount;
        $order_data['pay_time'] = $order_data['create_time'] = $order_data['update_time'] = time();
        $order_data['order_id']  = $order['id'];
        $order_data['id'] = (new HbhOrderPay())->insert($order_data);

        $data = [
            "merchantOrderNo" => $order_sn_pay,
            "subject" => $subject,
            'totalAmount' => [
                'currency' => 'AED',
                'amount' => $amount,
            ],
            "notifyUrl" => $domain_api."/order/notifyPay",
            "paySceneCode" => "PAYPAGE",
            "paySceneParams " => [
                "redirectUrl " => $domain_pc,
                "customerId  " => $user_id
            ],
            "reserved" => "sid_".$shop_id,
        ];

        $rrr = \PayBy\Api\Order::placeOrder($data);



        $tokenUrl = $rrr['body']['interActionParams']['tokenUrl'] ?? '';
        if(empty($tokenUrl)){
//pj(['tokenUrl' => $tokenUrl,'data' => $order_data, 'rrr' => $rrr], 0);
            $this->error("error");
        }

        $this->redirect($tokenUrl);
//$this->error("error", 'Index/index');

pj(['tokenUrl' => $tokenUrl,'data' => $order_data, 'rrr' => $rrr]);
        return  successReturn(['data' => $order_data, 'rrr' => $rrr]);
    }


}
