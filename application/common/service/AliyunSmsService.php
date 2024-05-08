<?php
namespace app\common\service;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliyunSmsService{

    //初始化
    private static function initClient(){
        AlibabaCloud::accessKeyClient(env('sms.alisms_access_key',''), env('sms.alisms_access_secret',''))
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
    }

    //发送单条短信
    public static function sendSms($sms_param){
        self::initClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')->action('SendSms')->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => $sms_param
                ])
                ->request();
            print_r($result->toArray());
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }
    }

    //发送多个手机号
    public static function sendBatchSms($code,$phone,$sign_name,$param,$shop_uid){
        self::initClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')->action('SendBatchSms')->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumberJson' => json_encode($phone),//发送手机号码
                        'SignNameJson' => json_encode($sign_name),//签名
                        'TemplateCode' => $code,//模版ID
                        'TemplateParamJson' => json_encode($param),//短信内容参数
                    ],
                ])
                ->request();
            print_r($result->toArray());
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
        }
    }
}
