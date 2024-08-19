<?php
namespace app\shop\controller;

//use app\common\model\HbhCourse;
use app\common\model\HbhProduct;
use app\common\model\HbhProductCategory;
use app\common\model\HbhSjLog;
use think\Db;
use think\facade\Lang;

class Product extends Base {

    //数据列表
    public function dataList(){
        $this->title = '商品信息';

        $op['where'][] = ['status', '=', 1];
        $c_list = (new HbhProductCategory())->getList($op);
        $cat_list = $c_list['list'];
        $this->assign('cat_list', $cat_list);

        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['product_name']) && !empty($param['product_name'])) {
            $where[] = ['product_name', 'like', '%' . $param['product_name'] . '%'];
        }
        if (isset($param['product_code']) && !empty($param['product_code'])) {
            $where[] = ['product_code', 'like', '%' . $param['product_code'] . '%'];
        }
        if (isset($param['status']) && $param['status'] != '') {
            $where[] = ['status', '=', $param['status']];
        }
        if (isset($param['product_category_id']) && !empty($param['product_category_id'])) {
            $where[] = ['product_category_id', '=', $param['product_category_id']];
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
        $list = (new HbhProduct())->getList($op);

        $cat_list = (new HbhProductCategory())->getAllProductCatList($this->shop_id);
        foreach ($list['list'] as &$item) {
            $item['cat_name'] = $cat_list[$item['product_category_id']]['name'] ?? '';
        }

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
        $info = (new HbhProduct())->info($id);
        if ($this->request->isPost()) {
            $this->add_log($info, $data);
            $res = (new HbhProduct())->updateById($id, $data);
            if(!$res){
                return adminOutError(Lang::get('OperateFailed'));
            }
            return adminOut(['msg' => Lang::get('OperateSuccess')]);
        }


        $this->assign('info', $info);
        return $this->fetch();

    }
    //删除操作(包含批量删除,使用del方法是为了删除对应的缓存)

    function ajaxSetShow(){
        $data = input();
        $update['status'] = $data['status'];
        $bool = (new HbhProduct())->updateById($data['id'], $update);
        if($bool){
            $res['msg'] = Lang::get('OperateSuccess');
        }else{
            $res['msg'] = Lang::get('OperateFailed');
        }
        return adminOut($res);
    }
    public function form()
    {
        $data = input();
        $id = $data['id'] ?? 0;
        $info = (new HbhProduct())->info($id);

        $op['where'][] = ['status', '=', 1];
        $c_list = (new HbhProductCategory())->getList($op);
        $cat_list = $c_list['list'];

        $this->assign('cat_list', $cat_list);
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
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }

        $info = (new HbhProduct())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhProduct())->del($id);
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
        if(empty($data['product_name'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }
        $isAdmin = $this->isAdmin();
        if(!$isAdmin){
            return adminOutError(Lang::get('NoPermission'));
        }
        $is_exist = (new HbhProduct())
            ->where('product_name', $data['product_name'])
//            ->where('shop_id', $this->shop_id)
            ->when(!empty($id), function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->find();
        if (!empty($is_exist)) return adminOutError(Lang::get('DuplicateName'));


        $course_data['shop_id']     = $this->shop_id;
        $course_data['product_name']        = $data['product_name'];
        $course_data['product_category_id'] = $data['category_id'];
        $course_data['desc'] = $data['desc'] ?? '';
        $course_data['amount'] = $data['amount'] ?? 0;
        $course_data['class_num'] = $data['class_num'] ?: 1;
        $course_data['update_time'] = time();
        if (empty($id)) {
            $this->add_log([], $course_data, HbhSjLog::type_add);
            $course_id = (new HbhProduct())->insertGetId($course_data);
        } else {
            $info = (new HbhProduct())->info($id);
            $this->add_log($info, $course_data);
            $course_id =  (new HbhProduct())->updateById($id,  $course_data);
        }
//
//pj([$id, $course_data, $course_id]);
        if($course_id){
            return adminOut(Lang::get('OperateSuccess'));
        }

        return adminOut(Lang::get('OperateFailed'));
    }



}
