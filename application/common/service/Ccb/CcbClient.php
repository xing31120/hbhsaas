<?php
/**
 * 建行分账
 * Date: 2021/9/13 15:10
 */

namespace app\common\service\Ccb;

use think\facade\Config;

class CcbClient
{
    public function __construct(){
        $is_test = Config::get('amqp.is_test');
        if($is_test){
            $this->config = Config::pull('ccbtest');
        }else{
            $this->config = Config::pull('ccb');
        }
    }

    /**
     *
     * 获取配置
     * @param bool $getCommonParams 是否只返回接口公共参数配置
     * @param int $pymdCd 支付方式
     * @return array|mixed
     * User: cwh  DateTime:2021/10/28 9:49
     */
    public function getConfig($getCommonParams =false,$pymdCd = 0){
        if($getCommonParams){
            //根据支付方式读取市场编号
            $mkt_id = $this->config['mktIdBypayMethod'][$pymdCd] ?? 0;
            $this->config['commonParams']['Mkt_Id'] = $mkt_id;
            return $this->config['commonParams'];
        }else{
            unset($this->config['commonParams']);
        }
        return $this->config;
    }

    /**
     * 获取支付方式
     * @return mixed
     * User: cwh  DateTime:2021/11/3 19:43
     */
    public function mktIdBypayMethod(){
        return $this->config['mktIdBypayMethod'];
    }
    
    //回调地址域名
    public function getCallBackDomain(){
        return $this->config['call_back_domain'];
    }
    
    //递归将参数字段转小写
    public function ucstrtolower($data){
        $params = [];
        foreach ($data as $key => $item){
            if(is_array($item)){
                $k = strtolower($key);
                $params[$k] = $this->ucstrtolower($item);
            }else{
                $k = strtolower($key);
                $params[$k] = $item;
            }
        }
        return $params;
    }
}