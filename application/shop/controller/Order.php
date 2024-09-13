<?php
namespace app\shop\controller;

//use app\common\model\HbhCourse;
use app\common\model\HbhOrderPay;
use app\common\model\HbhProduct;
use app\common\model\HbhProductCategory;
use app\common\model\HbhSjLog;
use app\common\model\HbhUsers;
use think\Db;
use think\facade\Lang;

class Order extends Base {

    //数据列表
    public function dataList(){
        $this->title = '订单信息';

        $op['where'][] = ['status', '=', 1];
        $c_list = (new HbhProductCategory())->getList($op);
        $cat_list = $c_list['list'];
        $this->assign('cat_list', $cat_list);

        $student_list = (new HbhUsers())->getAllStudentList();
        $this->assign('student_list', $student_list);

        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['order_sn']) && !empty($param['order_sn'])) {
            $where[] = ['order_sn', 'like', '%' . $param['order_sn'] . '%'];
        }
        if (isset($param['user_id']) && !empty($param['user_id'])) {
            $where[] = ['user_id', '=', $param['user_id']];
        }
        if (isset($param['product_id']) && !empty($param['product_id'])) {
            $where[] = ['product_id', '=', $param['product_id']];
        }
        if (isset($param['order_status']) && !empty($param['order_status'])) {
            $where[] = ['order_status', '=', $param['order_status']];
        }
        return $where;
    }

    //异步获取列表数据
    public function ajaxList(){
        $data = input();
        $limit = 10;//每页显示的数量
//pj($this->setWhere($data));
        $op['where'] = $this->setWhere($data);
        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['limit'] = $data['limit'] ?? $limit;
        $op['order'] = 'id desc';
        $list = (new HbhOrderPay())->getList($op);

        $cat_list = (new HbhProductCategory())->getAllProductCatList($this->shop_id);
        $cat_list =  array_column($cat_list, null,'id');
//pj($cat_list);
        $product_list = (new HbhProduct())->getAllProductList($this->shop_id);
        $product_list =  array_column($product_list, null,'id');
        foreach ($list['list'] as &$item) {
//pj($product_list);

            $item['cat_name'] = $cat_list[$product_list[$item['product_id']]['product_category_id']]['name'] ?? '';
            $item['order_status_text'] = $item['order_status'] == HbhOrderPay::order_status_wait ? Lang::get('OrderStatusUnpaid') : Lang::get('OrderStatusPaid');
            $item['product_name'] = $product_list[$item['product_id']]['product_name'] ?? '';
            $item['pay_time'] = date('Y-m-d H:i', $item['pay_time']);
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
        $info = (new HbhOrderPay())->info($id);
        if ($this->request->isPost()) {
            $this->add_log($info, $data);
            $res = (new HbhOrderPay())->updateById($id, $data);
            if(!$res){
                return adminOutError(Lang::get('OperateFailed'));
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
        $info = (new HbhOrderPay())->info($id);

        $cat_list = (new HbhProductCategory())->getAllProductCatList();

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

        $info = (new HbhOrderPay())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhOrderPay())->del($id);
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
        $is_exist = (new HbhOrderPay())
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
            $course_id = (new HbhOrderPay())->insertGetId($course_data);
        } else {
            $info = (new HbhOrderPay())->info($id);
            $this->add_log($info, $course_data);
            $course_id =  (new HbhOrderPay())->updateById($id,  $course_data);
        }
//
//pj([$id, $course_data, $course_id]);
        if($course_id){
            return adminOut(Lang::get('OperateSuccess'));
        }

        return adminOut(Lang::get('OperateFailed'));
    }



}
