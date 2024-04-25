<?php
namespace app\shop\controller;

use app\common\model\HbhClassTime;
use think\Db;
use think\facade\Lang;

class Classtime extends Base {

    //数据列表
    public function dataList(){
        $this->title = '上课时间配置';
        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
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
        $list = (new HbhClassTime())->getList($op);

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
        $info = (new HbhClassTime())->info($id);
        if ($this->request->isPost()) {
            $res = (new HbhClassTime())->updateById($id, $data);
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
        $bool = (new HbhClassTime())->updateById($data['id'], $update);
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
        $info = (new HbhClassTime())->info($id);

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

        $info = (new HbhClassTime())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhClassTime())->del($id);
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
        $id = $data['id'] ?? 0;
        $isAdmin = $this->isAdmin();
        if(!$isAdmin){
            return adminOutError(Lang::get('NoPermission'));
        }
        if(empty($data['start_time']) || empty($data['end_time'])){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }
        if (empty($id)) {
            $data['shop_id'] = $this->shop_id;
            $course_id = (new HbhClassTime())->insertGetId($data);
        } else {
            $course_id =  (new HbhClassTime())->updateById($id,  $data);
        }

        if($course_id){
            return adminOut(Lang::get('OperateSuccess'));
        }

        return adminOut(Lang::get('OperateFailed'));
    }



}
