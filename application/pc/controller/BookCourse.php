<?php


namespace app\pc\controller;


use app\common\model\HbhBookCourse;
use app\common\model\HbhClassTime;
use app\common\model\HbhClassTimeDetail;
use app\common\model\HbhCourse;
use app\common\model\HbhCourseCat;
use app\common\model\HbhUsers;
use app\common\service\PhpMailerService;
use think\Db;
use think\facade\Lang;

class BookCourse extends Base {


    function index(){
        // 可预约几天后的课程; 目前是7天后+当天总的8天
        $config_before_day = config('extend.time_before');
        $config_before_day = 7;
        if(empty($this->hbh_user)){
            $this->redirect('auth/login');
        }

        // 默认第一个 课程分类
        $cat_first = (new HbhCourseCat())
            ->where('status', HbhCourseCat::status_true)
            ->where('shop_id', $this->shop_id)
            ->order('id', 'asc')->findOrEmpty();
        $cat_first_id = $cat_first['id'] ?? 0;
        $cat_name = $cat_first['name'] ?? '';
        $cat_more = input('cat_more', $cat_first_id);
        $cat_more_arr = explode(',', $cat_more);

        //所有课程列表
        $course_list = (new HbhCourse())->getAllCourseList($this->shop_id);
        $course_list = array_column($course_list, null, 'id');
//pj($course_list);
        // 时间配置表
        $class_time_list = (new HbhClassTime())->getAllList($this->shop_id);
        $class_time_list = array_column($class_time_list, null, 'id');
        // 教师用户数据
        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_list = array_column($teacher_list, null, 'id');

        // 15(env配置)天的课时列表  ---start
        $year = date('Y');
        $which_week = date("W");
        //2024-02-25 15:08:00  星期日 1708844880
        $today = date("Y-m-d");  // 当天
        $todayTime = strtotime(date("Y-m-d"));  // 当前0点的时间戳
        $sevenDays = date('Y-m-d', strtotime("+{$config_before_day} days"));
        $sevenDaysTime = strtotime($sevenDays);
        if(!in_array('-1', $cat_more_arr)){
            $where_mob[] = ['course_cat_id', 'in', $cat_more_arr];
        }else{
            $cat_more_arr = [-1];
        }
        $where_mob[] = $where_book[] = ['shop_id', '=', $this->shop_id];
        $where_mob[] = $where_book[] = ['day', '>=', $today];
        $where_mob[] = $where_book[] = ['day', '<=', $sevenDays];
        $where = $where_mob;

        $detail_list = (new HbhClassTimeDetail())->where($where)->select()->toArray();
        $detail_ids_arr = array_column($detail_list, 'id');

//        $where_book[] = ['detail_id', 'in', $detail_ids_arr];
//pj($where_book);

//        $book_course_group = (new HbhBookCourse())->where($where_book)->group('detail_id')->select()->toArray();
        $book_course_group = Db::name('hbh_book_course as bs')
            ->field("detail_id, count(detail_id) as count")
            ->where($where_book)
            ->group('detail_id')
            ->select();
        $book_course_group = array_column($book_course_group, 'count', 'detail_id');
//pj([$book_course_group, $detail_list]);
        // 已经预约数据, $book_course_list, $book_course_list_mob,
        $book_course_list = (new HbhBookCourse())
            ->whereIn('detail_id', $detail_ids_arr)
            ->where('custom_uid', $this->hbh_user['id'])
            ->select()->toArray();
        $book_detail_id_arr = array_column($book_course_list, 'detail_id');
        $book_detail_id_str = implode(',', $book_detail_id_arr);

        foreach ($detail_list as &$item) {
            $item['date_string'] = date("n.d", strtotime($item['day']));
            $item['detail_select'] = 0;
            $course_name = $course_list[$item['course_id']]['name'] ?? '';
            $course_description = $course_list[$item['course_id']]['description'] ?? '';
            $item['course_name'] = $course_name;
            $item['course_description'] = $course_description;
            $item['book_course_people'] = $book_course_group[$item['id']] ?? 0;
            $item['total_people'] = $course_list[$item['course_id']]['total_people'] ?? 6;
//            $item['course_name'] = $course_name."({$course_description})";
//            $item['course_name'] = $course_list[$item['course_id']]['name'] ?? '';
            $item['teacher_name'] = $teacher_list[$item['uid']]['name'] ?? '';
            if(in_array($item['id'], $book_detail_id_arr)){
                $item['detail_select'] = 1;
            }
        }
        $detail_list_mob = $detail_list;
//pj($detail_list_mob);
        $book_course_list_mob = $book_course_list;
        $book_detail_id_arr_mob = $book_detail_id_arr;
        $book_detail_id_str_mob = $book_detail_id_str;
        // 15(env配置)天的课时列表  ---end
//pj($class_time_list);
        $new_data_pc = [];
        foreach ($class_time_list as $class_time) {
            for ($i = 0; $i < $config_before_day; $i++) {
                $class_time[date('Y-m-d', $todayTime + 86400 * $i)] = [];
            }
        }


        foreach ($class_time_list as $class_time) {
            foreach ($detail_list as $item2) {
                //不是一个上课时间点, 跳过
                if($class_time['id'] != $item2['class_time_id']){
                    continue;
                }
                $item2['nd'] = date("n.d", strtotime($item2['day']));
                //同一个上课时间点的, 并且week相同的
                $class_time[$item2['day']][] = $item2;
            }
            $new_data_pc[] = $class_time;
        }
//pj([1,$new_data_pc]);
//
        //每一天的课时
        $book_detail_id_arr_mob_today = [];
        $book_detail_id_str_mob_today = '';
        foreach ($book_course_list_mob as $item4) {
            if($item4['day'] == $today){
                $book_detail_id_arr_mob_today[] = $item4['detail_id'];
                $book_detail_id_str_mob_today .= $item4['detail_id']. ',';
            }
        }

//pj([22,$detail_list_mob]);
        $new_data_mob_base = [
            'data' => [],
            'count' => 0,
            'week' => '',
        ];
        $new_data_mob = [];
        for ($i=0; $i<$config_before_day; $i++){
            $new_data_mob[date('Y-m-d', $todayTime + 86400 * $i)] = $new_data_mob_base;
        }
//pj($detail_list_mob);
        $class_time_mob_count = [];
        foreach ($new_data_mob as $date_md => &$class_time_mob) {
            if (!isset($class_time_mob['count'])) {
                $class_time_mob['count'] = 0;
            }
            $class_time_mob['week'] = date("l", strtotime($date_md));
            $class_time_mob['nd'] = date("n.d", strtotime($date_md));
            foreach ($detail_list_mob as $item3) {
                $date_str = date('n.d', strtotime($item3['day']));
                $date_nd = date('n.d', strtotime($date_md));
//                pj([$date_nd, $date_str]);
                //当天的 课时
                if($date_str == $date_nd){
                    $class_time_mob['count'] += 1;;
                    $new_data_mob[$date_md]['data'][] = $item3;
                }
            }
        }

        foreach ($new_data_pc as &$val) {
            $val['is_hidden'] = true;
            foreach ($new_data_mob as $ke => $val_mob) {
                if (!empty($val[$ke])) {
                    $val['is_hidden'] = false;
                }
            }
        }

//pj([1,$new_data_pc, $new_data_mob]);
//pj($new_data_mob);
        $this->assign('cat_more', $cat_more_arr);
        $this->assign('cat_more_count', count($cat_more_arr));
        $this->assign('cat_name', $cat_name);
        $this->assign('book_detail_id_arr', $book_detail_id_arr);
        $this->assign('book_detail_id_arr_mob', $book_detail_id_arr_mob);
//pj($book_detail_id_arr_mob);
        $this->assign('book_detail_id_str', $book_detail_id_str);
        $this->assign('new_data_pc', $new_data_pc);
        $this->assign('book_course_list_mob', $book_course_list_mob);
        $this->assign('book_detail_id_str_mob', $book_detail_id_str_mob);
        $this->assign('book_detail_id_arr_mob_today', $book_detail_id_arr_mob_today);
        $this->assign('book_detail_id_str_mob_today', $book_detail_id_str_mob_today);
        $this->assign('new_data_mob', $new_data_mob);
        $this->assign('today', $today);


        return $this->fetch();
    }
    // pc预约课程
    function ajaxSubmit(){
        if(empty($this->hbh_user)){
            return errorReturn('Please Login');
        }
//        $data = input();
        $detail_ids = input('select_detail_ids', '');
        $old_detail_ids = input('old_detail_ids', '');
        if(empty($detail_ids)){
            return errorReturn('Please Select a Course');
        }


        $length = strlen($detail_ids);

// 如果字符串最后一个字符是逗号，则删除该逗号
        if ($detail_ids[$length - 1] == ',') {
            $detail_ids = substr($detail_ids, 0, $length - 1);
        }
        $detail_ids_arr = explode(',', $detail_ids);
        $old_detail_ids_arr = explode(',', $old_detail_ids);

        $check_res = (new HbhUsers())->checkResidueQuantity($this->hbh_user['id'], count($detail_ids_arr));
        if(!$check_res['result']){
            return $check_res;
        }
        Db::startTrans();
        // 删除当前周当前用户的所有预约记录
        $res_del = (new HbhBookCourse())->delByUidDetail($this->hbh_user['id'], $old_detail_ids_arr);
        if(!$res_del['result']){
            Db::rollback();
            return $res_del;
        }
        foreach ($detail_ids_arr as $detail_id) {
            if(empty($detail_id)){
                continue;
            }
            $res = (new HbhBookCourse())->bookCourse($this->hbh_user['id'],$detail_id);
            if(!$res['result']){
                Db::rollback();
                return $res;
            }
        }
        Db::commit();
        return successReturn('success');
    }
    // 手机端当天的预约课时id
    function ajaxTodayDetailId(){
        $today = date("Y-m-d");  // 当天
        $cat_id = input('cat_id', '');
        $day = input('day', $today);
        if(empty($cat_id)){
            return errorReturn('error cat');
        }

        $where_mob[] = ['course_cat_id', '=',$cat_id];
        $where_mob[] = ['day', '=', $day];
        // 已经预约数据, $book_course_list_mob
        $detail_list_mob = (new HbhClassTimeDetail())->where($where_mob)->select()->toArray();
        $detail_ids_arr_mob = array_column($detail_list_mob, 'id');
        $book_course_list_mob = (new HbhBookCourse())
            ->whereIn('detail_id', $detail_ids_arr_mob)
            ->where('custom_uid', $this->hbh_user['id'])
            ->select()->toArray();
//pj($book_course_list_mob);
        $book_detail_id_arr_mob = array_column($book_course_list_mob, 'detail_id');
        $book_detail_id_str_mob = implode(',', $book_detail_id_arr_mob);


        return successReturn(['detail_id_arr' => $book_detail_id_arr_mob, 'detail_id_str' => $book_detail_id_str_mob, ]);
    }
    //手机端预约课程
    function ajaxSubmitMob(){
        if(empty($this->hbh_user)){
            return errorReturn('Please Login');
        }
        $today = date("Y-m-d");  // 当天
        $cat_id = input('cat_id_mob', '');
        $day = input('today', $today);
        if(empty($cat_id)){
            return errorReturn('error cat');
        }

//        $data = input();
        $book_detail_id_str_mob_today = input('book_detail_id_str_mob_today', '');
        $book_detail_id_str_mob_today = substr($book_detail_id_str_mob_today, 0, strlen($book_detail_id_str_mob_today) - 1);
        if(empty($book_detail_id_str_mob_today)){
            return errorReturn('Please select a course');
        }
        $book_detail_id_arr_mob_today = explode(',', $book_detail_id_str_mob_today);
        if(empty($book_detail_id_arr_mob_today)){
            return errorReturn('Please select a course');
        }
        $check_res = (new HbhUsers())->checkResidueQuantity($this->hbh_user['id'], count($book_detail_id_arr_mob_today));
        if(!$check_res['result']){
            return $check_res;
        }
        Db::startTrans();
        // 删除某天当前用户的所有预约记录
        $res_del = (new HbhBookCourse())->delByUidDay($this->hbh_user['id'], $day);
        if(!$res_del['result']){
            Db::rollback();
            return $res_del;
        }
        foreach ($book_detail_id_arr_mob_today as $detail_id) {
            if(empty($detail_id)){
                continue;
            }
            $res = (new HbhBookCourse())->bookCourse($this->hbh_user['id'],$detail_id);
            if(!$res['result']){
                Db::rollback();
                return $res;
            }
        }
        Db::commit();
        return successReturn('success');
    }

