<?php


namespace app\common\service;
use app\common\tools\SysEnums;
use app\common\service\AllInPay\AllInPayMemberService;
use app\common\tools\Http;

//会员用户 服务
class UserService{

    public $backHost = "http://betafin-back.zzsupei.com";

    /**
     * 检测账号是否存在，不存在则调用通商云注册
     * 
     * @param array $appInfo  users_app表主键 id , code , platform_id
     * @param int $appUid
     * @param int $bizUid
     * @param int $memberType
     * @param int $source
     * @return bool|mixed
     * User: 宋星 DateTime: 2020/11/5 17:05
     */
    public function checkUser($appInfo, $appUid, $bizUid, $memberType = 3, $source = 1){

        $bizUserId = $appUid . $bizUid;
        $info = model('Users')->infoByBizUid($appUid, $bizUid);
        if($info){
            return $info;
        }
        return $this->createMember($appUid, $bizUid, $memberType, $source);

        // //通商云注册
        // $MemberService = new AllInPayMemberService();
        // $result = $MemberService->createMember($bizUserId,$memberType,$source);
        // if($result['code'] != 0){   //注册失败
        //     return $result;
        // }
        // //写入users表
        // $data['app_uid'] = $appUid;
        // $data['biz_uid'] = $bizUid;
        // $data['biz_user_id'] = $bizUserId;
        // $data['allinpay_uid'] = $result['data']['userId'];
        // $data['member_type'] = $memberType;
        // $data['source'] = $source;
        // $data['platform_id'] = $appInfo['platform_id'];
        // $data['id'] = model('Users')->insert($appUid,$data);

        // return $data;
    }

    public function createMember($appUid, $param){
        $bizUserId = $appUid . $param['bizUid'];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->createMember($bizUserId,$param);
        if(!$result['result']){   //注册失败
            return $result;
        }
        $usersAppInfo = model('UsersApp')->info($appUid);
        //写入users表
        $data['app_uid'] = $appUid;
        $data['biz_uid'] = $param['bizUid'];
        $data['biz_user_id'] = $bizUserId;
        $data['allinpay_uid'] = $result['data']['userId'];
        $data['member_type'] = $param['memberType']??3;
        $data['source'] = $param['source']??1;
        $data['platform_id'] = $usersAppInfo['platform_id'];
        $data['biz_nickname'] = $param['bizNickname']??'';

        $data['id'] = model('Users')->insert($appUid,$data);

        // unset($data['allinpay_uid']);
        // unset($data['app_uid']);
        unset($data['biz_user_id']);
        $result['data'] = $data;
        return $result;
    }

    /**
     * [getMemberInfo 获取会员信息]
     * @param [type] $appUid
     * @param [type] $bizUid
     * @return void
     * @date 2020-11-11
     */
    public function getMemberInfo($appUid, $bizUid)
    {
        $bizUserId = $appUid . $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->getMemberInfo($bizUserId);
        if($result['code'] != 0){   //失败
            return $result;
        }
        // $resultDate = $result['data'];
        return $result;
    }

    /**
	 * [sendVerificationCode 发送短信验证码]
	 * 绑定手机前先调用
	 * @param [type] $bizUserId	users_app表主键 id , code , platform_id
     * @param [type] $bizUid
	 * @param [type] $phone	手机号码
	 * @param [type] $type	验证码类型 9-绑定手机，6-解绑手机
	 * @return void
	 * @date 2020-11-10 
	 */
	public function sendVerificationCode($appUid, $bizUid, $phone, $type = 9)
	{
        $bizUserId = $appUid ? $appUid . $bizUid : $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->sendVerificationCode($bizUserId, $phone, $type);
        return $result;
    }
    
    /**
	 * [bindPhone 绑定手机]
	 * 个人会员创建会员后即可绑定手机，与是否实名认证无关。企业会员需审核通过后才能绑定手机。
	 * @param [type] $bizUserId	users_app表主键 id , code , platform_id
     * @param [type] $bizUid
	 * @param [type] $phone	手机号码
	 * @param [type] $verificationCode	验证码
	 * @return void
	 * @date 2020-11-11
	 */
	public function bindPhone($appUid, $bizUid, $phone, $verificationCode)
	{
        $bizUserId = $appUid ? $appUid . $bizUid : $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->bindPhone($bizUserId, $phone, $verificationCode);
        if($result['code'] != 0){   //失败
            return $result;
        }
        $info = model('Users')->infoByBizUid($appUid, $bizUid);
        $data['mobile'] = $phone;
        $update = model('Users')->updateById($info['id'],$appUid,$data);

        return $result;
    }
    
