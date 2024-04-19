<?php

namespace app\admin\controller;

use AllInPay\MemberService;
use AllInPay\SDK\yunClient;
use app\common\amqp\BizConsumer;
use app\common\amqp\BizProducer;
use app\common\service\AllInPay\AllInPayClient;
use app\common\service\AllInPay\AllInPayOrderService;
use app\common\service\OrderEntryService;
use app\common\service\OrderProcessService;
use app\common\service\UserFundsService;
use app\common\service\OrderRefundService;
use app\common\service\UserService;
use app\common\tools\Redis;
use app\push\service\ToolsService;
use think\Controller;
use think\Db;
use think\facade\Config;
use think\facade\Env;
use think\facade\Log;
use app\common\service\AllInPay\AllInPayMemberService;

/**
 * 临时用
 * @author LX
 * @date 2021-04-21
 */
class TempTest extends Controller {

    public $accountSetNo = '';

    public function __construct(){
        parent::__construct();
        $allInPayClient = new AllInPayClient();
        $config = $allInPayClient->getConfig();
        $this->accountSetNo = $config['account_set_no'];
    }

    public function index(){
        $userService = new UserService();
        $bizUserId = '-10';
        $param = [
            'memberType' => 2,
        ];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->createMember($bizUserId,$param);
        var_dump($result);
    }

    public function getMemberInfo(){
        $MemberService = new AllInPayMemberService();
        $bizUserId = '-10';
        echo "<pre>";
        $result = $MemberService->getMemberInfo($bizUserId);
        var_dump($result);
    }

    public function setCompanyInfo(){
        // $userService = new UserService();
        $MemberService = new AllInPayMemberService();
        $bizUserId = '-10';
        $companyBasicInfo = [
            'companyName' => '海南中装速配科技有限公司',
            'authType' => 2,
            'uniCredit' => '91469027MA5RCYE32U',
            'legalName' => '缪林',
            'legalIds' => '352202198512065417',
            'legalPhone' => '17305925045',
            'accountNo' => '4100200119200074962',
            'parentBankName' => '中国工商银行',
        ];
        $param = [
            // 'memberType' => 2,
            'companyBasicInfo' => $companyBasicInfo,
        ];

        $result = $MemberService->setCompanyInfo($bizUserId,$param);    
        var_dump($result);
    }

    public function idCardCollect1(){
        // $userService = new UserService();
        $MemberService = new AllInPayMemberService();
        $bizUserId = '-10';
        $file = "./hn/yy.png";
		$base64_data = base64_encode(file_get_contents($file));
        $param = [
            'picType' => 1,
            'pictureBase64' => $base64_data,
        ];

        $result = $MemberService->idcardCollect($bizUserId,$param);    
        var_dump($result);
    }

    public function idCardCollect2(){
        // $userService = new UserService();
        $MemberService = new AllInPayMemberService();
        $bizUserId = '-10';
        $file = "./hn/id1.jpg";
		$base64_data = base64_encode(file_get_contents($file));
        $param = [
            'picType' => 8,
            'pictureBase64' => $base64_data,
        ];

        $result = $MemberService->idcardCollect($bizUserId,$param);    
        var_dump($result);
    }

    public function idCardCollect3(){
        // $userService = new UserService();
        $MemberService = new AllInPayMemberService();
        $bizUserId = '-10';
        $file = "./hn/id2.jpg";
		$base64_data = base64_encode(file_get_contents($file));
        $param = [
            'picType' => 9,
            'pictureBase64' => $base64_data,
        ];

        $result = $MemberService->idcardCollect($bizUserId,$param);    
        var_dump($result);
    }

    public function signContract(){

        $bizUserId = '-10';
        $param = [

        ];
        $MemberService = new AllInPayMemberService();
        $result = $MemberService->signContract($bizUserId,$param);
        header("Location:$result");
        // var_dump($result);
    }

    public function sendVerificationCode(){

        $bizUserId = '-10';
        $phone = '13850083306';
        $verificationCodeType = 9; // 9-绑定手机，6-解绑手机

        $MemberService = new AllInPayMemberService();
        $result = $MemberService->sendVerificationCode($bizUserId,$phone,$verificationCodeType);
        var_dump($result);
    }

    public function bindPhone(){

        $bizUserId = '-10';
        $phone = '13850083306';
        $verificationCode = '545460'; //验证码

        $MemberService = new AllInPayMemberService();
        $result = $MemberService->bindPhone($bizUserId,$phone,$verificationCode);
        var_dump($result);
    }




    




    
}
