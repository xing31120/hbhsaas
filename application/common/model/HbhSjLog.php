<?php
namespace app\common\model;
use app\common\model\basic\Single;

class HbhSjLog extends Single {
    public $mcName = 'hbh_sj_log_';
//    public $selectTime = 600;
//    public $mcTimeOut = 600;


    const type_add = 1;
    const type_update = 2;
    const type_del = 4;

}
