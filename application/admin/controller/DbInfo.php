<?php
namespace app\admin\controller;

class DbInfo extends Base {
    //进入新增或修改页面
    public function add(){
        return $this->fetch();
    }
    //新增和修改保存
    public function save(){
        
    }
    //删除操作(包含批量删除,使用del方法是为了删除对应的缓存)
    public function del(){
       
    }
    //数据列表
    public function dataList(){
        $this->title = '';
        return $this->fetch();
    }
    
    //异步获取列表数据
    public function ajaxList(){
        // $data = input();
        // $limit = 10;//每页显示的数量
        // if(isset($data['member_type']) && !empty($data['member_type'])){
        //     $op['where'][] = ['member_type','=',$data['member_type']];
        // }
        // if(isset($data['biz_uid']) && !empty($data['biz_uid'])){
        //     $op['where'][] = ['biz_uid','=',$data['biz_uid']];
        // }
        // if(isset($data['allinpay_uid']) && !empty($data['allinpay_uid'])){
        //     $op['where'][] = ['allinpay_uid','=',$data['allinpay_uid']];
        // }
        // if(isset($data['mobile']) && $data['mobile'] != ''){
        //     $op['where'][] = ['mobile','=',trim($data['mobile'])];
        // }
        // if(isset($data['name']) && $data['name'] != ''){
        //     $op['where'][] = ['name','=',trim($data['name'])];
        // }
        // if(isset($data['status']) && !empty($data['status'])){
        //     $op['where'][] = ['status','=',$data['status']];
        // }

        // $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        // $op['doPage'] = true;
        // $op['field'] = '*';
        // $op['limit'] = $data['limit'] ?? $limit;
        // $op['order'] = 'id desc';
        // $list = model('Users')->getList($this->appUid,$op);
        // $res = ['count'=>$list['count'],'data'=>$list['list']];
        // return adminOut($res);
    }

    public function index() {
        return $this->fetch();
    }

    
    
}
