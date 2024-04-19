<?php


namespace app\common\model\mongo;


use app\common\model\basic\Mongo;

class AdminActionLogMongo extends Mongo
{
    public $mcName = 'admin_action_log_mongo_';
    protected $autoWriteTimestamp = true;

    //类型
    const PLATFORM = 1; //平台
    const SHOP = 2; //商家后台
    const OPERATE_TYPE = [
        'INSERT' => 1, //新增
        'UPDATE' => 2, //修改
        'DEL' => 3, //删除
    ];

    public static function getOperateTypeList(){
        return [
            1 => '新增',
            2 => '修改',
            3 => '删除'
        ];
    }

    /**
     * Notes:后台操作日志
     * @param int $shop_uid
     * @param int $operate_type
     * @param array $insert
     * @return bool
     * @User: qc  DateTime: 2021/1/22 11:25
     */
    public function addLog(int $shop_uid, int $operate_type, $insert)
    {
        $module = request()->module();
        if (in_array($module, ['admin', 'shop'])) {
            $controller = request()->controller();
            $action = request()->action();
            $ip = request()->ip();
            $data = [
                'shop_uid' => $shop_uid,
                'admin_user_id' => $module == 'admin' ? session('uid') : session('login_uid'),
                'type' => $module == 'admin' ? self::PLATFORM : self::SHOP,
                'admin_user_name' => session('username')??'',
                'content' => is_array($insert) ? json_encode($insert, JSON_UNESCAPED_UNICODE) : $insert,
                'module_name' => $module,
                'controller_name' => $controller,
                'action_name' => $action,
                'ip' => $ip,
                'operate_type' => $operate_type,
                'create_time' => time(),
                'update_time' => time()
            ];
            return parent::insert($data);
        }

    }
}