<?php
namespace app\common\model;
use app\common\model\basic\Common;
use app\common\model\basic\Single;

class Users extends Common {
    public $mcName = 'users_';
    public $selectTime = 60;
    public $mcTimeOut = 60;
    public $status = [10=> '正常', 40=>'已锁定/禁用'];
    public $realAuthStatus =     [10=> '未实名', 20=>'审核中', 30=> '实名通过', 40=>'实名失败'];
    public $signContractStatus = [10=> '未签约', 20=>'签约中', 30=> '签约通过', 40=>'签约失败'];

    function info($id, $appUid, $field = ''){
        $info = parent::info($id, $appUid, $field);
        unset($info['pay_password']);
        return $info;
    }

    /**
     * 修改余额接口
     * @param $appUid
     * @param $bizUid
     * @param $allAmount
     * @param null $freezenAmount
     * @return array
     * User: 宋星 DateTime: 2020/11/16 15:03
     */
    function upUsersFunds($appUid, $bizUid, $allAmount, $freezenAmount = null){
        $info = $this->infoByBizUid($appUid, $bizUid);
        if( empty($info) ){
            return errorReturn('查询用户信息失败!');
        }
        //当不一致的时候才更新余额
        if($allAmount!=$info['all_amount'] || (!empty($freezenAmount) && $freezenAmount!=$info['freezen_amount']) ){
//var_dump($allAmount);
//var_dump($info['all_amount']);
//var_dump($freezenAmount);
//var_dump($info['freezen_amount']);
            $data['id'] = $info['id'];
//            $data['name'] = $info['name'].'1';
            $data['all_amount'] = $allAmount;
            $data['freezen_amount'] = $freezenAmount ? $freezenAmount : $info['freezen_amount'];
//            $res =  $this->saveData($appUid, $data);
            $this->submeter($appUid);
            $res =  $this::update($data, ['id' =>$data['id']]);
            return successReturn(['data' => $res]);
        }

        return successReturn(['data' => $info]);
    }


    function plusUserFund($appUid, $bizUid, $plusAmount, $freezenAmount = 0){
        $info = $this->infoByBizUid($appUid, $bizUid);
        if( empty($info) ){
            return errorReturn('查询用户信息失败!');
        }

        return $this->upUsersFunds($appUid, $bizUid, $plusAmount+$info['all_amount'], $freezenAmount+$info['freezen_amount']);
    }

    function subUserFund($appUid, $bizUid, $subAmount, $freezenAmount = 0){
        $info = $this->infoByBizUid($appUid, $bizUid);
        if( empty($info) ){
            return errorReturn('查询用户信息失败!');
        }

        return $this->upUsersFunds($appUid, $bizUid, $info['all_amount'] - $subAmount, $info['freezen_amount'] - $freezenAmount);
    }
}
