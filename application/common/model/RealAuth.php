<?php
namespace app\common\model;
use app\common\model\basic\Common;

class RealAuth extends Common{
    public $mcName = 'real_auth_';
    public $selectTime = 6;
    public $mcTimeOut = 6;
    public $status = [0 => '待审核', 10 =>'成功', 40 =>'失败'];
    public $memberType = [2 => '企业会员',3 => '个人会员'];
    
}
