<?php


namespace app\pc\controller;


use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhUsers;
use app\shop\controller\Course;
use think\Db;
use think\facade\Lang;

class User extends Base {

    function index()
    {
        return $this->fetch();
    }

    function qrcode(){
        if(empty($this->hbh_user)){
            $this->redirect('auth/login');
        }
        $uid = $this->hbh_user['id'];
        $userInfo = (new HbhUsers())->info($uid);
        $userInfo['expiry_date_en'] = date("M d, Y", strtotime($userInfo['expiry_date']));   //expiry_date

        $this->assign('userInfo', $userInfo);
//        $this->assign('url', url("user/signCheckUid",[ 'uid'=>$this->hbh_user['id'] ]), 'html', true);
        $this->assign('url', url("user/signCheckUid", ['uid'=> $uid], 'html', true));
        return $this->fetch();
    }

    function setWhere(){
        $uid = session('hbh_uid');
//pj([$uid, $_SESSION]);
//echo $uid;exit();
        $where[] = ['custom_uid', '=', $uid];
        return $where;
    }

    function ajaxList(){
        $data = input();
        $limit = 20;//每页显示的数量

        $op['where'] = $this->setWhere($data);
        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['limit'] = $data['limit'] ?? $limit;
        $op['order'] = 'status asc, day desc';
        $list = (new HbhBookCourse())->getList($op);

        $course_list = (new HbhCourse())->getAllCourseList();
        $course_name_list = array_column($course_list, 'name', 'id');

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_name_list = array_column($teacher_list, 'name', 'id');

//        $student_list = (new HbhUsers())->getAllStudentList();
//        $student_name_list = array_column($student_list, 'name', 'id');
//pj($student_name_list);
        foreach ($list['list'] as &$item) {
            $item['day_short'] = date('M d', strtotime($item['day']));
            $item['course_name'] = $course_name_list[$item['course_id']] ?? '';
            $item['teacher_name'] = $teacher_name_list[$item['teacher_uid']] ?? '';
//            $item['student_name'] = $student_name_list[$item['custom_uid']] ?? '';
//            $item['status_text'] = $item['status'] == HbhBookCourse::status_wait ? Lang::get('Booked') : Lang::get('SignedIn');
            $item['status_text'] = HbhBookCourse::getStatusText($item['status']);
        }

//        $res = ['count'=>$list['count'],'data'=>$list['list']];
        $list['page'] = $op['page'];
        $list['limit'] = $op['limit'];
        return adminOut($list);
    }

    /**
     * 扫码访问的url, 需要教师或者管理员, 才能扫码
     * @return array
     */
    function signCheckUid(){
        if(empty($this->hbh_user)){
            $this->redirect('auth/login');
        }
        $role = $this->hbh_user['role'] ?? '';
        if(!in_array($role, ['superadmin', 'teacher'])){
            $res['msg'] = 'Role Error, Please switch accounts';
            $this->assign("res", $res);
            return $this->fetch();
        }

        $today = date("Y-m-d");  // 当天
        $uid = input('uid', 0);
        $day = input('today', $today);

        $res = $this->checkSign($uid, $day);
        $this->assign("res", $res);
        return $this->fetch();
    }

    /**
     * 检测用户, 并且自动签到
     * @param $uid
     * @param $day
     * @return array
     */
    function checkSign($uid, $day=''){
        $today = date("Y-m-d");  // 当天
        empty($day) && $day = $today;
        if(empty($uid)){
            return errorReturn(Lang::get('UserError'));
        }
        $user_model = new HbhUsers();
        $userInfo = $user_model->where('id', $uid)->findOrEmpty()->toArray();
        if(empty($userInfo)){
            return errorReturn(Lang::get('UserNotFound'));
        }

        $list = (new HbhBookCourse())
            ->where('status', HbhBookCourse::status_wait)
            ->where('day', $day)
            ->where('custom_uid', $uid)
            ->select()->toArray();
//pj([$list]);
        if(count($list) == 0){
            return errorReturn(Lang::get('NoScheduledCourses'));
        }
        $course_id_arr = array_column($list, 'course_id');
        $course_list = (new HbhCourse())->whereIn('id', $course_id_arr)->select()->toArray();
        $course_name_list = array_column($course_list, 'name');
        $course_name_string = implode($course_name_list, ',');

        $check_res = $user_model->checkResidueQuantity($uid, count($list));
        if(!$check_res['result']){
            return $check_res;
        }
        Db::startTrans();
        //更新预约数据
        $update_book_course['update_time'] = time();
        $update_book_course['status'] = HbhBookCourse::status_end;
        $update_book_course['is_unlimited_number'] = $userInfo['is_unlimited_number'];
        $update_book_course['is_pay'] = HbhBookCourse::is_pay_true;
        $res = (new HbhBookCourse())->where('day', $day)->where('custom_uid', $userInfo['id'])->update($update_book_course);
        if(false === $res){
            Db::rollback();
            return errorReturn(Lang::get('OperateFailed'));
        }
        //更新用户钱包
        foreach ($list as $info) {
            $info_course = (new HbhCourse())->info($info['course_id']);
            $pay_fee = $info_course['course_fees'] ?? 0;
            $res = (new HbhUsers())->reduceWallet($uid, $pay_fee);
            if(!$res['result']){
                Db::rollback();
                return errorReturn($res);
            }
        }

        Db::commit();
        $msg = "Your Scheduled {$course_name_string} ".Lang::get('SuccessSignIn');
        return successReturn(['data' => $res, 'msg' => $msg]);

    }

}
