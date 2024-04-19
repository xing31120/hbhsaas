<?php
/**
 * CcbShopInfo.php
 * Create by PhpStorm
 * @author: sx
 * @date: 2021/9/18 11:24
 */

namespace app\common\service\Ccb;


use AllInPay\Log\Log;
use app\common\amqp\BizProducer;
use app\common\model\MqErrorLog;
use app\common\service\AllInPay\AllInPayClient;
use think\facade\Config;

class CcbAccountingRulesService{

    /*
     {"Svc_Rsp_St":"00","Expdt":"20991231","Memblist":[{"Clrg_Mtdcd":"5","Clrg_Pctg":"5","Seq_No":"1"},{"Clrg_Mtdcd":"5","Clrg_Pctg":"95","Seq_No":"2"}],
     "Sub_Acc_Cyc":"1","Clrg_Rule_Id":"F410608608002941866","Efdt":"20240917","Clrg_Dlay_Dys":"1",
     "Sign_Inf":"xxx","Clrg_Mtdcd":"2","Clrg_Mode":"2",
     "Mkt_Nm":"\u516c\u53f8\u96f6\u4e5d\u4e5d\u4e5d\u4e5d\u4e5d\u4e5d\u4e5d\u4e5d\u4e5d","Per_Sub_Acc":"0","Mnt_Type":"00","Mkt_Id":"41060860800294"}
     */
    //增加规则
    function addRule($param){

        if( !isset($param['Clrg_Rule_Id']) || empty($param['Clrg_Rule_Id']) )
            return errorReturn('市场商家错误');

        $infoRes = $this->getInfoByRuleId($param['Clrg_Rule_Id']);
        if($infoRes['result']){
            return errorReturn('规则已存在');
        }

        $childList = $param['Memblist'] ?? '';
        if(empty($childList)){
            return errorReturn('参数错误');
        }
        // 以下这4个字段在 新增商家时要带上
        $data['app_uid']        = $param['app_uid'] ?? 0;
        $data['app_id']         = $param['app_id'] ?? 0;
        $data['biz_uid']        = $param['biz_uid'] ?? '';
        $data['mkt_mrch_id']    = $param['Mkt_Mrch_Id'] ?? '';

        $data['mkt_id']         = $param['Mkt_Id'] ?? '';
        $data['mkt_nm']         = $param['Mkt_Nm'] ?? '';
        $data['clrg_rule_id']   = $param['Clrg_Rule_Id'] ?? '';
        $data['rule_nm']        = $param['Rule_Nm'] ?? '';
        $data['rule_dsc']       = $param['Rule_Dsc'] ?? '';
        $data['sub_acc_cyc']    = $param['Sub_Acc_Cyc'] ?? '';
        $data['clrg_dlay_dys']  = $param['Clrg_Dlay_Dys'] ?? '';
        $data['clrg_mode']      = $param['Clrg_Mode'] ?? '';
        $data['efdt']           = $param['Efdt'] ?? '';
        $data['expdt']          = $param['Expdt'] ?? '';
        $data['is_gather']      = 0;

        $insertData = [];
        foreach ($childList as $val){

            $temp = $data;
            $temp['seq_no'] = $val['Seq_No'];
            $temp['clrg_mtdcd'] = $val['Clrg_Mtdcd'];   //4按金额 5按比例
            $temp['clrg_pctg'] = $val['Clrg_Pctg'];     //比例()

            $insertData[] = $temp;
        }

        $res = model('CcbAccountingRules')->insertAll($insertData);
        if(!$res){
            return errorReturn('新增规则失败');
        }
        return $this->getInfoByRuleId($param['Clrg_Rule_Id']);
    }

