<?php

namespace app\pc\controller;

use app\common\model\HbhPageConfig;
use app\common\model\HbhUsers;

class School extends Base {

    function hbh() {
        $teacher_list = (new HbhUsers())->getAllTeacherList(1);
        $teacher_list = array_column($teacher_list, null, 'id');

        $background_img =(new HbhPageConfig())->where("type_id", HbhPageConfig::type_HBH_background)->findOrEmpty();
        $this->assign("background_img", $background_img['value']);

        $this->assign('teacher_list', $teacher_list);
        return $this->fetch();
    }

    function tnt() {
        $teacher_list = (new HbhUsers())->getAllTeacherList(2);
        $teacher_list = array_column($teacher_list, null, 'id');

        $background_img_1 =(new HbhPageConfig())->where("type_id", HbhPageConfig::type_TNT_background_1)->findOrEmpty();
        $this->assign("background_img_1", $background_img_1['value']);

        $background_img_2 =(new HbhPageConfig())->where("type_id", HbhPageConfig::type_TNT_background_2)->findOrEmpty();
        $this->assign("background_img_2", $background_img_2['value']);

        $background_img_3 =(new HbhPageConfig())->where("type_id", HbhPageConfig::type_TNT_background_3)->findOrEmpty();
        $this->assign("background_img_3", $background_img_3['value']);

        $this->assign('teacher_list', $teacher_list);
        return $this->fetch();
    }
}