    /**
     * [unbindPhone 解绑手机]
	 * @param [type] $bizUserId	users_app表主键 id , code , platform_id
     * @param [type] $bizUid
     * @param [type] $phone 手机号码
     * @param [type] $verificationCode 验证码
     * @return void
     * @date 2020-11-10
     */
    public function unbindPhone($appUid, $bizUid, $phone, $verificationCode)
	{
        $bizUserId = $appUid . $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->unbindPhone($bizUserId, $phone, $verificationCode);
        if($result['code'] != 0){   //失败
            return $result;
        }
        $info = model('Users')->infoByBizUid($appUid, $bizUid);
        $data['mobile'] = 0;
        $update = model('Users')->updateById($info['id'],$appUid,$data);

        return $result;
    }

    /**
     * [updatePhoneByPayPwd 修改绑定手机【密码验证版】]
     * 成功回调，失败不回调
     * @param [type] $appUid
     * @param [type] $bizUid
     * @param [type] $name 姓名
     * @param [type] $oldphone 旧手机号码
     * @param [type] $identityNo 身份证号码
     * @param [type] $jumpUrl 跳转地址
     * @param [type] $backUrl 回调通知地址
     * @return void
     * @date 2020-11-11
     */
    public function updatePhoneByPayPwd($appUid,$params )
	{
        // $backUrl = $this->backHost.'/AllinPay/notifyUpdatePhoneByPayPwd';
        // $params['backUrl'] = $backUrl;
        $bizUserId = $appUid . $params['bizUid'];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->updatePhoneByPayPwd($bizUserId,$params);
        return $result;
    }

    /**
	 * [setRealName 个人实名认证]
	 * @param [type] $bizUserId	users_app表主键 id , code , platform_id
     * @param [type] $bizUid
	 * @param [type] $name	姓名
	 * @param [type] $identityNo	证件号码
	 * @return void
	 * @date 2020-11-10
	 */
	public function setRealName($appUid,$bizUid, $name, $identityNo)
	{
        $bizUserId = $appUid . $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->setRealName( $bizUserId, $name, $identityNo);
        if($result['code'] != 0){   //失败
            return $result;
        }
        $info = model('Users')->infoByBizUid($appUid, $bizUid);
        $data['uid'] = $info['id'];
        $data['app_uid'] = $info['app_uid'];
        $data['biz_uid'] = $info['biz_uid'];
        $data['member_type'] = 3;
        $data['name'] = $name;
        $data['identity_type'] = 1;
        $data['identity_no'] = $identityNo;
        $data['status'] = 10;
        $data['id'] = model('RealAuth')->insert($appUid,$data);

        $infoData['name'] = $name;
        $infoData['real_auth_id'] = $data['id'];
        $infoData['real_auth_status']  = 30;
        $update = model('Users')->updateById($info['id'],$appUid,$infoData);

        return $result;
    }
    
    /**
	 * [signContract 会员电子协议签约]
	 * @param [type] $bizUid
	 * @param [type] $jumpUrl	签约后跳转地址 117字符
	 * @param [type] $backUrl	后台通知地址 117字符
     * @param integer $jumpPageType 跳转页面类型 
	 * @param [type] $source	终端 1.mobile 2.PC
	 * @return void
	 * @date 2020-10-29
	 */
	public function signContract($appUid, $param)
	{
        $signContractInfo = model('SignContract')->infoByBizUid($appUid, $param['bizUid']);
        if( $signContractInfo && $signContractInfo['sign_status'] == 10){
            $res['result'] = true;
            $res['msg'] = '已签约';
            $res['data']['sign_status'] = 10;
            return $res;
        }
        // $backUrl = $this->backHost.'/AllinPay/notifySignContract';
        // $param['backUrl'] = $backUrl;
        $MemberService = new AllInPayMemberService();
        $bizUserId = $appUid . $param['bizUid'];
        $url = $MemberService->signContract($bizUserId,$param);

        $info = model('Users')->infoByBizUid($appUid, $param['bizUid']);
        $data['biz_back_url'] = $param['bizBackUrl'] ?? '';
        $data['sign_status'] = 0;
        if(empty($signContractInfo)){
            $data['uid'] = $info['id'];
            $data['app_uid'] = $info['app_uid'];
            $data['biz_uid'] = $info['biz_uid'];
            $bool = model('SignContract')->insert($appUid,$data);
        }else{
            $bool = model('SignContract')->updateById($signContractInfo['id'],$appUid,$data);
        }
        return $url;
    }

