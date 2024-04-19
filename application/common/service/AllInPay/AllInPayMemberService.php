<?php


namespace app\common\service\AllInPay;
use app\common\tools\SysEnums;
use app\common\service\AllInPay\AllInPayClient;

class AllInPayMemberService{

    function __construct(){
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
		$this->url = $config['server_url'];
		$this->xcxUrl = $config['server_xcx_url'];
		$this->companyIsAuth = $config['company_is_auth'];
		$this->backUrl = $config['call_back_domain'];
    }

    /**
     *
     * @param $bizUserId
     * @param int $memberType 2:企业会员  3:个人会员
     * @param int $source 1:Mobile  2:PC
     * @return bool|mixed
     * User: 宋星 DateTime: 2020/11/5 16:49
     */
    function createMember($bizUserId, $paramData){
        $param = array();
        $param["bizUserId"] = $bizUserId;
        $param["memberType"] = $paramData['memberType'] ?? 3;
        $param["source"] = $paramData['source']??1;
        $method = "allinpay.yunst.memberService.createMember";
        $yunClient = new AllInPayClient();
        $result = $yunClient->request($method,$param);
        return $result;
    }

    /**
	 * [sendVerificationCode 发送短信验证码]
	 * 绑定手机前先调用
	 * @param [type] $bizUserId	商户系统用户标识，商户系统中唯一编号
	 * @param [type] $phone	手机号码
	 * @param [type] $verificationCodeType	验证码类型 9-绑定手机，6-解绑手机
	 * @return void
	 * @date 2020-11-11
	 */
	public function sendVerificationCode($bizUserId, $phone, $verificationCodeType)
	{
		$param = array();
		$param["bizUserId"] = $bizUserId;
		$param["phone"] = $phone;
		$param["verificationCodeType"] = $verificationCodeType;
		$method = "allinpay.yunst.memberService.sendVerificationCode";
		$yunClient = new AllInPayClient();
		$result = $yunClient->request($method,$param);
		return $result;
		//result
		//bizUserId
		//phone 手机号码
    }
    
    /**
	 * [bindPhone 绑定手机]
	 * 个人会员创建会员后即可绑定手机，与是否实名认证无关。企业会员需审核通过后才能绑定手机。
	 * @param [type] $bizUserId	商户系统用户标识
	 * @param [type] $phone	手机号码
	 * @param [type] $verificationCode	验证码
	 * @return void
	 * @date 2020-11-11
	 */
	public function bindPhone($bizUserId, $phone, $verificationCode)
	{
		$param = array();
		$param["bizUserId"] = $bizUserId;
		$param["phone"] = $phone;
		$param["verificationCode"] = $verificationCode;
		$method = "allinpay.yunst.memberService.bindPhone";
		$yunClient = new AllInPayClient();
        $result = $yunClient->request($method,$param);
        return $result;
		//result
		//bizUserId
		//phone 手机号码
    }
    
    /**
     * [unbindPhone 解绑手机（]
     * 验证原手机短信验证码）
     * @param [type] $bizUserId
     * @param [type] $phone
     * @param [type] $verificationCode
     * @return void
     * @date 2020-11-11
     */
	public function unbindPhone($bizUserId,$phone,$verificationCode)
	{
		$param = array();
		$yunClient = new AllInPayClient();
		$param["bizUserId"] = $bizUserId;
		$param["phone"] = $phone;
		$param["verificationCode"] = $verificationCode;
		$method = "allinpay.yunst.memberService.unbindPhone";
		$result = $yunClient->request($method,$param);
		return $result;
	}

    /**
	 * [setRealName 实名认证]
	 *
	 * @param [type] $bizUserId
	 * @param [type] $name	姓名
	 * @param [type] $identityNo 证件号码
	 * @return void
	 * @date 2020-11-11
	 */
	public function setRealName($bizUserId,$name,$identityNo)
	{
		$param = array();
		$yunClient = new AllInPayClient();
		$param["bizUserId"] = $bizUserId;
		$param["name"] = $name;
		$param["identityType"] = "1";
		$param["identityNo"] = $yunClient->encryptAES($identityNo);
		$method = "allinpay.yunst.memberService.setRealName";
		$result = $yunClient->request($method,$param);
		return $result;
		//result
		//bizUserId
		//name	姓名
		//identityType 1.身份证
		//identityNo	证件号码
    }
    
