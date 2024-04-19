<?php
namespace app\common\service;
use OSS\OssClient;
use OSS\Core\OssException;

class AliyunOssService{
    protected $accessKeyId;
    protected $accessKeySecret;
    protected $endpoint;
    protected $bucket;
    protected $oss_domain;

    public function __construct(){
        $this->accessKeyId = env('oss.oss_access_key_id','');
        $this->accessKeySecret = env('oss.oss_access_key_secret','');
        $this->endpoint = env('oss.oss_endpoint','http://oss-cn-hangzhou.aliyuncs.com');
        $this->bucket = env('oss.oss_bucket','');
        $this->oss_domain = env('oss.oss_domain','');
    }

    //初始化
    private function initClient(){
        $client = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
        return $client;
    }

    //文件上传($object是云空间内对应的路径,$filePath是本地文件)
    public function upload($object,$filePath){
        try{
            $oss_client = $this->initClient();
            $oss_info = $oss_client->uploadFile($this->bucket, $object, $filePath);
            is_file($filePath) && unlink($filePath);//删除临时存储在服务器上的文件
            if($oss_info){
                return true;
            }else{
                return false;
            }
        }catch (OssException $e){
            is_file($filePath) && unlink($filePath);//删除临时存储在服务器上的文件
            echo $e->getMessage();
            return false;
        }
    }

    //通过上传二进制文件流进行上传
    public function uploadStream($object,$stream){
        try{
            $oss_client = $this->initClient();
            $oss_info = $oss_client->uploadStream($this->bucket, $object, $stream);
            if($oss_info){
                return true;
            }else{
                return false;
            }
        }catch (OssException $e){
            echo $e->getMessage();
            return false;
        }
    }

    //通过上传二进制文件流进行上传
    public function putObject($object,$stream){
        try{
            $oss_client = $this->initClient();
            $oss_info = $oss_client->putObject($this->bucket, $object, $stream);
            if($oss_info){
                return true;
            }else{
                return false;
            }
        }catch (OssException $e){
            echo $e->getMessage();
            return false;
        }
    }

    //删除文件
    public function delObject($url){
        $object = str_replace($this->oss_domain . '/', '', $url);
        try{
            $oss_client = $this->initClient();
            $bool = $oss_client->doesObjectExist($this->bucket,$object);
            if($bool){
                return $oss_client->deleteObject($this->bucket,$object);
            } else {
                return false;
            }
        }catch(OssException $e){
            echo $e->getMessage();
            return false;
        }
    }

    //验证文件是否存在
    public function checkObject($url){
        $object = str_replace($this->oss_domain . '/', '', $url);
        try{
            $oss_client = $this->initClient();
            $bool = $oss_client->doesObjectExist($this->bucket,$object);
            if($bool){
                return true;
            } else {
                return false;
            }
        }catch(OssException $e){
            echo $e->getMessage();
            return false;
        }
    }

    //获取文件信息
    public function getObject($url){
        $object = str_replace($this->oss_domain . '/', '', $url);
        try{
            $oss_client = $this->initClient();
            $bool = $oss_client->doesObjectExist($this->bucket,$object);
            if($bool){
                return $oss_client->getObject($this->bucket,$object);
            } else {
                return false;
            }
        }catch(OssException $e){
            echo $e->getMessage();
            return false;
        }
    }

    //下载文件
    public function download($url) {
        $bool = $this->checkObject($url);//验证文件是否存在
        if(!$bool){
            echo 'file not exist';
            exit;
        }
        $rs = $this->getObject($url);//获取文件信息
        if(is_array($rs)){
            echo 'get file fail';
            exit;
        }
        $content_length = strlen($rs);
        if($content_length > 0){
            $filename = explode('.', pathinfo($url, PATHINFO_BASENAME));
            //设置header信息让浏览器识别并下载文件
            header('Content-Description: File Transfer');
            $content_type = $this->getTypeByFileExt($filename[1]);
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
    private function getTypeByFileExt($ext){
        $ext_array = [
            'mp3'=>'audio/mp3',
            'mp4'=>'video/mpeg4',
        ];
        return $ext_array[$ext] ?: 'application/octet-stream';
    }

}