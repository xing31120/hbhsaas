<?php
/**
 * 建行分账Api对接
 * Date: 2021/9/13 15:07
 */

namespace app\common\service\Ccb;

use app\common\tools\Http;
use think\facade\Env;

class CcbSdkService
{
    private $apiUrl = '';  //最终请求的url
//    private $params = [];    //最终请求的参数
    public $result = [];
    public $error;
    
    public function __construct(){
        $this->ccbClient = new CcbClient();
        $this->config = $this->ccbClient->getConfig();
    }
    
    //生成支付订单
    public function gatherPlaceorder($params,$app_uid,$pay_method){
        $this->apiUrl = $this->config['server_url'] .='/online/direct/gatherPlaceorder';
        return $this->handle($params,$app_uid,$pay_method);
    }
    
    //刷新聚合二维码
    public function mergePayUrl($params,$app_uid,$pay_method){
        $this->apiUrl = $this->config['server_url'] .='/online/direct/mergePayUrl';
        return $this->handle($params,$app_uid,$pay_method);
    }
    
    //查询支付结果
    public function gatherEnquireOrder($params,$app_uid,$pay_method){
        $this->apiUrl = $this->config['server_url'] .='/online/direct/gatherEnquireOrder';
        return $this->handle($params,$app_uid,$pay_method);
    }
    
    //分账规则查询
    public function accountingRulesList($params,$app_uid,$pay_method){
        $this->apiUrl = $this->config['server_url'] .='/online/direct/accountingRulesList';
        return $this->handle($params,$app_uid,$pay_method);
    }

    /**
     * 退款结果查询
     * @param $params
     * @param $app_uid
     * @param $pay_method
     * @return bool|mixed
     * User: cwh  DateTime:2021/11/16 16:14
     */
    public function enquireRefundOrder($params,$app_uid,$pay_method){
        $this->apiUrl = $this->config['server_url'] .='/online/direct/enquireRefundOrder';
        return $this->handle($params,$app_uid,$pay_method);
    }

    //退款下单
    public function refundOrder($params,$app_uid,$pay_method){
        $this->apiUrl = $this->config['server_url'] .='/online/direct/refundOrder';
        return $this->handle($params,$app_uid,$pay_method);
    }
    
