<?php
namespace app\common\service;

use app\common\model\HbhBookCourse;
use app\common\model\HbhShop;
use app\common\model\HbhUsers;

class StatisticsService{

    function homepageStatistics($shopId = 0){
        $all_num = 0;
        $member_type_student = $member_type_teacher = 0;    //总学员, 总教师
        $active_student = $unlimited_student = 0;           //在读学员(总学员), 不限次数会员
        $lesson_hours_all = $lesson_hours_enable = 0;
        $where = [];
        $where[] = ['status','=',1];
        if($shopId > 0){
            $where[] = ['shop_id','=',$shopId];
        }
        $field = [
            'count(*) as count',
            'role',
        ];
        $memberInfo = (new HbhUsers())->field($field)->where($where)->group('role')->select();
        $res_base = [
            'shop_num'   => (new HbhUsers())->count(),
            'all_num' => $all_num,
            'unlimited_student'  =>  $unlimited_student,
            'member_type_student'  =>  $member_type_student,
            'member_type_teacher'  =>  $member_type_teacher,
            'active_student'  =>  $active_student,
            'lesson_hours_all'  =>  $lesson_hours_all,
            'lesson_hours_enable'  =>  $lesson_hours_enable,
        ];
        if(empty($memberInfo)){
            return $res_base;
        }
        //总会员数量, 在读会员数量, 总剩余课时->有效课时
        foreach($memberInfo->toArray() as $k => $v){
            // 总会员
            if($v['role'] == 'student' || $v['role'] == 'teacher'){
                $all_num += $v['count'];
            }
            // 客户会员
            if($v['role'] == 'student'){
                $member_type_student += $v['count'];
            }

        }
        //在读会员有限数量 $active_student
        $where_active = $where;
        $where_active[] = ['expiry_date','>',date("Y-m-d")];
        $where_active[] = ['residue_quantity','>',0];
        $where_active[] = ['is_unlimited_number','=',HbhUsers::is_unlimited_number_false];
        $activeInfo = (new HbhUsers())->field($field)->where($where_active)->findOrEmpty()->toArray();
        !empty($activeInfo) && $active_student = $activeInfo['count'];
        //不限次数会员数量 $unlimited_student
        $where_unlimited = $where;
        $where_unlimited[] = ['expiry_date','>',date("Y-m-d")];
        $where_unlimited[] = ['is_unlimited_number','=',HbhUsers::is_unlimited_number_true];
        $unlimited_info = (new HbhUsers())->field($field)->where($where_unlimited)->findOrEmpty()->toArray();
        !empty($unlimited_info) && $unlimited_student = $unlimited_info['count'];

        // 总剩余课时->
        $field_lesson_hours= [
            'sum(residue_quantity) as quantity_sum',
        ];
        $where_lesson_hours = $where;
        $lessonHoursInfo = (new HbhUsers())->field($field_lesson_hours)->where($where_lesson_hours)->findOrEmpty()->toArray();
        !empty($activeInfo) && $lesson_hours_all = $lessonHoursInfo['quantity_sum'];
        // 有效课时
        $where_lesson_hours[] = ['expiry_date','>',date("Y-m-d")];
        $lessonHoursInfo = (new HbhUsers())->field($field_lesson_hours)->where($where_lesson_hours)->findOrEmpty()->toArray();
        !empty($activeInfo) && $lesson_hours_enable = $lessonHoursInfo['quantity_sum'];


        return $res_base = [
            'shop_num'   => (new HbhShop())->count(),
            'all_num' => $all_num,
            'unlimited_student'  =>  $unlimited_student,
            'member_type_student'  =>  $member_type_student,
            'member_type_teacher'  =>  $member_type_teacher,
            'active_student'  =>  $active_student,
            'lesson_hours_all'  =>  $lesson_hours_all,
            'lesson_hours_enable'  =>  $lesson_hours_enable,
        ];
    }

    public function getBookLastNumDay($day_num = 7,$shop_id = 0){
        $end_time = strtotime(date('Y-m-d',strtotime('+1 day')));
        $begin_time = $end_time - $day_num*86400;

        for($i=$day_num;$i>0;$i--){
            $days[] = date('m-d',$end_time - $i*86400);
        }
        $where = [];
        if($shop_id > 0){
            $where[] = ['shop_id','=',$shop_id];
        }
        $where[] = ['create_time','BETWEEN',[$begin_time,$end_time]];
        $where[] = ['status', '=', HbhBookCourse::status_end];
        $field = [
//            "FROM_UNIXTIME(create_time,'%m-%d') AS day",
            "FROM_UNIXTIME(UNIX_TIMESTAMP(day),'%m-%d') AS day",
            'count(*) as count',
//            'sum(amount) as amount',
        ];
        $group = 'day';
        $orderInfo = (new HbhBookCourse())->field($field)->where($where)->group($group)->select();
        $orderInfo = empty($orderInfo)?:$orderInfo->toArray();
        $orderInfo = array_column($orderInfo,null,'day');

        $list = [];
        foreach($days as $day){
            $list[] = $orderInfo[$day]??['day'=>$day,'count'=>0];
        }
        // echo "<pre>";
        // var_dump($list);
        return $list;
    }

