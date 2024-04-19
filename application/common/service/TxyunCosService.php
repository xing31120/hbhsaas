<?php
namespace app\common\service;
use Qcloud\Cos\Client as CosClient;

class TxyunCosService{
    static $secretId;
    static $secretKey;
    static $region;
    static $bucket;
    static $cos_domain;

    protected function __construct(){
        self::$secretId = env('cos.cos_secret_id','');
        self::$secretKey = env('cos.cos_secret_key','');
        self::$region = env('cos.cos_region','');
        self::$bucket = env('cos.cos_bucket','');
        self::$cos_domain = env('cos.cos_domain','');
    }

    //初始化
    private static function initClient(){
        $client = new CosClient(
            [
                'region' => self::$region,
                'schema' => 'https', //协议头部，默认为http
                'credentials'=> [
                    'secretId'  => self::$secretId ,
                    'secretKey' => self::$secretKey
                ]]);
        return $client;
    }

    //上传文件($object是云空间内对应的路径,$filePath是本地文件)
    public static function upload($object,$filePath){
        $result = false;
        try{
            $cos_client = self::initClient();
            $file = fopen($filePath, 'rb');
            if ($file) {
                $result = $cos_client->upload(self::$bucket,$object,$file);
            }
            unlink($filePath);//删除临时存储在服务器上的文件
            if($result){
                return true;
            }else{
                return false;
            }
        }catch (\Exception $e){
            unlink($filePath);//删除临时存储在服务器上的文件
            echo $e;
            return false;
        }
    }

    //删除文件
    public static function delObject($url){
        $object = str_replace(self::$cos_domain . '/', '', $url);
        try{
            $cos_client = self::initClient();
            $result = $cos_client->deleteObject(['Bucket'=>self::$bucket,'key'=>$object,'VersionId'=>'string']);
            if($result){
                return true;
            }else{
                return false;
            }
        }catch (\Exception $e){
            echo $e;
            return false;
        }
    }

    //获取文件信息
    public static function getObject($url){
        $object = str_replace(self::$cos_domain . '/', '', $url);
        try{
            $cos_client = self::initClient();
            $result = $cos_client->getObject(['Bucket'=>self::$bucket,'key'=>$object]);
            if($result){
                return $result['body'];
            }else{
                return false;
            }
        }catch (\Exception $e){
            echo $e;
            return false;
        }
    }

    //下载文件
    public static function download($url) {
        $rs = self::getObject($url);//获取文件信息
        if(is_array($rs)){
            echo 'get file fail';
            exit;
        }
        $content_length = strlen($rs);
        if($content_length > 0){
            $filename = explode('.', pathinfo($url, PATHINFO_BASENAME));
            //设置header信息让浏览器识别并下载文件
            header('Content-Description: File Transfer');
            $content_type = self::getTypeByFileExt($filename[1]);
            header('Content-Type: '.$content_type);
            header('Content-Disposition: attachment; filename=record.' . $filename[1]);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Accept-Ranges: bytes');
            header('Content-Length: ' . strlen($rs));
            header('Content-Range: bytes 0-'.(strlen($rs) - 1).'/'.strlen($rs));
            echo $rs;
        }else{
            echo 'empty file';
            exit;
        }
    }

    //根据文件后缀设置不同的content-type(媒体文件设置对应的请求头才能在浏览器播放)
    private static function getTypeByFileExt($ext){
        $ext_array = [
            'mp3'=>'audio/mp3',
            'mp4'=>'video/mpeg4',
        ];
        return $ext_array[$ext] ?: 'application/octet-stream';
    }

}