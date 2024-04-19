<?php

namespace app\api\controller;

use app\common\amqp\BizProducer;
use app\common\model\OrderEntry;
use app\common\model\OrderRefund;
use app\common\service\AllInPay\AllInPayOrderService;
use app\common\service\Ccb\CcbAccountingRulesService;
use app\common\service\Ccb\CcbClient;
use app\common\service\Ccb\CcbOrderService;
use app\common\service\Ccb\CcbShopInfoService;
use app\common\service\OrderProcessService;
use app\common\service\OrderRefundService;
use app\common\tools\SysEnums;
use app\push\service\SendWxWorkService;
use think\Controller;
use think\Db;

class CcbOrder extends Base {

    private $params = [];
    private $ccbClient = [];
    private $config = [];

    public function __construct(){
        parent::__construct();
        $this->ccbClient = new CcbClient();
        $this->config = $this->ccbClient->getConfig();
        $this->params = input();
        if(empty($this->params)){
            return apiOutError('参数不能为空',SysEnums::ApiParamMissing);
        }
    }

    //生成请求序列号
    public function getSn(){
        list($usec, $sec) = explode(" ", microtime());
        $msec = round($usec*1000);  //毫秒时间戳
        return date("ymdHis",$sec).sprintf("%03d", $msec).mt_rand(1,9);
    }

    //生成发起时间戳
    public function getTms(){
        list($usec, $sec) = explode(" ", microtime());
        $msectime = (float)sprintf('%.0f', (floatval($usec)) * 1000);
        return date('YmdHis',$sec).$msectime;
    }