    /**
     * 首页消耗课时界面
     * @param string $startDate 例如: "2024-04-10"
     * @param string $endDate   例如: "2024-04-10"
     * @param $shop_id
     * @return array
     */
    public function getBookStartEnd($startDate, $endDate, $shop_id = 0){
        $begin_time = strtotime($startDate.' 00:00:00');
        $end_time = strtotime($endDate.' 00:00:00') + 86400;

        $day_num = round(($end_time - $begin_time ) / 86400);

        for($i=$day_num; $i>0; $i--){
            $days[] = date('m-d',$end_time - $i*86400);
        }
        $where = [];
        if($shop_id > 0){
            $where[] = ['shop_id','=',$shop_id];
        }
        $where[] = ['create_time','BETWEEN',[$begin_time, $end_time]];
        $where[] = ['status', '=', HbhBookCourse::status_end];
        $where[] = ['is_unlimited_number', '=', HbhUsers::is_unlimited_number_false];   //有限次数的会员才算课时
        $field = [
            "FROM_UNIXTIME(UNIX_TIMESTAMP(day),'%m-%d') AS day",
            'count(*) as count',
        ];
        $group = 'day';
        $orderInfo = (new HbhBookCourse())->field($field)->where($where)->group($group)->select();
        $orderInfo = empty($orderInfo)?:$orderInfo->toArray();
        $orderInfo = array_column($orderInfo,null,'day');

        $list = [];
        foreach($days as $day){
            $list[] = $orderInfo[$day]??['day'=>$day,'count'=>0];
        }
        return $list;
    }





//-------------------------------↓↓↓↓ 旧代码弃用 ↓↓↓↓-------------------------------
    /**
     * 获取用户数据
     * @param integer $app_uid
     * @return void
     * @author LX
     * @date 2021-01-07
     */
    public function getUserStatistics($app_uid = 0){
        $all_num = 0;
        $member_type2 = $member_type3 = 0;
        $member_type2_pass = $member_type3_pass = 0;

        $where = [];
        if($app_uid > 0){
            $where[] = ['app_uid','=',$app_uid];
        }
        $field = [
            'count(*) as count',
            'member_type',
            'sign_contract_status',
        ];
        $memberInfo = model('Users')->field($field)->where($where)->group('member_type,sign_contract_status')->select();
        $memberInfo = empty($memberInfo)?:$memberInfo->toArray();
        if($memberInfo){
            foreach($memberInfo as $k => $v){
                $all_num += $v['count'];
                if($v['member_type'] == 2){
                    $member_type2 += $v['count'];
                    if($v['sign_contract_status'] == 30){
                        $member_type2_pass += $v['count'];
                    }
                }
                if($v['member_type'] == 3){
                    $member_type3 += $v['count'];
                    if($v['sign_contract_status'] == 30){
                        $member_type3_pass += $v['count'];
                    }
                }
            }
        }

        $res = [
            'app_num'   => model('UsersApp')->count(),
            'all_num' => $all_num,
            'all_num_pass' => $member_type2_pass + $member_type3_pass,
            'member_type2'  =>  $member_type2,
            'member_type3'  =>  $member_type3,
            'member_type2_pass' =>  $member_type2_pass,
            'member_type3_pass' =>  $member_type3_pass,
        ];
        return $res;
    }

    /**
     * 获取指定日期的入账订单状态分布数据
     * @param integer $day_num
     * @param integer $app_uid
     * @return void
     * @author LX
     * @date 2021-01-07
     */
    public function getOrderStatus($day_num = 0,$app_uid = 0){
        $all_amount = 0;
        $all_count = 0;
        $begin_time = strtotime(date('Y-m-d',strtotime('-'.$day_num.' day')));
        // $begin_time = strtotime(date('Y-m-d'));
        // var_dump($begin_time);
        $where = [];
        if($app_uid > 0){
            $where[] = ['app_uid','=',$app_uid];
        }
        $where[] = ['create_time','>=',$begin_time];
        $field = [
            'count(*) as count',
            'order_entry_status',
            'sum(amount) as amount',
        ];
        $orderInfo = model('OrderEntry')->field($field)->where($where)->group('order_entry_status')->select();
        $orderInfo = empty($orderInfo)?:$orderInfo->toArray();

        $orderEntryStatus = model('OrderEntry')::orderEntryStatus;
        $orderInfo = array_column($orderInfo,null,'order_entry_status');
        $list = [];
        foreach( $orderEntryStatus as $k => $v){
            $data_info = $orderInfo[$k]??['order_entry_status'=>$k,'count'=>0,'amount'=>0];

            $all_amount += $data_info['amount'];
            $all_count += $data_info['count'];
            $data_info['amount'] = pennyToRmb($data_info['amount']);
            $data_info['status_name'] = $v;
            $list[] = $data_info;
        }
        $res = [
            'all_count'     =>  $all_count,
            'all_amount'    =>  $all_amount,
            'orderInfo'     =>  $list,
        ];
        return $res;
    }

    /**
     * 获取指定日期的入账订单汇总数据
     * @param integer $day_num
     * @param integer $app_uid
     * @return void
     * @author LX
     * @date 2021-01-07
     */
    public function getOrderLastNumDay($day_num = 7,$app_uid = 0){
        $end_time = strtotime(date('Y-m-d',strtotime('+1 day')));
        $begin_time = $end_time - $day_num*86400;

        for($i=$day_num;$i>0;$i--){
            $days[] = date('m-d',$end_time - $i*86400);
        }
        $where = [];
        if($app_uid > 0){
            $where[] = ['app_uid','=',$app_uid];
        }
        $where[] = ['create_time','BETWEEN',[$begin_time,$end_time]];
        $field = [
            "FROM_UNIXTIME(create_time,'%m-%d') AS day",
            'count(*) as count',
            'sum(amount) as amount',
        ];
        $group = 'day';
        $orderInfo = model('OrderEntry')->field($field)->where($where)->group($group)->select();
        $orderInfo = empty($orderInfo)?:$orderInfo->toArray();
        $orderInfo = array_column($orderInfo,null,'day');

        $list = [];
        foreach($days as $day){
            $list[] = $orderInfo[$day]??['day'=>$day,'count'=>0,'amount'=>0];
        }
        // echo "<pre>";
        // var_dump($list);
        return $list;
    }

}
