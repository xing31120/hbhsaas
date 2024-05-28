<?php

namespace app\cron\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhUsers;

class TaskNotice extends Base{

    function classNotice(){
        $book_course_model = new HbhBookCourse();
        $day = date("Y-m-d");

        $op['where'][] = ['notice_status', '=', HbhBookCourse::notice_status_false];
        $op['where'][] = ['day', '=', $day];
        $op['doPage'] = false;
        $list = $book_course_model->getList($op)['list'];
        $uid_arr = array_column($list, 'custom_uid');

        $op_user['where'][] = ['id', 'in', $uid_arr];
        $op_user['doPage'] = false;
        $userList = (new HbhUsers())->getList($op_user)['list'];

        foreach ($userList as $user) {
            $phone_code = $user['phone_code'];
            $phone = $user['phone'];

            $mobile = $phone_code . $phone;
            $type = 2;  //2: 验证码登录
            $return_msg = SendSmsCode($mobile,$type);
        }
pj([$list,$userList]);




    }
}
