<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;
use think\Db;
use think\facade\Lang;

class HbhBookCourse extends SingleSubData {
    public $mcName = 'hbh_book_course_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    //状态 0:待签到 10:签到完成 40: 取消预约
    const status_wait = 0;
    const status_cancel = 40;
    const status_end = 10;

    //确认状态 0:待确认 10:确认完成 40: 取消预约
    const status_confirm_wait = 0;
    const status_confirm_end = 10;
    const status_confirm_cancel = 40;

    //是否已扣费用 0: 未扣, 10:已经扣除
    const is_pay_false = 0;
    const is_pay_true = 10;

    //是否已扣费用 0: 未扣, 10:已经扣除
    const is_unlimited_number_false = 0;
    const is_unlimited_number_true = 1;

    static function getStatusText($status){
        $text = '';
        if($status == self::status_wait){
            $text = Lang::get('Booked');
        }elseif ($status == self::status_cancel){
            $text = Lang::get('ConfirmCancel');
        }elseif ($status == self::status_end){
            $text = Lang::get('SignedIn');
        }
        return $text;
    }

    static function getStatusConfirmText($status){
        $text = '';
        if($status == self::status_confirm_wait){
            $text = Lang::get('ConfirmWait');
        }elseif ($status == self::status_confirm_cancel){
            $text = Lang::get('ConfirmCancel');
        }elseif ($status == self::status_confirm_end){
            $text = Lang::get('ConfirmBooked');
        }
        return $text;
    }

    /**
     * Notes: 删除当前用户的所有$detail_ids预约记录
     * @param $custom_uid
     * @param array $detail_ids
     * @param int $shop_id
     * @return array
     * User: songX
     * Date: 2024/2/28 15:15:13
     */
    function delByUidDetail($custom_uid, $detail_ids, $shop_id = 1){
        if(empty($detail_ids)){
            return successReturn('success');
        }
        if(empty($custom_uid ) || empty($shop_id )){
            return errorReturn("{$custom_uid}del参数错误");
        }

        $where = [
            'status' => self::status_wait,
            'custom_uid' => $custom_uid,
            'shop_id' => $shop_id,
        ];
        $res = $this->where($where)->whereIn('detail_id', $detail_ids)->delete();
        if ( $res !== false ) {
            return successReturn('delete success');
        } else {
            return errorReturn('delete error');
        }
    }

    /**
     * 删除某天当前用户的所有预约记录
     * @param $custom_uid
     * @param $day
     * @param $shop_id
     * @return array
     * @throws \Exception
     */
    function delByUidDay($custom_uid, $day, $shop_id = 1){
        if(empty($day )){
            return successReturn('success');
        }
        if(empty($custom_uid ) || empty($shop_id )  ){
            return errorReturn("{$custom_uid}del参数错误{$day}");
        }

        $where = [
            'status' => self::status_wait,
            'custom_uid' => $custom_uid,
            'shop_id' => $shop_id,
            'day' => $day,
        ];
        $res = $this->where($where)->delete();
        return successReturn('delete success');
    }

    /**
     * 单条预约增加
     * @param $custom_uid
     * @param $detail_id
     * @param $shop_id
     * @return array
     */
    function bookCourse($custom_uid, $detail_id, $shop_id = 1){
        if(empty($custom_uid ) || empty($detail_id ) || empty($shop_id )  ){
            return errorReturn("{$custom_uid}参数错误{$detail_id}");
        }
        $check = $this->where([
            'detail_id' => $detail_id,
            'custom_uid' => $custom_uid,
            'status' => self::status_wait,
        ])->find();
        if ($check) {
            return successReturn('已经预约了该课时');
        }

        $detail_info = (new HbhClassTimeDetail())->info($detail_id);
        if(empty($detail_info)){
            return errorReturn('课时错误');
        }
        $userInfo = (new HbhUsers())->info($custom_uid);
        if(empty($userInfo)){
            return errorReturn(Lang::get('ParameterError').'-uid');
        }
        $data['shop_id']        = $shop_id;
        $data['custom_uid']     = $custom_uid;
        $data['teacher_uid']    = $detail_info['uid'] ?? 0;
        $data['course_id']      = $detail_info['course_id'] ?? 0;
        $data['detail_id']      = $detail_info['id'] ?? 0;
        $data['start_time']     = $detail_info['start_time'] ?? '';
        $data['end_time']       = $detail_info['end_time'] ?? '';
        $data['day']            = $detail_info['day'] ?? '';
        $data['year']           = $detail_info['year'] ?? '';
        $data['which_week']     = $detail_info['which_week'] ?? '';
        $data['is_unlimited_number'] = $userInfo['is_unlimited_number'];

        $data['id'] = $this->insert($data);

        return  successReturn(['data' => $data]);

    }

