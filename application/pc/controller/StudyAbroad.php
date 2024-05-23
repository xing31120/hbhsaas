<?php


namespace app\pc\controller;


use app\common\model\HbhBookCourse;
use app\common\model\HbhCourse;
use app\common\model\HbhStudyAbroad;
use app\common\model\HbhStudyAbroadCat;
use app\common\model\HbhUsers;
use app\shop\controller\Course;
use think\Db;
use think\facade\Lang;

class StudyAbroad extends Base {



    function setWhere($cat_id){
//        $cat_id =  $data['cat_id'];
        $where[] = ['cat_id', '=', $cat_id];
        $where[] = ['status', '=', HbhStudyAbroad::status_true];
        return $where;
    }

    function schoolList(){
        $cat_info = (new HbhStudyAbroadCat())
            ->where('status', HbhStudyAbroadCat::status_true)
            ->order('id', 'asc')->findOrEmpty();
        $cat_first_id = $cat_info['id'] ?? 0;
        $cat_id = input('cat_id', $cat_first_id);

        $op['where'] = $this->setWhere($cat_id);
        $op['order'] = 'sort asc, id asc';
        $op['doPage'] = false;
        $list = (new HbhStudyAbroad())->getList($op);
//        $cat_list = (new HbhStudyAbroadCat())->getAllList();
//        $cat_list = array_column($cat_list, 'shop_name_en', 'id');

        foreach ($list['list'] as &$item) {
            $item['cat_name'] = $cat_info[$item['shop_name_en']] ?? '';
        }
        $this->assign('cat_id', $cat_id);
        $this->assign('cat_info', $cat_info);
        $this->assign('list', $list['list']);
        return $this->fetch();
    }

    function detail(){
        $id = input('id', 0);
        $info = (new HbhStudyAbroad())->info($id);
//        $info['text_detail'] = htmlspecialchars($info['text_detail']);
        $info['text_detail'] = htmlspecialchars_decode($info['text_detail']);

        $this->assign('info', $info);
        return $this->fetch();
    }


}
