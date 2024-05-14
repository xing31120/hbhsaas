<?php
namespace app\shop\controller;

use app\common\model\HbhTeacherClassTime;
use app\common\model\HbhUsers;
use app\common\service\HbhUserService;
use think\Db;
use think\facade\Lang;

class Member extends Base {

    //进入新增或修改页面
    public function read(){
        $data = input();
        $id = $data['id'];
        $info = model('HbhUsers')->info($id);

        $this->assign('info', $info);
        return $this->fetch();
    }
    //进入新增或修改页面
    public function add(){
        return $this->fetch();
    }
    //修改保存
    public function edit(){
        $data = input();
        $id = $data['id'];
        $info = model('HbhUsers')->info($id);
        if ($this->request->isPost()) {
            if(empty($data['password'])){
                unset($data['password']);
            }
            $res = (new HbhUsers())->updateById($id, $data);
            if(!$res){
                return adminOut(Lang::get('OperateFailed'));
            }
            return adminOut(['msg' => Lang::get('OperateSuccess')]);
        }


        $this->assign('info', $info);
        return $this->fetch();

    }
    public function editTeacher(){
        $data = input();
        $id = $data['id'];
        $info = model('HbhUsers')->info($id);
//pj($info);
        if ($this->request->isPost()) {
            if(empty($data['password'])){
                unset($data['password']);
            }
            $res = model('HbhUsers')->updateById($id, $data);
            $select_region = array_unique($data['select_region']);
            $teacher_class_time_arr = [];
            foreach ($select_region as $value) {
                $val = explode(',', $value);
                $teacher_class_time = [
                    'shop_id' => $info['shop_id'],
                    'uid' => $info['id'],
                    'course_id' => $info['course_id'],
                    'week' => $val[0],
                    'class_time_id' => $val[1],
                    'create_time' => time(),
                    'update_time' => time(),
                ];

                $teacher_class_time_arr[] = $teacher_class_time;
            }

            $where_del = [
                'shop_id' => $info['shop_id'],
                'uid' => $info['id'],
            ];
//            Db::startTrans();
//            try {
//                $res = HbhTeacherClassTime::where($where_del) -> delete();
//                $rs = model('HbhTeacherClassTime')->insertAll($teacher_class_time_arr);
//                if(!$rs || $res === false){
//                    Db::rollback();
//                    return adminOut(Lang::get('OperateFailed'));
//                }
//                Db::commit();
//            } catch (\Exception $e) {
//                Db::rollback();
//                return errorReturn(Lang::get('OperateFailed'));
//            }
            return adminOut(['msg' => Lang::get('OperateFailed')]);
        }
        $courseList = model('HbhCourse')->getAllCourseList();
        $courseList = array_column($courseList, null, 'id');

        $classTimeList = model('HbhClassTime')->getAllList();
        $classTimeList = array_column($classTimeList, null, 'id');

//        $teacherClassTimeList = model('HbhTeacherClassTime')->getAllList($info['id']);
//        foreach ($teacherClassTimeList as &$item) {
//            $class_time_row = $classTimeList[$item['class_time_id']] ?? '';
//            $item['time_str'] = '';
//            if(!empty($class_time_row)){
//                $item['time_str'] = $class_time_row['start_time']."-".$class_time_row['end_time'];
//            }
//        }
//        $info['teacher_class_time'] = $teacherClassTimeList;

        $this->assign('course', $courseList);
        $this->assign('classTimeList', $classTimeList);
        $this->assign('info', $info);
        return $this->fetch();

    }
    //删除操作(包含批量删除,使用del方法是为了删除对应的缓存)
    public function del(){
        $data = input();
        $id = $data['id'] ?? 0;

        if(empty($id)){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }

        $info = (new HbhUsers())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhUsers())->del($id);
        if (!$bool) {
            return adminOut(['msg' => Lang::get('OperateFailed')]);
        }
        return adminOut(['msg' => Lang::get('OperateSuccess')]);
    }
    //数据列表
    public function dataListC(){
        $this->title = '会员信息';
        return $this->fetch();
    }
    public function dataListNew(){
        $this->title = '会员信息';
        return $this->fetch();
    }
    //数据列表
    public function dataListTeacher(){
        $this->title = '教师信息';

        return $this->fetch();
    }
    //异步获取列表数据
    public function ajaxList(){
        $data = input();
        $limit = 10;//每页显示的数量
        $op['where'][] = ['role','=',$data['role']];
        if(isset($data['email']) && $data['email'] != ''){
            $op['where'][] = ['email','like',"%{$data['email']}%"];
        }
        if(isset($data['name']) && $data['name'] != ''){
            $name = trim($data['name']);
            $op['where'][] = ['name','like',"%{$name}%"];
        }
        if(isset($data['card_number']) && $data['card_number'] != ''){
            $op['where'][] = ['card_number','like',"%{$data['card_number']}%"];
        }
        if(isset($data['phone']) && $data['phone'] != ''){
            $op['where'][] = ['phone','like',"%{$data['phone']}%"];
        }
        if(isset($data['level_id']) && !empty($data['level_id']) ){
            $op['where'][] = ['level_id','=', $data['level_id']];
        }

        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['doPage'] = true;
        $op['field'] = '*';
        $op['limit'] = $data['limit'] ?? $limit;
        $op['order'] = 'id desc';
        $list = model('HbhUsers')->getList($op);
        $courseList = model('HbhCourse')->getAllCourseList();
        $courseList = array_column($courseList, null, 'id');

        foreach ($list['list'] as &$item) {
            $item['course_name'] = $courseList[$item['course_id']]['name'] ?? '';
        }
        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }

    public function index() {
        return $this->fetch();
    }

    public function form(){
        $data = input();
        $role = input('role', 'student');
        $id = $data['id'] ?? 0;
        $info = model('HbhUsers')->info($id);

        $this->assign('info', $info);
        $this->assign('role', $role);
        return $this->fetch();
    }

    function register() {
        $data = input();
        $where[] = function ($query) use ($data) {
            $query->whereRaw("name = :name OR email = :email", ['name' => $data['name'], 'email'=> $data['email']]);
        };
        $result = HbhUsers::where($where)->find();
        if (!empty($result) && $result['email'] == $data['email']){
            return adminOutError(['msg'=> 'email occupied','data'=> $result, 'url' => url('auth/reg') ]);
        }
        if (!empty($result) && $result['name'] == $data['name']){
            return adminOutError(['msg'=> 'name occupied','data'=> $result, 'url' => url('auth/reg') ]);
        }
        if(empty($data['password'])){
            return adminOutError(['msg'=> 'password is empty','data'=> $result, 'url' => url('auth/reg') ]);
        }
        if(empty($data['confirm_password'])){
            return adminOutError(['msg'=> 'confirm password is empty','data'=> $result, 'url' => url('auth/reg') ]);
        }

        if($data['confirm_password'] != $data['password']){
            return adminOutError(['msg'=> 'password inconsistency','data'=> $result, 'url' => url('auth/reg') ]);
        }

        unset($data['confirm_password']);
        $data['shop_id'] = $this->shop_id;
        $card = '000'.getRandomCode(7);
//        $data['role'] = 'student';
        $data['watch_history'] = '[]';
        $data['address'] = '';
        $data['card_number'] = $data['serial_num'] = $card;
        $data['expiry_date'] = date("Y-m-d");
        $data['class_details'] = $data['second_class'] = $data['third_class'] = '';
//pj($data);
        HbhUsers::create($data);

        $data['login_name'] = $data['name'];
        $data['login_password'] = $data['password'];
        return adminOut($data);
    }

}
