<?php
/**
 * 建行分账
 * Date: 2021/9/13 15:07
 */

namespace app\common\service\Ccb;

use app\common\tools\SysEnums;

class CcbOrderService
{
    private $ccbClient = [];
    public $result = [];
    public $error = [];
    
    public function __construct(){
        $this->ccbClient = new CcbClient();
    }
    
    //生成支付订单
    public function gatherPlaceorder($params,$app_uid,$pay_method){
        //请求接口
        $ccb = new CcbSdkService();
        $res = $ccb->gatherPlaceorder($params,$app_uid,$pay_method);
        if($res){
            if($res['Ordr_Stcd'] != '1'){
                return errorReturn('不是待支付订单',SysEnums::ApiParamMissing);
            }
            $data = [
                'ittparty_jrnl_no' => $res['Ittparty_Jrnl_No'],   //发起方流水号
                'main_ordr_no' => $res['Main_Ordr_No'],   //主订单编号
                'py_trn_no' => $res['Py_Trn_No'],         //支付流水号,惠市宝生成
                'prim_ordr_no' => $res['Prim_Ordr_No'],   //订单编号,惠市宝生成
                'ordr_gen_tm' => $res['Ordr_Gen_Tm'],     //订单生成时间
                'ordr_ovtm_tm' => $res['Ordr_Ovtm_Tm'],   //订单超时时间
                'sub_ordr_id' => $res['Orderlist'][0]['Sub_Ordr_Id'],   //子订单编号 惠市宝生成 退款时用
            ];
            switch ($params['pymdCd']){
                case '01':  //PC端
                case '03':  //移动端H5页面 (app)
                    $data['cshdk_url'] = $res['Cshdk_Url'];       //收银台URL
                    break;
                case '05':  //微信小程序（无收银台）
                    $data['rtn_par_data'] = $res['Rtn_Par_Data'];    //微信小程序JSON串形参数数据
                    break;
                case '07':  //聚合二维码（无收银台）
                    $data['pay_qr_code'] = $res['Pay_Qr_Code'];     //支付二维码串
                    break;
                case '06':  //对私网银（无收银台)
                case '08':  //龙支付（无收银台）
                    $data['pay_url'] = $res['Pay_Url'];         //龙支付url
                    break;
            }
            return successReturn(['data' => $data,/* 'resData' => $rs['data']->toArray()*/]);
        }else{
            //失败
            write_logs('下单失败：'.$ccb->error, 'ccb/unifiedorder','ccb');
            return errorReturn($ccb->error,SysEnums::ApiParamMissing);
        }
    }
    
    //刷新聚合二维码
    //pyTrnNo	 支付流水号
    public function mergePayUrl($params){
        //请求接口
        $ccb = new CcbSdkService();
        $res = $ccb->mergePayUrl($params);
        if($res){
            $data = [
                'main_ordr_no' => $res['Main_Ordr_No'],   //主订单编号
                'py_trn_no' => $res['Py_Trn_No'],
                'ordr_gen_tm' => $res['Ordr_Gen_Tm'],   //订单生成时间
                'ordr_ovtm_tm' => $res['Ordr_Ovtm_Tm'],   //订单超时时间
                'pay_qr_code' => urldecode($res['Pay_Qr_Code']),   //支付二维码串
            ];
            return successReturn(['data' => $data]);
        }else{
            //失败
            write_logs('刷新聚合二维码失败：'.$ccb->error, 'ccb/merge_pay_url','ccb');
            return errorReturn($ccb->error,SysEnums::ApiParamMissing);
        }
    }
    
    //查询支付结果
    public function gatherEnquireOrder($params){
        //请求接口
        $ccb = new CcbSdkService();
        $res = $ccb->gatherEnquireOrder($params);
        if($res){
            $data = [
                'main_ordr_no' => $res['Main_Ordr_No'], //主订单编号
                'py_trn_no' => $res['Py_Trn_No'],       //支付流水号
                'txnamt' => $res['Txnamt'],             //支付金额
                'ordr_gen_tm' => $res['Ordr_Gen_Tm'],   //订单生成时间
                'ordr_ovtm_tm' => $res['Ordr_Ovtm_Tm'], //订单超时时间
                'ordr_stcd' => $res['Ordr_Stcd'],       //订单状态代码.1待支付  2成功  3失败  4全部退款  5部分退款  6失效(超时未支付)
            ];
            return successReturn(['data' => $data]);
        }else{
            //失败
            write_logs('查询订单失败：'.$ccb->error, 'ccb/order_query','ccb');
            return errorReturn($ccb->error,SysEnums::ApiParamMissing);
        }
    }
    