    /**
     * [signContract 会员电子协议签约查询]
     *
     * @param [type] $appUid
     * @param [type] $bizUid
     * @param string $jumpUrl 点击确定按钮之后，跳转返回的页面地址
     * @param integer $jumpPageType 跳转页面类型 
     * @param integer $source 终端 1.mobile 2.PC
     * @return void
     * @date 2020-11-11
     */
    public function signContractQuery($appUid, $param)
    {
        $MemberService = new AllInPayMemberService();
        $bizUserId = $appUid . $param['bizUid'];
        $url = $MemberService->signContractQuery($bizUserId, $param );
        return $url;
    }

    /**
	 * [setCompanyInfo 设置企业信息]
	 * ！！！发送企业营业执照、开户许可证、法人身份证正反面扫码件至通联通商云企业用户认证邮箱yunzhanghu@allinpay.com。（测试环境无需发送）
     * @param [type] $appUid
     
     * @param [type] $bizUid
	 * @param [type] $backUrl	企业会员审核结果通知
	 * @param array $companyInfo 企业基本信息
	 * @param $isAuth 是否进行线上认证 true：系统自动审核，false：需人工审核
	 * 
	 * companyInfo:
	 * name 企业名称，如有括号，用中文格式（）
	 * auth_type 认证类型 1:三证 2:一证 默认1
	 * uni_credit 统一社会信用（一证）认证类型为2时必传
	 * business_license	营业执照号（三证）
	 * organization_code	营业执照号（三证）
	 * tax_register	组织机构代码（三证）认证类型为1时必传
	 * legal_name 法人姓名
	 * identity_no 法人证件号码
	 * telephone	法人手机号码
	 * account_no	企业对公账户 支持数字和“-”字符
	 * parent_bank_name	开户银行名称
	 * 	
	 * @return void
	 * @date 2020-11-11
	 */
    public function setCompanyInfo($appUid,$params)
    {
        $info = model('Users')->infoByBizUid($appUid, $params['bizUid']);
        if($info && $info['real_auth_status'] == 30){
            $res['result'] = true;
            $res['msg'] = '已签约';
            return $res;
        }
        // $backUrl = $this->backHost.'/AllinPay/notifySetCompanyInfo';
        // $params['backUrl'] = $backUrl;
        $bizUserId = $appUid . $params['bizUid'];
        $params['companyBasicInfo'] = json_decode(urldecode($params['companyBasicInfo']),true);
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->setCompanyInfo($bizUserId,$params);
        if($result['code'] != 0){   //失败
            return $result;
        }
        //处理企业信息
        $company = $params['companyBasicInfo'];
        $data = [];
        $data['uid'] = $info['id'];
        $data['app_uid'] = $info['app_uid'];
        $data['biz_uid'] = $info['biz_uid'];
        $data['member_type'] = 2;
        $data['name'] = $company['companyName'];
        $data['legal_name'] = $company['legalName'];
        $data['identity_type'] = 1;
        $data['identity_no'] = $company['legalIds'];
        $data['auth_type'] = $company['authType'] ?? 1;
        $data['uni_credit'] = $company['uniCredit'] ?? '';
        $data['business_license'] = $company['businessLicense'] ?? '';
        $data['organization_code'] = $company['organizationCode'] ?? '';
        $data['tax_register'] = $company['taxRegister'] ?? '';
        $data['legal_phone'] = $company['legalPhone'];
        $data['account_no'] = $company['accountNo'];
        $data['parent_bank_name'] = $company['parentBankName'];
        $data['bank_name'] = $company['bankName'] ?? '';
        $data['union_bank'] = $company['unionBank'] ?? '';
        $data['biz_back_url'] = $params['bizBackUrl'] ?? '';
        if( isset($result['data']['result'])){
            $data['company_info_status'] = $result['data']['result'] == 2 ? 1 : 2;
            $data['allinpay_fail_reason'] = $result['data']['failReason'] ?? '';
            $data['allinpay_remark'] = $result['data']['remark'] ?? '';
        }
        //写入或更新realauth信息
        $realAuthInfo = model('RealAuth')->infoByBizUid($appUid, $params['bizUid']);
        if( $realAuthInfo){
            $bool = model('RealAuth')->updateById($realAuthInfo['id'], $appUid, $data);
            $id = $realAuthInfo['id'];
        }else{
            $id = model('RealAuth')->insert($appUid,$data);
        }
        //更新user表
        $infoData['name'] = $company['companyName'];
        $infoData['real_auth_id'] = $id;
        $infoData['real_auth_status']  = 20;
        $update = model('Users')->updateById($info['id'], $appUid, $infoData);
        return $result;
    }



