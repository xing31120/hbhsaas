<?php

namespace app\cron\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhUsers;

class TaskNotice extends Base{

    function classNotice(){
        $book_course_model = new HbhBookCourse();
        $day = date("Y-m-d");
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
//pj($course_name_list);
        $mobile_arr = [];
        foreach ($userList as $user) {
            $phone_code = $user['phone_code'];
            $phone = $user['phone'];
            $mobile_arr[] = $phone_code . $phone;
        }

        $template_param_arr = [];
        foreach ($list as $book_course) {
            $template_param['start'] = $book_course['start_time'] ?? '';
            $template_param['end'] = $book_course['end_time'] ?? '';
            $template_param['course'] = $course_name_list[$book_course['course_id']] ?? '';

            $template_param_arr[] = $template_param;
        }
pj([$list,$mobile_arr, $template_param_arr]);



        $return_msg = SendSmsClassNotice($mobile_arr, $template_param_arr);
//pj($return_msg);
        return apiOut($return_msg);




    }
}
