<?php

namespace app\api\behavior;


use app\common\model\HbhNotifyLog;
use app\common\model\pay\PayNotifyLog;
use think\facade\Request;

class NotifyLog{

    public function run(Request $request,$params){
        // 行为逻辑
    }
    public function appInit($data, Request $request){
        $this->Asciisort($data);
        $controller = Request::controller();
        $action = Request::action();
        $header = Request::header();

        $notifyData['request_method'] = Request::method(true);
        $notifyData['url'] = $controller.'/'.$action;
        $notifyData['check_result'] = 1;
        $notifyData['header'] = is_array($header) ? json_encode($header) : $header;
//        $checkResult = $yunClient->checkSign($data);
        if(isset($checkData['data'])){
//echo 11;
            $notifyData['notify_data'] = is_array($checkData['data']) ? json_encode($checkData['data']) : $checkData['data'];
        }else{
            $notifyData['notify_data'] = is_array($data) && !empty($data) ? json_encode($data) : $data;;
        }

        $shopUid = 0;
//        $attach = null;
//        if(isset($data['attach'])){
//            $attach = $data['attach'] ;
//        }
//        if(isset($data['passback_params'])){
//            $attach = $data['passback_params'] ;
//        }
//        if($attach !== null){
//            $shopUid = str_replace('shop_','', $attach);
//        }
        $notifyData['shop_uid'] = $shopUid;

//var_dump($notifyData);exit;
        $payNotifyLog = new HbhNotifyLog($notifyData);

        $rs = $payNotifyLog->saveData($shopUid, $notifyData);

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