    //请求聚合支付接口,并处理返回的结果
    private function handle($params,$app_uid,$pay_method){
        //处理参数
        $params = $this->asmParam($params,$pay_method);
        //生成签名
        $params['Sign_Inf'] = $this->sign($params);
        if(empty($params['Sign_Inf'])){
            $this->error = '生成签名失败';
            return false;
        }
        write_logs('请求路径：'.$this->apiUrl.PHP_EOL.'参数：'.json_encode($params), 'ccb/request','api');
        $client = new \GuzzleHttp\Client([
            'timeout'  => 10000,
        ]);
        $res = $client->request('POST', $this->apiUrl, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $params
        ]);
        $responseData = json_decode($res->getBody(), true);
        $jsonResponseData = json_encode($responseData,256);
//        $http = new Http();
//        $res = $http->sendRequest($this->apiUrl,$params,'POST',[],['Content-Type: application/json; charset=utf-8']);
        write_logs('请求路径：'.$this->apiUrl.PHP_EOL.'参数：'.json_encode($params), 'ccb/request','api');
        write_logs('应答报文：'.$jsonResponseData, 'ccb/request','api');
        if($responseData){
            if(isset($responseData['Svc_Rsp_St']) && $responseData['Svc_Rsp_St'] == '00'){
//                return $responseData;
                //成功,验签
                $verify = $this->verify($responseData,$pay_method);
                if($verify){
                    $this->result = $responseData;
                    return $responseData;
                }else{
                    //验签失败
                    $this->error = '验签失败';
                    return false;
                }
            }else{
                if(!isset($responseData['Rsp_Inf'])){
                    $this->error = '接口异常，应答报文：' . $jsonResponseData;
                    return false;
                }else{
                    //失败
                    $this->error = $responseData['Rsp_Inf'];
                    return false;
                }
            }
        }else{
            $this->error = 'json解析失败，应答报文：'.$jsonResponseData;
            return false;
        }

    }
    
    /**
     * 处理参数.合并公共参数
     * @param $data
     * @param string $separator
     * @return string
     */
    public function asmParam($data,$pymdCd){
        //处理参数下标,并排序
        $params = $this->uncamelize($data);
        //合并公共参数
        $ccbClient = new CcbClient();
//        $pymdCd = $data['pymdCd'] ?? 0;
        $commonParams = $ccbClient->getConfig(true,$pymdCd);
        $params = array_merge($params,$commonParams);
        ksort($params);
        return $params;
    }
    
    /**
     * 递归在驼峰大写字母前加下划线,并且首字母转大写,然后按key排序
     * @param $data 原参数
     * @param string $separator 分隔字符
     * @return array
     * @author lizl 2021/9/14 11:51
     */
    private function uncamelize($data,$separator = '_'){
        $params = [];
        foreach ($data as $key => $item){
            if(is_array($item)){
                $k = ucfirst($key);
                $params[$k] = $this->uncamelize($item,$separator);
                ksort($params);
            }else{
                $k = ucfirst(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $key));
                $params[$k] = $item;
                ksort($params);
            }
        }
        return $params;
    }
    
    //生成签名
    public function sign($params){
        $str = trim($this->spliceStr($params),'&');
        $privateKey = $this->getPrivateKey();
        $sign = $this->rsaPrivateSign($str, $privateKey);
        return $sign;
    }
    
    //验证返回的数据的合法性
    public function verify($params,$pay_method){
        $data['sign'] = $params['Sign_Inf'];
        //去除不参与签名的公共参数
        unset($params['Sign_Inf'],$params['Svc_Rsp_St'],$params['Svc_Rsp_Cd'],$params['Rsp_Inf']);
        $data['str'] = trim($this->spliceStr($params),'&');
        $key = $this->getPublicKey($pay_method);
        return $this->checkRsaSign($data,$key);
    }
    
    //拼接签名字符串
    private function spliceStr($params,$str=''){
        ksort($params);
        foreach ($params as $key => $item){
            if(is_array($item)){
                $str = $this->spliceStr($item,$str);
            }else{
                if(!strlen($item)) continue; //没有值不参与签名
                $str .= $key.'='.$item.'&';
            }
        }
        return $str;
    }
    
    /**
     * 私钥生成签名
     * @param string $data          待签名字符串
     * @param string $privateKey    私钥
     * @return string               base64结果值
     */
    private function rsaPrivateSign($data,$privateKey){
        $privKeyId = openssl_pkey_get_private($privateKey);
        if(empty($privKeyId)) return false;    //证书错误
        $signature = '';
        openssl_sign($data, $signature, $privKeyId, OPENSSL_ALGO_SHA256);
        openssl_free_key($privKeyId);
        return base64_encode($signature);
    }
    
    /**
     * 验签
     * @return bool
     */
    public function checkRsaSign($data,$key){
        $pkeyId = openssl_pkey_get_public($key);
        $decoded = "";
        for ($i=0; $i < ceil(strlen($data['sign'])/256); $i++){
            $decoded = $decoded . base64_decode(substr($data['sign'],$i*256,256));
        }
        $verify = (bool)openssl_verify($data['str'], $decoded, $pkeyId, OPENSSL_ALGO_SHA256);
        openssl_free_key($pkeyId);
        return  $verify;
    }
    
    /**
     *获取私匙
     */
    private function getPrivateKey(){
        return $this->formatPriKey(file_get_contents(Env::get('root_path').'extend/Ccb/'.$this->config['private_cert_path']));
    }
    
    /**
     *获取公匙
     */
    private function getPublicKey($pay_method){
        return $this->formatPubKey(file_get_contents(Env::get('root_path').'extend/Ccb/'.$this->config['pub_cert_path'].$pay_method.".cer"));
//        return file_get_contents(Env::get('root_path').'extend/Ccb/'.$this->config['pub_cert_path']);
    }
    
    /**
     * 私钥格式化
     * @param $priKey   待格式化的字符串
     * @return string
     */
    private function formatPriKey($priKey){
        return "-----BEGIN PRIVATE KEY-----\n"
            . wordwrap($priKey, 64, "\n", true)
            . "\n-----END PRIVATE KEY-----";
    }
    
    /**
     * 公钥格式化
     * @param $pubKey   待格式化的字符串
     * @return string
     */
    private function formatPubKey($pubKey){
        return  "-----BEGIN PUBLIC KEY-----\n"
            . wordwrap($pubKey, 64, "\n", true)
            . "\n-----END PUBLIC KEY-----";
    }
}