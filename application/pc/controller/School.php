<?php

namespace app\pc\controller;

use app\common\model\HbhUsers;

class School extends Base {

    function hbh() {
        $teacher_list = (new HbhUsers())->getAllTeacherList(1);
        $teacher_list = array_column($teacher_list, null, 'id');

        $this->assign('teacher_list', $teacher_list);
        return $this->fetch();
    }

    function tnt() {
        $teacher_list = (new HbhUsers())->getAllTeacherList(2);
        $teacher_list = array_column($teacher_list, null, 'id');

        $this->assign('teacher_list', $teacher_list);
        return $this->fetch();
    }
}
