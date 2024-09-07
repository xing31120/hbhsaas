<?php

namespace app\cron\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhUsers;


// 通知家长上课
class TaskTeacherNotice extends Base{


    function test(){
        $mobile_arr = ['971585965288'];
        $template_param_arr[] = [
            "start" =>'13:30',
            "end" =>'14:30',
            'courge' => 'mathematics level 4',
        ];
        $return_msg = SendSmsClassNotice($mobile_arr, $template_param_arr);
        pj($return_msg);
    }
    function teacherNotice(){
        $user_model = new HbhUsers();
        $book_course_model = new HbhBookCourse();
        $today = date("Y-m-d");
        $day = input('day', $today);
//$day = '2024-05-27';
//pj($day);
        // 查今天的所有预约
        $op['where'][] = ['notice_status', '=', HbhBookCourse::notice_status_false];
        $op['where'][] = ['day', '=', $day];
        $op['doPage'] = false;
        $list = $book_course_model->getList($op)['list'];
        $uid_arr = array_column($list, 'custom_uid');
        $course_id_arr = array_column($list, 'course_id');
        $book_course_arr =  array_column($list, null, 'id');

        // 今天所有预约里面符合 新注册用户+有课时的用户
        $op_user['where'][] = ['id', 'in', $uid_arr];
        $op_user['where'][] = ['level_id', '=', HbhUsers::level_id_reg];
//        $op_user['where'][] = ['sms_notice', '=', HbhUsers::sms_notice_true];
        $op_user['where'][] = ['expiry_date', '>', $today];
        $op_user['where'][] = ['balance', '>', 0];
        $op_user['doPage'] = false;
        $student_list = $user_model->getList($op_user)['list'];
        $student_uid_arr = array_column($student_list, 'id');

        $teacher_list = $user_model->getAllTeacherList();
        $teacher_list = array_column($teacher_list, null, 'id');

        $course_list = (new HbhCourse())->whereIn('id', $course_id_arr)->select()->toArray();
        $course_name_list = array_column($course_list, 'name', 'id');

        if(empty($student_uid_arr)){
            return successReturn('empty  studentList');
        }

        $mobile_arr = $uid_arr = [];
//        foreach ($studentList as $user) {
//            $res = $user_model->checkPhone($user['phone']);
//            if(!$res['result']){
//                continue;
//            }
//            $uid_arr[$user['id']] = $user;
//
//        }

        $temp_mobile = [];
        $template_param_arr = [];
        foreach ($list as $book_course) {
            if(!in_array($book_course['custom_uid'], $student_uid_arr)){
                continue;
            }
//            $user = $uid_arr[$book_course['custom_uid']] ?? '';
//            if(empty($user)){
//                continue;
//            }
            $user = $teacher_list[$book_course['teacher_uid']] ?? '';

            $res = $user_model->checkPhone($user['phone']);
            if(!$res['result']){
                continue;
            }
            if(in_array($user['phone'], $temp_mobile)){
                continue;
            }
            $mobile_arr[] = $user['phone_code'] . $user['phone'];
            $temp_mobile[] = $user['phone'];
//$template_param['id'] = $book_course['id'] ?? '';
//$template_param['custom_uid'] = $book_course['custom_uid'] ?? '';
//$template_param['phone'] = $user['phone'];
            $template_param['start'] = $book_course['start_time'] ?? '';
            $template_param['end'] = $book_course['end_time'] ?? '';
            $template_param['course'] = $course_name_list[$book_course['course_id']] ?? '';

            $template_param_arr[] = $template_param;
        }
//pj([$mobile_arr, $template_param_arr, $temp_mobile]);
//pj([$mobile_arr, $template_param_arr]);
        $return_msg = SendSmsClassNotice($mobile_arr, $template_param_arr);
//pj($return_msg);
        if($return_msg['result']){
            $up = $book_course_model->where($op['where'])->update(['notice_status' => HbhBookCourse::notice_status_true]);
            if(!$up) return errorReturn('update error222');
        }
//pj($return_msg);
        return $return_msg;




    }
}
