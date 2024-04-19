<?php


namespace app\common\service\AllInPay;

use AllInPay\Log\Log;
use AllInPay\Config\conf;
use app\common\tools\SysEnums;
use think\facade\Config;
use think\facade\Env;

class AllInPayClient{

    private  $logIns;
    private  $config;

    public function __construct(){
        $this->logIns = Log::getInstance();
        $is_test = Config::get('amqp.is_test');
        if($is_test){
            $this->config = Config::pull('allinpaytest');
        }else{
            $this->config = Config::pull('allinpay');
        }
//var_dump($is_test);
//var_dump($this->config);
//exit;
    }

    function getLogIns(){
        return $this->logIns;
    }

    public function getConfig(){
        return $this->config;
    }
    //回调地址域名
    function getCallBackDomain(){
        return $this->config['call_back_domain'];
    }

    /**
     *请求封装
     */
    public  function request($method,$param){
        $request["appId"] = $this->config['app_id'];
        $request["method"] = $method;
        $request["charset"] = "utf-8";
        $request["format"] = "JSON";
        $request["signType"] = "SHA256WithRSA";
        $request["timestamp"] = date("Y-m-d H:i:s", time());
        $request["version"] = $this->config['version'];
        $request["bizContent"] = json_encode($param,JSON_UNESCAPED_UNICODE);
        $request["sign"] = $this->sign($request);
        $serverAddress = $this->config['server_url'];
//echo $serverAddress.'<br><br>';
//echo json_encode($param);exit;
        $result = $this->requestYSTAPI($serverAddress, $request);
        // var_dump($result);
        return $this->checkResult($result);
    }

    /**
     * [concatUrlParams 请求链接拼装]
     */
    public  function concatUrlParams($method,$param){
        $request = [];
        $sb = '';
        $request["appId"] = $this->config['app_id'];
        $request["method"] = $method;
        $request["charset"] = "utf-8";
        $request["format"] = "JSON";
        $request["signType"] = "SHA256WithRSA";
        $request["timestamp"] = date("Y-m-d H:i:s", time());
        $request["version"] = $this->config['version'];
        $request["bizContent"] = json_encode($param);
        $request["sign"] = $this->sign($request);
        foreach ($request as $entry_key => $entry_value) {
            $sb .= $entry_key . '=' . urlencode($entry_value) . '&';
        }
        $sb = trim($sb, "&");
        return $sb;

    }

    /**
     * [sign 开放平台签名算法]
     */
    public function sign($strRequest){
        unset($strRequest['signType']);
        $strRequest = array_filter($strRequest);//剔除值为空的参数
        ksort($strRequest);
        $sb = '';
        foreach ($strRequest as $entry_key => $entry_value) {
            $sb .= $entry_key . '=' . $entry_value . '&';
        }
        $sb = trim($sb, "&");
        $this->logIns->logMessage("[待签名源串]",Log::INFO,$sb);
        //MD5摘要计算,Base64
        $sb = base64_encode(hash('md5', $sb, true));
        $privateKey = $this->getPrivateKey();
        if (openssl_sign(utf8_encode($sb), $sign, $privateKey, OPENSSL_ALGO_SHA256)) {//SHA256withRSA密钥加签
//			openssl_free_key($privateKey);
            $sign = base64_encode($sign);
            return $sign;
        } else {
            echo "sign error";
            exit();
        }
    }

    /**
     *获取私匙的绝对路径;
     */
    public function getPrivateKey(){
        return $this->loadPrivateKey(Env::get('root_path').'extend/AllInPay/'.$this->config['path'],$this->config['pwd']);
    }

    /**
     * 从证书文件中装入私钥 pem格式;
     */
    private function loadPrivateKey($path, $pwd){

        $str = explode('.', $path);
        $houzuiName = $str[count($str) - 1];
        if ($houzuiName == "pfx") {
            return $this->loadPrivateKeyByPfx($path, $pwd);
        }

        if ($houzuiName == "pem") {
            $priKey = file_get_contents($path);
            $res = openssl_get_privatekey($priKey, $pwd);
            if (!$res) {
                exit('您使用的私钥格式错误，请检查私钥配置');
            }
            return $res;
        }
    }

    /**
     * 从证书文件中装入私钥 Pfx 文件格式
     */
    private function loadPrivateKeyByPfx($path, $pwd){
        if (file_exists($path)) {
            $priKey = file_get_contents($path);
            if (openssl_pkcs12_read($priKey, $certs, $pwd)) {
                $privateKey = $certs['pkey'];
                return $privateKey;

            }
            die("私钥文件格式错误");

        }
        die('私钥文件不存在');
    }

    /**
     *请求云商通
     */
    private function requestYSTAPI($serverUrl, $args){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $serverUrl);

