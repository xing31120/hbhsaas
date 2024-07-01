<?php
namespace app\shop\controller;

use app\common\model\HbhPageConfig;
use app\common\model\HbhSeoConfig;
use think\Db;
use think\facade\Lang;

class Pageconfig extends Base {

    //数据列表
    public function dataList(){

        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['name_zh']) && !empty($param['name_zh'])) {
            $where[] = ['name_zh', 'like', '%' . $param['name_zh'] . '%'];
        }
        if (isset($param['name_en']) && !empty($param['name_en'])) {
            $where[] = ['name_en', 'like', '%' . $param['name_en'] . '%'];
        }
        return $where;
    }

    //异步获取列表数据
    public function ajaxList(){
        $data = input();
        $limit = 10;//每页显示的数量

        $op['where'] = $this->setWhere($data);
        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['limit'] = $data['limit'] ?? $limit;
        $op['order'] = 'id desc';
        $list = (new HbhPageConfig())->getList($op, 0);

        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }


    //进入新增或修改页面
    public function add(){
        return $this->fetch();
    }

    public function form()
    {
        $data = input();
        $id = $data['id'] ?? 0;
        $info = (new HbhPageConfig())->info($id);
        $list = HbhPageConfig::type_list;
//pj($list);
        $this->assign('info', $info);
        $this->assign('list', $list);
        return $this->fetch();
    }

    /**
     * Notes: 删除
     * @return \think\response\Json
     * User: qc DateTime: 2021/7/17 12:33
     */
    function del(){
        $data = input();
        $id = $data['id'] ?? 0;

        if(empty($id)){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }

        $info = (new HbhPageConfig())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhPageConfig())->del($id);
        if (!$bool) {
            return adminOut(['msg' => Lang::get('OperateFailed')]);
        }
        return adminOut(['msg' => Lang::get('OperateSuccess')]);
    }

    public function save()
    {
        $data = input();
        $id = $data['id'] ?? 0;
        if(empty($data['type_id'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }
        if(empty($data['value'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }
        $list = HbhPageConfig::type_list;

        $course_data['shop_id'] = $this->shop_id;
        $course_data['name_zh'] = $list[$data['type_id']]['name_zh'] ?? '';
        $course_data['name_en'] = $list[$data['type_id']]['name_en'] ?? '';
        $course_data['type_id'] = $data['type_id'];
        $course_data['value']   = $data['value'] ?? '';
        $course_data['create_time'] = time();
        if (empty($id)) {
            $course_id = (new HbhPageConfig())->insertGetId($course_data);
        } else {
            $course_id =  (new HbhPageConfig())->updateById($id,  $course_data);
        }

        if($course_id){
            return adminOut(Lang::get('OperateSuccess'));
        }

        return adminOut(Lang::get('OperateFailed'));
    }




}
