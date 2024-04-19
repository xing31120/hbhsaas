<?php

namespace app\common\model;
use app\common\model\basic\Common;
use app\common\model\basic\Single;

class MqErrorLog extends Single {
    public $mcName = 'mq_error_log';
    public $selectTime = 600;
    public $mcTimeOut = 600;
    protected $autoWriteTimestamp = true;


}
