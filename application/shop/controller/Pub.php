<?php
namespace app\shop\controller;

use app\common\model\HbhCourse;
use app\common\model\HbhCourseCat;
use app\shop\controller\Base;
use think\facade\Lang;

class Pub extends Base {

    function ajaxCourseByCat(){
        $cat_id =  input('cat_id', 0);
        $info = (new HbhCourseCat())->info($cat_id);
        if(empty($info))
            return adminOut(['msg' => Lang::get('ParameterError')]);

        $op['where'][] = ['category_id', '=', $cat_id];
        $op['doPage'] = false;
        $course_list = (new HbhCourse())->getList($op)['list'];
//        $course_list = array_column($course_list, null, 'id');
        $res = ['data'=>$course_list];

        return adminOut($res);
    }


}


