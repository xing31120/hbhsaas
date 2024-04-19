<?php
namespace app\shop\controller;

use app\common\model\HbhClassTime;
use app\common\model\HbhClassTimeDetail;
use app\common\model\HbhClassTimePlan;
use app\common\model\HbhCourse;
use app\common\model\HbhCourseCat;
use app\common\model\HbhUsers;
use think\Db;
use think\facade\Lang;

class Plan extends Base {

    //数据列表
    public function dataList(){
        $this->title = '每天的课时表';
        $course_list = (new HbhCourse())->getAllCourseList();
        $this->assign('course_list', $course_list);

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $this->assign('teacher_list', $teacher_list);

        $cat_list = (new HbhCourseCat())->getAllCourseCatList();
        $this->assign('cat_list', $cat_list);

        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['course_id']) && !empty($param['course_id'])) {
            $where[] = ['course_id', '=', $param['course_id']];
        }
        if (isset($param['course_cat_id']) && !empty($param['course_cat_id'])) {
            $where[] = ['course_cat_id', '=', $param['course_cat_id']];
        }
        if (isset($param['teacher_uid']) && !empty($param['teacher_uid'])) {
            $where[] = ['uid', '=', $param['teacher_uid']];
        }
        if (isset($param['week']) && $param['week'] != ''){
            $where[] = ['week', '=', $param['week']];
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
        $list = (new HbhClassTimePlan())->getList($op);

        $course_list = (new HbhCourse())->getAllCourseList();
        $course_name_list = array_column($course_list, null, 'id');

        $cat_list = (new HbhCourseCat())->getAllCourseCatList();
        $cat_list = array_column($cat_list, 'name', 'id');

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_name_list = array_column($teacher_list, 'name', 'id');

        foreach ($list['list'] as &$item) {
            $course_name = $course_name_list[$item['course_id']]['name'] ?? '';
            $course_description = $course_name_list[$item['course_id']]['description'] ?? '';
            $item['course_name'] = $course_name."({$course_description})";
            $item['cat_name'] = $cat_list[$item['course_cat_id']] ?? '';
            $item['teacher_name'] = $teacher_name_list[$item['uid']] ?? '';
        }


        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }


    //进入新增或修改页面
    public function add(){
        return $this->fetch();
    }
    //新增和修改保存
    public function edit(){
        $data = input();
        $id = $data['id'];
        $info = (new HbhClassTimePlan())->info($id);
        if ($this->request->isPost()) {
            $res = (new HbhClassTimePlan())->updateById($id, $data);
            if(!$res){
                return adminOutError(Lang::get('SaveFailed'));
            }
            return adminOut(['msg' => Lang::get('SaveSuccess')]);
        }


        $this->assign('info', $info);
        return $this->fetch();

    }
    //删除操作(包含批量删除,使用del方法是为了删除对应的缓存)


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

        $info = (new HbhClassTimePlan())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhClassTimePlan())->del($id);
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

        $is_exist = (new HbhClassTimePlan())
//            ->where('teacher_class_time_id', $data['teacher_class_time_id'])
//            ->where('day', $data['day'])
            ->where('uid', $data['uid'])
            ->where('class_time_id', $data['class_time_id'])
            ->where('week', $data['week'])
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if (!empty($is_exist)) return adminOutError('Duplicate Time Plan');
//        $time_t = strtotime($data['day']);
//        $which_week = date("W", $time_t);
//        $year = substr($data['day'], 0, 4);
        $courseInfo = (new HbhCourse())->info($data['course_id']);
        $select_uid_arr = $data['select_uid'] ?? [];

        $class_time_info = (new HbhClassTime())->info($data['class_time_id']);
        $course_data['uid']                     = $data['uid'];
        $course_data['course_id']               = $data['course_id'];
        $course_data['course_cat_id']           = $courseInfo['category_id'] ?? 0;
        $course_data['shop_id']                 = $this->shop_id;
        $course_data['week']                    = $data['week'];
        $course_data['class_time_id']           = $data['class_time_id'];
        $course_data['start_time']              = $class_time_info['start_time'];
        $course_data['end_time']                = $class_time_info['end_time'];
        $course_data['custom_uid_str']          = implode(',', $select_uid_arr);
//pj($course_data);
        if (empty($id)) {
            $course_data['create_time'] = time();
            $course_id = (new HbhClassTimePlan())->insertGetId($course_data);
        } else {
            $course_data['update_time'] = time();
            $course_id =  (new HbhClassTimePlan())->updateById($id,  $course_data);
        }
        if($course_id){
            return adminOut(Lang::get('OperateSuccess')); //. json_encode($course_data)
        }

        return adminOut(Lang::get('OperateFailed'));
    }



}
