<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhPageConfig extends SingleSubData {
    public $mcName = 'hbh_page_config_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;


    const type_homepage_video = 1;
    const type_HBH_background = 2;
    const type_TNT_background_1 = 3;
    const type_TNT_background_2 = 4;
    const type_TNT_background_3 = 5;
    const type_list = [
        self::type_homepage_video => ["name_zh" => "首页视频", "name_en" => "Homepage Video",],
        self::type_HBH_background => ["name_zh" => "HBH背景图", "name_en" => "HBH Background image",],
        self::type_TNT_background_1 => ["name_zh" => "T.nt背景图-1", "name_en" => "T.nt Background image 1",],
        self::type_TNT_background_2 => ["name_zh" => "T.nt背景图-2", "name_en" => "T.nt Background image 2",],
        self::type_TNT_background_3 => ["name_zh" => "T.nt背景图-3", "name_en" => "T.nt Background image 3",],
    ];



}
