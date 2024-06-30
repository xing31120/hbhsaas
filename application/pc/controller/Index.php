<?php
namespace app\pc\controller;


//use think\Request;
use app\common\model\HbhContactForm;
use app\common\model\HbhPageConfig;
use app\common\model\HbhShop;
use think\facade\Lang;
use think\facade\Request;

class Index extends Base {

    function index() {
        $video_row =(new HbhPageConfig())->where("type_id", 1)->findOrEmpty();
        $this->assign("video", $video_row['value']);
//pj($video);
        return $this->fetch();
    }

    function contact() {
        $shop_list = (new HbhShop())->getAllShopList();
        $this->assign('shop_list', $shop_list);
        return $this->fetch();
    }
    function contact_form(){
        $data = input();
        if(empty($data['phone'])){
            return adminOutError(Lang::get('PleaseEnterYourPhoneNumber'));
        }
        if(empty($data['email'])){
            return adminOutError(Lang::get('PleaseEnterYourEmail'));
        }
        if(empty($data['name'])){
            return adminOutError(Lang::get('PleaseEnterYourName'));
        }
        if(empty($data['shop_id'])){
            return adminOutError(Lang::get('PleaseSelectASchool'));
        }

        $res = HbhContactForm::create($data);
        // 3. 执行成功
        if ( $res ) {
            return adminOut(Lang::get('Success'));
        }
    }

    function about() {
        return $this->fetch();
    }

    // 翰德学校 介绍
    function shop_1(){
        return $this->fetch();
    }

    //活动详情
    function event_detail()
    {
        return $this->fetch();
    }

    function event_detail_1()
    {
        return $this->fetch();
    }

    //留学介绍
    function study_abroad()
    {
        return $this->fetch();
    }


    function index_dance_school(){
        return $this->fetch();
    }

    function single_event(){
        return $this->fetch();
    }

    function index_ballet_studio(){
        return $this->fetch();
    }

    function index_onepage(){
        return $this->fetch();
    }

    function index_onepage_2(){
        return $this->fetch();
    }

    function index_onepage_3(){
        return $this->fetch();
    }
    function about2() {
        return $this->fetch();
    }
}