    /**
	 * [signContract 会员电子协议签约]
	 *
	 * @param [type] $bizUserId
	 * @param [type] $jumpUrl	跳转地址 117字符
	 * @param [type] $backUrl	后台通知地址 117字符\     
	 * @param integer $jumpPageType 跳转页面类型 1.H5 2.小程序
	 * @param [type] $source	终端 1.mobile 2.PC
	 * @return void
	 * @date 2020-10-29
	 */
	public function signContract($bizUserId,$paramData)
	{
		$param = array();
		$param["bizUserId"] = $bizUserId;
		$param["jumpPageType"] = $paramData['jumpPageType'] ?? 1;
		$param["jumpUrl"] = $paramData['jumpUrl'] ?? '';
		$param["backUrl"] = $this->backUrl.'/AllinPay/notifySignContract';
		$param["source"] = $paramData['source'] ?? 1;
		$method = "allinpay.yunst.memberService.signContract";
		$yunClient = new AllInPayClient();

		if( $param["jumpPageType"] == 2){
			$result = $yunClient->request($method,$param);
			$url = $this->xcxUrl.'/member/signContract.html?'.$result['data']['param'];
		}else{
			$result = $yunClient->concatUrlParams($method,$param);
			$url = $this->url.'?'.$result;   
		}
        return $url;		
	}

    /**
     * [signContract 会员电子协议签约查询]
     *
     * @param [type] $bizUserId
     * @param string $jumpUrl 点击确定按钮之后，跳转返回的页面地址
     * @param integer $jumpPageType 跳转页面类型 
     * @param integer $source 终端 1.mobile 2.PC
     * @return void
     * @date 2020-11-11
     */
    public function signContractQuery($bizUserId, $paramData)
    {
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$param["jumpPageType"] = $paramData['jumpPageType'] ?? 1;
		$param["jumpUrl"] = $paramData['jumpUrl'] ?? '';
		$param["source"] = $paramData['source'] ?? 1;
        $method = "allinpay.yunst.memberService.signContractQuery";
		$yunClient = new AllInPayClient();

		if( $param["jumpPageType"] == 2){
			$result = $yunClient->request($method,$param);
			$url = $this->xcxUrl.'/member/signContractQuery.html?'.$result['data']['param'];
		}else{
			$result = $yunClient->concatUrlParams($method,$param);
			$url = $this->url.'?'.$result; 
		}
        return $url;
    }

