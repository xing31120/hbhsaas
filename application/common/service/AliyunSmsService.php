<?php
namespace app\common\service;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliyunSmsService{

    //初始化
    private static function initClient(){
        AlibabaCloud::accessKeyClient(env('sms.alisms_access_key',''), env('sms.alisms_access_secret',''))
//            ->regionId('cn-hangzhou')
            ->asDefaultClient();
    }

    //发送单条短信
    public static function sendSms(){
        self::initClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')->action('SendSms')->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
//                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => "",//发送手机号码
                        'SignName' => "",//签名
                        'TemplateCode' => "",//模版ID
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

    //发送多个手机号
    public static function sendBatchSms(){
        self::initClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')->action('SendBatchSms')->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
//                        'RegionId' => "cn-hangzhou",
                        'PhoneNumberJson' => "",//发送手机号码
                        'SignNameJson' => "",//签名
                        'TemplateCode' => "",//模版ID
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