    //生成支付订单
    /*
    //参数
    'mainOrdrNo' => $sn,       //客户方主订单流水号(可以与ittpartyJrnlNo一致),不允许重复  VarChar 40
    'pymdCd' => '07',          //支付方式代码. 01 PC端  02 线下支付（无收银台） 03 移动端H5页面 (app)  05 微信小程序（无收银台） 06 对私网银（无收银台） 07 聚合二维码（无收银台） 08龙支付（无收银台）
    'pgfcRetUrlAdr' => 'http://baidu.com',   //支付完成后，跳转到的页面URL地址
    'ordrTamt' => 1,          //应付总金额  NUMBER	19,2
    'txnTamt' => 1,           //消费者实付总金额  NUMBER	19,2
    'subOpenid' => '',        //用户openid.“Pymd_Cd（支付方式代码）”为“05-微信小程序”时必输
    'payDsc' => '',           //(可选)支付描述,该字段会在微信或支付宝的商品栏进行显示  VarChar	300
    'orderlist' => [          //(可选)子订单列表(json对象),可以多个
        [
            'clrgRuleId' => 'F410608608002941775', //分账规则编号
            'cmdtyOrdrNo' => $sn.'01', //客户方子订单流水号,不允许重复  VarChar 40
            'ordrAmt' => 1,    //订单商品总金额，即应付金额，所有商品订单金额之和等于主订单金额  NUMBER	19,2
            'txnamt' => 1,     //消费者实付金额，所有商品订单金额之和等于主交易总金额金额  NUMBER	19,2
            'mktMrchId' => '41060860800294000000', //商家编号,20位商家编号该字段由银行在正式上线前提供，测试阶段有测试数据  VarChar 30
            'cmdtyDsc' => '',  //(可选)商品描述 VarChar 300
            'parlist' => [    //(可选)分账方列表，1.“Py_Ordr_Tpcd（订单类型）”为“02-消费券购买订单”时该域无效，可不送；  2.不送分账规则编号与参与方列表时，走默认分账策略（即100天后分给子订单的商家） 3.多个子订单时不可送
                [
                    'seqNo' => 1,  //顺序号,1.参与方顺序号（默认从1开始）,多个子订单时不可送  NUMBER 10
                    'mktMrchId' => '41060860800294001779', //商家编号,多个子订单时不可送  VarChar 30
                ],
                [
                    'seqNo' => 2,  //顺序号,1.参与方顺序号（默认从1开始）,多个子订单时不可送  NUMBER 10
                    'mktMrchId' => '41060860800294001780', //商家编号,多个子订单时不可送  VarChar 30
                ],
            ],
        ],
    ]
    */
    public function gatherPlaceorder()
    {
        $data = input();
        if(isset($data['orderlist'])){
            $data['orderList'] = json_decode(html_entity_decode($data['orderList']),true);
        }

        if(empty($data['payMethodKey'])){
            return apiOutError('缺少支付方式',SysEnums::ApiParamMissing);
        }
        if(!empty($data['payMethodKey'])){
            $pymdCd = explode(',', $this->config['pymd_cd']);
            if(!in_array($data['payMethodKey'], $pymdCd)){
                return apiOutError('不支持的支付方式',SysEnums::ApiParamMissing);
            };
        }

        //测试参数
        $sn = $this->getSn();
//        dump($sn);exit;
        $today = date("Ymd");
        //正则查找, 保留(中文,字母,数字,-,)
        preg_match_all('/[\x{4e00}-\x{9fa5}a-zA-Z0-9-_]/u',$data['summary'], $matches);
        $data['summary'] = join('',$matches[0]);
        $data['summary'] = mb_substr($data['summary'], 0, 10, 'utf-8');

        $format_order_list = $this->checkFormatOrderList($data,$data['amount']);
        if(!$format_order_list['result']){
            return $format_order_list;
        }
        $params = [
            'pyOrdrTpcd' => $this->config['Py_Ordr_Tpcd'],    //订单类型,固定04
            'ccy' => $this->config['Ccy'],  //币种,固定156
//            'Order_Time_Out' => $this->config['Order_Time_Out'],//订单超时时间,默认30分钟(可选)
            'ittpartyTms' => $this->getTms(),     //发起方时间戳,毫秒时间 年月日, 时分秒，毫秒
            'ittpartyJrnlNo' => $sn,   //该笔直连交易的客户方流水号（不允许重复） VarChar 32

            //业务端必传参数
            'mainOrdrNo' => $data['bizOrderNo'],     //客户方主订单流水号(可以与 ittpartyJrnlNo 一致),不允许重复  VarChar 40
            'pymdCd' => $data['payMethodKey'],         //支付方式代码. 01 PC端  02 线下支付（无收银台） 03 移动端H5页面 (app)  05 微信小程序（无收银台） 06 对私网银（无收银台） 07 聚合二维码（无收银台） 08龙支付（无收银台）
            'pgfcRetUrlAdr' => $data['bizFrontUrl'],   //支付完成后，跳转到的页面URL地址
            'ordrTamt' => bcdiv($data['amount'],100,2),          //应付总金额  NUMBER	19,2
            'txnTamt' => bcdiv($data['amount'],100,2),       //消费者实付总金额  NUMBER	19,2
            'payDsc' => $data['summary'],           //(可选)支付描述,该字段会在微信或支付宝的商品栏进行显示  VarChar	300
            'orderlist' => $format_order_list['data'],
            'vno'       =>4,
            'clrgDt'   =>$today,
        ];

        if($params['pymdCd'] =='06'){
            if(empty($data['bnkCd'])){
                return apiOutError('对私网银bnkcd必传',SysEnums::ApiParamMissing);
            }
            $params['bnkCd'] = $data['bnkCd'];
        }else if($params['pymdCd'] =='05'){
            if(empty($data['subAppid']) || empty($data['acct'])){
                return apiOutError('小程序支付，subAppid和subOpenid必传',SysEnums::ApiParamMissing);
            }
            $params['subAppid'] = $data['subAppid'];   //小程序的APPID
            $params['subOpenid'] = $data['acct'];//用户openid.“Pymd_Cd（支付方式代码）”为“05-微信小程序”时必输；
        }

        $appUid         = $this->appUid;    //默认1000
        $payerBizUid    = $data['bizUid']; //付款人biz_uid
        $payerId        = $appUid . $payerBizUid;
        $payMethod[$data['payMethodKey'] ?? '05'] = []; //支付方式  QUICKPAY_VSP 快捷支付

        $paramNew['bizOrderNo']    = $data['bizOrderNo'] ?? "SX".date("YmdHis");   //代收订单号
        $paramNew['payerId']       = $payerId;
        $paramNew["amount"]        = $data['amount'];
        $paramNew["front_url"]   = $data['bizFrontUrl'] ?? '';
        $paramNew["back_url"]    = $data['bizBackUrl']  ?? '';
        $paramNew["processBackUrl"] = $data['bizProcessBackUrlBackUrl']  ?? '';
        $paramNew['extendInfo']    = $appUid;
        $paramNew["showUserName"]  = $data['showUserName']  ?? '';
        $paramNew["showOrderNo"]   = $data['showOrderNo']  ?? '';
        $paramNew['payMethod']    = $payMethod;
        $paramNew['type']          = 2;
        $paramNew['open_id']       = $data['acct']?? '';
        $paramNew['summary']       = $data['summary']?? '';
        $paramNew['fee']           = floor($data['amount'] * OrderEntry::FEE);
        $ccbOrder = new CcbOrderService();
        $res = $ccbOrder->gatherPlaceorder($params,$appUid,$data['payMethodKey']);
        if(!$res['result']){
            $errorMsg = $res['msg'] ?? '添加建行BBC订单失败';
            return apiOutError($errorMsg);
        }
        $paramNew['ittparty_jrnl_no'] = $sn;
        $paramNew['allinpay_order_no'] = $res['data']['prim_ordr_no'] ?? '';
        $paramNew['py_trn_no'] = $res['data']['py_trn_no'] ?? '';
        $paramNew['pay_qr_code'] = $res['data']['pay_qr_code'] ?? '';
        $paramNew['sub_ordr_id'] = $res['data']['sub_ordr_id'] ?? '';
        $paramNew['result_msg']  = json_encode($res);
        $rs = model('OrderEntry')->addOrder($appUid, $paramNew);
        $paramNew['orderlist'] = $data['orderList'];
        $resProcess = model('OrderProcess')->addProcessOrderByBbc($appUid,$paramNew);

        if(!$rs['result']){
            $errorMsg = $rs['msg'] ?? '添加建行BBC订单失败';
            return apiOutError($errorMsg);
        }
        if(!$resProcess['result']){
            $errorMsg = $resProcess['msg'] ?? '添加建行BBC订单失败';
            return apiOutError($errorMsg);
        }
        //mq通知企业微信
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'SendWxWorkService',
            'fun' =>  'sendWxWork',   //fun 必填, 值是 Service 的方法名
            'appId' => $appUid,
            'bizOrderNo' => $res['data']['main_ordr_no'] ?? '',
            'type'=> SendWxWorkService::ADD_PAY_ORDER,
            'workKey'  =>env('SAASWORK.HSB_PUSH', '')
        ];
        $result = $producer->publish($arrMsg);