    /**
	 * [setCompanyInfo 设置企业信息]
	 * ！！！发送企业营业执照、开户许可证、法人身份证正反面扫码件至通联通商云企业用户认证邮箱yunzhanghu@allinpay.com。（测试环境无需发送）
	 * @param [type] $bizUserId	商户系统用户标识
	 * @param [type] $backUrl	企业会员审核结果通知
	 * @param array $companyBasicInfo 企业基本信息
	 * @param $isAuth 是否进行线上认证 true：系统自动审核，false：需人工审核
	 * 
	 * companyBasicInfo：
	 * companyName 企业名称，如有括号，用中文格式（）
	 * authType 认证类型 1:三证 2:一证 默认1
	 * uniCredit 统一社会信用（一证）认证类型为2时必传
	 * businessLicense	营业执照号（三证）
	 * organizationCode	营业执照号（三证）
	 * taxRegister	组织机构代码（三证）认证类型为1时必传
	 * expLicense 统一社会信用/营业执照号到期时间 格式：yyyy-MM-dd 非必填
	 * legalName 法人姓名
	 * identityType 法人证件类型 默认1 身份证
	 * legalIds 法人证件号码
	 * legalPhone	法人手机号码
	 * accountNo	企业对公账户 支持数字和“-”字符
	 * parentBankName	开户银行名称
	 * bankName	开户行支行名称	是否必须？
	 * unionBank	支付行号，12位数字	是否必须？
	 * 	
	 * @return void
	 * @date 2020-11-11
	 */
	public function setCompanyInfo($bizUserId,$paramData)//$backUrl,$companyBasicInfo=array(),$isAuth=false
	{
		$companyInfo = $paramData['companyBasicInfo'];
		if(empty($companyInfo['companyName']) || empty($companyInfo["legalName"]) || empty($companyInfo["legalIds"]) || empty($companyInfo["legalPhone"])  || empty($companyInfo["accountNo"]) || empty($companyInfo["parentBankName"]) ){//|| empty($companyInfo["bankName"]) || empty($companyInfo["unionBank"])
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}

		$companyBasicInfo['companyName'] = $companyInfo['companyName'];
		$companyBasicInfo['authType'] = $companyInfo['authType'] ?? 1;
		if( $companyBasicInfo['authType'] == 1){//三证
			if(empty($companyInfo['businessLicense']) || empty($companyInfo["organizationCode"]) || empty($companyInfo["taxRegister"]) ){
				return errorReturn('参数错误-缺少三证信息',SysEnums::ApiParamMissing);
			}
			$companyBasicInfo['businessLicense'] = $companyInfo['businessLicense'];
            $companyBasicInfo['organizationCode'] = $companyInfo['organizationCode'];
            $companyBasicInfo['taxRegister'] = $companyInfo['taxRegister'];
		}else{
			if(empty($companyInfo['uniCredit']) ){
				return errorReturn('参数错误-缺少一证信息',SysEnums::ApiParamMissing);
			}
			$companyBasicInfo['uniCredit'] = $companyInfo['uniCredit'];
		}

		$yunClient = new AllInPayClient();

		$companyBasicInfo['legalName'] = $companyInfo['legalName'];
		$companyBasicInfo['identityType'] = 1;
		$companyBasicInfo['legalIds'] = $yunClient->encryptAES($companyInfo['legalIds']);
		$companyBasicInfo['legalPhone'] = $companyInfo['legalPhone'];
		$companyBasicInfo['accountNo'] = $yunClient->encryptAES($companyInfo['accountNo']);
        $companyBasicInfo['parentBankName'] = $companyInfo['parentBankName'];
        $companyBasicInfo['bankName'] = $companyInfo['bankName'] ??'';
        $companyBasicInfo['unionBank'] = $companyInfo['unionBank'] ?? '';

        $param = array();
		$param["bizUserId"] = $bizUserId;
        $param["backUrl"] = $this->backUrl.'/AllinPay/notifySetCompanyInfo';
		$param["companyBasicInfo"] = $companyBasicInfo;
		$param["isAuth"] = $this->companyIsAuth;
		// var_dump($param);
		$method = "allinpay.yunst.memberService.setCompanyInfo";
        $result = $yunClient->request($method,$param);
        return $result;
    }	
    

    /**
	 * [getMemberInfo 获取会员信息]
	 *
	 * @param [type] $bizUserId
	 * @return void
	 * @date 2020-10-29
	 */
	public function getMemberInfo($bizUserId)
	{
        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$param["acctOrgType"] = 0;//通联
		$method = "allinpay.yunst.memberService.getMemberInfo";
        $result = $yunClient->request($method,$param);
        return $result;
		
		//result
		//bizUserId
		//memberType 会员类型 2.企业 3.个人
		//memberInfo 
		
		// 个人信息：
		//	name 姓名
		//	userState 用户状态 1.有效 3.审核失败 5.已锁 7.待审核
		//	userId	通商云用户id
		//	country 国家
		//	province 省市
		//	area	县市
		//	address	地址
		//	phone	手机号码
		//	identityCardNo 身份证号码，AES加密。
		// 	isPhoneChecked	是否绑定手机
		//  registerTime	创建时间yyyy-MM-dd HH:mm:ss
		// 	registerIp		创建ip
		// 	payFailAmount	支付失败次数
		// 	isIdentityChecked	是否进行实名认证
		//	realNameTime	实名认证时间yyyy-MM-dd HH:mm:ss
		// 	remark	备注
		//  source	访问终端类型 1.mobile 2.PC
		//	isSetPayPwd 是否已设置支付密码
		//  isSignContract	是否已签电子协议
		//  acctOrgType	开户机构类型 0.通联 1.华通银行
		//	subAcctNo	会员开通的华通子账号或通联子账号
		//  signContractTime 签订电子协议时间
		//	ContractNo	电子协议编号

		//  企业信息：
		//	companyName	企业名称
		//  companyAddress 企业地址
		//  authType	认证类型（三证或一证）
		//  businessLicense 营业执照号（三证）
		//  organizationCode 组织机构代码（三证）
		//  uniCredit 统一社会信用（一证）
		//  taxRegister 税务登记证（三证）
		//	expLicense	统一社会信用/营业执照号到期时间 yyyy-MM-dd
		//	telephone	联系电话
		//	phone		手机号码
		//	legalName	法人姓名
		//	identityType	1.身份证
		//	legalIds	法人证件号码AES加密
		//  legalPhone	法人手机号码
		//  accountNo	企业对公账户账号AES加密
		//  parentBankName	开户银行名称
		// 	bankCityNo	开户行地区代码（根据代码表）
		//	bankName 	开户行支行名称	
		//  unionBank	支付行号，12位数字
		//  province	开户行所在省
		//	city		开户行所在市
		//  isSignContract 是否已签电子协议
		//  status	审核状态
    }
    
