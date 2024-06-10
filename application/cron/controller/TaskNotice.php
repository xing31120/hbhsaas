<?php

namespace app\cron\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhUsers;


// 通知家长上课
class TaskNotice extends Base{

    function classNotice(){
        $book_course_model = new HbhBookCourse();
        $today = date("Y-m-d");
        $day = input('day', $today);
//$day = '2024-05-27';

        $op['where'][] = ['notice_status', '=', HbhBookCourse::notice_status_false];
        $op['where'][] = ['day', '=', $day];
        $op['doPage'] = false;
        $list = $book_course_model->getList($op)['list'];
        $uid_arr = array_column($list, 'custom_uid');
        $course_id_arr = array_column($list, 'course_id');

        $op_user['where'][] = ['id', 'in', $uid_arr];
        $op_user['doPage'] = false;
        $userList = (new HbhUsers())->getList($op_user)['list'];

        $course_list = (new HbhCourse())->whereIn('id', $course_id_arr)->select()->toArray();
        $course_name_list = array_column($course_list, 'name', 'id');

        $mobile_arr = [];
        foreach ($userList as $user) {
//            $phone_code = $user['phone_code'];
//            $phone = $user['phone'];
            $mobile_arr[$user['id']] = $user;
        }

        $template_param_arr = [];
        foreach ($list as $book_course) {
            $user = $mobile_arr[$book_course['custom_uid']] ?? '';
            $res = (new HbhUsers())->checkPhone($user['phone']);
            if(!$res['result']){
                continue;
            }
            $template_param['start'] = $book_course['start_time'] ?? '';
            $template_param['end'] = $book_course['end_time'] ?? '';
            $template_param['course'] = $course_name_list[$book_course['course_id']] ?? '';

            $template_param_arr[] = $template_param;
        }

        $return_msg = SendSmsClassNotice($mobile_arr, $template_param_arr);
        if($return_msg['result']){
            $up = $book_course_model->where($op['where'])->update(['notice_status' => HbhBookCourse::notice_status_true]);
            if(!$up) return errorReturn('update error');
        }
//pj($return_msg);
        return $return_msg;




    }
}