    //分账规则查询接口
    public function accountingRulesList($params){
        //请求接口
        $ccb = new CcbSdkService();
        $res = $ccb->accountingRulesList($params, $params['appId'], $params['payMethodKey']);
        if($res){
            $data = [
                'curr_page' => $res['Curr_Page'],   //当前页
                'curr_rec' => $res['Curr_Rec'],     //每页记录数,最多20条
                'total_page' => $res['Total_Page'], //总页数
                'total_rec' => $res['Total_Rec'],   //总记录数
                'rulelist' => $res['Rulelist'],   //分账规则列表
            ];
            $data = $this->ccbClient->ucstrtolower($data);
            return successReturn(['data' => $data]);
        }else{
            //失败
            write_logs('查询规则失败：'.$ccb->error, 'ccb/accounting_rules','ccb');
            return errorReturn($ccb->error,SysEnums::ApiParamMissing);
        }
    }

    //退款订单接口
    public function refundOrder($params,$app_uid,$pay_method){
        //请求接口
        $ccb = new CcbSdkService();
        $res = $ccb->refundOrder($params,$app_uid,$pay_method);
        if($res){
            $data = [
                'cust_rfnd_trcno' => $res['Cust_Rfnd_Trcno'],   //客户方退款流水号
                'ittparty_jrnl_no' => $res['Ittparty_Jrnl_No'],     //发起方流水号
                'ittparty_tms' => $res['Ittparty_Tms'], //发起方时间戳
                'refund_rsp_inf' => $res['Refund_Rsp_Inf'], //退款响应信息
                'refund_rsp_st' => $res['Refund_Rsp_St'],   //退款响应状态
                'rfnd_trcno' => $res['Rfnd_Trcno'],   //退款流水号 惠市宝生成
                'rsp_inf' => $res['Rsp_Inf'],   //响应信息
                'sign_inf' => $res['Sign_Inf'],   //签名信息
                'svc_rsp_cd' => $res['Svc_Rsp_Cd'],   //服务响应码
                'svc_rsp_st' =>$res['Svc_Rsp_St'],//服务响应状态
            ];
            $data = $this->ccbClient->ucstrtolower($data);
            return successReturn(['data' => $data]);
        }else{
            //失败
            write_logs('申请退款失败：'.$ccb->error, 'ccb/request','ccb');
            return errorReturn($ccb->error,SysEnums::ApiParamMissing);
        }
    }

    //查询退款接口
    public function enquireRefundOrder($params,$app_uid,$pay_method){
        //请求接口
        $ccb = new CcbSdkService();
        $res = $ccb->enquireRefundOrder($params,$app_uid,$pay_method);
        pr($res);
        if($res){
            $data = [
                'cust_rfnd_trcno' => $res['Cust_Rfnd_Trcno'],   //客户方退款流水号
                'ittparty_jrnl_no' => $res['Ittparty_Jrnl_No'],     //发起方流水号
                'ittparty_tms' => $res['Ittparty_Tms'], //发起方时间戳
                'refund_rsp_st' => $res['Refund_Rsp_St'],   //退款响应状态
                'rfnd_trcno' => $res['Rfnd_Trcno'],   //退款流水号 惠市宝生成
                'sign_inf' => $res['Sign_Inf'],   //签名信息
                'rfnd_amt'  => $res['Rfnd_Amt'] ?? 0,
            ];
            $data = $this->ccbClient->ucstrtolower($data);
            return successReturn(['data' => $data]);
        }else{
            //失败
            write_logs('申请退款失败：'.$ccb->error, 'ccb/request','ccb');
            return errorReturn($ccb->error,SysEnums::ApiParamMissing);
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
    
}