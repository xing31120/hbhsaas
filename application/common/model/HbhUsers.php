<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;
use app\common\service\workSendMessage\WorkSendMessageService;
use think\facade\Lang;

class HbhUsers extends SingleSubData {
    public $mcName = 'hbh_users_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;
    public $status = [1=> '正常', 4=>'已锁定/禁用'];

    const Status_Disabled = 4;  //用户禁用
    const Status_Enable = 1;
    const is_unlimited_number_false = 0;    // 0:有限数量,
    const is_unlimited_number_true = 1;    // 1:无限数量,

    const level_id_reg = 1;     //用户等级  1: 新注册用户
    const level_id_user = 2;    //用户等级  2: 等级会员

    //是否短信通知 10:通知, 0:不通知
    const sms_notice_false = 0;    // 0:不通知
    const sms_notice_true = 10;    // 10:通知,

    /**
     * 注册, 修改手机的手机号验证
     * @param $phone
     * @return array
     */
    function checkPhone($phone){
//$phone  = '585330846';

        // 去掉空格
        $search = " ";
        $replace = "";
        $phone = str_replace($search, $replace, $phone);

        $firstChar = substr($phone, 0, 1);
        if($firstChar != 5){
            return errorReturn(Lang::get('PhoneStart5'));
        }

        $pattern = '/^5\d{8}$/';
        if (preg_match($pattern, $phone, $matches)) {
            return successReturn(['data' => $phone]);
        }
        return errorReturn(Lang::get('Number9NumberStartWith5'));

    }

    function info($id,  $field = ''){
        $info = parent::info($id, $field);
        unset($info['pay_password']);
        return $info;
    }

    function getAllTeacherList(){
        $op['where'][] = ['role','=','teacher'];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = model('HbhUsers')->getList($op);
        return $list['list'];
    }

    function getAllStudentList(){
        $op['where'][] = ['role','=','student'];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'id desc';
        $list = model('HbhUsers')->getList($op);
        return $list['list'];
    }

    /**
     * 检测用户 是否能够预约课程, 禁用? 过期? 次数?
     * @param int $uid
     * @param int $num
     * @return array|void
     */
    function checkResidueQuantity(int $uid, int $num = 0){
        if(empty($uid)){
            return errorReturn(Lang::get('UserError'));
        }
        $userInfo = (new HbhUsers())->where('id', $uid)->findOrEmpty()->toArray();
        if(empty($userInfo)){
            return errorReturn(Lang::get('UserNotFound'));
        }
        // 会员是否禁用
        if($userInfo['status'] == self::Status_Disabled){
            return errorReturn(Lang::get('UserDisabled'));
        }
        // 会员是否过期
        $expiry_time = strtotime($userInfo['expiry_date'].' 00:00:00');
        if(time() > $expiry_time){
            return errorReturn(Lang::get('MembershipExpiration'));
        }

        // 非无限卡用户, 要验证次数
        if($userInfo['is_unlimited_number'] == self::is_unlimited_number_false){
            $residue_quantity = $userInfo['residue_quantity'] ?? -99;
//            if($num > $residue_quantity){
            if($num - $residue_quantity < -5){
                return errorReturn(Lang::get('InsufficientRemainingClassHours'));
            }
        }

        return successReturn('check success');

    }


    /**
     * 扣除用户额度
     * @param int $uid
     * @param int $num
     * @return void
     */
    function reduceWallet(int $uid, int $num = 0){
        $check_res = $this->checkResidueQuantity($uid, $num);
        if(!$check_res['result']){
            return $check_res;
        }
        // 无限卡用户, 直接通过验证
        $userInfo = (new HbhUsers())->where('id', $uid)->findOrEmpty()->toArray();
        if($userInfo['is_unlimited_number'] == self::is_unlimited_number_true){
            return successReturn('reduce success');
        }
        // 扣除额度,  后期改成扣除钱包
        $userInfo['residue_quantity'] = $userInfo['residue_quantity'] - $num;
        unset($userInfo['create_time']);
        unset($userInfo['update_time']);
        $res = $this->saveData($userInfo);
        if(!$res){
            return errorReturn(Lang::get('FailedToDeductUserBalance'));
        }
        return successReturn(['data' => $res, 'msg' => 'success']);

    }

    public function sendExpiryDate($content_input){
        $time = date("Y-m-d H:i:s");
        $content = <<<EOF
### <font color="warning">会员到期+课时通知，请查看</font>
> 时间：{$time}
{$content_input}
EOF;
        (new WorkSendMessageService(env('SAASWORK.SAAS_EXPIRY_DATE_PUSH', '')))->sendMarkDown($content);
    }

}
