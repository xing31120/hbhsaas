<?php
/**
 * CcbShopInfo.php
 * Create by PhpStorm
 * @author: sx
 * @date: 2021/9/18 11:24
 */

namespace app\common\service\Ccb;


use think\Db;

class CcbShopInfoService{

    //6217001930038760865
    //增加商家, $param['Udf_Id'] 的格式为 app_uid@biz_uid 例如    1010@1_0
    //  biz_uid在saas中是shopUid_0 例如 1_0 是aaa的商家
    // 例如正式环境  4000@829_0   4000是美鑫测试的app_uid  829是saas商家的shopUid  "@"和"_0"是固定内容
    function addShop($param){

        if(
            !isset($param['Mkt_Mrch_Id']) || !isset($param['Mkt_Mrch_Nm']) || !isset($param['Udf_Id']) ||
            empty($param['Mkt_Mrch_Id']) || empty($param['Mkt_Mrch_Nm']) || empty($param['Udf_Id'])
        )
            return errorReturn('市场商家错误');

        $array = explode('@', $param['Udf_Id']);
        if(count($array) != 2 || empty($array)){
            return errorReturn('Udf_Id参数错误');
        }
        $appUid = $array[0];
        $bizUid = $array[1];

        $res = $this->getInfoByMrchId($param['Mkt_Mrch_Id']);
        if($res['result']){
            return errorReturn('商家已存在!');
        }

        $appInfo = model('UsersApp')->info($appUid);
//pj($appInfo);
        $data['app_uid']        = $appUid;
        $data['app_id']         = $appInfo['app_id'];
        $data['biz_uid']        = $bizUid;
        $data['mkt_mrch_id']    = $param['Mkt_Mrch_Id'];
        $data['mkt_mrch_nm']    = $param['Mkt_Mrch_Nm'] ?? '';
        $data['pos_no']         = $param['Pos_No'] ?? '';
        $data['mrch_crdt_tp']   = $param['Mrch_Crdt_Tp'] ?? '';
        $data['mrch_crdt_no']   = $param['Mrch_Crdt_No'] ?? '';
        $data['mrch_cnter_cd']  = $param['Mrch_Cnter_Cd'] ?? '';
        $data['crdt_tp']        = $param['Crdt_Tp'] ?? '';
        $data['ctcpsn_nm']      = $param['Ctcpsn_Nm'] ?? '';
        $data['crdt_no']        = $param['Crdt_No'] ?? '';
        $data['mblph_no']       = $param['Mblph_No'] ?? '';

        Db::startTrans();
//pj($data);
        $res = model('CcbShopInfo')->saveData($data);
        if(!$res){
            Db::rollback();;
            return errorReturn('新增商家失败');
        }
        is_object($res) && $res = $res->toArray();


        //增加规则
        $ccbAccountingRulesService = new CcbAccountingRulesService();
        $checkList = $ccbAccountingRulesService->getListByBizUid($appInfo['app_id'], $bizUid);
        if($checkList['result']){
            Db::commit();
            return errorReturn(['msg' => '商家规则已存在!', 'code' => \app\common\tools\SysEnums::CcbAccountingRulesAlreadyExist]);
        }

        $dataRule = array_merge($param, $res);
        $dataRule['Mnt_Type'] = '00';
        $dataRule['Mkt_Mrch_Id'] = $dataRule['mkt_mrch_id'];
        //查找基础规则
        $resRuleList = $ccbAccountingRulesService->getListByBizUid('1635334877','1_0');
//pj($resRuleList);
        $ruleList = $resRuleList['data'];
        $insertData = [];
        foreach ($ruleList as $ruleRow){
            unset($ruleRow['id']);
            $ruleRow['create_time'] = $ruleRow['update_time'] = time();

            $temp = $ruleRow;
            $temp['app_uid'] = $dataRule['app_uid'];
            $temp['app_id'] = $dataRule['app_id'];
            if($ruleRow['seq_no'] != 1){
                $temp['mkt_mrch_id'] = $dataRule['mkt_mrch_id'];
                $temp['mkt_nm'] = $dataRule['Mkt_Mrch_Nm'];
            }
            $temp['biz_uid'] = $dataRule['biz_uid'];

            $insertData[] = $temp;
        }
//pj($ruleList);
        $resRule = model('CcbAccountingRules')->insertAll($insertData);
        if(!$resRule){
            Db::rollback();;
            return errorReturn('新增规则失败');
        }
        Db::commit();
//pj($resRule);
        return successReturn(['data' => $res]);
    }

