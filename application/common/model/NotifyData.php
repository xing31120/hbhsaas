<?php


namespace app\common\model;


use app\common\model\basic\Common;
use app\common\model\basic\Single;

class NotifyData extends Single {
    public $mcName = 'notify_data_';
    public $mcOpen = false;
    public $selectTime = false;
    public $mcTimeOut = 0;

}