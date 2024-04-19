<?php
/**
 * 消费
 * Date: 2020-12-2
 * Time: 16:47
 */

namespace app\common\amqp;

use app\common\model\MqErrorLog;
use app\common\service\workSendMessage\WorkSendMessageService;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use think\facade\Config;

class BizConsumer extends BaseMq{


    function shutdown($channel, $connection){
        $channel->close();
        $connection->close();
    }

    function process_message($message){

        $res = false;
        $data = json_decode($message->body, true);
//var_dump($data);exit;
        $tempData = $data;
        unset($tempData['serviceClass']);
        unset($tempData['fun']);
        //数据入库
        //调用 push模块的服务 (例如: MemberService) 的指定方法 ($data['fun'])
        $className = '\\app\\push\\service\\'.$data['serviceClass'];
        $ConsumeService = new $className();
        if (isset($data['fun'])) {
            $res = call_user_func([$ConsumeService, $data['fun']], $tempData);
        }
//var_dump($data);
var_dump($res);
var_dump(date('m-d H:i:s'));
var_dump('-------------------------');
        if(!$res){ //消费失败, 重新放回队列
            $this->alarmMq($data, $className, $ConsumeService);

            //记录失败日志
            $modelData['fun'] = $data['fun'];
            $modelData['msg_json'] = json_encode($tempData);
            $mqErrorLog = new MqErrorLog();
            $mqErrorLog->isUpdate(false)->save($modelData);
        }
        //手动发送ack
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        //重新打回队列
//        $message->delivery_info['channel']->basic_recover(true);

        // Send a message with the string "quit" to cancel the consumer.
        if ($message->body === 'quit') {
            $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
        }


    }

    //启动
    function start(){

//        const exchange = 'finance_exchange_';
//        const routingKey = 'finance_routing_';
//        const queueName = 'finance_queue_biz_push_';
//        const consumerTag = 'finance_consumer';

        $prefix       = Config::get('amqp.prefix');
        $exchange     = $prefix.'exchange_';        //交换机名称
        $routingKey   = $prefix.'routing_';         //路由秘钥
        $queueName    = $prefix.'queue_biz_push_';  //队列名称
        $consumerTag  = $prefix.'consumer_';        //标签
var_dump($exchange);
        $channel = $this->channel();
        $channel->queue_declare($queueName, $this->passive, $this->durable, $this->exclusive, $this->autoDelete);
        $channel->exchange_declare($exchange, $this->type, $this->passive, $this->durable, $this->autoDelete);
        $channel->queue_bind(
            $queueName,
            $exchange,
            $routingKey
        );
        $channel->basic_qos(null, 1, null);

//var_dump($this->noAck);
        $channel->basic_consume($queueName, $consumerTag, $this->nolocal, $this->noAck,$this->exclusive,$this->nowait, array($this, 'process_message'));

        register_shutdown_function(array($this, 'shutdown'), $channel, $this->handler);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        file_put_contents("abc.log",'starting_end');
    }

    function  test(){
//        var_dump(Config::get('amqp.'));
//        file_put_contents("abc.log",'test@@@@@@@@@@@@@ starting ');
    }


    function alarmMq($data, $className, $ConsumeService){
        $is_test = Config::get('amqp.is_test');
        $huanJing = $is_test ? '测试环境' : '正式环境';
//        https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=542cf8ff-6add-4e1e-9eb0-0364b12e0868

        $key = '542cf8ff-6add-4e1e-9eb0-0364b12e0868';
        try {
            //将异常所在文件,以及行数 通知开发者 方便排查异常原因
            $date_time = date('Y-m-d H:i:s');
            $funName = $data['fun'] ?? '';
            $request = request();
            // 路径
            $url = $request->url(true);
            // 来路
            $origin = $request->header('origin');
            $uri = urldecode($request->header('app-from'));
            $origin = $origin."/".$uri;

            if (!empty($data)) {
                // 删除 URI 操作
                $uri = "/" . $request->path();
                if (array_key_exists($uri, $data)) {
                    unset($data[$uri]);
                }
                $request_all_to_json = json_encode($data, JSON_UNESCAPED_UNICODE);
            } else {
                $request_all_to_json = '';
            }
            $bizBackUrl = $ConsumeService->backUrl;

            $content = <<<EOF
### <font color="warning">{$huanJing}---业务接口bug！！！</font>
> 时间：{$date_time}
> 路径：{$url}
> 来路：{$origin}
> 服务：{$className}
> 方法：{$funName}
> 参数：{$request_all_to_json}
> 回调：{$bizBackUrl} 
> 参考  mq_error_log  表
EOF;
            (new WorkSendMessageService($key))->sendMarkDown($content);
        } catch (Exception $e) {
            //需手动捕获异常,防止上文异常后死循环
            trace('消息发送失败:' . $e->getMessage());
        }
    }
}