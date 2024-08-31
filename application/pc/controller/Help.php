<?php
namespace app\pc\controller;



use app\common\model\HbhOrder;
use app\common\model\HbhOrderPay;
use app\common\model\HbhProduct;
use app\common\model\HbhUsers;
use think\Db;
use think\facade\Env;

class Help extends Base {

    function index(){
        return $this->fetch();
    }


}