    /**
	 * [applyBindBankCard 请求绑定银行卡]
	 * 
	 * @param [type] $bizUserId 商户系统用户标识
	 * @param [type] $cardNo 银行卡号
	 * @param [type] $phone	银行预留手机
	 * @param [type] $name 姓名 若企业会员填写法人
	 * @param [type] $cardCheck 绑卡方式 默认7
	 * @param [type] $identityType 证件类型 1身份证
	 * @param [type] $identityNo 证件号码
	 * @param [type] $validate 有效期 格式为月年；如0321，2位月2位年，21年3月
	 * @param [type] $cvv2 CVV2 3位数字
	 * @return void
	 * 
	 * @date 2020-10-29
	 */
    public function applyBindBankCard($bizUserId,$paramData)
	{
		// echo "<pre>";
// var_dump($paramData);
// exit;
		if(empty($paramData['cardNo']) || empty($paramData["phone"]) || empty($paramData["name"]) || empty($paramData["identityNo"])  ){
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}
        $validate = $paramData['validate'] ?? null;
        $cvv2 = $paramData['cvv2'] ?? null;

        $yunClient = new AllInPayClient();
		$param = array();
		$param["bizUserId"] = $bizUserId;
		$param["cardNo"] = $yunClient->encryptAES($paramData['cardNo']);
		$param["phone"] = $paramData['phone'];
		$param["name"] = $paramData['name'];
		$param["cardCheck"] = $paramData['cardCheck'] ?? 7;
		$param["identityType"] = 1;
		$param["identityNo"] = $yunClient->encryptAES($paramData['identityNo']);
		if( $validate){
			$param["validate"] = $yunClient->encryptAES($validate);
		}
		if( $cvv2){
			$param["cvv2"] = $yunClient->encryptAES($cvv2);
		}
//var_dump($param);
//exit;
		$method = "allinpay.yunst.memberService.applyBindBankCard";
        $result = $yunClient->request($method,$param);
        return $result;
		//result
		//bizUserId	商户系统用户标识，商户系统中唯一编号
		//tranceNum	流水号 
		//transDate	申请时间	YYYYMMDD
		//bankName	银行名称
		//bankCode	银行代码
		//cardType	银行卡类型	1储蓄卡 2信用卡
    }
    
    /**
     * [applyBindBankCard 确认绑定银行卡]
     *
     * @param [type] $bizUserId
     * @param [type] $tranceNum
     * @param [type] $phone
     * @param [type] $verificationCode
     * @param [type] $validate
     * @param [type] $cvv2
     * @return void
     * @date 2020-11-11
     */
    public function bindBankCard($bizUserId,$paramData)
	{
		if(empty($paramData['tranceNum']) || empty($paramData["phone"]) || empty($paramData["verificationCode"]) ){
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}
        $validate = $paramData['validate'] ?? null;
        $cvv2 = $paramData['cvv2'] ?? null;

        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$param["tranceNum"] = $paramData['tranceNum'];
		$param["phone"] = $paramData['phone'];
		$param["verificationCode"] = $paramData['verificationCode'];
		$param["validate"] = $yunClient->encryptAES($validate);
		$param["cvv2"] = $yunClient->encryptAES($cvv2);
		$method = "allinpay.yunst.memberService.bindBankCard";
		$result = $yunClient->request($method,$param);
		return $result;
    }
    
