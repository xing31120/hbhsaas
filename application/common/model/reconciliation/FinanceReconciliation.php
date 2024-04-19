<?php
namespace app\common\model\reconciliation;

class FinanceReconciliation extends FinanceBase {
    public $mcName = 'fin_finance_reconciliation_';
    public $selectTime = 6;
    public $mcTimeOut = 6;

    //表头对应意义                订单号            市场商户编号    ，市场商户名称  ，交易日期，支付流水号，订单类型代码， 交易金额，手续费    收款状态代码        交易类型        发卡行           卡种         卡号       交易时间
    const SUMMARY_HEADER = ['main_ordr_no','mkt_mrch_id','mkt_mrch_nm','txn_dt','ordr_no','ordr_tpcd','txnamt','hdcg','rcncl_rslt_stcd','txn_tp_dsc','lssubnk_dsc','py_crdtp_dsc','pyr_accno','txn_tm'];
    const SUMMARY_HEADER_RULES = ['string','string','string','string','string','int','amount','amount','string','string','string','string','string','string'];
    /**
     * 通过文件读取数据
     * User: cwh  DateTime:2021/9/23 15:08
     * 市场编号-日期-det.txt
     */
    function addReconciliationByFile($dir_name,$file_name){
//        $dir_name = '.././runtime/txt/';
//        $file_name = "41060860804004-20181022-chk.txt";
        $dir_name = $dir_name."/";
        $file_name = $file_name."-chk.txt";
        $file_data = $this->readFile($dir_name,$file_name);
        //组装数据
        array_shift($file_data);
        if(empty($file_data)){
            write_logs('导入对账明细失败,文件内容没有数据：'.$file_name, 'ccb/import','ccb');
            return errorReturn("导入失败");
        }
        $insertData = $this->formData($file_data,self::SUMMARY_HEADER,self::SUMMARY_HEADER_RULES);
        $res = $this->insertAll($insertData);
        if(!$res){
            write_logs('导入对账明细失败：'.$file_name, 'ccb/import','ccb');
            return errorReturn("导入失败");
        }
        write_logs('导入对账明细成功：'.$file_name, 'ccb/import','ccb');
        return successReturn(["msg"=>"导入成功"]);
    }

}
