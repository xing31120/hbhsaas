<?php

namespace app\cron\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhUsers;


// 通知家长上课
class TaskCustomerService extends Base{

    function classNotice(){
        $model = new HbhUsers();
        $today = date("Y-m-d");
        $today = input('today', $today);
        $after_config = 7 ;
        $num_config = 3 ;
        $time = strtotime($today. ' 00:00:00');
        $after_day = date("Y-m-d", $time + 86400 * $after_config);
        $content_input = '> ';

//$day = '2024-05-27';
//pj([$today, $after_day]);
        $op['where'][] = ['expiry_date', 'between', [$today, $after_day]];
        $op['where'][] = ['is_unlimited_number', '=', HbhUsers::is_unlimited_number_true];
        $op['doPage'] = false;
        $list = $model->getList($op)['list'];
        $list_time = array_column($list, null, 'id');
//        $content_time = '> 到期会员：';
        foreach ($list_time as $item) {
            $content_input .= "到期会员：{$item['name']} \r\n";
        }

        $op_num['where'][] = ['expiry_date', '>', $today];
        $op_num['where'][] = ['balance', '<=', $num_config];
        $op_num['where'][] = ['balance', '>', 0];
        $op_num['where'][] = ['is_unlimited_number', '=', HbhUsers::is_unlimited_number_false];
        $op_num['where'][] = ['level_id', '=', HbhUsers::level_id_user];
        $op_num['doPage'] = false;
        $list = $model->getList($op_num)['list'];
        $list_num = array_column($list, null, 'id');
//        $content_input .= '\r\n';
        foreach ($list_num as $item) {
            $content_input .= "课时提醒：{$item['name']} \r\n";
        }
//pj($content_input);


        $res = $model->sendExpiryDate($content_input);

pj([$list_time, $list_num]);



    }
}
