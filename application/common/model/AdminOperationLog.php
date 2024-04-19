<?php
namespace app\common\model;
use app\common\model\basic\Single;

//后台管理员日志
class AdminOperationLog extends Single{

    public $mcOpen = false;   //缓存开关
    public $mcName = 'admin_operation_log_';
    protected $autoWriteTimestamp = true;//地区表不需要创建时间和修改时间


    static function writeLog($data){
        $logObj = self::create($data);

        return $logObj;
    }
}
