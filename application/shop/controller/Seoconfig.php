<?php
namespace app\shop\controller;

use app\common\model\HbhSeoConfig;
use think\Db;
use think\facade\Lang;

class Seoconfig extends Base {

    //数据列表
    public function dataList(){

        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['page_name_zh']) && !empty($param['page_name_zh'])) {
            $where[] = ['page_name_zh', 'like', '%' . $param['page_name_zh'] . '%'];
        }
        if (isset($param['page_name_en']) && !empty($param['page_name_en'])) {
            $where[] = ['page_name_en', 'like', '%' . $param['page_name_en'] . '%'];
        }
        if (isset($param['path']) && !empty($param['path'])) {
            $where[] = ['page_path', 'like', '%' . $param['path'] . '%'];
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
        $list = (new HbhSeoConfig())->getList($op, 0);

        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }


    //进入新增或修改页面
    public function add(){
        return $this->fetch();
    }

    //新增和修改保存
    public function edit(){
        $data = input();
        $id = $data['id'];
        $info = (new HbhSeoConfig())->info($id);
        if ($this->request->isPost()) {
            $res = (new HbhSeoConfig())->updateById($id, $data);
            if(!$res){
                return adminOut(Lang::get('OperateFailed'));
            }
            return adminOut(['msg' => Lang::get('OperateSuccess')]);
        }


        $this->assign('info', $info);
        return $this->fetch();

    }

    public function form()
    {
        $data = input();
        $id = $data['id'] ?? 0;
        $info = (new HbhSeoConfig())->info($id);
        $this->assign('info', $info);
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

        $info = (new HbhSeoConfig())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhSeoConfig())->del($id);
        if (!$bool) {
            return adminOut(['msg' => Lang::get('OperateFailed')]);
        }
        return adminOut(['msg' => Lang::get('OperateSuccess')]);
    }

    public function save()
    {
        $data = input();
        $id = $data['id'] ?? 0;
        if(empty($data['page_name_zh'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }
        if(empty($data['page_name_en'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }
        if(empty($data['page_path'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }

        $is_exist = (new HbhSeoConfig())
            ->where('page_path', $data['page_path'])
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if (!empty($is_exist)) return adminOutError(Lang::get('DuplicatePagePath'));


        $course_data['page_name_zh']    = $data['page_name_zh'];
        $course_data['page_name_en']    = $data['page_name_en'];
        $course_data['page_path']       = $data['page_path'];
        $course_data['seo_title']       = $data['seo_title'] ?? '';
        $course_data['seo_keywords']    = $data['seo_keywords'] ?? '';
        $course_data['seo_description'] = $data['seo_description'] ?? '';
        $course_data['create_time'] = time();
        if (empty($id)) {
            $course_id = (new HbhSeoConfig())->insertGetId($course_data);
        } else {
            $course_id =  (new HbhSeoConfig())->updateById($id,  $course_data);
        }

        if($course_id){
            return adminOut(Lang::get('OperateSuccess'));
        }

        return adminOut(Lang::get('OperateFailed'));
    }



    function ajaxSetShow(){
        $data = input();
        $update['status'] = $data['status'];
        $bool = (new HbhSeoConfig())->updateById($data['id'], $update);
        if($bool){
            $res['msg'] = Lang::get('OperateSuccess');
        }else{
            $res['msg'] = Lang::get('OperateFailed');
        }
        return adminOut($res);
    }



}
