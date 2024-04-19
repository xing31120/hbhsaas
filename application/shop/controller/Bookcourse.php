<?php
namespace app\shop\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhClassTimeDetail;
use app\common\model\HbhCourse;
use app\common\model\HbhCourseCat;
use app\common\model\HbhUsers;
use think\Db;
use think\facade\Lang;

//课程预约列表
class Bookcourse extends Base {

    //数据列表
    public function dataList(){
        $this->title = '课程预约列表';
        $course_list = (new HbhCourse())->getAllCourseList();
        $this->assign('course_list', $course_list);

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $this->assign('teacher_list', $teacher_list);

        $student_list = (new HbhUsers())->getAllStudentList();
        $this->assign('student_list', $student_list);


        return $this->fetch();
    }

    //按时间，老师，课程来筛选
    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['course_id']) && !empty($param['course_id'])) {
            $where[] = ['course_id', '=', $param['course_id']];
        }
        if (isset($param['custom_uid']) && !empty($param['custom_uid'])) {
            $where[] = ['custom_uid', '=', $param['custom_uid']];
        }
        if (isset($param['teacher_uid']) && !empty($param['teacher_uid'])) {
            $where[] = ['teacher_uid', '=', $param['teacher_uid']];
        }
        if (isset($param['create_time']) && $param['create_time'] != ''){
            $time = explode('~', $param['create_time']);
            $where[] = ['create_time', 'between', [strtotime($time[0]), strtotime($time[1]) + 3600 * 24]];
        }
        if (isset($param['day']) && $param['day'] != ''){
            $time_day = explode('~', $param['day']);
//            $day_end = date("Y-m-d", strtotime($time_day[1]) + 3600 * 24 - 1);
            $where[] = ['day', 'between', [trim($time_day[0]), trim($time_day[1])]];
        }
//pj($where);
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
        $list = (new HbhBookCourse())->getList($op);
        $custom_uid_arr = array_values(array_unique(array_column($list['list'], 'custom_uid')));

        $course_list = (new HbhCourse())->getAllCourseList();
        $course_name_list = array_column($course_list, null, 'id');

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_name_list = array_column($teacher_list, 'name', 'id');

        $op_user['doPage'] = false;
        $op_user['where'][] = ['id', 'in', $custom_uid_arr];
        $student_list = (new HbhUsers())->getList($op_user)['list'];
//        $student_list = (new HbhUsers())->getAllStudentList();
        $student_list = array_column($student_list, null, 'id');
//pj($student_list);
        foreach ($list['list'] as &$item) {
            $course_name = $course_name_list[$item['course_id']]['name'] ?? '';
            $course_description = $course_name_list[$item['course_id']]['description'] ?? '';
            $item['course_name'] = $course_name."({$course_description})";
//            $item['course_name'] = $course_name_list[$item['course_id']] ?? '';
            $item['teacher_name'] = $teacher_name_list[$item['teacher_uid']] ?? '';
            $student_name = $student_list[$item['custom_uid']]['name'] ?? '';
            $phone = $student_list[$item['custom_uid']]['phone'] ?? '';
            $item['student_name'] = "{$student_name}({$phone})";
            $item['status_text'] = $item['status'] == HbhBookCourse::status_wait ? Lang::get('Booked') : Lang::get('SignIn');
        }

        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }

    function ajaxSetShow(){
        $data = input();
        $update['status'] = $data['status'];
        $bool = (new HbhBookCourse())->updateById($data['id'], $update);
        if($bool){
            $res['msg'] = Lang::get('OperateSuccess');
        }else{
            $res['msg'] = Lang::get('OperateFailed');
        }
        return adminOut($res);
    }

    //进入新增或修改页面
    public function add(){
        return $this->fetch();
    }

    public function form()
    {
        $data = input();
        $detail_id = input('detail_id', 0);
        $detail_info = (new HbhClassTimeDetail())->info($detail_id);
        $course_id = $detail_info['course_id'] ?? 0;
        $teacher_uid = $detail_info['uid'] ?? 0;
        $start_time = $detail_info['start_time'] ?? 0;
        $end_time = $detail_info['end_time'] ?? 0;

        $course_cat_id = $detail_info['course_cat_id'] ?? 0;
        $op['where'][] = ['category_id', '=', $course_cat_id];
        $course_list = (new HbhCourse())->getList($op)['list'];

        $course_name_list = array_column($course_list, null, 'id');

        $cat_list = (new HbhCourseCat())->getAllCourseCatList();

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_name_list = array_column($teacher_list, 'name', 'id');

        $student_list = (new HbhUsers())->getAllStudentList();
        $student_name_list = array_column($student_list, 'name', 'id');

        $course_name = $course_name_list[$course_id]['name'] ?? '';
        $course_description = $course_name_list[$course_id]['description'] ?? '';
        $detail_info['course_name'] = $course_name."({$course_description})";
        $detail_info['teacher_name'] = $teacher_name_list[$teacher_uid] ?? '';
        $detail_info['class_time'] = $start_time . '-' . $end_time;
        $detail_info['teacher_uid'] = $teacher_uid;
//pj($detail_info);
        $classTimeList = model('HbhClassTime')->getAllList();
        $classTimeList = array_column($classTimeList, null, 'id');

        $this->assign('classTimeList', $classTimeList);
        $this->assign('course_name_list', $course_name_list);
        $this->assign('teacher_name_list', $teacher_name_list);
        $this->assign('student_name_list', $student_name_list);
        $this->assign('cat_list', $cat_list);
        $this->assign('detail_info', $detail_info);
        $this->assign('detail_id', $detail_id);
        return $this->fetch();
    }

    /**
     * Notes: 删除
     * @return \think\response\Json
     * User: qc DateTime: 2021/7/17 12:33
     */
    function del(){
        $data = input();
        $id = $data['id'] ?? 0;

        if(empty($id)){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }

        $info = (new HbhBookCourse())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhBookCourse())->del($id);
        if (!$bool) {
            return adminOut(['msg' => Lang::get('OperateFailed')]);
        }
        return adminOut(['msg' => Lang::get('OperateSuccess')]);
    }

    /**
     * Notes:保存
     * @return \think\response\Json
     * User: qc DateTime: 2021/7/17 12:33
     */
    public function save()
    {
        $data = input();
        $id = $data['id'] ?? 0;

        $is_exist = (new HbhBookCourse())
            ->where('detail_id', $data['detail_id'])
            ->where('day', $data['day'])
            ->where('custom_uid', $data['custom_uid'])
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if (!empty($is_exist)) return adminOut('Duplicate Book Course');


        $course_data['shop_id']                 = $this->shop_id;
        $course_data['custom_uid']              = $data['custom_uid'];
        $course_data['teacher_uid']             = $data['teacher_uid'];
        $course_data['course_id']               = $data['course_id'];
        $course_data['detail_id']               = $data['detail_id'];
        $course_data['start_time']              = $data['start_time'];
        $course_data['end_time']                = $data['end_time'];
        $course_data['day']                     = $data['day'];
        $course_data['create_time'] = time();

        if (empty($id)) {
            $course_id = (new HbhBookCourse())->insertGetId($course_data);
        } else {
            $course_id = (new HbhBookCourse())->updateById($id,  $course_data);
        }
        if($course_id){
            return adminOut(Lang::get('SaveSuccess'));
        }

        return adminOut(Lang::get('SaveFailed'));
    }



}
