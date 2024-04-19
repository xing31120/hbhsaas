<?php

namespace app\admin\controller;

use app\common\service\OrderRefundService;
use app\common\service\UserService;
use think\App;

class OrderRefundHsb extends Base{

    private $service = null;
    public function __construct(App $app = null, OrderRefundService $service){
        parent::__construct($app);
        $this->service = $service;
    }

    //数据列表
    public function dataList(){
        $data = input();
        $confirmStatus = [
            0 => '待确认',
            1 => '入款确认'
        ];
        $this->assign('confirm_status', $confirmStatus);
        $this->assign('funName', 'refund_hsb');
        $ori_biz_order_no = $data['ori_biz_order_no'] ?? '';
        $this->assign('ori_biz_order_no',$ori_biz_order_no);
        return $this->fetch();
    }

    //设置搜索的where条件
    private function setWhere($data){
        $where = [];
//        $where[] = ['pay_status', '=', \app\common\model\OrderRefund::PAY_STATUS['ALL_IN_PAY_COMPLETE']];
        $where[] = ['type', '=', 2];
        if (isset($data['ori_biz_order_no']) && $data['ori_biz_order_no'] != '') $where[] = ['ori_biz_order_no', '=', trim($data['ori_biz_order_no'])];
        if (isset($data['allinpay_pay_no']) && $data['allinpay_pay_no'] != '') $where[] = ['allinpay_pay_no', '=', trim($data['allinpay_pay_no'])];
        if (isset($data['biz_order_no']) && $data['biz_order_no'] != '') $where[] = ['biz_order_no', '=', trim($data['biz_order_no'])];
        if(isset($data['shop_search'])&&$data['shop_search']!=""){
            $appUids = model('UsersApp')->where('app_name','like', '%' . trim($data['shop_search']) . '%')->column('app_uid');
            if(!empty($appUids)){
                $where[] = ['app_uid', 'in', $appUids];
            }else{
                $where[] = ['app_uid', 'in', 0];
            }
        }
        if(isset($data['user_search'])&&$data['user_search']!=''){
//            $result = UserService::getFinMemberIdBySearch($data['user_search']);
//            if(!empty($result['data'])) $where[] = ['biz_uid', 'in', $result['data']];
            $where[] = ['show_user_name','like','%' . trim($data['user_search']) . '%'];
        }
        if (isset($data['update_time']) && $data['update_time'] != ''){
            $time = explode('~', $data['update_time']);
            $where[] = ['update_time', 'between', [strtotime($time[0]), strtotime($time[1]) + 3600 * 24]];
        }
        return $where;
    }

    //异步获取列表数据
    public function ajaxList()
    {
        $data = input();
        $limit = 10;//每页显示的数量
        $op['where'] = $this->setWhere($data);
        $op['page'] = isset($data['page']) ? intval($data['page']) : 1;
        $op['doPage'] = true;
        $op['field'] = '*';
        $op['limit'] = $data['limit'] ?? $limit;
        $op['order'] = 'create_time desc, id desc';
        $list = $this->service->getList($this->appUid, $op);
        $res = ['count' => $list['count'], 'data' => $list['list']];
        return adminOut($res);
    }


}
