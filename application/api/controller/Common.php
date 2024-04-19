<?php
namespace app\api\controller;
use think\Controller;
use thans\jwt\facade\JWTAuth;
use app\common\tools\SysEnums;

class Common extends Controller{



    public function esSearch(){
        $data = input();
        if(isset($data['es']) && $data['es'] == 1){
            model('app\common\model\basic\Es')->es();
            return 1;
        }
        $kw = isset($data['kw']) ? $data['kw'] : "";
        $r = model('app\common\model\basic\Es')->search_doc($kw);  //4.搜索结果
        echo '<pre>';
        print_r($r);
        echo '</pre>';
        return 1;
    }


}
