<?php

namespace app\cron\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhUsers;


// 新注册学员提醒老师
class TaskTeacherNotice extends Base{


    function test(){
        $mobile_arr[] = '971585965288';
//        pj($mobile_arr);
        $template_param_arr[] = [
            "course" =>'aaaaa',
            "start" =>'13:30',
            "end" =>'14:30',
            'day' => '2024-06-25',
            'student' => 'stevenSong',
        ];
        $return_msg = SendSmsTeacherNotice($mobile_arr, $template_param_arr);
        pj($return_msg);
    }


    //
    function teacherNotice(){
        //You have a new student reservation for the “${course} Class”
        // at “ ${start} to ${end}.” on ${day}. The student’s name is ${student}.
        // Please pay attention and prepare the class well!
        $book_course_model = new HbhBookCourse();
        $today = date("Y-m-d");
        $day = input('day', $today);
//$day = '2024-05-27';

//        $op['where'][] = ['notice_status', '=', HbhBookCourse::notice_status_false];
        $op['where'][] = ['day', '=', $day];
        $op['doPage'] = false;
        $list = $book_course_model->getList($op)['list'];
        $uid_arr = array_column($list, 'custom_uid');
        $course_id_arr = array_column($list, 'course_id');

        //新注册用户
        $op_user['where'][] = ['id', 'in', $uid_arr];
        $op_user['where'][] = ['level_id', '=', HbhUsers::level_id_reg];
        $op_user['doPage'] = false;
        $reg_user_list = (new HbhUsers())->getList($op_user)['list'];
        $reg_user_list = array_column($reg_user_list, null, 'id');
//pj(strlen(',BJPH2024067271,BJPH2024067270,BJPH2024067269,BJMAS2024067268,BJPH2024067267,BJPH2024067265,BJPH2024067262,BJPH2024067258,BJPH2024067257,BJPH2024067246,BJPH2024067208,BJPH2024067207,BJPH2024067206,BJPH2024067205,BJPH2024067204,BJPH2024067200,BJPH2024067194,BJPH2024067109,BJPH2024067103'));
//pj([$list,$uid_arr, $reg_user_list]);
        // 教师用户数据
        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_list = array_column($teacher_list, null, 'id');

        $course_list = (new HbhCourse())->whereIn('id', $course_id_arr)->select()->toArray();
        $course_name_list = array_column($course_list, 'name', 'id');

        $uid_array = [];
//        foreach ($reg_user_list as $user) {
//            $res = (new HbhUsers())->checkPhone($user['phone']);
//            if(!$res['result']){
//                continue;
//            }
//            $uid_array[$user['id']] = $user;
//        }

        $temp_mobile = $mobile_arr = [];
        $template_param_arr = [];
        foreach ($list as $book_course) {
            $teacher = $teacher_list[$book_course['teacher_uid']] ?? '';
            $reg_user = $reg_user_list[$book_course['custom_uid']] ?? '';
            if(empty($teacher)){
                continue;
            }
            if(empty($reg_user)){
                continue;
            }
            $res = (new HbhUsers())->checkPhone($teacher['phone']);
            if(!$res['result']){
                continue;
            }
            if(in_array($teacher['phone'], $temp_mobile)){
                continue;
            }
            $mobile_arr[] = $teacher['phone_code'] . $teacher['phone'];
            $temp_mobile[] = $teacher['phone'];
//$template_param['id'] = $book_course['id'] ?? '';
//$template_param['custom_uid'] = $book_course['custom_uid'] ?? '';
//$template_param['phone'] = $user['phone'];
            $template_param['course'] = $course_name_list[$book_course['course_id']] ?? '';
            $template_param['start'] = $book_course['start_time'] ?? '';
            $template_param['end'] = $book_course['end_time'] ?? '';
            $template_param['day'] = $day;
            $template_param['student'] = $reg_user_list[$book_course['custom_uid']]['name'] ?? '';

            $template_param_arr[] = $template_param;
        }
//pj([$mobile_arr, $template_param_arr, $temp_mobile]);
//pj([$mobile_arr, $template_param_arr]);
        $return_msg = SendSmsTeacherNotice($mobile_arr, $template_param_arr);
//        if($return_msg['result']){
//            $up = $book_course_model->where($op['where'])->update(['notice_status' => HbhBookCourse::notice_status_true]);
//            if(!$up) return errorReturn('update error');
//        }
//pj($return_msg);
        return $return_msg;




    }
}
