<?php
namespace app\common\model\reconciliation;

use app\common\amqp\BizProducer;
use app\common\model\MqErrorLog;
use think\Db;

class FinanceSummaryDetail extends FinanceBase {
    public $mcName = 'finance_summary_';
    public $selectTime = 6;
    public $mcTimeOut = 6;

    const RECONCILIATION_OK = 1;//已对账

    const NO_PUSH = 0;//未推送
    const YES_PUSH = 1;//已推送

    //表头对应意义               订单号        序号    ，支付流水号      ，收款账号，    收款方商家编号，        收款方商家名称，        分账金额，分账状态代码  分账日期   分摊手续费    子订单编号     原始分账金额
    const SUMMARY_HEADER = ['main_ordr_no','sn','py_ordr_no','rcvpymt_accno','rcvprt_mkt_mrch_id','rcvprt_mkt_mrch_nm','clrgamt','clrg_stcd','clrg_dt','hdcg_amt','sub_ordr_no','shld_subacc_amt'];
    const SUMMARY_HEADER_RULES = ['string','string','string','string','string','string','amount','int','string','amount','string','amount'];
    /**
     * 通过文件读取数据
     * User: cwh  DateTime:2021/9/23 15:08
     * 市场编号-日期-det.txt
     */
    function addSummaryDetailByFile($dir_name,$file_name){
//        $dir_name = '.././runtime/file/package/44200001617128-20211101/';
//        $file_name = "44200001617128-20211101-det.txt";
        $dir_name = $dir_name."/";
        $file_name = $file_name."-det.txt";
        $file_data = $this->readFile($dir_name,$file_name);
        //组装数据
        array_shift($file_data);
        if(empty($file_data)){
            write_logs('导入分账明细失败,文件内容没有数据：'.$file_name, 'ccb/import','ccb');
            return errorReturn("导入失败");
        }
        $insertData = $this->formData($file_data,self::SUMMARY_HEADER,self::SUMMARY_HEADER_RULES);
        foreach($insertData as $v){
            $info = $this->where("sn",$v['sn'])->findOrEmpty()->toArray();
            if(empty($info)){
                $res = $this->insert($v);
                if(!$res){
                    write_logs('导入分账明细失败：'.$file_name, 'ccb/import','ccb');
                    return errorReturn("导入失败");
                }
            }
        }
//        $res = $this->insertAll($insertData);
//        if(!$res){
//            write_logs('导入分账明细失败：'.$file_name, 'ccb/import','ccb');
//            return errorReturn("导入失败");
//        }
        write_logs('导入分账明细成功：'.$file_name, 'ccb/import','ccb');
        return successReturn(["msg"=>"导入成功"]);
    }

    /**
     * 自动对账
     * User: cwh  DateTime:2021/10/15 17:15
     */
    public function autoReconciliation(){
        $where[] = ['is_reconciliation','=',0];
        $list = $this->where($where)->limit(10)->select()->toArray();
        $main_ordr_nos = array_column($list,'main_ordr_no');
        $model = model('OrderProcess');
        $orderProcessList = $model->getOrderListByMainOrdrNoList($main_ordr_nos);
//        pr($orderProcessList);
        $updataData = [];
        $orderProcessUpdateData = [];
        $all_ccb_reconciliation_amount = [];
        foreach($list as $v){
            $key = $v['main_ordr_no']."_".$v['rcvprt_mkt_mrch_id'];
            $orderProcessInfo = $orderProcessList[$key] ?? [];
            if(empty($orderProcessInfo)) continue;
            $status = 1;
            if($orderProcessInfo['remain_amount'] != $v['clrgamt']){
                $status = 2;
            }
            if(isset($all_ccb_reconciliation_amount[$orderProcessInfo['order_entry_no']])){
                $all_ccb_reconciliation_amount[$orderProcessInfo['order_entry_no']]['ccb_reconciliation_amount'] += $v['clrgamt'];
                $all_ccb_reconciliation_amount[$orderProcessInfo['order_entry_no']]['fee'] += $v['hdcg_amt'];
            }else{
                $all_ccb_reconciliation_amount[$orderProcessInfo['order_entry_no']]['app_uid'] = $orderProcessInfo['app_uid'];
                $all_ccb_reconciliation_amount[$orderProcessInfo['order_entry_no']]['ccb_reconciliation_amount'] = $v['clrgamt'];
                $all_ccb_reconciliation_amount[$orderProcessInfo['order_entry_no']]['fee'] = $v['hdcg_amt'];
                $all_ccb_reconciliation_amount[$orderProcessInfo['order_entry_no']]['status'] = $status;
                $all_ccb_reconciliation_amount[$orderProcessInfo['order_entry_no']]['is_reconciliation'] = self::RECONCILIATION_OK;
            }
            $updataData[] = [
                'id'=>$v['id'],
                'is_reconciliation'=>self::RECONCILIATION_OK
            ];
            $orderProcessUpdateData[] = [
                'id'=>$orderProcessInfo['id'],
                'is_reconciliation'=>self::RECONCILIATION_OK,
                'reconciliation_status'=>$status,
                'finance_summary_detail_id'=>$v['id'],
                'ccb_reconciliation_amount'=>$v['clrgamt'],//分账金额
                'fee'=>$v['hdcg_amt'],//交易手续费
                'clrg_stcd'=>$v['clrg_stcd'],
                'split_time'=>strtotime((new FinanceReconciliation())->formatData($v['clrg_dt'])),
            ];
        }
        if(!empty($updataData)){
            $this->updateAll($updataData);
            $model->updateAll($orderProcessInfo['app_uid'],$orderProcessUpdateData);
        }
        foreach($all_ccb_reconciliation_amount as $k=>$v){
            $order_info = model('OrderEntry')->infoByBizOrderNo($v['app_uid'],$k);
            $updataOrderData['ccb_reconciliation_amount'] = $v['ccb_reconciliation_amount'];
            $updataOrderData['reconciliation_status'] = $v['status'];
            $updataOrderData['is_reconciliation'] = $v['is_reconciliation'];
            $updataOrderData['fee'] = $v['fee'];
//            $result = model('OrderEntry')->updateById($order_info['id'],$v['app_uid'],$updataOrderData);
            $result = model('OrderEntry')->where("id",$order_info['id'])->update($updataOrderData);
        }


        $resultData['msg'] ='对账完成';
        return successReturn($resultData);
    }


}
