<?php


namespace app\push\service;

use app\common\model\OrderEntry;
use app\common\model\OrderProcess;

use app\common\model\OrderRefund;
use app\common\model\OrderWithdraw;
use app\common\model\UsersApp;
use app\common\service\workSendMessage\WorkSendMessageService;
use GuzzleHttp\Client;

/**
 * Class OrderService
 * @package app\push\service
 */
class SendWxWorkService extends BaseService {

    public $backUrl = '';

    const ADD_PAY_ORDER = 1;//支付单下单通知，
    const ADD_PAY_SUCCESS = 2;//支付成功通知
    const ADD_PAY_REFUND = 3;//退款发起通知
    const ADD_PAY_REFUND_SUCCESS = 4;//退款回调通知
    const ADD_PAY_RECONCILIATION = 5;//对账单推送通知
    const PAY_REFUND_ERROR = 6;//退款失败通知

    /**
     * 发送企微通知
     * @param $data
     * @return bool
     * User: cwh  DateTime:2021/10/30 10:55
     */
    function sendWxWork($data){
        $content = $this->formatData($data);
        if(empty($content)){
            return 'success';
        }
        (new WorkSendMessageService($data['workKey']))->sendMarkDown($content);
        return 'success';
    }

    /**
     * 组装数据
     * User: cwh  DateTime:2021/10/30 11:11
     */
    public function formatData($data){
        $time = date("Y-m-d H:i:s");
        switch($data['type']){
            case self::ADD_PAY_ORDER:
                $orderEntry = new OrderEntry();
                $info = $orderEntry->infoByBizOrderNo($data['appId'],$data['bizOrderNo']);
                $status = $orderEntry::orderEntryStatus[$info['order_entry_status']] ?? '';
                //支付单下单通知，
                $content = <<<EOF
### <font color="warning">支付单下单通知</font>
> 主订单：{$info['biz_order_no']}
> 惠市宝单号：{$info['allinpay_order_no']}
> 金额：{$info['amount']}分
> 状态：{$status}
> app_uid：{$info['app_uid']}
> uid：{$info['uid']} 
> 时间：{$time}
EOF;
                break;
            case self::ADD_PAY_SUCCESS:
                //支付成功通知，
                $orderEntry = new OrderEntry();
                $info = $orderEntry->infoByBizOrderNo($data['appId'],$data['bizOrderNo']);
                $status = $orderEntry::orderEntryStatus[$info['order_entry_status']] ?? '';
                $content = <<<EOF
### <font color="warning">支付回调通知</font>
> 主订单：{$info['biz_order_no']}
> 惠市宝单号：{$info['allinpay_order_no']}
> 金额：{$info['amount']}分
> app_uid：{$info['app_uid']}
> 状态：{$status}
> uid：{$info['uid']} 
> 时间：{$time}
EOF;
                break;
            case self::ADD_PAY_REFUND:
                //退款发起通知
                $orderRefund = new OrderRefund();
                $info = $orderRefund->infoByBizOrderNo($data['appId'], $data['bizOrderNo']);
                $status = $orderRefund::orderRefundStatus[$info['pay_status']] ?? '';
                $content = <<<EOF
### <font color="warning">退款发起通知</font>
> 主订单：{$info['ori_biz_order_no']}
> 退款单号：{$info['biz_order_no']}
> 惠市宝单号：{$info['allinpay_order_no']}
> 金额：{$info['amount']}分
> app_uid：{$info['app_uid']}
> 状态：{$status}
> uid：{$info['uid']} 
> 时间：{$time}
EOF;
                break;
            case self::ADD_PAY_REFUND_SUCCESS:
                //退款成功通知
                $orderRefund = new OrderRefund();
                $info = $orderRefund->infoByBizOrderNo($data['appId'], $data['bizOrderNo']);
                $status = $orderRefund::orderRefundStatus[$info['pay_status']] ?? '';
                $content = <<<EOF
### <font color="warning">退款回调通知</font>
> 主订单：{$info['ori_biz_order_no']}
> 退款单号：{$info['biz_order_no']}
> 惠市宝单号：{$info['allinpay_order_no']}
> 金额：{$info['amount']}分
> app_uid：{$info['app_uid']}
> 状态：{$status}
> uid：{$info['uid']} 
> 时间：{$time}
EOF;
                break;
            case self::PAY_REFUND_ERROR:
                //退款异常通知
                $orderRefund = new OrderRefund();
                $info = $orderRefund->infoByBizOrderNo($data['appId'], $data['bizOrderNo']);
                $status = $orderRefund::orderRefundStatus[$info['pay_status']] ?? '';
                $content = <<<EOF
### <font color="warning">退款异常通知</font>
> 主订单：{$info['ori_biz_order_no']}
> 退款单号：{$info['biz_order_no']}
> 惠市宝单号：{$info['allinpay_order_no']}
> 金额：{$info['amount']}分
> app_uid：{$info['app_uid']}
> 状态：{$status}
> uid：{$info['uid']} 
> 时间：{$time}
EOF;
                break;
            case self::ADD_PAY_RECONCILIATION:
                //对账通知
                $content = <<<EOF
### <font color="warning">慧市宝对账单，对账异常，请查看</font>
> 主订单：{$data['biz_order_no']}
> 可分账金额：{$data['remain_amount']}
> 建行分账金额：{$data['ccb_reconciliation_amount']}
>> 分账明细订单1：{$data['orderProcessList'][0]['biz_order_process_no']}
>> 商家编号：{$data['orderProcessList'][0]['mkt_mrch_id']}
>> 分账系统分账金额：{$data['orderProcessList'][0]['remain_amount']}
>> 建行分账金额：{$data['orderProcessList'][0]['ccb_reconciliation_amount']}
>> 分账明细订单2：{$data['orderProcessList'][1]['biz_order_process_no']}
>> 商家编号：{$data['orderProcessList'][1]['mkt_mrch_id']}
>> 分账系统分账金额：{$data['orderProcessList'][1]['remain_amount']}
>> 建行分账金额：{$data['orderProcessList'][1]['ccb_reconciliation_amount']}
> 时间：{$time}
EOF;
                break;
                default :
                    $content='';
                    break;
        }
        return $content;
    }

}