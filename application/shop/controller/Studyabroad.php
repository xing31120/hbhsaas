<?php
namespace app\shop\controller;

use app\common\model\HbhStudyAbroad;
use app\common\model\HbhStudyAbroadCat;
use think\Db;
use think\facade\Lang;

class Studyabroad extends Base {

    //数据列表
    public function dataList(){
        $this->title = '留学信息';
        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['shop_name_en']) && !empty($param['shop_name_en'])) {
            $where[] = ['shop_name_en', 'like', '%' . $param['shop_name_en'] . '%'];
        }
        if (isset($param['shop_name']) && !empty($param['shop_name'])) {
            $where[] = ['shop_name', 'like', '%' . $param['shop_name'] . '%'];
        }
        if (isset($param['status']) && $param['status'] != '') {
            $where[] = ['status', '=', $param['status']];
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
        $op['order'] = 'sort asc,id asc';
        $list = (new HbhStudyAbroad())->getList($op);

        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }


    //进入新增或修改页面
    public function add(){
        return $this->fetch();
    }
    //删除操作(包含批量删除,使用del方法是为了删除对应的缓存)

    function ajaxSetShow(){
        $data = input();
        $update['status'] = $data['status'];
        $bool = (new HbhStudyAbroad())->updateById($data['id'], $update);
        if($bool){
            $res['msg'] = Lang::get('OperateSuccess');
        }else{
            $res['msg'] = Lang::get('OperateFailed');
        }
        return adminOut($res);
    }
    public function form(){
        $data = input();
        $id = $data['id'] ?? 0;
        $info = (new HbhStudyAbroad())->info($id);

        $cat_list =(new HbhStudyAbroadCat())->getAllList();

        $this->assign('info', $info);
        $this->assign('cat_list', $cat_list);
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
        $isAdmin = $this->isAdmin();
        if(!$isAdmin){
            return adminOutError(Lang::get('NoPermission'));
        }
        if(empty($id) || !isset($data['status'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }
        $model = new HbhStudyAbroad();
        $info = $model->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = $model->del($id);
        if (!$bool) {
            return adminOut(['msg' => Lang::get('OperateFailed')]);
        }
        return adminOut(['msg' => Lang::get('OperateSuccess')]);
    }

    /**
     * Notes:保存
     * @return \think\response\Json
     * User: qc DateTime: 2021/7/17 12:33
     */
    public function save()
    {
        $data = input();
//pj($data);
        $id = $data['id'] ?? 0;
        if(empty($data['shop_name_en'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }
        $isAdmin = $this->isAdmin();
        if(!$isAdmin){
            return adminOutError(Lang::get('NoPermission'));
        }
        $model = new HbhStudyAbroad();
        $is_exist = $model
            ->where('shop_name_en', $data['shop_name_en'])
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if (!empty($is_exist)) return adminOutError(Lang::get('DuplicateName'));


        $course_data['shop_id']     = $this->shop_id;
        $course_data['shop_name_en']        = $data['shop_name_en'];
        $course_data['cat_id'] = $data['cat_id'];
        $course_data['img_url'] = $data['img_url'] ?: '';
        $course_data['profile'] = $data['profile'] ?: '';
        $course_data['sort'] = $data['sort'] ?? 99;
        $course_data['text_detail'] = $data['text_detail'] ?: '';
        $course_data['create_time'] = time();
        if (empty($id)) {
            $course_id = $model->insertGetId($course_data);
        } else {
            $course_id = $model->updateById($id,  $course_data);
        }
//
//pj([$id, $course_data, $course_id]);
        if($course_id){
            return adminOut(Lang::get('OperateSuccess'));
        }

        return adminOut(Lang::get('OperateFailed'));
    }



}
