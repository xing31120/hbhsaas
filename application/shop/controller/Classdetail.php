<?php
namespace app\shop\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhClassTime;
use app\common\model\HbhClassTimeDetail;
use app\common\model\HbhCourse;
use app\common\model\HbhCourseCat;
use app\common\model\HbhUsers;
use think\Db;
use think\facade\Lang;

class Classdetail extends Base {

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
        if (isset($param['day']) && $param['day'] != ''){
            $time = explode('~', $param['day']);
            $where[] = ['day', 'between', [str_replace(" ", "", $time[0]), str_replace(" ", "", $time[1])]];
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
        $list = (new HbhClassTimeDetail())->getList($op);

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
        $info = (new HbhClassTimeDetail())->info($id);
        if ($this->request->isPost()) {
            $res = (new HbhClassTimeDetail())->updateById($id, $data);
            if(!$res){
                return adminOutError(Lang::get('SaveFailed'));
            }
            return adminOut(['msg' => Lang::get('SaveSuccess')]);
        }


        $this->assign('info', $info);
        return $this->fetch();

    }
    //删除操作(包含批量删除,使用del方法是为了删除对应的缓存)


    public function form(){
        $data = input();
        $id = $data['id'] ?? 0;
        $info = (new HbhClassTimeDetail())->info($id);
        $course_id = $info['course_id'] ?? 0;
        $uid = $info['uid'] ?? 0;
        $start_time = $info['start_time'] ?? 0;
        $end_time = $info['end_time'] ?? 0;

        $course_cat_id = $info['course_cat_id'] ?? 0;
        $op['where'][] = ['category_id', '=', $course_cat_id];
        $course_list = (new HbhCourse())->getList($op)['list'];

        $course_name_list = array_column($course_list, null, 'id');

        $cat_list = (new HbhCourseCat())->getAllCourseCatList();

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_name_list = array_column($teacher_list, 'name', 'id');

        $course_name = $course_name_list[$course_id]['name'] ?? '';
        $course_description = $course_name_list[$course_id]['description'] ?? '';
        $info['course_name'] = $course_name."({$course_description})";
        $info['teacher_name'] = $teacher_name_list[$uid] ?? '';
        $info['class_time'] = $start_time . '-' . $end_time;

        $classTimeList = model('HbhClassTime')->getAllList();
        $classTimeList = array_column($classTimeList, null, 'id');

        $this->assign('classTimeList', $classTimeList);
        $this->assign('course_name_list', $course_name_list);
        $this->assign('teacher_name_list', $teacher_name_list);
        $this->assign('cat_list', $cat_list);
        $this->assign('info', $info);
        return $this->fetch();
    }

    function addBooked(){
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

    function SaveBooked(){
        $data = input();
        $id = $data['id'] ?? 0;

        $is_exist = (new HbhBookCourse())
//            ->where('teacher_class_time_id', $data['teacher_class_time_id'])
            ->where('day', $data['day'])
            ->where('custom_uid', $data['custom_uid'])
            ->where('detail_id', $data['detail_id'])
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if (!empty($is_exist)) return adminOutError('Duplicate Book Course');
        $check_res = (new HbhUsers())->checkResidueQuantity($data['custom_uid'], 1);
        if(!$check_res['result']){
            return adminOutError($check_res);
        }

        $time_t = strtotime($data['day']);
        $which_week = date("W", $time_t);
        $year = substr($data['day'], 0, 4);

        $class_time_info = (new HbhClassTime())->info($data['class_time_id']);
        $course_data['status']                  = $data['status'];
        $course_data['custom_uid']              = $data['custom_uid'];
        $course_data['teacher_uid']             = $data['teacher_uid'];
        $course_data['course_id']               = $data['course_id'];
        $course_data['detail_id']               = $data['detail_id'];
        $course_data['shop_id']                 = $this->shop_id;
        $course_data['start_time']              = $class_time_info['start_time'];
        $course_data['end_time']                = $class_time_info['end_time'];
        $course_data['year']                    = $year;
        $course_data['day']                     = $data['day'];
        $course_data['is_pay']                  = $data['is_pay'];
        $course_data['which_week']              = $which_week;
//pj([$id, $course_data]);
        Db::startTrans();
        if (empty($id)) {
            $course_data['create_time'] = time();
            $book_course_id = (new HbhBookCourse())->insertGetId($course_data);
        } else { //修改预约, 如果修改is_pay需要扣费
            $book_course_id = false;
            $res = (new HbhBookCourse())->payByBoosCourseId($id, $data['is_pay']);
            if($res['result']){
                $book_course_id =  (new HbhBookCourse())->updateById($id,  $course_data);
            }
        }

        if($book_course_id){
            Db::commit();
            return adminOut(Lang::get('OperateSuccess')); //. json_encode($course_data)
        }
        Db::rollback();
        return adminOut(Lang::get('OperateFailed'));
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

        $info = (new HbhClassTimeDetail())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhClassTimeDetail())->del($id);
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

        $is_exist = (new HbhClassTimeDetail())
//            ->where('teacher_class_time_id', $data['teacher_class_time_id'])
            ->where('day', $data['day'])
            ->where('uid', $data['uid'])
            ->where('class_time_id', $data['class_time_id'])
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if (!empty($is_exist)) return adminOut('Duplicate Time Detail');
        $time_t = strtotime($data['day']);
        $which_week = date("W", $time_t);
        $year = substr($data['day'], 0, 4);
        $courseInfo = (new HbhCourse())->info($data['course_id']);

        $class_time_info = (new HbhClassTime())->info($data['class_time_id']);
        $course_data['uid']                     = $data['uid'];
        $course_data['course_id']               = $data['course_id'];
        $course_data['course_cat_id']           = $courseInfo['category_id'] ?? 0;
        $course_data['shop_id']                 = $this->shop_id;
        $course_data['week']                    = date('l', strtotime($data['day']));
        $course_data['class_time_id']           = $data['class_time_id'];
        $course_data['start_time']              = $class_time_info['start_time'];
        $course_data['end_time']                = $class_time_info['end_time'];
        $course_data['year']                    = $year;
        $course_data['day']                     = $data['day'];
        $course_data['which_week']              = $which_week;
//        $course_data['uid']                     = $data['uid'];
//        $course_data['course_id']               = $data['course_id'];
//        $course_data['teacher_class_time_id']   = $data['teacher_class_time_id'];

        if (empty($id)) {
            $course_data['create_time'] = time();
            $course_id = (new HbhClassTimeDetail())->insertGetId($course_data);
        } else {
            $course_data['update_time'] = time();
            $course_id =  (new HbhClassTimeDetail())->updateById($id,  $course_data);
        }
        if($course_id){
            return adminOut(Lang::get('OperateSuccess')); //. json_encode($course_data)
        }

        return adminOut(Lang::get('OperateFailed'));
    }



}
