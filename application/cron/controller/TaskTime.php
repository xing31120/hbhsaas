<?php

namespace app\cron\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhClassTimeDetail;
use app\common\model\HbhClassTimePlan;
use app\common\model\HbhCourse;
use app\common\model\HbhTeacherClassTime;
use app\common\tools\Redis;
use think\Db;
use think\facade\Log;

class TaskTime extends Base{

    /**
     * Notes: 根据时间戳查询课时计划, 生成时间戳当天的所有课时
     * @param int $time 生成时间戳
     * @return array
     * User: songX
     * Date: 2024/4/3 16:32:45
     */
    function createClassDetail($time)
    {
        if(empty($time)){
            return errorReturn('error time');
        }
        $time_before = config('extend.time_before');
        $time = str_replace(",", "", $time);
        $after_time = $time + 86400 * $time_before;
        $this_week = date("l", $after_time); //英文星期几
        $year = date("Y", $after_time);
        $day = date("Y-m-d", $after_time);
        $which_week = date("W", $after_time);
//pj($this_week);
        $op['where'][] = ['week','=', $this_week];
        $op['doPage'] = false;
        $op['order'] = 'class_time_id desc';
        // 待生成的 教师关联课时表
        $planList = (new HbhClassTimePlan())->getList($op, false)['list'];
        if(empty($planList)){
            return successReturn('empty HbhTeacherClassTime '. $day);
        }

        //课程列表
        $course_id = array_column($planList, 'course_id');
        $course_list = (new HbhCourse())->whereIn('id', $course_id)->select()->toArray();
        $course_list = array_column($course_list, null, 'id');
        // 查询已经生成的 每天的课时表
        $op['where'][] = ['day','=', $day];
        $op['doPage'] = false;
        $classTimeDetailList = (new HbhClassTimeDetail())->getList($op, false)['list'];
//        $classTimeDetailList = array_column($classTimeDetailList, null, 'teacher_class_time_id');
        $detailList = [];
        foreach ($classTimeDetailList as $item) {
            $key ="{$item['uid']}_{$item['course_id']}_{$item['class_time_id']}";
            $detailList[$key] = $item;
        }
        // 课程时间配置表
        $classTimeList = model('HbhClassTime')->getAllList();
        $classTimeList = array_column($classTimeList, null, 'id');

        $class_time_detail = [];
        foreach ($planList as $item) {
            $key ="{$item['uid']}_{$item['course_id']}_{$item['class_time_id']}";
            $detail_check = $detailList[$key] ?? [];
            if(!empty($detail_check)){
                continue;
            }

            $temp = [];
            $temp['shop_id']                = $item['shop_id'];
            $temp['uid']                    = $item['uid'];
            $temp['course_id']              = $item['course_id'];
            $temp['course_cat_id']          = $course_list[$item['course_id']]['category_id'];
            $temp['week']                   = $item['week'];
            $temp['class_time_id']          = $item['class_time_id'];
//            $temp['teacher_class_time_id']  = $item['id'];
            $temp['start_time']             = $classTimeList[$item['class_time_id']]['start_time'] ?? '';
            $temp['end_time']               = $classTimeList[$item['class_time_id']]['end_time'] ?? '';
            $temp['year']                   = $year;
            $temp['day']                    = $day;
            $temp['which_week']             = $which_week;
            $temp['plan_id']                = $item['id'] ?? 0;
            $temp['create_time']            = $time;
            $temp['update_time']            = $time;
            $class_time_detail[] = $temp;
        }

        if(empty($class_time_detail)){
            return successReturn('suc');
        }
        $rs = (new HbhClassTimeDetail())->insertAll($class_time_detail);

        return successReturn(['planList' =>$planList,'day' =>$day, 'data' => [$this_week, $rs, date('Y-m-d H:s',$time)]]);
    }

