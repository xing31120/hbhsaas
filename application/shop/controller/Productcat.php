<?php
namespace app\shop\controller;

use app\common\model\HbhCourseCat;
use app\common\model\HbhSjLog;
use think\Db;
use think\facade\Lang;

class Productcat extends Base {

    //数据列表
    public function dataList(){
        $this->title = '产品分类';
        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['name']) && !empty($param['name'])) {
            $where[] = ['name', 'like', '%' . $param['name'] . '%'];
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
        $list = model('HbhCourseCat')->getList($op);

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
        $info = model('HbhCourseCat')->info($id);
        if ($this->request->isPost()) {
            $res = model('HbhCourseCat')->updateById($id, $data);
            if(!$res){
                return adminErrorOut(Lang::get('OperateFailed'));
            }
            return adminOut(['msg' => Lang::get('OperateSuccess')]);
        }


        $this->assign('info', $info);
        return $this->fetch();

    }
    //删除操作(包含批量删除,使用del方法是为了删除对应的缓存)


    public function form()
    {
        $data = input();
        $id = $data['id'] ?? 0;
        $info = model('HbhCourseCat')->info($id);

//        $op['where'][] = ['status', '=', 1];
//        $c_list = model('HbhCourseCat')->getList($op);
//        $cat_list = $c_list['list'];

//        $this->assign('cat_list', $cat_list);
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
        $isAdmin = $this->isAdmin();
        if(!$isAdmin){
            return adminOutError(Lang::get('NoPermission'));
        }
        if(empty($id) || !isset($data['status'])){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }

        $info = model('HbhCourseCat')->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = model('HbhCourseCat')->del($id);
        if (!$bool) {
            return adminOut(['msg' => Lang::get('OperateFailed')]);
        }
        $this->add_log(['id' => $id], [], HbhSjLog::type_del);
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
        $id = $data['id'] ?? 0;
        $isAdmin = $this->isAdmin();
        if(!$isAdmin){
            return adminOutError(Lang::get('NoPermission'));
        }
        $model = new HbhCourseCat();

        $is_exist = $model
            ->where('name', $data['name'])
            ->where('shop_id', $this->shop_id)
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if (!empty($is_exist)) return adminOut('Duplicate Name');


        $course_data['shop_id']     = $this->shop_id;
        $course_data['name']        = $data['name'];
//        $course_data['category_id'] = $data['category_id'];
//        $course_data['description'] = $data['description'];
        $course_data['create_time'] = time();
        if (empty($id)) {
            $this->add_log([], $course_data, HbhSjLog::type_add);
            $course_id = (new HbhCourseCat())->insertGetId($course_data);
        } else {
            $info = $model->info($id);
            $this->add_log($info, $course_data);
            $course_id =  $model->updateById($id,  $course_data);
        }
        if($course_id){
            return adminOut(Lang::get('OperateSuccess'));
        }

        return adminOut(Lang::get('OperateFailed'));
    }



}