    /**
     * 请求绑定银行卡
     *
     * @param [type] $appUid
     
     * @param [type] $bizUid
	 * @param [type] $cardNo 银行卡号
	 * @param [type] $phone	银行预留手机
	 * @param [type] $name 姓名 若企业会员填写法人
	 * @param [type] $cardCheck 绑卡方式 默认7
	 * @param [type] $identityNo 证件号码
	 * @param [type] $validate 有效期 格式为月年；如0321，2位月2位年，21年3月
	 * @param [type] $cvv2 CVV2 3位数字
     * @return void
     * @date 2020-11-13
     */
    public function applyBindBankCard($appUid, $params)
    {
        $bizUserId = $appUid . $params['bizUid'];

        $MemberService = new AllInPayMemberService();
        $result = $MemberService->applyBindBankCard($bizUserId,$params);
        if($result['code'] != 0){   //失败

            // if( $result['code'] == 9300){ // 【交易要素不存在或不合法 -- 渠道号不存在】 => 通联那边【新的银行卡bin】未添加.
            //     // $result['msg'] = '暂不支持此卡,请换一张卡';
            //     //20210510 发现code9300 错误原因不只一种 暂时先不处理文案
            // }
            return $result;
        }

        $info = model('Users')->infoByBizUid($appUid, $params['bizUid']);
        $data['uid'] = $info['id'];
        $data['app_uid'] = $appUid;
        $data['biz_uid'] = $info['biz_uid'];
        $data['card_no'] = $params['cardNo'];
        $data['card_category'] = $info['member_type'];
        $data['name'] = $params['name'];
        $data['phone'] = $params['phone'];
        $data['identity_type'] = 1;
        $data['identity_no'] = $params['identityNo'];
        $data['validate'] = $params['validate']??'';
        $data['cvv2'] = $params['cvv2']??'';
        $data['status'] = 0;
        $data['trance_num'] = $result['data']['tranceNum'] ?? '';
        $data['trans_date'] = $result['data']['transDate'] ?? '';
        $data['bank_name'] = $result['data']['bankName'];
        $data['bank_code'] = $result['data']['bankCode'];
        $data['card_type'] = $result['data']['cardType'];
        $data['id'] = model('BankCard')->insert($appUid,$data);
        return $result;
    }

    /**
     * 确认绑定银行卡
     *
     * @param [type] $appUid
     
     * @param [type] $bizUid
     * @param [type] $tranceNum 流水号 （请求绑定银行卡接口返回）
     * @param [type] $phone 银行预留手机	
     * @param [type] $verificationCode 短信验证码	
     * @param [type] $validate 申请时间	（请求绑定银行卡接口返回）
     * @param [type] $cvv2
     * @return void
     * @date 2020-11-13
     */
    public function bindBankCard($appUid, $params)
    {
        $bizUserId = $appUid . $params['bizUid'];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->bindBankCard($bizUserId,$params);
        if($result['code'] != 0){   //失败
            return $result;
        }
        $data['status'] = 10;
        $info = model('BankCard')->infoByTranceNum($appUid,$params['tranceNum']);
        $update = model('BankCard')->updateById($info['id'],$appUid,$data);
        return $result;
    }

    /**
     * 查询绑定银行卡
     *
     * @param [type] $appUid
     * @param [type] $bizUid
     * @return void 
     * @date 2020-11-13 
     */
    public function queryBankCard($appUid, $bizUid)
    {
        $bizUserId = $appUid . $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->queryBankCard($bizUserId);
        if($result['code'] != 0){   //失败
            return $result;
        }
        return $result;
    }