    function signCheck(){
        return $this->fetch();
    }


    // 打卡签到--测试
    function ajaxSignCheck(){
        $today = date("Y-m-d");  // 当天
        $card_number = input('card_number', '');
        $day = input('today', $today);

        if(empty($card_number)){
            return errorReturn('error card');
        }
        $user_model = new HbhUsers();
        $userInfo = $user_model->where('card_number', $card_number)->findOrEmpty()->toArray();
        if(empty($userInfo)){
            return errorReturn('error user card');
        }

        $list = (new HbhBookCourse())->where('day', $day)->where('custom_uid', $userInfo['id'])->select()->toArray();
//        $residue_quantity = $userInfo['residue_quantity'] ?? 0;
//        if(count($list) > $residue_quantity){
//            return errorReturn('剩余课时不足');
//        }
        $check_res = $user_model->checkResidueQuantity($userInfo['id'], count($list));
        if(!$check_res['result']){
            return $check_res;
        }

        $userInfo['residue_quantity'] = $userInfo['residue_quantity'] - count($list);
        unset($userInfo['create_time']);
        unset($userInfo['update_time']);
        $res = $user_model->saveData($userInfo);
        if(!$res){
            return errorReturn(Lang::get('FailedToDeductUserBalance'));
        }
        $res = (new HbhBookCourse())->where('day', $day)->where('custom_uid', $userInfo['id'])->update(['status' => HbhBookCourse::status_end]);
        return successReturn(['data' => $res, 'msg' => 'success']);
    }


    function ajaxList(){
    }

    function test(){
        $rrr = (new PhpMailerService())->sendEmail('info@4x4life.shop','hbhTTT', 'aabbcc');
        pj($rrr);
    }
}