    function editShop($param){
        if( !isset($param['Udf_Id']) || empty($param['Udf_Id'])) return errorReturn('缺少Udf_Id参数');
        if( !isset($param['Mkt_Mrch_Id']) || empty($param['Mkt_Mrch_Id'])) return errorReturn('缺少Mkt_Mrch_Id参数');
        $res = $this->getInfoByMrchId($param['Mkt_Mrch_Id']);
        if(!$res['result']){
            return errorReturn('查找商家失败');
        }

        $data = $res['data'];
        unset($data['create_time']);
        unset($data['update_time']);
        unset($data['delete_time']);
        $data['mkt_mrch_id']    = $param['Mkt_Mrch_Id'];
        isset($param['Mkt_Mrch_Nm'])    &&  $data['mkt_mrch_nm']    = $param['Mkt_Mrch_Nm'];
        isset($param['Pos_No'])         &&  $data['pos_no']         = $param['Pos_No'];
        isset($param['Mrch_Crdt_Tp'])   &&  $data['mrch_crdt_tp']   = $param['Mrch_Crdt_Tp'];
        isset($param['Mrch_Crdt_No'])   &&  $data['mrch_crdt_no']   = $param['Mrch_Crdt_No'];
        isset($param['Mrch_Cnter_Cd'])  &&  $data['mrch_cnter_cd']  = $param['Mrch_Cnter_Cd'];
        isset($param['Crdt_Tp'])        &&  $data['crdt_tp']        = $param['Crdt_Tp'];
        isset($param['Ctcpsn_Nm'])      &&  $data['ctcpsn_nm']      = $param['Ctcpsn_Nm'];
        isset($param['Crdt_No'])        &&  $data['crdt_no']        = $param['Crdt_No'];
        isset($param['Mblph_No'])       &&  $data['mblph_no']       = $param['Mblph_No'];

        $res = model('CcbShopInfo')->saveData($data);
        //清除缓存
        $mcKey = model("CcbShopInfo")->mcName . '_mkt_mrch_id_' . $param['Mkt_Mrch_Id'];
        cache($mcKey, NULL);

        if(!$res){
            return errorReturn('修改商家失败');
        }
        is_object($res) && $res = $res->toArray();
        return successReturn(['data' => $res]);
    }

    function delShop($param){
        if( !isset($param['Udf_Id']) || empty($param['Udf_Id'])) return errorReturn('缺少Udf_Id参数');
        if( !isset($param['Mkt_Mrch_Id']) || empty($param['Mkt_Mrch_Id'])) return errorReturn('缺少Mkt_Mrch_Id参数');

        $res = $this->getInfoByMrchId($param['Mkt_Mrch_Id']);
        if(!$res['result']){
            return errorReturn('查找商家失败');
        }

        $id = $res['data']['id'] ?? 0;

        if(!$id){
            return errorReturn('删除失败');
        }

        $res2 = model('CcbShopInfo')->del($id);
        if(!$res2){
            return errorReturn('删除商家失败');
        }

        return successReturn(['data' => $res['data']]);
    }

    function getInfoByMrchId($mkt_mrch_id){
        $where[] = ['mkt_mrch_id', '=', $mkt_mrch_id];
        $info = null;
        if (model("CcbShopInfo")->mcOpen ) {
            $mcKey = model("CcbShopInfo")->mcName . '_mkt_mrch_id_' . $mkt_mrch_id;
            $info = cache($mcKey);
            if ($info === false) {
                $info = model("CcbShopInfo")->where($where)->findOrEmpty()->toArray();
                $time = model("CcbShopInfo")->mcTimeOut > 0 ? model("CcbShopInfo")->mcTimeOut : 0;

                !empty($info) && cache($mcKey, $info, $time);
            }
        } else {
            $info = model("CcbShopInfo")->where($where)->findOrEmpty()->toArray();
        }
        if(empty($info)){
            return errorReturn('查找失败!');
        }

        return successReturn(['data' => $info]);
    }

    function getInfoByBizUid($appId, $bizUid, $field){
        $where[] = ['app_id', '=', $appId];
        $where[] = ['biz_uid', '=', $bizUid];
//pj($where);
//        $info = null;
//        if (model("CcbShopInfo")->mcOpen ) {
//            $mcKey = model("CcbShopInfo")->mcName . '_app_uid_' . $appId. 'biz_uid' . $bizUid;
//            $rs = cache($mcKey);
//            if ($rs === false) {
//                $info = model("CcbShopInfo")->field($field)->where($where)->findOrEmpty();
//                $time = model("CcbShopInfo")->mcTimeOut > 0 ? model("CcbShopInfo")->mcTimeOut : 0;
//                cache($mcKey, $info, $time);
//            }
//        } else {
            $info = model("CcbShopInfo")->field($field)->where($where)->findOrEmpty();
//        }
        if(empty($info)){
            return errorReturn('查找失败!');
        }

        return successReturn(['data' => $info]);
    }


    function getAll($field){
        $where[] = ['delete_time', '=', 0];
//pj($where);
        $list = null;
        if (model("CcbShopInfo")->mcOpen ) {
            $mcKey = model("CcbShopInfo")->mcName . '_All_1';
            $list = cache($mcKey);
            if ($list === false) {
                $list = model("CcbShopInfo")->field($field)->select()->toArray();
                $time = model("CcbShopInfo")->mcTimeOut > 0 ? model("CcbShopInfo")->mcTimeOut : 0;
                cache($mcKey, $list, $time);
            }
        } else {
            $list = model("CcbShopInfo")->field($field)->select()->toArray();
        }
        if(empty($list)){
            return errorReturn('查找失败!');
        }

        return successReturn(['data' => $list]);
    }
}