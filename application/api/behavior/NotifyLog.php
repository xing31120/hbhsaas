<?php

namespace app\api\behavior;


use app\common\model\HbhNotifyLog;
use think\facade\Log;
use think\facade\Request;

class NotifyLog{

    public function run(Request $request,$params){
        // 行为逻辑
    }
    public function appInit($data, Request $request){ //, Request $request

        $this->Asciisort($data);
        $controller = Request::controller();
        $action = Request::action();
        $header = Request::header();

        $notifyData['request_method'] = Request::method(true);
        $notifyData['url'] = $controller.'/'.$action;
        $notifyData['header'] = is_array($header) ? json_encode($header) : $header;
//        $checkResult = $yunClient->checkSign($data);
        $notifyData['notify_data'] = json_encode($data);
        $shopUid = 0;
        $notifyData['shop_id'] = $shopUid;
        $notifyData['created_at'] = date("Y-m-d H:i:s");
        $notifyData['updated_at'] = date("Y-m-d H:i:s");

        $payNotifyLog = new HbhNotifyLog($notifyData);
        $rs = $payNotifyLog->saveData($notifyData);
//pj([$notifyData, $rs]);

    }

    public function appEnd($params){
        echo "结束";
    }

    public function Asciisort(&$ar) {
        if(is_array($ar)) {
            ksort($ar);
            foreach($ar as &$v) $this->Asciisort($v);
        }
    }
}
