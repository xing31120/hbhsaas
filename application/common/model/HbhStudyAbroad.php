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
        $op['where'][] = ['status','=', self::status_true];
        $op['doPage'] = false;
        $op['field'] = '*';
        $op['order'] = 'level asc';
        $res = $this->getList($op);

        $list = [];
        foreach ($res['list'] as $item) {
            if($item['level'] == 1 && $item['p_id'] == 0){
                $item['child'] = [];
                $list[$item['id']] = $item;
                continue;
            }

            $pid = $item['p_id'];
            $list[$pid]['child'][$item['id']] = $item;

        }




        return $list;
    }
}