    /**
	 * [queryBankCard 查询绑定银行卡]
	 */
	public function queryBankCard($bizUserId)
	{
        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$method = "allinpay.yunst.memberService.queryBankCard";
		$result = $yunClient->request($method,$param);
		return $result;
	}

    /**
	 * [unbindBankCard 解绑绑定银行卡]
	 */
	public function unbindBankCard($bizUserId,$paramData)
	{
		if( empty($paramData['cardNo']) ){
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}
        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$param["cardNo"] = $yunClient->encryptAES($paramData['cardNo']);
		$method = "allinpay.yunst.memberService.unbindBankCard";
		$result = $yunClient->request($method,$param);
		return $result;
	}

    /**
	 * [lockMember 锁定会员]
	 */
	public function lockMember($bizUserId)
	{
        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$method = "allinpay.yunst.memberService.lockMember";
		$result = $yunClient->request($method,$param);
		return $result;
	}

	/**
	 * [unlockMember 解锁会员]
	 */
	public function unlockMember($bizUserId)
	{
        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$method = "allinpay.yunst.memberService.unlockMember";
		$result = $yunClient->request($method,$param);
		return $result;
	}

    /**
     * [setPayPwd 设置支付密码【密码验证版】]
     *
     * @param [type] $bizUserId
     * @param [type] $name 姓名
     * @param [type] $phone 	手机号码
     * @param [type] $identityNo 证件号码	
     * @param [type] $jumpUrl 设置密码之后，跳转返回的页面地址
     * @param [type] $backUrl 后台通知地址
     * @return void
     * @date 2020-11-11
     */
	public function setPayPwd($bizUserId,$paramData)
	{
		if(empty($paramData['phone']) || empty($paramData["identityNo"]) ){
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}

        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$param["phone"] = $paramData['phone'];
		$param["name"] = $paramData['name'] ?? '';
		$param["identityType"] = 1;
		$param["identityNo"] = $yunClient->encryptAES($paramData['identityNo']);
		$param["jumpPageType"] = $paramData['jumpPageType']??'';
		$param["jumpUrl"] = $paramData['jumpUrl'] ?? '';
		$param["errorJumpUrl"] = $paramData['errorJumpUrl']??'';
		$param["backUrl"] = $this->backUrl.'/AllinPay/notifySetPayPwd';
		$method = "allinpay.yunst.memberService.setPayPwd";
		$result = $yunClient->concatUrlParams($method,$param);
        $url = $this->url.'?'.$result;
        return $url;
		// $this->logIns->logMessage("[设置支付密码【密码验证版】]",Log::INFO,$url);
		// header("Location:$url");
    }
    

    /**
	 * [updatePayPwd 修改支付密码【密码验证版】]
	 */
	public function updatePayPwd($bizUserId,$paramData)
	{
		if( empty($paramData["identityNo"]) ){
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}
        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$param["jumpPageType"] = $paramData['jumpPageType']??'';
		$param["name"] = $paramData['name'];
		$param["identityType"] = 1;
		$param["identityNo"] = $yunClient->encryptAES($paramData['identityNo']);
		$param["jumpUrl"] = $paramData['jumpUrl']??'';
		$param["errorJumpUrl"] = $paramData['errorJumpUrl']??'';
		$param["backUrl"] = $this->backUrl.'/AllinPay/notifyUpdatePayPwd';
		$method = "allinpay.yunst.memberService.updatePayPwd";
        $result = $yunClient->concatUrlParams($method,$param);
        $url = $this->url.'?'.$result;
        return $url;
		// $url = "http://test.allinpay.com/op/gateway?".$result;
		// $this->logIns->logMessage("[修改支付密码【密码验证版】]",Log::INFO,$url);
		// header("Location:$url");
	}

