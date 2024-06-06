<?php
namespace app\common\model;
use app\common\model\basic\Single;
use app\common\model\basic\SingleSubData;

class HbhSjLog extends SingleSubData {
    public $mcName = 'hbh_sj_log_';
//    public $selectTime = 600;
//    public $mcTimeOut = 600;


    const type_add = 1;
    const type_update = 2;
    const type_del = 4;


    const type_text = [
        self::type_add => ['text' => '新增', 'text_en' => 'add'],
        self::type_update => ['text' => '更新', 'text_en' => 'update'],
        self::type_del => ['text' => '删除', 'text_en' => 'delete'],
    ];



    const controller_text = [
        'Member'        => ['text' => '会员', 'text_en' => 'Member'],
        'Course'        => ['text' => '课程', 'text_en' => 'Course'],
        'Classdetail'   => ['text' => '课时', 'text_en' => 'Class Detail'],
        'Plan'          => ['text' => '计划', 'text_en' => 'Plan'],
        'Bookcourse'    => ['text' => '预约', 'text_en' => 'Reservation'],
    ];

}