    /**
     * 解绑绑定银行卡
     *
     * @param [type] $appUid
     
     * @param [type] $bizUid
     * @param [type] $cardNo 需要解绑的银行卡号
     * @return void
     * @date 2020-11-13
     */
    public function unbindBankCard($appUid, $params)
    {
        $bizUserId = $appUid . $params['bizUid'];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->unbindBankCard($bizUserId,$params);
        if($result['code'] != 0){   //失败
            return $result;
        }
        $info = model('Users')->infoByBizUid($appUid, $params['bizUid']);
        $where[] = ['uid','=',$info['id']];
        $where[] = ['card_no','=',$params['cardNo']];
        $where[] = ['status','=',10];
        $op['where'] = $where;
        // $op['order'] = ['id','desc'];
        $infoBankCards = model('BankCard')->getList($appUid,$op)['list'];
        $infoBankCard = $infoBankCards[0]??'';
        if( $infoBankCard){
            $data['status'] = 40;
            $update = model('BankCard')->updateById($infoBankCard['id'], $appUid, $data);
        }
        $result['msg'] = '解绑成功';
        return $result;
    }

    /**
     * 锁定会员
     *
     * @param [type] $appUid
     
     * @param [type] $bizUid
     * @return void
     * @date 2020-11-13
     */
	public function lockMember($appUid, $bizUid)
	{
        $bizUserId = $appUid . $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->lockMember($bizUserId);
        if($result['code'] != 0){   //失败
            return $result;
        }
        $info = model('Users')->infoByBizUid($appUid, $bizUid);
        $data['status'] = 40;
        $update = model('Users')->updateById($info['id'],$appUid,$data);

        return $result;
    }
    
    /**
     * 解锁会员
     *
     * @param [type] $appUid
     
     * @param [type] $bizUid
     * @return void
     * @date 2020-11-13
     */
    public function unlockMember($appUid, $bizUid)
	{
        $bizUserId = $appUid . $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->unlockMember($bizUserId);
        if($result['code'] != 0){   //失败
            return $result;
        }
        $info = model('Users')->infoByBizUid($appUid, $bizUid);
        $data['status'] = 10;
        $update = model('Users')->updateById($info['id'],$appUid,$data);

        return $result;
    }

    /**
     * [setPayPwd 设置支付密码【密码验证版】]
     * @param [type] $appUid
     
     * @param [type] $bizUid
     * @param [type] $name 姓名
     * @param [type] $phone 手机号码
     * @param [type] $identityNo 身份证号
     * @param [type] $jumpUrl 设置密码之后，跳转返回的页面地址
     * @return void
     * @date 2020-11-11
     */
    public function setPayPwd($appUid, $params)
	{
        // $backUrl = $this->backHost.'/AllinPay/notifySetPayPwd';
        // $params['backUrl'] = $backUrl;
        $bizUserId = $appUid . $params['bizUid'];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->setPayPwd($bizUserId,$params);
        return $result;
    }

    /**
     * [updatePayPwd 修改支付密码【密码验证版】]
     *
     * @param [type] $appUid
     
     * @param [type] $bizUid
     * @param [type] $name 姓名
     * @param [type] $identityNo 身份证号
     * @param [type] $jumpUrl 修改密码之后，跳转返回的页面地址
     * @return void
     * @date 2020-11-11
     */
    public function updatePayPwd($appUid, $params)
	{
        // $backUrl = $this->backHost.'/AllinPay/notifyUpdatePayPwd';
        // $params['backUrl'] = $backUrl;
        $bizUserId = $appUid . $params['bizUid'];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->updatePayPwd($bizUserId,$params);
        return $result;
    }

    /**
     * [resetPayPwd 重置支付密码【密码验证版】]
     * @param [type] $appUid
     
     * @param [type] $bizUid
     * @param [type] $name 姓名
     * @param [type] $phone 手机号码
     * @param [type] $identityNo 身份证号
     * @param [type] $jumpUrl 设置密码之后，跳转返回的页面地址
     * @return void
     * @date 2020-11-11
     */
    public function resetPayPwd($appUid, $params)
	{
        // $backUrl = $this->backHost.'/AllinPay/notifyResetPayPwd';
        // $params['backUrl'] = $backUrl;
        $bizUserId = $appUid . $params['bizUid'];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->resetPayPwd($bizUserId,$params);
        return $result;
    }

