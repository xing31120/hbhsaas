<?php

namespace app\callback\behavior;
use app\common\service\AllInPay\AllInPayClient;
use think\facade\Request;

class NotifyLog{

    public function run(Request $request,$params){
        // 行为逻辑
    }
    public function appInit($data, Request $request){
        $this->Asciisort($data);
        $yunClient = new AllInPayClient();
        $data['sign'] = $data['sign'] ?? '';
        $data['sign'] = str_replace(' ','+',$data['sign']);
        unset($data['signType']);
        $controller = Request::controller();
        $action = Request::action();

        //先关闭验签名
//        $checkResult = $yunClient->checkSign($data);
        $notifyData['request_method'] = Request::method(true);
        $notifyData['url'] = $controller.'/'.$action;
        $notifyData['check_result'] = $checkResult['result'] ?? 4;
        if(isset($checkData['data'])){
            $notifyData['notify_data'] = is_array($checkData['data']) ? json_encode($checkData['data']) : $checkData['data'];
        }else{
            $notifyData['notify_data'] = is_array($data) ? json_encode($data) : $data;;
        }

        $data['bizContent'] = isset($data['bizContent']) ? html_entity_decode($data['bizContent']) : [];
        $data['bizContent'] = isset($data['bizContent']) ? @json_decode($data['bizContent'], true) : [];
        $notifyData['biz_order_no'] = $data['bizContent']['bizOrderNo'] ?? '';
        $header = Request::header();
        $notifyData['header'] = is_array($header) ? json_encode($header) : $header;;

        $rs = model('NotifyData')->saveData($notifyData);

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