    /**
	 * [resetPayPwd 重置支付密码【密码验证版】]
	 */
	public function resetPayPwd($bizUserId, $paramData)
	{
		if( empty($paramData["name"]) || empty($paramData["phone"]) || empty($paramData["identityNo"])){
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}
        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$param["jumpPageType"] = $paramData['jumpPageType']??'';
		$param["name"] = $paramData['name'];
		$param["phone"] = $paramData['phone'];
		$param["identityType"] = 1;
		$param["identityNo"] = $yunClient->encryptAES($paramData['identityNo']);
		$param["jumpUrl"] = $paramData['jumpUrl']??'';
		$param["errorJumpUrl"] = $paramData['errorJumpUrl']??'';
		$param["backUrl"] = $this->backUrl.'/AllinPay/notifyResetPayPwd';
		$method = "allinpay.yunst.memberService.resetPayPwd";
        $result = $yunClient->concatUrlParams($method,$param);
        $url = $this->url.'?'.$result;
        return $url;
		// $url = "http://test.allinpay.com/op/gateway?".$result;
		// $this->logIns->logMessage("[重置支付密码【密码验证版】]",Log::INFO,$url);
		// header("Location:$url");
    }
    
    /**
     * [updatePhoneByPayPwd 修改绑定手机【密码验证版】]
     * 成功回调，失败不回调
     * @param [type] $bizUserId
     * @param [type] $name 姓名
     * @param [type] $oldphone 旧手机号码
     * @param [type] $identityNo 身份证号码
     * @param [type] $jumpUrl 跳转地址
     * @param [type] $backUrl 回调通知地址
     * @return void
     * @date 2020-11-11
     */
	public function updatePhoneByPayPwd($bizUserId, $paramData)
	{
		if( empty($paramData["name"]) || empty($paramData["oldPhone"]) || empty($paramData["identityNo"])){
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}
        $yunClient = new AllInPayClient();
        $param = array();
		$param["bizUserId"] = $bizUserId;
		$param["jumpPageType"] = $paramData['jumpPageType']??'';
		$param["name"] = $paramData['name'];
		$param["oldPhone"] = $paramData['oldPhone'];
		$param["identityType"] = 1;
		$param["identityNo"] = $yunClient->encryptAES($paramData['identityNo']);
		$param["jumpUrl"] = $paramData['jumpUrl']??'';
		$param["errorJumpUrl"] = $paramData['errorJumpUrl']??'';
		$param["backUrl"] = $this->backUrl.'/AllinPay/notifyUpdatePhoneByPayPwd';
		$method = "allinpay.yunst.memberService.updatePhoneByPayPwd";
        $result = $yunClient->concatUrlParams($method,$param);
        $url = $this->url.'?'.$result;
        return $url;
		// $url = "http://test.allinpay.com/op/gateway?".$result;
		// $this->logIns->logMessage("[修改绑定手机【密码验证版】]",Log::INFO,$url);
		// header("Location:$url");
    }
    
    /**
     * 影印件采集 设置企业信息必传
     *
     * @param [type] $bizUserId 
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
     * @return void
     * @date 2020-11-13
     */
    public function idcardCollect($bizUserId,$paramData)
	{
		if( empty($paramData["picType"]) || empty($paramData["pictureBase64"]) ){
			return errorReturn('参数错误',SysEnums::ApiParamMissing);
		}
		$param = array();
		$yunClient = new AllInPayClient();
		$param["bizUserId"] = $bizUserId;
		$param["picType"] = $paramData['picType'];
		$param["picture"] = $paramData['pictureBase64'];
		$param['ocrComparisonResultBackUrl'] = $this->backUrl.'/AllinPay/notifyIdcardCollect';
		$method = "allinpay.yunst.memberService.idcardCollect";
		$result = $yunClient->request($method,$param);
		return $result;
    }	
    

    /**
     * 会员子账户开通
     *
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
    public function createBankSubAcctNo($bizUserId,$accountSetNo,$acctOrgType = 0)
	{
		$param = array();
		$yunClient = new AllInPayClient();
		$param["bizUserId"] = $bizUserId;
		$param["accountSetNo"] = $accountSetNo;
		$param["acctOrgType"] = $acctOrgType;
		$method = "allinpay.yunst.memberService.createBankSubAcctNo";
		$result = $yunClient->request($method,$param);
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
		$param = array();
		$yunClient = new AllInPayClient();
		$param["cardNo"] = $yunClient->encryptAES($cardNo);
		$method = "allinpay.yunst.memberService.getBankCardBin";
		$result = $yunClient->request($method,$param);
		return $result;
	}

}