<?php


namespace app\push\service;


use AllInPay\Log\Log;
use app\common\service\AllInPay\AllInPayClient;
use GuzzleHttp\Client;

class BaseService{

    function bizCurl($backUrl, $params){
var_dump($backUrl);
        //伪造浏览器UA
        $headers = [
            'user-agent' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36',
        ];
        $guzzle = new Client([
            'timeout' => 10,
            'headers' => $headers
        ]);

        $sign = ToolsService::sign($params);
        if(!$sign){
            return false;
        }
        $params['sign'] = $sign;
//var_dump($backUrl);exit;
var_dump($params);
        try {
            $response = $guzzle->request('POST',$backUrl,[
                'form_params' => $params
            ]);
            //回调内容必须为 success
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
//            echo $e->getErrorMessage() . PHP_EOL;
            $errorMsg = json_encode($backUrl) .'-->'. json_encode($params) .'<-->'. json_encode($e). PHP_EOL;
            $log = new Log();
            $log->LogMessage("[bizCurlError]",Log::ERROR, $errorMsg);
        }

    }
}