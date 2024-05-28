<?php
namespace app\common\service;
//use AlibabaCloud\Client\AlibabaCloud;
//use AlibabaCloud\Client\Exception\ClientException;
//use AlibabaCloud\Client\Exception\ServerException;



use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendBatchSmsRequest;
use \Exception;
use AlibabaCloud\Tea\Exception\TeaError;
use AlibabaCloud\Tea\Utils\Utils;

use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\Tea\Utils\Utils\RuntimeOptions;


class AliyunSmsService{

    //初始化
    public  static function createClient(){
//        env('sms.alisms_access_key',''), env('sms.alisms_access_secret','')
        $config = new Config([
            // 必填，请确保代码运行环境设置了环境变量 ALIBABA_CLOUD_ACCESS_KEY_ID。
            "accessKeyId" => env('sms.alisms_access_key',''),
            // 必填，请确保代码运行环境设置了环境变量 ALIBABA_CLOUD_ACCESS_KEY_SECRET。
            "accessKeySecret" => env('sms.alisms_access_secret','')
        ]);
        // Endpoint 请参考 https://api.aliyun.com/product/Dysmsapi
        $config->endpoint = "dysmsapi.aliyuncs.com";
//pj($config);
        return new Dysmsapi($config);
    }

    //发送单条短信
    public static function sendSms($sms_param){
        $client = self::createClient();
        $sendSmsRequest = new SendSmsRequest($sms_param);

        try {
            // 复制代码运行请自行打印 API 的返回值
            $res = $client->sendSmsWithOptions($sendSmsRequest, new RuntimeOptions([]));
            $result = $res->toMap();
            return $result['body'];
        }
        catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            // 此处仅做打印展示，请谨慎对待异常处理，在工程项目中切勿直接忽略异常。
            // 错误 message
            var_dump($error->message);
            // 诊断地址
            var_dump($error->data["Recommend"]);
            Utils::assertAsString($error->message);
        }
    }


    //发送多个手机号
    public static function sendBatchSms($sms_param)
    {
        $client = self::createClient();
        $sendBatchSmsRequest = new SendBatchSmsRequest($sms_param);

        try {
            // 复制代码运行请自行打印 API 的返回值
            $res = $client->sendBatchSmsWithOptions($sendBatchSmsRequest, new RuntimeOptions([]));
            $result = $res->toMap();
            return $result['body'];
        }
        catch (Exception $error) {
            if (!($error instanceof TeaError)) {
                $error = new TeaError([], $error->getMessage(), $error->getCode(), $error);
            }
            // 此处仅做打印展示，请谨慎对待异常处理，在工程项目中切勿直接忽略异常。
            // 错误 message
            var_dump($error->message);
            // 诊断地址
            var_dump($error->data["Recommend"]);
            Utils::assertAsString($error->message);
        }
    }

}
