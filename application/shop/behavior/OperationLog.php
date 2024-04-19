<?php

namespace app\admin\behavior;
use app\common\model\AdminOperationLog;
use think\facade\Session;
use think\Request;

class OperationLog{

    public function run(Request $request,$params){
        // 行为逻辑
    }
    public function appInit($params, Request $request){

        $controller = $request->controller();
        $action = $request->action();
        $log['admin_user_id']   = Session::get('uid');
        $log['admin_user_name'] = Session::get('username');
        $log['content']         = "操作人<{$log['admin_user_name']}> 对路径{$controller}/{$action} 进行了操作, 请求参数为《".json_encode($params)."》";

        AdminOperationLog::writeLog($log);

    }


    public function appEnd($params){
        echo "结束";
    }
}