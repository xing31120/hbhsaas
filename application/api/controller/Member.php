<?php


namespace app\api\controller;

use app\common\tools\SysEnums;
use app\api\controller\Base;
use app\common\service\UserService;
use app\common\service\UserFundsService;

use think\Db;

class Member extends Base{


    public function __construct(){
        parent::__construct();
        $params = input();

        if( empty($params['bizUid']) ){
            return apiOutError('参数错误',SysEnums::ApiParamMissing);
        }

    }

    //创建会员
    public function createMember(){
        $params = input();
        $info = model('Users')->infoByBizUid( $this->appUid, $params['bizUid']);
        if($info){
            $resInfo = [
                'id' => $info['id'],
                'biz_uid' => $info['biz_uid'],
                'allinpay_uid' => $info['allinpay_uid'],
                'name'  => $info['name'],
                'member_type' => $info['member_type'],
                'all_amount'    => $info['all_amount'],
                'freezen_amount' => $info['freezen_amount'],
                'real_auth_status' => $info['real_auth_status'],
                'sign_contract_status'  => $info['sign_contract_status'],
                'source'    => $info['source'],
                'platform_id'   => $info['platform_id'],
                'mobile'    => $info['mobile'],
                'status'    => $info['status'],
                'create_time'   => $info['create_time'],
                'update_time'   => $info['update_time'],
                'biz_nickname' => $info['biz_nickname'],
            ];
            $res['data'] = $resInfo;
            return apiOut($res);
        }

        //请求通联注册
        $userService = new UserService();
        $res = $userService->createMember( $this->appUid, $params);
        if($res['result'] == true){

        }
        return apiOut($res);
    }

    //获取会员信息
    public function getMemberInfo(){
        $params = input();
        if(!isset($params['bizUid'])){
            return apiOutError('参数错误',SysEnums::ApiParamMissing);
        }
        $info = model('Users')->infoByBizUid( $this->appUid, $params['bizUid']);
        $resInfo = [
            'id' => $info['id'],
            'biz_uid' => $info['biz_uid'],
            'allinpay_uid' => $info['allinpay_uid'],
            'name'  => $info['name'],
            'member_type' => $info['member_type'],
            'all_amount'    => $info['all_amount'],
            'freezen_amount' => $info['freezen_amount'],
            'real_auth_status' => $info['real_auth_status'],
            'sign_contract_status'  => $info['sign_contract_status'],
            'source'    => $info['source'],
            'platform_id'   => $info['platform_id'],
            'mobile'    => $info['mobile'],
            'status'    => $info['status'],
            'create_time'   => $info['create_time'],
            'update_time'   => $info['update_time'],
        ];
        $res['data'] = $resInfo;
        return apiOut($res);
    }

    //获取会员信息 【通联版】
    public function getMemberInfoAllinPay(){
        $params = input();
        $userService = new UserService();
        $res = $userService->getMemberInfo( $this->appUid, $params['bizUid']);
        return apiOut($res);
    }
    

    //发送短信验证码
    public function sendVerificationCode(){
        $params = input(); 
        if( empty($params['phone']) ){
            return apiOutError('参数错误',SysEnums::ApiParamMissing);
        }
        $params['type'] = $params['type'] ?? 9;
        $userService = new UserService();
        $res = $userService->sendVerificationCode($this->appUid,$params['bizUid'],$params['phone'],$params['type']);
        return apiOut($res);
    }

    //绑定手机
    public function bindPhone(){
        $params = input(); 
        if( empty($params['phone']) || empty($params['verificationCode']) ){
            return apiOutError('参数错误',SysEnums::ApiParamMissing);
        }
        $userService = new UserService();
        $res = $userService->bindPhone($this->appUid,$params['bizUid'],$params['phone'],$params['verificationCode']);
        return apiOut($res);
    }

    //解绑手机
    public function unbindPhone(){
        $params = input(); 
        if( empty($params['phone']) || empty($params['verificationCode']) ){
            return apiOutError('参数错误',SysEnums::ApiParamMissing);
        }
        $userService = new UserService();
        $res = $userService->unbindPhone($this->appUid,$params['bizUid'],$params['phone'],$params['verificationCode']);
        return apiOut($res);
    }

    //更改绑定手机 
    public function updatePhoneByPayPwd(){
        $params = input(); 
        $userService = new UserService();
        $return = $userService->updatePhoneByPayPwd($this->appUid,$params);
        if( isset($return['result'])){
            $res = $return;
        }else{
            $res['data']['url'] = $return;
        }
        return apiOut($res);
    }

