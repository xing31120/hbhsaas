<?php
namespace app\shop\controller;

use app\common\model\HbhClassTime;
use app\common\model\HbhClassTimeDetail;
use app\common\model\HbhClassTimePlan;
use app\common\model\HbhCourse;
use app\common\model\HbhCourseCat;
use app\common\model\HbhSjLog;
use app\common\model\HbhUsers;
use think\Db;
use think\facade\Lang;

class Sjlog extends Base {

    //数据列表
    public function dataList(){
        $lang = input('lang', 'zh-cn');
//pj($this->lang);
        $this->title = '操作日志';
        $cat_list = (new HbhCourseCat())->getAllCourseCatList();
        $this->assign('cat_list', $cat_list);
        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['admin_id']) && !empty($param['admin_id'])) {
            $where[] = ['admin_id', '=', $param['admin_id']];
        }
        if (isset($param['create_time']) && $param['create_time'] != ''){
            $time = explode('~', $param['create_time']);
            $where[] = ['create_time', 'between', [strtotime($time[0]), strtotime($time[1]) + 3600 * 24]];
        }

        return $where;
    }

    //异步获取列表数据
    public function ajaxList(){
        $data = input();
        $limit = 10;//每页显示的数量

        $op['where'] = $this->setWhere($data);
        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['limit'] = $data['limit'] ?? $limit;
        $op['order'] = 'id desc';
        $list = (new HbhSjLog())->getList($op);

        $text_key = 'text';
        if($this->lang != 'zh-cn') $text_key = 'text_en';
//pj($this->lang);
        foreach ($list['list'] as &$item) {
            $item['type_text'] =HbhSjLog::type_text[$item['type']][$text_key];
            $item['controller_text'] =HbhSjLog::controller_text[$item['controller']][$text_key];
            $item['before_data'] = json_decode($item['before_data'], 1);
            $item['after_data'] = json_decode($item['after_data'], 1);

            $item['operation_content'] = '';
            if($item['type'] == HbhSjLog::type_del){
                $item['operation_content'] = $item['type_text']. $item['controller_text'].' '. $item['before_data']['id'];
            }
            if($item['type'] == HbhSjLog::type_add){
                $item['operation_content'] = $item['type_text']. $item['controller_text'].' ';
                foreach ($item['after_data'] as $k => $val) {
                    $item['operation_content'] = $item['operation_content']." {$k} : {$val}";
                }
            }

            if($item['type'] == HbhSjLog::type_update){
                $item['operation_content'] = $item['type_text']. $item['controller_text'].' ';
                foreach ($item['after_data'] as $k => $after_val) {
                    $before_val = $item['before_data'][$k];

                    $item['operation_content'] = $item['operation_content']." {$k} : {$before_val} => {$after_val}";
                }
            }

//            var_dump();exit();

        }

        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }

    public function form()
    {
        $data = input();
        $id = $data['id'] ?? 0;
        $info = (new HbhClassTimePlan())->info($id);
        $course_id = $info['course_id'] ?? 0;
        $uid = $info['uid'] ?? 0;
        $start_time = $info['start_time'] ?? 0;
        $end_time = $info['end_time'] ?? 0;

        $course_cat_id = $info['course_cat_id'] ?? 0;
        $op['where'][] = ['category_id', '=', $course_cat_id];
        $op['doPage'] = false;
        $course_list = (new HbhCourse())->getList($op)['list'];
//pj($course_list);
        $course_name_list = array_column($course_list, null, 'id');

        $cat_list = (new HbhCourseCat())->getAllCourseCatList();

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_name_list = array_column($teacher_list, 'name', 'id');

        $student_list = (new HbhUsers())->getAllStudentList();
//        $student_name_list = array_column($student_list, 'name', 'id');

        $course_name = $course_name_list[$course_id]['name'] ?? '';
        $course_description = $course_name_list[$course_id]['description'] ?? '';
        $info['course_name'] = $course_name."({$course_description})";
        $info['teacher_name'] = $teacher_name_list[$uid] ?? '';
        $info['class_time'] = $start_time . '-' . $end_time;
//pj([$info, $course_name_list]);
        $classTimeList = model('HbhClassTime')->getAllList();
        $classTimeList = array_column($classTimeList, null, 'id');

        $info['user_list'] = [];
        $custom_uid_str = $info['custom_uid_str'] ?? '';
        $custom_uid_arr = explode(',', $custom_uid_str);
        if(!empty($custom_uid_str)){
            $op_user['where'][] = ['id', 'in', $custom_uid_arr];
            $op_user['doPage'] = false;
            $info['user_list'] = (new HbhUsers())->getList($op_user)['list'];
        }
//pj($info['user_list']);

        $this->assign('classTimeList', $classTimeList);
        $this->assign('course_name_list', $course_name_list);
        $this->assign('teacher_name_list', $teacher_name_list);
        $this->assign('student_list', $student_list);
        $this->assign('cat_list', $cat_list);
        $this->assign('info', $info);
        return $this->fetch();
    }



}
