<?php
namespace app\shop\controller;


use app\common\model\HbhPointProductCategory;
use app\common\model\HbhPointProductTags;
use app\common\model\HbhSjLog;
use think\Db;
use think\facade\Lang;

class PointProductTag extends Base {

    //数据列表
    public function dataList(){
        $this->title = '积分商品标签';
        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['name']) && !empty($param['name'])) {
            $where[] = ['name', 'like', '%' . $param['name'] . '%'];
        }
        if (isset($param['status']) && $param['status'] !== '') {
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
        $op['order'] = 'id desc';
        $list = (new HbhPointProductTags())->getList($op);

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
        $info = (new HbhPointProductTags())->info($id);
        if ($this->request->isPost()) {
            $res = (new HbhPointProductTags())->updateById($id, $data);
            if(!$res){
                return adminOutError(Lang::get('OperateFailed'));
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
        $info = (new HbhPointProductTags())->info($id);
        $this->assign('info', $info);
        return $this->fetch();
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
//        $isAdmin = $this->isAdmin();
//        if(!$isAdmin){
//            return adminOutError(Lang::get('NoPermission'));
//        }
        $model = new HbhPointProductTags();

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
        $course_data['status']      = $data['status'];
        $course_data['max_num']     = $data['max_num'];
//        $course_data['category_id'] = $data['category_id'];
//        $course_data['description'] = $data['description'];
        $course_data['create_time'] = time();
        if (empty($id)) {
            $this->add_log([], $course_data, HbhSjLog::type_add);
            $course_id = (new HbhPointProductTags())->insertGetId($course_data);
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
