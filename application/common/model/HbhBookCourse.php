<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;
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

    static function getStatusText($status){
        $text = '';
        if($status == self::status_wait){
            $text = Lang::get('Booked');
        }elseif ($status == self::status_cancel){
            $text = Lang::get('Cancel');
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
     * Notes:
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
     * 单条预约
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

        $time = time();
        $insert_data = [];
        foreach ($custom_uid_arr as $custom_uid) {
            if(isset($book_course_list[$custom_uid])){
                continue;
            }
            if(empty($custom_uid)){
                continue;
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
            $data['create_time']    = $time;
            $insert_data[] = $data;
        }


        $res = $this->insertAll($insert_data);

        return  successReturn(['data' => $insert_data, 'res' => $res]);

    }
}
