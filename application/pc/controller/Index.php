<?php
namespace app\pc\controller;


//use think\Request;
use app\common\model\HbhContactForm;
use think\facade\Request;

class Index extends Base {

    function index() {
        return $this->fetch();
    }

    function contact_form(){
        $data = input();
        if($data['email'] && $data['phone'] ){
            $res = HbhContactForm::create($data);
        }


        // 3. 执行成功
        if ( $res ) {
            $this -> success('Send Success');
        }
    }

    function about() {
        return $this->fetch();
    }

    function about2() {
        return $this->fetch();
    }

    function contact() {
        return $this->fetch();
    }

    function index_dance_school(){
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

}