    function editRule($param){
        if( !isset($param['Clrg_Rule_Id']) || empty($param['Clrg_Rule_Id']) )
            return errorReturn('市场商家错误');

        $infoRes = $this->getInfoByRuleId($param['Clrg_Rule_Id']);
        if(!$infoRes['result']){
            return errorReturn('规则不存在');
        }
        $data = $infoRes['data'][0] ?? '';
        $childList = $param['Memblist'] ?? '';
        if(empty($childList) || empty($data)){
            return errorReturn('参数错误');
        }
        unset($data['create_time']);
        unset($data['update_time']);
        unset($data['delete_time']);
        unset($data['id']);

        isset($param['Mkt_Id'])         && $data['mkt_id']       = $param['Mkt_Id'];
        isset($param['Mkt_Nm'])         && $data['mkt_nm']       = $param['Mkt_Nm'];
        isset($param['Rule_Nm'])        && $data['rule_nm']      = $param['Rule_Nm'];
        isset($param['Rule_Dsc'])       && $data['rule_dsc']     = $param['Rule_Dsc'];
        isset($param['Sub_Acc_Cyc'])    && $data['sub_acc_cyc']  = $param['Sub_Acc_Cyc'];
        isset($param['Clrg_Dlay_Dys'])  && $data['clrg_dlay_dys']= $param['Clrg_Dlay_Dys'];
        isset($param['Clrg_Mode'])      && $data['clrg_mode']    = $param['Clrg_Mode'];
        isset($param['Efdt'])           && $data['efdt']         = $param['Efdt'];
        isset($param['Expdt'])          && $data['expdt']        = $param['Expdt'];


        $where[] = ['clrg_rule_id', '=', $param['Clrg_Rule_Id']];
        foreach ($childList as $val){
            $baseWhere = $where;
            $baseWhere[] = ['seq_no', '=', $val['Seq_No']];

            $data['seq_no'] = $val['Seq_No'];
            $data['clrg_mtdcd'] = $val['Clrg_Mtdcd'];   //4按金额 5按比例
            $data['clrg_pctg'] = $val['Clrg_Pctg'];     //比例()
            $res = model('CcbAccountingRules')->update($data, $baseWhere);
            if(!$res){
                return errorReturn('修改失败');
            }
        }

        return $infoRes;
    }

    function getInfoByRuleId($clrgRuleId){
        $model = model("CcbAccountingRules");
        $where[] = ['clrg_rule_id', '=', $clrgRuleId];

        $list = $model->where($where)->select()->toArray();
        if(empty($list)){
            return errorReturn('查找失败!');
        }

        return successReturn(['data' => $list]);
    }

    function getListByBizUid($appId, $bizUid = '1_0', $payMethodKey = null, $field = '*'){
//        $bizUid = '1_0';
        $model = model("CcbAccountingRules");
        if($payMethodKey !== null){
            $mktId = $this->getMktIdByConfig($payMethodKey);
            $mktIdArr = [$mktId];
        }else{
            $mktIdArr = ['44200001617128','44200001617129','44200001617130'];
            $mktId = json_encode($mktIdArr);
        }


        $where[] = ['app_id', '=', $appId];
        $where[] = ['biz_uid', '=', $bizUid];
        $where[] = ['mkt_id', 'in', $mktIdArr];
//pj($where, 0);
        $list = null;
        if ($model->mcOpen ) {
            $mcKey = $model->mcName . '_app_uid3_' . $appId. '_biz_uid_' . $bizUid. '_mkt_id_' . $mktId;
            $list = cache($mcKey);
            if (empty($list)) {
                $list = $model->field($field)->where($where)->select()->toArray();
                $time = $model->mcTimeOut > 0 ? $model->mcTimeOut : 0;
                !empty($list) && cache($mcKey, $list, $time);
            }
        } else {
            $list = $model->field($field)->where($where)->select()->toArray();
        }
        if(empty($list)){
            return errorReturn('查询规则失败!');
        }

        return successReturn(['data' => $list]);
    }

    public function getMktIdByConfig($payMethodKey){
        $is_test = Config::get('amqp.is_test');
        if($is_test){
            $this->config = Config::pull('ccbtest');
        }else{
            $this->config = Config::pull('ccb');
        }

        $mktIdArray = $this->config['mktIdBypayMethod'];
        return $mktIdArray[$payMethodKey] ?? 0;

    }

    public function ruleFun( $data ){
//pj($data);
        $actionType = $data['Mnt_Type'] ?? '';
        $res = [];
        $returnData['Svc_Rsp_St'] = '00';
        if($actionType == '00'){
            $res = (new CcbAccountingRulesService())->addRule($data);
        }elseif($actionType == '01'){
            $res = (new CcbAccountingRulesService())->editRule($data);
        }
//pj($res);
        if(!isset($res['result']) || !$res['result'] || !isset($res['data'][0]) || !$res['data'][0]) {
            $returnData['Svc_Rsp_St'] = '01';
            $logIns = (new AllInPayClient())->getLogIns();
            $logIns->LogMessage("[finRuleCallback]",Log::INFO,json_encode($res));
            return json($returnData);
        }
        //推送saas系统
        $info = $res['data'][0];
        $producer = new BizProducer();
        $arrMsg = [
            'serviceClass' => 'CcbOrderService',
            'fun' =>  'rulesCallBack',   //fun 必填, 值是 Service 的方法名
            'data' => $res['data'],
            'actionType' => $actionType,
            'appUid' => $info['app_uid'],
        ];
        $result = $producer->publish($arrMsg);
        if($result !== null){
            $returnData['Svc_Rsp_St'] = '01';
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($arrMsg);
        }

        return $returnData;
    }
}