    /**
     * 根据课时, 会员批量预约
     * @param $custom_uid_arr
     * @param $detail_id
     * @param $shop_id
     * @return array
     */
    function bookCourseAll($custom_uid_arr, $detail_id, $shop_id = 1){
        if(empty($custom_uid_arr ) || empty($detail_id ) || empty($shop_id )  ){
            return errorReturn("参数错误-bookCourseAll");
        }

        $op['where'][] = ['custom_uid', 'in', $custom_uid_arr];
        $op['where'][] = ['detail_id', '=', $detail_id];
        $op['doPage'] = false;
        $book_course_list = $this->getList($op);
        $book_course_list = array_column($book_course_list, null, 'custom_uid');

        $detail_info = (new HbhClassTimeDetail())->info($detail_id);
        if(empty($detail_info)){
            return errorReturn('课时错误');
        }

        $op_user['where'][] = ['id', 'in', $custom_uid_arr];
        $op_user['doPage'] = false;
        $user_list = (new HbhUsers())->getList($op_user);
        $user_list = array_column($user_list['list'], null, 'id');

        $time = time();
        $insert_data = [];
        foreach ($custom_uid_arr as $custom_uid) {
            if(isset($book_course_list[$custom_uid])){
                continue;
            }
            if(empty($custom_uid)){
                continue;
            }
            $userInfo = $user_list[$custom_uid] ?? [];

            $data['shop_id']        = $shop_id;
            $data['custom_uid']     = $custom_uid;
            $data['teacher_uid']    = $detail_info['uid'] ?? 0;
            $data['course_id']      = $detail_info['course_id'] ?? 0;
            $data['detail_id']      = $detail_info['id'] ?? 0;
            $data['start_time']     = $detail_info['start_time'] ?? '';
            $data['end_time']       = $detail_info['end_time'] ?? '';
            $data['day']            = $detail_info['day'] ?? '';
            $data['year']           = $detail_info['year'] ?? '';
            $data['which_week']     = $detail_info['which_week'] ?? '';
            $data['is_unlimited_number'] = $userInfo['is_unlimited_number'] ?? 0;
            $data['create_time']    = $time;
            $insert_data[] = $data;
        }


        $res = $this->insertAll($insert_data);

        return  successReturn(['data' => $insert_data, 'res' => $res]);

    }


    /**
     *  支付扣费根据预约id
     * @param $id
     * @param $is_pay
     * @return array
     */
    function payByBoosCourseId($id, $is_pay = self::is_pay_false){
        $info = $this->info($id);
        $uid = $info['custom_uid'] ?? 0;
        $info_course = (new HbhCourse())->info($info['course_id']);
        $userInfo = (new HbhUsers())->info($uid);
        if(empty($userInfo)){
            return errorReturn(Lang::get('UserNotFound'));
        }
//        if($is_pay == HbhBookCourse::is_pay_false) {
//            return successReturn(Lang::get('OperateSuccess'.'aa')); //. json_encode($course_data)
//        }
        //已经付费了, 不扣除额度
        if($info['is_pay'] == HbhBookCourse::is_pay_true) {
            return successReturn(Lang::get('OperateSuccess').'bb'); //. json_encode($course_data)
        }
        //更新预约数据
        $update['is_unlimited_number'] = $userInfo['is_unlimited_number'];
        $update['update_time'] = time();
        $update['is_pay'] = $is_pay;
        $bool = (new HbhBookCourse())->updateById($id, $update);
        if(false === $bool){
            return errorReturn(Lang::get('OperateFailed'));
        }
        // 如果是未签到的用户, 要扣除余额
        $pay_fee = $info_course['course_fees'] ?? 0;
        $res = (new HbhUsers())->reduceWallet($uid, $pay_fee);
        if(!$res['result']){
            return errorReturn($res);
        }
        return successReturn(Lang::get('OperateSuccess').'ccc');
    }

}
