<?php
namespace app\common\model;
use app\common\model\basic\Common;

class SignContract extends Common {
    public $mcName = 'sign_contract_';
    public $selectTime = 6;
    public $mcTimeOut = 6;
    public $signStatus = [0 => '待审核', 10 =>'成功', 40 =>'失败'];
    

    





}
