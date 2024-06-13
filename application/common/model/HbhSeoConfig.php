<?php
namespace app\common\model;
use app\common\model\basic\SingleSubData;

class HbhSeoConfig extends SingleSubData {
    public $mcName = 'hbh_seo_config2_';
//    public $selectTime = 600;
    public $mcTimeOut = 600;


    function infoByPath($path, $field = ''){
        if(!$this->mcOpen){
            $rs = $this->where(array('page_path' => $path))->findOrEmpty()->toArray();
            return $this->resetRs($rs, $field);
        }

        $mcKey = $this->mcName . '_' . $path;
        $rs = cache($mcKey);
        if ($rs === false || $rs === null) {
            $rs = $this->where(array('page_path' => $path))->findOrEmpty()->toArray();
            if(empty($rs)){ //如果是空seo, 直接返回id=1的 info
                $rs = $this->info(1);
                return $this->resetRs($rs, $field);
            }
            $time = $this->mcTimeOut > 0 ? $this->mcTimeOut : 0;
            cache($mcKey, $rs, $time);
        }
        return $this->resetRs($rs, $field);
    }


    function updateByPath($path, $update) {
        if ( empty($update)) {
            return false;
        }
        $info = $this->infoByPath($path);
        $where['page_path'] = $path;
        if (parent::update($update,$where) === false) {
            return false;
        }

        //删除缓存
        $this->delCachePath($path);
        return true;
    }


    function delCachePath($path = 0) {
        if($this->mcOpen){
            $mcKey = $this->mcName . '_' . $path;
            cache($mcKey, null);
        }
        return true;
    }
}
