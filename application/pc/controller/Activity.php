<?php


namespace app\pc\controller;


use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhUsers;
use app\shop\controller\Course;
use think\Db;
use think\facade\Lang;

class Activity extends Base {

    function index(){
        return $this->fetch();
    }

}