    function autoCreateBookCourse($plan_list, $day){
        $plan_id_arr = array_column($plan_list, 'id');
        $plan_list = array_column($plan_list, null, 'id');
        $op['where'][] = ['plan_id', 'in', $plan_id_arr];
        $op['where'][] = ['day', '=', $day];
        $op['doPage'] = false;
        $detail_list = (new HbhClassTimeDetail())->getList($op)['list'];
        if(empty($detail_list)){
            return successReturn('empty detail list '. $day);
        }
//pj($detail_list);
        $res = [];
        $book_course_model = new HbhBookCourse();
        foreach ($detail_list as $detail) {
            $plan_custom_uid_str = $plan_list[$detail['plan_id']]['custom_uid_str'] ?? '';
            if(empty($plan_custom_uid_str)){
                continue;
            }
            $custom_uid_arr = explode(',', $plan_custom_uid_str);
            $rrr = $book_course_model->bookCourseAll($custom_uid_arr, $detail['id'], $detail['shop_id']);

            $res[] = $rrr;
        }

        return successReturn($res);
    }

    function addClassDetail(){
        $time = input('time', time());
        Db::startTrans();
        // 根据时间戳查询课时计划, 生成时间戳当天的所有课时
        $res = $this->createClassDetail($time);

        $res2 = $this->autoCreateBookCourse($res['planList'], $res['day']);
        Db::commit();
        pj([$res, $res2]);
    }

    function addClassDetail_bak(){
        $time_before = config('extend.time_before');
        $time = input('time', time());
        $time = str_replace(",", "", $time);
//pj($time);
        $after_time = $time + 86400 * $time_before;
        $this_week = date("l", $after_time); //英文星期几
        $year = date("Y", $after_time);
        $day = date("Y-m-d", $after_time);
        $which_week = date("W", $after_time);

        $op['where'][] = ['week','=', $this_week];
        $op['doPage'] = false;
        $op['order'] = 'class_time_id desc';
        // 待生成的 教师关联课时表
        $teacherClassTimeList = (new HbhTeacherClassTime())->getList($op, false)['list'];

        //课程分类
        $course_id = array_column($teacherClassTimeList, 'course_id');
        $course_list = (new HbhCourse())->whereIn('id', $course_id)->select()->toArray();
        $course_list = array_column($course_list, null, 'id');

        // 查询已经生成的 每天的课时表
        $op['where'][] = ['day','=', $day];
        $op['doPage'] = false;
        $classTimeDetailList = (new HbhClassTimeDetail())->getList($op, false)['list'];
        $classTimeDetailList = array_column($classTimeDetailList, null, 'teacher_class_time_id');
//pj($classTimeDetailList);
        // 课程时间配置表
        $classTimeList = model('HbhClassTime')->getAllList();
        $classTimeList = array_column($classTimeList, null, 'id');

        if(empty($teacherClassTimeList)){
            return successReturn('empty HbhTeacherClassTime '. $this_week);
        }

        $class_time_detail = [];
        foreach ($teacherClassTimeList as $item) {
            $detail_check = $classTimeDetailList[$item['id']] ?? [];
            if(!empty($detail_check)){
                continue;
            }

            $temp = [];
            $temp['shop_id']                = $item['shop_id'];
            $temp['uid']                    = $item['uid'];
            $temp['course_id']              = $item['course_id'];
            $temp['course_cat_id']          = $course_list[$item['course_id']]['category_id'];
            $temp['week']                   = $item['week'];
            $temp['class_time_id']          = $item['class_time_id'];
            $temp['teacher_class_time_id']  = $item['id'];
            $temp['start_time']             = $classTimeList[$item['class_time_id']]['start_time'] ?? '';
            $temp['end_time']               = $classTimeList[$item['class_time_id']]['end_time'] ?? '';
            $temp['year']                   = $year;
            $temp['day']                    = $day;
            $temp['which_week']             = $which_week;
            $temp['create_time']            = $time;
            $temp['update_time']            = $time;
            $class_time_detail[] = $temp;
        }

        if(empty($class_time_detail)){
            return successReturn('suc');
        }
        $rs = (new HbhClassTimeDetail())->insertAll($class_time_detail);


pj([$this_week, $rs, $teacherClassTimeList, date('Y-m-d H:s',$time)]);
    }

    function test(){
        $res = (new HbhBookCourse())->bookCourse(120,6);
//pj($res);
    }
}