    //个人实名认证
    public function setRealName(){
        $params = input(); 
        if( empty($params['name']) || empty($params['identityNo']) ){
            return apiOutError('参数错误',SysEnums::ApiParamMissing);
        }
        $userService = new UserService();
        $res = $userService->setRealName($this->appUid,$params['bizUid'],$params['name'],$params['identityNo']);
        return apiOut($res);
    }

    //设置企业信息
    public function setCompanyInfo(){
        $params = input(); 
        $userService = new UserService();
        $res = $userService->setCompanyInfo($this->appUid,$params);
        return apiOut($res);
    }

    //影印件采集 设置企业信息后传
    public function idcardCollect(){
        $params = input(); 
        $userService = new UserService();
        $res = $userService->idcardCollect($this->appUid,$params);
        return apiOut($res);
    }

    //电子协议签约
    public function signContract(){
        $params = input(); 
        if( $params['jumpUrl']){
            $params['jumpUrl'] = urldecode($params['jumpUrl']);
        }
        if( $params['bizBackUrl']){
            $params['bizBackUrl'] = urldecode($params['bizBackUrl']);
        }
        $userService = new UserService();
        $return = $userService->signContract($this->appUid,$params);
        if( isset($return['result'])){
            $res = $return;
        }else{
            $res['data']['url'] = $return;
        }
        return apiOut($res);
    }

    //电子协议签约查询
    public function signContractQuery(){
        $params = input(); 
        if( $params['jumpUrl']){
            $params['jumpUrl'] = urldecode($params['jumpUrl']);
        }
        $userService = new UserService();
        $return = $userService->signContractQuery($this->appUid,$params);
        if( isset($return['result'])){
            $res = $return;
        }else{
            $res['data']['url'] = $return;
        }
        return apiOut($res);
    }

    //获取银行卡bin信息
    public function getBankCardBin(){
        $params = input();
        $userService = new UserService();
        $res = $userService->getBankCardBin($params['cardNo']);
        return apiOut($res);
    }
    
    //查询绑定银行卡
    public function queryBankCard(){
        $params = input();
        $userInfo = model('Users')->infoByBizUid( $this->appUid, $params['bizUid']);
        $where[] = ['status','=',10];
        $where[] = ['uid','=',$userInfo['id']];
        $op['field'] = 'card_no,name,bank_name,phone,bank_code,card_type,create_time,card_category';
        $op['where'] = $where;
        $bankCard = model('BankCard')->getList($this->appUid,$op);
        $bankCard['count'] = count($bankCard['list']);
        
        $res['data']['bizUid'] = $params['bizUid'];
        $res['data']['bankCard'] = $bankCard;
        // $userService = new UserService();
        // $res = $userService->queryBankCard($this->appUid,$params['bizUid']);
        return apiOut($res);
    }

    //请求绑定银行卡
    public function applyBindBankCard(){
        $params = input(); 
        $userService = new UserService();
        $res = $userService->applyBindBankCard($this->appUid,$params);
        return apiOut($res);
    }

    //确认绑定银行卡
    public function bindBankCard(){
        $params = input(); 
        $userService = new UserService();
        $res = $userService->bindBankCard($this->appUid,$params);
        return apiOut($res);
    }

    //解绑银行卡
    public function unbindBankCard(){
        $params = input(); 
        $userService = new UserService();
        $res = $userService->unbindBankCard($this->appUid,$params);
        return apiOut($res);
    }

    //设置支付密码
    public function setPayPwd(){
        $params = input(); 
        $userService = new UserService();
        $return = $userService->setPayPwd($this->appUid,$params);
        if( isset($return['result'])){
            $res = $return;
        }else{
            $res['data']['url'] = $return;
        }
        return apiOut($res);
    }

    //修改支付密码
    public function updatePayPwd(){
        $params = input(); 
        $userService = new UserService();
        $return = $userService->updatePayPwd($this->appUid,$params);
        if( isset($return['result'])){
            $res = $return;
        }else{
            $res['data']['url'] = $return;
        }
        return apiOut($res);
    }

    //重置支付密码
    public function resetPayPwd(){
        $params = input(); 
        $userService = new UserService();
        $return = $userService->resetPayPwd($this->appUid,$params);
        if( isset($return['result'])){
            $res = $return;
        }else{
            $res['data']['url'] = $return;
        }
        return apiOut($res);
    }

    //获取用户余额
    public function queryBalance(){
        $params = input();
        $UserFundsService = new UserFundsService();
        $res = $UserFundsService->queryBalance($this->appUid, $params['bizUid']);
        return apiOut($res);
    }

    //获取用户余额，直接查通联
    public function queryBalanceAllInPay(){
        $params = input();
        $UserFundsService = new UserFundsService();
        $res = $UserFundsService->queryBalanceAllInPay($this->appUid, $params['bizUid']);
        return apiOut($res);
    }
    








}