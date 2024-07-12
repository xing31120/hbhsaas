<?php
namespace app\shop\controller;

use app\common\model\HbhBookCourse;
use app\common\model\HbhClassTimeDetail;
use app\common\model\HbhCourse;
use app\common\model\HbhCourseCat;
use app\common\model\HbhSjLog;
use app\common\model\HbhUsers;
use app\common\model\HbhUserWalletDetail;
use think\Db;
use think\facade\Lang;

//课程预约列表
class Walletdetail extends Base {

    //数据列表
    public function dataList(){
        $this->title = '钱包明细列表';

        $student_list = (new HbhUsers())->getAllStudentList();
        $this->assign('student_list', $student_list);

        $lang = $_COOKIE['think_var'];
        $lang_key = 'label';
        if($lang == 'zh-cn'){
            $lang_key = 'label_cn';
        }
        $biz_type = [];
        foreach (HbhUserWalletDetail::bizType as $k => $item) {
            $temp['id'] = $k;
            $temp['text'] = $item[$lang_key];
            $biz_type[] = $temp;
        }
//pj($lang_key);
        $this->assign('biz_type', $biz_type);

        return $this->fetch();
    }

    //按时间，老师，课程来筛选
    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['user_id']) && !empty($param['user_id'])) {
            $where[] = ['user_id', '=', $param['user_id']];
        }
        if (isset($param['wallet_type']) && !empty($param['wallet_type'])) {
            $where[] = ['wallet_type', '=', $param['wallet_type']];
        }
        if (isset($param['biz_type']) && !empty($param['biz_type'])) {
            $where[] = ['biz_type', '=', $param['biz_type']];
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
        $list = (new HbhUserWalletDetail())->getList($op);
        $user_id_arr = array_values(array_unique(array_column($list['list'], 'user_id')));

        $teacher_list = (new HbhUsers())->getAllTeacherList();
        $teacher_name_list = array_column($teacher_list, 'name', 'id');

        $op_user['doPage'] = false;
        $op_user['where'][] = ['id', 'in', $user_id_arr];
        $student_list = (new HbhUsers())->getList($op_user)['list'];
//        $student_list = (new HbhUsers())->getAllStudentList();
        $student_list = array_column($student_list, null, 'id');
//pj($student_list);
        foreach ($list['list'] as &$item) {
            $student_name = $student_list[$item['user_id']]['name'] ?? '';
            $phone = $student_list[$item['user_id']]['phone'] ?? '';
            $item['student_name'] = "{$student_name}({$phone})";
            $item['biz_type_text'] = $item['biz_type'] == HbhUserWalletDetail::bizTypeRecharge ? Lang::get('BizTypeRecharge') :Lang::get('BizTypeDeduction');;
//            $item['is_unlimited_number_text'] = $item['is_unlimited_number'] == HbhBookCourse::is_unlimited_number_true ? Lang::get('Unlimited') :Lang::get('Limited');;
//            $item['status_text'] = HbhBookCourse::getStatusText($item['status']);
//            $item['status_confirm_text'] = HbhBookCourse::getStatusConfirmText($item['status']);
        }

        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }

}
