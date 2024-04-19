<?php
namespace app\common\model;
use app\common\model\basic\Single;
use think\model\concern\SoftDelete;

class CcbShopInfo extends Single {

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
//    public $pk = 'id';
    public $mcName = 'ccb_shop_info_';
    public $selectTime = 600;
    public $mcTimeOut = 600; 
    public $mcOpen = true;

}
