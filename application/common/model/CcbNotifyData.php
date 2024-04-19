<?php


namespace app\common\model;


use app\common\model\basic\Common;
use app\common\model\basic\Single;

class CcbNotifyData extends Single {
    public $mcName = 'ccb_notify_data_';
    public $mcOpen = false;
    public $selectTime = false;
    public $mcTimeOut = 0;

}