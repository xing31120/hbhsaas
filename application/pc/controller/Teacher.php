<?php


namespace app\pc\controller;


use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhUsers;
use app\shop\controller\Course;
use think\Db;
use think\facade\Lang;

class Teacher extends Base {

    function bookCourse(){
        if(empty($this->hbh_user)){
            $this->redirect('auth/login');
        }
        $role = $this->hbh_user['role'] ?? '';
        if(!in_array($role, ['superadmin', 'teacher'])){
            $res['msg'] = 'Role Error, Please switch accounts';
            $this->assign("res", $res);
            return $this->fetch("User/sign_check_uid");
        }

        $uid = $this->hbh_user['id'];
        $userInfo = (new HbhUsers())->info($uid);
        $userInfo['expiry_date_en'] = date("d M,Y");

        $this->assign('userInfo', $userInfo);
        return $this->fetch();
    }

    function setWhere(){
        $uid = session('hbh_uid');
        $where[] = ['teacher_uid', '=', $uid];
        $where[] = ['day', '=', date("Y-m-d")];
        return $where;
    }

    function ajaxList(){
        $data = input();
        $limit = 10;//每页显示的数量

        //老师也只能看到 当天的课时预约记录
        //如果当天有多堂课,  默认最近的一堂课排前面
        $op['where'] = $this->setWhere($data);
        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['limit'] = $data['limit'] ?? $limit;
        $exp = new \think\db\Expression(' ABS( TIMESTAMPDIFF(SECOND, CONCAT(day, " ", start_time), NOW())) ');
        $op['order'] = $exp;
        $list = (new HbhBookCourse())->getList($op);

        $custom_uid_arr = array_values(array_unique(array_column($list['list'], 'custom_uid')));
//pj($custom_uid_arr);
        $course_list = (new HbhCourse())->getAllCourseList();
        $course_name_list = array_column($course_list, 'name', 'id');

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_name_list = array_column($teacher_list, 'name', 'id');

        $op_stu['doPage'] = false;
        $op_stu['where'][] = ['id', 'in', $custom_uid_arr];
        $student_list = (new HbhUsers())->getList($op_stu)['list'];
        $student_list = array_column($student_list, null, 'id');
//pj($student_list);
        foreach ($list['list'] as &$item) {
            $item['course_name'] = $course_name_list[$item['course_id']] ?? '';
            $item['teacher_name'] = $teacher_name_list[$item['teacher_uid']] ?? '';
            $student_name = $student_list[$item['custom_uid']]['name'] ?? '';
            $phone = $student_list[$item['custom_uid']]['phone'] ?? '';
            $item['student_name'] = "{$student_name}({$phone})";
            $item['status_text'] = $item['status'] == HbhBookCourse::status_wait ? Lang::get('Booked') : Lang::get('SignedIn');
            $item['confirm_text'] = $item['status'] == HbhBookCourse::status_wait ? Lang::get('ConfirmBooked') : '';
            $item['cancel_text'] = $item['status'] == HbhBookCourse::status_wait ? Lang::get('ConfirmCancel') : '';
        }

//        $res = ['count'=>$list['count'],'data'=>$list['list']];
        $list['page'] = $op['page'];
        $list['limit'] = $op['limit'];
        return adminOut($list);
    }

    /**
     * 老师确认到场学生
     * @return \think\response\Json
     */
    function ajaxConfirm(){
        $id = input('id', 0);
        if(empty($id)){
            return adminOutError(Lang::get('PleaseSelectAppointmentRecord'));
        }
        $book_course_info = (new HbhBookCourse())->info($id);
        if(empty($book_course_info)){
            return adminOutError(Lang::get('PleaseSelectAppointmentRecord'));
        }
        $info_course = (new HbhCourse())->info($book_course_info['course_id']);

        Db::startTrans();
        //更新预约数据
        $up_data['update_time'] = time();
        $up_data['status_confirm'] = HbhBookCourse::status_confirm_end;
        $up_data['status'] = HbhBookCourse::status_end;
        $up_data['is_pay'] = HbhBookCourse::is_pay_true;
        $res_up =  (new HbhBookCourse())->updateById($id,  $up_data);
        if(!$res_up){
            Db::rollback();
            return adminOutError(Lang::get('OperateFailed'));
        }
        //已经签到了, 不扣除额度
        if($book_course_info['status'] == HbhBookCourse::status_end) {
            Db::commit();
            return adminOut(Lang::get('OperateSuccess')); //. json_encode($course_data)
        }

        // 如果是未签到的用户, 要扣除余额
        $pay_fee = $info_course['course_fees'] ?? 0;
        $res = (new HbhUsers())->reduceWallet($book_course_info['custom_uid'], $pay_fee);
        if(!$res['result']){
            Db::rollback();
            return adminOutError($res);
        }
        Db::commit();
        return adminOut(Lang::get('OperateSuccess')); //. json_encode($course_data)

    }

    /**
     * 老师 取消到场学生的预约
     * @return \think\response\Json
     */
    function ajaxCancel(){
        $id = input('id', 0);
        if(empty($id)){
            return adminOutError(Lang::get('PleaseSelectAppointmentRecord'));
        }
        $book_course_info = (new HbhBookCourse())->info($id);
        if(empty($book_course_info)){
            return adminOutError(Lang::get('PleaseSelectAppointmentRecord'));
        }
        if($book_course_info['status'] == HbhBookCourse::status_end || $book_course_info['status'] == HbhBookCourse::status_cancel) {
            return adminOut(Lang::get('OperateSuccess')); //. json_encode($course_data)
        }

        Db::startTrans();
        $up_data['update_time'] = time();
        $up_data['status_confirm'] = HbhBookCourse::status_confirm_cancel;
        $up_data['status'] = HbhBookCourse::status_cancel;
        $res_up =  (new HbhBookCourse())->updateById($id,  $up_data);
        if(!$res_up){
            Db::rollback();
            return adminOutError(Lang::get('OperateFailed'));
        }

        Db::commit();
        return adminOut(Lang::get('OperateSuccess'));

    }

}
