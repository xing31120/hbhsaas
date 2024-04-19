<?php
namespace app\common\model;
use app\common\model\basic\Common;
use think\model\concern\SoftDelete;

class Transfer extends Common {

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    public $mcName = 'transfer_';
    public $selectTime = 6;
    public $mcTimeOut = 6;

    function infoByBizOrderNo($appUid, $bizOrderNO){
        if (empty($bizOrderNO)) {
            return false;
        }

        $this->submeter($appUid);
        $where[] = ['biz_transfer_no','=',$bizOrderNO];

        //缓存开启并且命中
//        $mcKey = $this->mcName . '_' . $bizOrderNO;
//        if($this->mcOpen && cache($mcKey) !== false){
//            return cache($mcKey)->toArray();
//        }
        //查询失败直接返回false
        $rs = $this->where($where)->find();
        if(empty($rs)){
            return false;
        }
        //设置缓存
//        if($this->mcOpen){
//            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
//            cache($mcKey, $rs, $time);
//        }
        return $rs->toArray();

    }


    /**
     * 增加转账记录
     * @param $appUid
     * @param $param
     * @return array
     * User: cwh  DateTime:2022/2/17 10:22
     */
    function addTransfer($appUid, $param){
        if(empty($param['bizTransferNo']))   return errorReturn('订单编号错误');
        if(empty($param['targetBizUserId'])) return errorReturn('用户编号错误');

        $info = $this->infoByBizOrderNo($appUid, $param['bizTransferNo']);
        if($info){
            return errorReturn('订单已经存在!');
        }
        $data['biz_uid'] = str_replace($appUid,"",$param['targetBizUserId']);
        $userInfo = model('Users')->infoByBizUid($appUid, $data['biz_uid']);

        $data['uid']           = $userInfo['id'];
        $data['app_uid']       = $appUid;
        $data['biz_uid']       = $userInfo['biz_uid'];
        $data['biz_transfer_no']  = $param['bizTransferNo'] ?? '';
        $data['source_account_set_no'] = $param['sourceAccountSetNo'];
        $data['target_account_set_no']  = $param['targetAccountSetNo'] ?? '';
        $data['amount']             = $param['amount'] ?? 0;
        if(isset($param['extend_info']) ){
            $data['extend_info']    = is_array($param['extend_info']) ? json_encode($param['extend_info']) : $param['extend_info'];
        }

        $res = $this->saveData($appUid, $data);

        if(!$res){
            return errorReturn('新增转账订单失败');
        }

        return successReturn(['data' => $res]);
    }
}
