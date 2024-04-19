<?php

namespace app\shop\controller;

//联系我, 提交的表单
use app\common\model\HbhContactForm;
use app\common\model\HbhCourse;
use think\facade\Lang;

class Contactform extends Base {

    public function dataList(){
        $this->title = '表单信息';
        $course_list = (new HbhCourse())->getAllCourseList();
        $this->assign('course_list', $course_list);
        return $this->fetch();
    }

    public function setWhere(array $param)
    {
        $where = [];
        if (isset($param['name']) && !empty($param['name'])) {
            $where[] = ['name', 'like', '%' . $param['name'] . '%'];
        }
        if (isset($param['phone']) && !empty($param['phone'])) {
            $where[] = ['phone', 'like', '%' . $param['phone'] . '%'];
        }
        if (isset($param['is_call']) && $param['is_call'] != '') {
            $where[] = ['is_call', '=', $param['is_call']];
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
        $op['order'] = 'is_call asc, id desc';
        $list = (new HbhContactForm())->getList($op);

        $course_list = (new HbhCourse())->getAllCourseList();
        $course_name_list = array_column($course_list, 'name', 'id');
        foreach ($list['list'] as &$item) {
            $item['course_name'] = $course_name_list[$item['course_id']] ?? '';
            $item['is_call_text'] = $item['is_call'] == HbhContactForm::IS_CALL_WAIT ? Lang::get('IsCallWait') : Lang::get('IsCallEnd');
        }

        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }

    function ajaxSetShow(){
        $data = input();
        $update['is_call'] = $data['is_call'];
        $bool = (new HbhContactForm())->updateById($data['id'], $update);
        if($bool){
            $res['msg'] = Lang::get('OperateSuccess');
        }else{
            $res['msg'] = Lang::get('OperateFailed');
        }
        return adminOut($res);
    }


    public function del(){
        $data = input();
        $id = $data['id'] ?? 0;

        if(empty($id)){
            return adminOutError(['msg' => Lang::get('ParameterError')]);
        }

        $info = (new HbhContactForm())->where('id', $id)->findOrEmpty()->toArray();
        if(!isset($info) ){
            return adminOut(['msg' => Lang::get('ParameterError')]);
        }
        $bool = (new HbhContactForm())->del($id);
        if (!$bool) {
            return adminOut(['msg' => Lang::get('OperateFailed')]);
        }
        return adminOut(['msg' => Lang::get('OperateSuccess')]);
    }
}