    /**
     * 影印件采集 设置企业信息必传
     *
     * @param [type] $appUid
     
     * @param [type] $bizUid
     * @param [type] $picType 
     * 1-营业执照（必传）
     * 2-组织机构代码证（三证时必传）
     * 3-税务登记证（三证时必传）
     * 4-银行开户证明（非必传，上传《银行开户许可证》或《基本存款账户信息》等可证明平台银行账号和户名的文件）
     * 5-机构信用代码（非必传）
     * 6-ICP备案许可（非必传）
     * 7-行业许可证（非必传）
     * 8-身份证正面（人像面）（必传）
     * 9-身份证反面（国徽面）（必传）
     * @param [type] $pictureBase64 影印件图片的base64码 图片大小不超过1M 图片格式jpg、png
     * 	
     * $file = "data/2222.png";
	 * $base64_data = base64_encode(file_get_contents($file));
     * 
     * @return void
     * @date 2020-11-13
     */
    public function idcardCollect($appUid, $params){
        // $backUrl = $this->backHost.'/AllinPay/notifyIdcardCollect';
        // //ocrComparisonResultBackUrl
        // $params['ocrComparisonResultBackUrl'] = $backUrl;
        $bizUserId = $appUid . $params['bizUid'];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->idcardCollect($bizUserId,$params);
        if($result['code'] != 0){   //失败
            return $result;
        }
        $info = model('Users')->infoByBizUid($appUid, $params['bizUid']);
        $data['uid'] = $info['id'];
        $data['app_uid'] = $info['app_uid'];
        $data['biz_uid'] = $info['biz_uid'];
        $data['pic_type'] = $params['picType'];
        // $data['pic_base64'] = $params['pictureBase64'];
        $data['pic_status'] = $result['data']['result'];
        $data['id'] = model('Picture')->insert($appUid,$data);

        return $result;
    }

    /**
     * 创建子账号 function
     * @param [type] $appUid
     * @param [type] $bizUid
     * @param [type] $bizUserId
     * 1）个人会员、企业会员上送商户系统唯一编号
     * 2）平台（平台标准余额账户集、标准营销账户集、平台外部渠道应收账款集）上送固定值：#yunBizUserId_B2C#
     * 
     * @param [type] $accountSetNo 托管账户集-各应用专属账户集 
     * 标准余额账户集	100001	用于平台管理自有资金，一般为经营收入、手续费收入
     * 标准保证金账户集	100002	用于平台向平台用户收取、计提保证金
     * 准备金额度账户集	100003	用于通商云向平台收取交易手续费、保证金等费用。注：平台预充值给通商云。
     * 中间账户集A	100004	用于正向交易资金的中间账户
     * 中间账户集B	100005	用于逆向交易资金的中间账户
     * 准营销账户集	2000000	用于平台管理营销活动相关的资金
     * 预付卡账户集	100006	用于平台管理预付卡业务资金注：默认不创建，发生预付卡交易时，系统自动创建。
     * 
     * @param [type] $acctOrgType 	0-通联 1-华通银行
     * @return void
     * @date 2020-11-13
     */
    public function createBankSubAcctNo($appUid, $bizUid, $accountSetNo,$acctOrgType = 0){
        $bizUserId = $appUid . $bizUid;
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->createBankSubAcctNo($bizUserId,$accountSetNo,$acctOrgType = 0);
        if($result['code'] != 0){   //失败
            return $result;
        }
        return $result;
    }
    

    /**
	 * [getBankCardBin 查询卡bin]
	 *
	 * @param [type] $cardNo 银行卡号
	 * @return void
	 * @date 2020-11-19
	 */
	public function getBankCardBin($cardNo)
	{
		$MemberService = new AllInPayMemberService();
		$method = "allinpay.yunst.memberService.getBankCardBin";
		$result = $MemberService->getBankCardBin($cardNo);
        if($result['code'] != 0){   //失败
            return $result;
        }
        return $result;
	}




    /**
     * 获取saas 用户信息
     * @param $bizUids string eg: 1_1,1_2
     * @return  array saas用户表信息
     */
    public static function getSaasUserInfo($bizUids = []){
        $backUrl = config('saas.saas_api_server')."User/getUserList";
        $params = [
          'fin_member_ids'=>is_string($bizUids)?$bizUids:implode(',',$bizUids)
        ];
        $result = Http::post($backUrl, $params);
        $result = is_array($result)?$result:json_decode($result,true);
        return $result;
    }

    /**
     * 根据手机号 昵称 真实姓名 搜索fin 用户
     * @return array ["1_1","1_2"]
     */
    public static function getFinMemberIdBySearch($searchText = ''){
        $backUrl = config('saas.saas_api_server')."User/getFinMemberIdByUserInfo";
        $params = [
            'user_search'=>$searchText
        ];
        $result = Http::post($backUrl, $params);
        $result = is_array($result)?$result:json_decode($result,true);
        return $result;
    }

}