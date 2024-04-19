<?php
namespace app\pc\controller;



class Classes extends Base {

    function dance_classes() {
        return $this->fetch();
    }

    function single_classes() {
        return $this->fetch();
    }

    function schedule_classes() {
        return $this->fetch();
    }
}
