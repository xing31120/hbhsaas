<?php
namespace app\common\model\reconciliation;

class FinanceSummary extends FinanceBase {
    public $mcName = 'finance_summary_';
    public $selectTime = 6;
    public $mcTimeOut = 6;

    //表头对应意义             分账交易流水号    ，商家编号      ，商家名称，       分账金额，分账失败原因，分账状态代码，分账日期
    const SUMMARY_HEADER = ['clrg_txnsrlno','mkt_mrch_id','mkt_mrch_nm','to_clrgamt','rsp_inf','clrg_stcd','clrg_dt'];
    const SUMMARY_HEADER_RULES = ['string','string','string','amount','string','int','string'];
    /**
     * 通过文件读取数据
     * User: cwh  DateTime:2021/9/23 15:08
     * 市场编号-日期-sum.txt
     */
    function addSummaryByFile($dir_name,$file_name){
//        $dir_name = '.././runtime/txt/';
//        $file_name = "41060860804004-20181022-sum.txt";
        $dir_name = $dir_name."/";
        $file_name = $file_name."-sum.txt";
        $file_data = $this->readFile($dir_name,$file_name);
        //组装数据
        array_shift($file_data);
        if(empty($file_data)){
            write_logs('导入分账汇总失败,文件内容没有数据：'.$file_name, 'ccb/import','ccb');
            return errorReturn("导入失败");
        }
        $insertData = $this->formData($file_data,self::SUMMARY_HEADER,self::SUMMARY_HEADER_RULES);
        $res = $this->insertAll($insertData);
        if(!$res){
            write_logs('导入分账汇总失败：'.$file_name, 'ccb/import','ccb');
            return errorReturn("导入失败");
        }
        write_logs('导入分账汇总成功：'.$file_name, 'ccb/import','ccb');
        return successReturn(["msg"=>"导入成功"]);
    }

}
