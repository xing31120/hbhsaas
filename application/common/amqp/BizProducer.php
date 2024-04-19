<?php
/**
 * 生产
 * Date: 2020-12-2
 * Time: 16:49
 */

namespace app\common\amqp;

use PhpAmqpLib\Message\AMQPMessage;
use think\facade\Config;

class BizProducer extends BaseMq{

    /**
     * Notes: 发消息到mq
     * @param string $msg
     * @param array $data
     * @throws \Exception
     * User: SongX DateTime: 2020-12-3 10:37
     */
    public function publish( $msg, array $data =[]){
        $prefix = Config::get('amqp.prefix');
        $result = false;
        $channel = $this->channel();
        $exchange = $data['exchange'] ?? $prefix.'exchange_';       //交换机名称
        $routingKey = $data['routingKey'] ?? $prefix.'routing_';    //路由秘钥
        $queueName = $data['queueName'] ?? $prefix.'queue_biz_push_'; //队列名称

        $msgString = is_array($msg) ? json_encode($msg) : $msg;
        //声明交换机
        if (!empty($exchange)) {
            $channel->exchange_declare($exchange, $this->type, $this->passive, $this->durable, $this->autoDelete);
        }

        //声明队列
        if (!empty($queueName)) {
            $channel->queue_declare($queueName,$this->passive,$this->durable,$this->exclusive,$this->autoDelete);
        }

        $properties = [];
        if ($this->durable){
            $properties = [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ];
        }
        //发消息
        $msg = new AMQPMessage($msgString, $properties);
        $result = $channel->basic_publish($msg, $exchange, $routingKey);

        $channel->close();
        $this->handler->close();

        return $result;
    }

}