        $sb = '';
        $reqbody = array();
        foreach ($args as $entry_key => $entry_value) {
            $sb .= $entry_key . '=' . urlencode($entry_value) . '&';
        }
        $sb = trim($sb, "&");
        $this->logIns->logMessage("[请求body]",Log::INFO,$sb);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sb);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-length', count($reqbody)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        $this->logIns->logMessage("[请求状态]",Log::INFO,json_encode($info));
        curl_close($ch);
        $this->logIns->logMessage("[请求返回]",Log::INFO,$result);
        return $result;
    }

    /**
     *检查返回的结果是否合法;
     */
    public function checkResult($result){
        $arr = json_decode($result,true);
        $sign = $arr['sign'];
        unset($arr['sign']);
        $this->Asciisort($arr);
        $str = json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_FORCE_OBJECT|JSON_UNESCAPED_SLASHES);
        $this->logIns->logMessage("[待验签字段]",Log::INFO,$str);
        $success = false;
        if ($sign != null) {
            $success = $this->verify($this->getPublicKeyPath(),$str,base64_decode($sign));
        }

        if(!$success){
            return errorReturn('sign检验失败a');
        }

        if( (isset($arr['subCode']) && $arr['subCode'] != 'OK' ) || (isset($arr['code']) && $arr['code'] != 10000) ){
            $errorMsg   = isset($arr['subMsg'])  ? $arr['subMsg']  : $arr['msg'];
            $subCode  = isset($arr['subCode']) ? $arr['subCode'] : '';
            $code  = isset($arr['code']) ? $arr['code'] : '';
            $errorCode = $subCode ?:$code;

            return errorReturn($errorMsg, $errorCode);
        }

        if(isset($arr['data']['payFailMessage']) && isset($arr['data']['payStatus']) && $arr['data']['payStatus'] =='unpay'){
            return errorReturn($arr['data']['payFailMessage'], SysEnums::UnPayStatusError);
        }

        if(isset($arr['data']['payFailMessage']) ){
            return errorReturn($arr['data']['payFailMessage'], SysEnums::PayStatusError);
        }
        
        if(isset($arr['data']['bizUserId'])){
            $bizUid = getBizUid($arr['data']['bizUserId'])['bizUid'];
            unset($arr['data']['bizUserId']);
            $arr['data']['bizUid'] = $bizUid;
        }
        // echo "<pre>";
        // var_dump($arr);
        $resData = $arr['data'] ?? [];
        return successReturn(['data' => $resData]);
    }

    /**
     * [foo 对返回数据按照第一个字符的键值ASCII码递增排序]
     */
    public function Asciisort(&$ar) {
        if(is_array($ar)) {
            ksort($ar);
            foreach($ar as &$v) $this->Asciisort($v);
        }
    }
    /**
     *验证返回的数据的合法性
     */
    private function verify($publicKeyPath, $text, $sign){
        //MD5摘要计算
        $text = base64_encode(hash('md5', $text, true));
        $pubKeyId = openssl_get_publickey(file_get_contents($publicKeyPath));
        $flag = (bool) openssl_verify($text, $sign, $pubKeyId, "sha256WithRSAEncryption");
        openssl_free_key($pubKeyId);
        $this->logIns->logMessage("[sign value]",Log::INFO, (bool)($flag));
        return $flag;
    }

    /**
     *获取公匙的绝对路径
     */
    public function getPublicKeyPath(){
        return Env::get('root_path').'extend/AllInPay/'.$this->config['tl_cert_path'];
    }

    /**
     * [encryptAES AES-SHA1PRNG加密算法]
     */
    public function encryptAES($string){
        //AES加密通过SHA1PRNG算法
        $key = substr(openssl_digest(openssl_digest($this->config['secret_key'], 'sha1', true), 'sha1', true), 0, 16);
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = strtoupper(bin2hex($data));
        return $data;
    }

    /**
     * [encryptAES AES-SHA1PRNG解密算法]
     */
    public function decryptAES($string){
        $key = substr(openssl_digest(openssl_digest($this->config['secret_key'], 'sha1', true), 'sha1', true), 0, 16);
        $decrypted = openssl_decrypt(hex2bin($string), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return $decrypted;
    }
    public function htBankWithdrawSign($text){
        $htCertPath = Env::get('root_path').'extend/AllInPay/'.$this->config['ht_cert_path'];
        $htCertPwd = $this->config['ht_cert_pwd'];
        $htPriKey = $this->loadPrivateKeyByPfx($htCertPath,$htCertPwd);
        openssl_sign($text, $sign, $htPriKey);
        openssl_free_key($htPriKey);
        $sign = base64_encode($sign);
        return $sign;
    }

    function checkSign($arrRequest){
        $arrRequest['sign'] = str_replace(' ','+',$arrRequest['sign']);
        $signRequest = $arrRequest['sign'];
        unset($arrRequest['signType']);
        unset($arrRequest['sign']);
        $this->Asciisort($arrRequest);
        $sb = '';
//return $arrRequest;
        foreach ($arrRequest as $entry_key => $entry_value) {
            $entry_value = html_entity_decode($entry_value);
            $sb .= $entry_key . '=' . $entry_value . '&';
        }
        $sb = trim($sb, "&");

//return $sb;
        $this->logIns->logMessage("[回调字符串---]",Log::INFO,$sb);
        //MD5摘要计算,Base64


        $success = false;
        if ($signRequest != null) {
            $success = $this->verify($this->getPublicKeyPath(),$sb,base64_decode($signRequest));
        }
        if(!$success){
            return errorReturn('sign检验失败b');
        }

        return successReturn(['msg' => 'success','data' => $arrRequest]);
    }

}