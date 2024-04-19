<?php

namespace AllInPay\SDK;

use AllInPay\Log\Log;
use AllInPay\Config\conf;

class yunClient
{

	private  $logIns;
	private  $config;
	private  $priKey = null;
    public function __construct(){
        $this->logIns = Log::getInstance();
        $this->config = conf::getInstance();
        $this->config->loadConf('/config.php');
    }

	/**
	 *请求封装
	 */
	public  function request($method,$param)
	{
		$request["appId"] = $this->config->getConf('appId');
		$request["method"] = $method;
		$request["charset"] = "utf-8";
		$request["format"] = "JSON";
		$request["signType"] = "SHA256WithRSA";
		$request["timestamp"] = date("Y-m-d H:i:s", time());
		$request["version"] = $this->config->getConf('version');
		$request["bizContent"] = json_encode($param,JSON_UNESCAPED_UNICODE);
		$request["sign"] = $this->sign($request);
		$serverAddress =$this->config->getConf('serverUrl');
//var_dump($request);
		$result = $this->requestYSTAPI($serverAddress, $request);
		return $this->checkResult($result);
	}

	/**
	 * [concatUrlParams 请求链接拼装]
	 */
	public  function concatUrlParams($method,$param)
	{
		$request = [];
		$sb = '';
		$request["appId"] = $this->config->getConf('appId');
		$request["method"] = $method;
		$request["charset"] = "utf-8";
		$request["format"] = "JSON";
		$request["signType"] = "SHA256WithRSA";
		$request["timestamp"] = date("Y-m-d H:i:s", time());
		$request["version"] = $this->config->getConf('version');
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
	public function sign($strRequest)
	{
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
	public function getPrivateKey()
	{
		return $this->loadPrivateKey(dirname(__FILE__).'/../'.$this->config->getConf('path'),$this->config->getConf('pwd'));
	}

	/**
	 * 从证书文件中装入私钥 pem格式;
	 */
	private function loadPrivateKey($path, $pwd)
	{

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
	private function loadPrivateKeyByPfx($path, $pwd)
	{
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
	private function requestYSTAPI($serverUrl, $args)
	{

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
	private function checkResult($result)
	{
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
		if ($success) {
			return $arr;
		}
		return $success;
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
	private function verify($publicKeyPath, $text, $sign)
	{
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
	public function getPublicKeyPath()
	{
		return dirname(__FILE__).'/../'.$this->config->getConf('tlCertPath');
	}

	/**
	 * [encryptAES AES-SHA1PRNG加密算法]
	 */
	public function encryptAES($string){
        //AES加密通过SHA1PRNG算法
        $key = substr(openssl_digest(openssl_digest($this->config->getConf('secretKey'), 'sha1', true), 'sha1', true), 0, 16);
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = strtoupper(bin2hex($data));
        return $data;
	}

	/**
	 * [encryptAES AES-SHA1PRNG解密算法]
	 */
	public function decryptAES($string)
    {
        $key = substr(openssl_digest(openssl_digest($this->config->getConf('secretKey'), 'sha1', true), 'sha1', true), 0, 16);
        $decrypted = openssl_decrypt(hex2bin($string), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return $decrypted;
    }
	public function htBankWithdrawSign($text){
		$htCertPath = dirname(__FILE__).'/../'.$this->config->getConf('htCertPath');
		$htCertPwd = $this->config->getConf('htCertPwd');
		$htPriKey = $this->loadPrivateKeyByPfx($htCertPath,$htCertPwd);
		openssl_sign($text, $sign, $htPriKey);
		openssl_free_key($htPriKey);
		$sign = base64_encode($sign);
		return $sign;
	}    

}
