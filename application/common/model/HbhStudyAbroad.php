<?php
namespace app\common\model;
use app\common\model\basic\Single;

class HbhStudyAbroad extends Single {
    public $mcName = 'hbh_study_abroad_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;

    const status_true = 1;
    const status_false = 4;

    function getTreeList(){
        $cat_list = (new HbhStudyAbroadCat())->getAllList();

        $op['where'][] = ['status','=', self::status_true];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'sort asc';
        $res = $this->getList($op);

        $list = [];
        foreach ($cat_list as $cat) {
            $cat['child'] = [];
            foreach ($res['list'] as $item) {
                if($item['cat_id'] == $cat['id']){
                    $cat['child'][$item['id']] = $item;
                }
            }
            $list[] = $cat;
        }

        return $list;
    }
}
