<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhPageConfig extends SingleSubData {
    public $mcName = 'hbh_page_config_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;


    const type_homepage_video = 1;
    const type_list = [
        self::type_homepage_video => ["name_zh" => "首页视频", "name_en" => "Homepage Video",],
    ];



}