        return apiOut($res);
        


    }

    /**
     * 转化orderList数据 校验价格是否正确
     * @param $data
     * @return mixed
     * User: cwh  DateTime:2021/9/16 22:11
     */
    public function checkFormatOrderList($data,$amount){
        if(empty($data)){
            return $data;
        }
        $total_ordr_amt = 0;
        $total_txnamt = 0;
        $total_amt = 0;
        foreach($data['orderList'] as &$v){
            $total_ordr_amt = bcadd($total_ordr_amt,$v['ordrAmt'],2);
            $total_txnamt = bcadd($total_txnamt,$v['txnamt'],2);
            $v['ordrAmt'] = bcdiv($v['ordrAmt'],100,2);
            $v['txnamt'] = bcdiv($v['txnamt'],100,2);
            $v['cmdtyDsc'] = $data['summary'];

            if(!empty($v['parlist']) ){
                foreach ($v['parlist'] as &$item) {
                    $total_amt =  bcadd($total_amt,$item['amt'],2);
                    $item['amt'] = bcdiv($item['amt'],100,2);
                    unset($item['amt']);
                }
            }
        }
        if($amount != $total_txnamt ){
            return errorReturn("订单总价和订单子价格不一致");
        }
        if($total_txnamt != $total_ordr_amt){
            return errorReturn("订单子价格不一致");
        }
        if($total_amt != $total_txnamt){
            return errorReturn("订单列表分账价格加起来不等于总价格");
        }
        $returnData['msg'] = '成功';
        $returnData['data'] = $data['orderList'];
        return successReturn($returnData);
    }

    //刷新聚合二维码
    //pyTrnNo	 支付流水号 必传
    public function mergePayUrl(){
        if(empty($this->params['pyTrnNo'])){
            return errorReturn('缺少支付流水号',SysEnums::ApiParamMissing);
        }
        $params = [
            'ittpartyJrnlNo' => $this->getSn(),
            'ittpartyTms' => $this->getTms(),
            'rqsPyTp' => '2',       //刷新聚合二维码
            'pyTrnNo' => $this->params['pyTrnNo'],  //支付流水号
        ];
        $ccbOrder = new CcbOrderService();
        return $ccbOrder->mergePayUrl($params);
    }


    //查询支付结果
    //主订单号与支付流水号必输其一
    //mainOrdrNo 主订单编号
    //pyTrnNo	 支付流水号
    public function gatherEnquireOrder(){
        if(empty($this->params['mainOrdrNo']) && empty($this->params['pyTrnNo'])){
            return errorReturn('缺少主订单编号或支付流水号',SysEnums::ApiParamMissing);
        }
        $params = [
            'ittpartyJrnlNo' => $this->getSn(),
            'ittpartyTms' => $this->getTms(),
            'mainOrdrNo' => $this->params['mainOrdrNo']??'',  //主订单编号
            'pyTrnNo' => $this->params['pyTrnNo']??'',        //支付流水号
            'vno'       =>4,
        ];
        $ccbOrder = new CcbOrderService();
        return $ccbOrder->gatherEnquireOrder($params);
    }

    //分账规则查询接口
    //page          第几页
    //clrgRuleId    清算规则编号(可选)
    //ruleNm        规则名称(可选)
    public function accountingRulesList(){
        if(empty($this->params['page'])){
            return errorReturn('缺少页数',SysEnums::ApiParamMissing);
        }
        $params = $this->params;
        $params['ittpartyJrnlNo']   = $this->getSn();
        $params['ittpartyTms']      = $this->getTms();
        $params['clrgRuleId']       = $this->params['clrgRuleId'] ?? '';//清算规则编号
        $params['ruleNm']           = $this->params['ruleNm'] ?? '';    //规则名称
        $params['recInPage']        = $this->params['limit'] ?? 20;     //每页条数
        $params['pageJump']         = $this->params['page'] ?? 1;       //页数
        $params['vno']              = 3;
        $ccbOrder = new CcbOrderService();
        return $ccbOrder->accountingRulesList($params);
    }

    //根据 app_id 查询规则列表
    function getRules(){
        $app_id = $this->params['appId'];
        $bizUid = $this->params['bizUid'];
        $payMethodKey = $this->params['payMethodKey'];

//        $field = ['id', 'app_id'];
        $field = 'id, app_id, biz_uid, mkt_id, mkt_nm, 
        mkt_mrch_id, clrg_rule_id, seq_no,
        sub_acc_cyc, clrg_dlay_dys, clrg_mode, clrg_mtdcd, clrg_pctg,
         efdt, expdt, create_time, update_time, is_gather';
        $ccbAccountingRulesService = new CcbAccountingRulesService();
        return $ccbAccountingRulesService->getListByBizUid($app_id, $bizUid, $payMethodKey, $field);
    }

    function getShopInfo(){
        $app_id = $this->params['appId'];
        $bizUid = $this->params['bizUid'];

        $field = 'id, app_id, biz_uid, mkt_mrch_id, mkt_mrch_nm, pos_no,
        mrch_crdt_tp, mrch_crdt_no, mrch_cnter_cd, crdt_tp, ctcpsn_nm,
        crdt_no, mblph_no, create_time, update_time';
        $ccbShopInfoService = new CcbShopInfoService();
        return $ccbShopInfoService->getInfoByBizUid($app_id, $bizUid, $field);

    }

    /**
     * 退款接口
     * @return array
     * User: cwh  DateTime:2021/9/18 17:45
     */
    public function refundOrder(){
        $data = input();
        $appUid         = $this->appUid;    //默认1000
        $bizUid         = $data['bizUid']; //付款人biz_uid
        $bizUserId      = $appUid . $bizUid;

        $param['bizOrderNo']    = $data['bizOrderNo'] ?? "RE".date("YmdHis");   //退款订单号
        $param['oriBizOrderNo'] = $data['oriBizOrderNo'] ?? "";   //原代收或者充值订单号
        $param['bizUserId']     = $bizUserId;
        $param['refundType']    = $data['refundType'] ?? '1';
        $param["amount"]        = $data['amount']  ?? 0;
//        $param["backUrl"]       = $this->domain .'AllinPay/notifyRefund';
        $param["bizBackUrl"]    = $data['bizBackUrl']  ?? '';
        $param['extendInfo']    = $appUid;
        $param['subOrdrList']   = $data['subOrdrList'];

        $orderEntryInfo = model('OrderEntry')->infoByBizOrderNo($appUid, $param['oriBizOrderNo']);
        $param['py_trn_no']     = $orderEntryInfo['py_trn_no'] ?? '';
        if(empty($orderEntryInfo)){
            return errorReturn('该订单不存在!');
        }

        if($orderEntryInfo['order_entry_status'] == OrderEntry::WAIT_PAY){
            return errorReturn('订单未支付');
        }

//        if($orderEntryInfo['amount'] != $data['amount']){
//            return errorReturn("暂时不支持部分退款");
//        }

        $orderRefundService = new OrderRefundService();

        //获取订单可退金额
        $can_return_amount = $orderRefundService->canReturnAmountByOrder($orderEntryInfo['biz_order_no'],$orderEntryInfo['amount']);
        if($can_return_amount < $param['amount']){
            return errorReturn('退款金额大于可退金额!');
        }

        $checkResult = $orderRefundService->checkFormatRefundOrderList($param['subOrdrList'],$param['amount'],$orderEntryInfo['sub_ordr_id']);
        if(!$checkResult['result']){
            return apiOut($checkResult);
        }
        $param['subOrdrList'] = $checkResult['data'];
        return $orderRefundService->ccbRefund($appUid, $param,$this->config);
    }

    /**
     * 财务确认退款成功或者失败
     * @return array
     * User: cwh  DateTime:2021/11/19 9:23
     */
    function financeRefundStatus(){
        $data = input();
        $appUid         = $this->appUid;    //默认1000

        if($data['check_status'] == 1){
            //财务确认成功
            $status = OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE'];
        }else if($data['check_status'] ==2){
            $status = OrderRefund::PAY_STATUS['ALL_IN_PAY_ERROR'];
        }
        if(empty($status)){
            return errorReturn('确认退款异常!');
        }
        $paramEntry['app_uid'] = $appUid;
        $paramEntry['order_entry_no'] = $data['pay_order_sn'];
        $paramEntry['amount'] = bcmul($data['refund_amount'],100);
        $res = (new OrderProcessService())->updateCcbEntryOrderStatus($paramEntry,$status,2);
        return $res;

    }
}