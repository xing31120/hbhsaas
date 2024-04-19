<?php
namespace app\admin\controller;
use think\Controller;

class Demo extends Controller {
    //进入新增或修改页面
    public function add(){
        $data = input();
        $info = [];
        if($data['id'] > 0){
            $info = model('DemoUser')->info($data['id']);
            if(empty($info)){
                $this->error('信息不存在');
            }
        }
        $this->assign('label',model('DemoUser')->label);
        $this->assign('info',$info);
        return $this->fetch();
    }
    //新增和修改保存
    public function save(){
        $data = input();
        if(isset($data['username'])){
            $array['username'] = trim($data['username']);
        }
        if(isset($data['name'])){
            $array['name'] = trim($data['name']);
        }
        if(isset($data['loginip'])){
            $array['loginip'] = trim($data['loginip']);
        }
        if(isset($data['status'])) {
            $array['status'] = $data['status'];
        }
        if(isset($data['password'])) {
            $array['password'] = md5($data['password'] . config('extend.SALT'));
        }
        $bool = false;
        $id = 0;
        if($data['id'] > 0){
            $bool = model('DemoUser')->updateById($data['id'],$array);
        }else{
            $id = model('DemoUser')->insert($array);
        }
        if($bool || $id > 0){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //删除操作(包含批量删除,使用del方法是为了删除对应的缓存)
    public function del(){
        $data = input();
        $bool = false;
        if(isset($data['id'])){
            $bool = model('DemoUser')->delAll($data['id']);
        }
        if($bool){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }
    //数据列表
    public function dataList(){
        return $this->fetch();
    }
    //异步获取列表数据
    public function ajaxList(){
        $data = input();
        $limit = 10;//每页显示的数量
        if(isset($data['username']) && $data['username'] != ''){
            $op['where'][] = ['username','=',trim($data['username'])];
        }
        if(isset($data['name']) && $data['name'] != ''){
            $op['where'][] = ['name','=',trim($data['name'])];
        }
        if(isset($data['loginip']) && $data['loginip'] != ''){
            $op['where'][] = ['loginip','=',$data['loginip']];
        }
        if(isset($data['status']) && !empty($data['status'])){
            $op['where'][] = ['status','=',$data['status']];
        }
        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['doPage'] = true;
        $op['field'] = '*';
        $op['limit'] = $data['limit'] ?? $limit;
        $op['order'] = 'id desc';
        $list = model('DemoUser')->getList($op);
        $label = model('DemoUser')->label;
        foreach ($list['list'] as $k => $v) {
            unset($list['list'][$k]['password']);
            $list['list'][$k]['logintime'] = $v['logintime'] > 0 ? date('Y-m-d H:i:s',$v['logintime']) : '-';
            $list['list'][$k]['label'] = $v['label'] > 0 ? $label[$v['label']] : '';
        }
        $res = ['count'=>$list['count'],'data'=>$list['list']];
        return adminOut($res);
    }

    public function index() {
        return $this->fetch();
    }

    public function login() {
        return $this->fetch();
    }

    public function register() {
        return $this->fetch();
    }

    public function console() {
        return $this->fetch();
    }
    public function homepage2() {
        return $this->fetch();
    }
    
    public function ueditor() {
        return $this->fetch();
    }
    public function button() {
        return $this->fetch();
    }
    public function element() {
        return $this->fetch();
    }
    public function group() {
        return $this->fetch();
    }
    public function nav() {
        return $this->fetch();
    }
    public function tabs() {
        return $this->fetch();
    }
    public function tablestatic() {
        return $this->fetch();
    }
    public function simple() {
        return $this->fetch();
    }
    public function auto() {
        return $this->fetch();
    }
    public function form() {
        return $this->fetch();
    }
    public function operate() {
        return $this->fetch();
    }
    public function demo1() {
        return $this->fetch();
    }
    public function demo2() {
        return $this->fetch();
    }
    public function carousel() {
        return $this->fetch();
    }
    public function goodslist() {
        return $this->fetch();
    }
    // public function login() {
    //     return $this->fetch();
    // }
    public function tips() {
        return $this->fetch();
    }
    public function pic() {
        return $this->fetch();
    }
    public function photo_gallery() {
        return $this->fetch();
    }
    public function album() {
        return $this->fetch();
    }
    public function content() {
        return $this->fetch();
    }
    public function crud() {
        return $this->fetch();
    }
    public function goodsSet() {
        return $this->fetch();
    }
    public function goodsManage() {
        return $this->fetch();
    }
    public function accordion() {
        return $this->fetch();
    }
    public function goodsAdd() {
        return $this->fetch();
    }
    public function step1() {
        return $this->fetch();
    }
    public function step2() {
        return $this->fetch();
    }
    public function crudadd() {
        return $this->fetch();
    }
    public function listform() {
        return $this->fetch();
    }
	public function brand() {
        return $this->fetch();
    }
    public function video() {
        return $this->fetch();
    }
    
}
