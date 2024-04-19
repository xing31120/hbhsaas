<?php
/**
 *
 * Date: 2020-12-2
 * Time: 16:40
 */

namespace app\common\amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use think\facade\Config;

class BaseMq{

    protected $handler;

    /**
     * 配置参数
     * @var array
     */
    protected $options = [];

    protected $type = null;
    protected $passive = false;
    protected $durable = true;
    protected $exclusive = false;
    protected $autoDelete = false;

    protected $nolocal = false;
    protected $nowait = false;
    protected $noAck = false;

    /**
     * BaseMq constructor.
     * @param array $config = [
     *  'config' => [], //配置
     *  'options' => [] //选项
     * ]
     */
    public function __construct(array $config = []){
//Config('amqp.');echo json_encode(Config('amqp.'));exit;
        $this->options = array_merge(Config('amqp.'), $config);

        $insist = false;
        $login_method = 'AMQPLAIN';
        $login_response = null;
        $locale = 'en_US';
        $connection_timeout = 3.0;
        $read_write_timeout = 3.0;
        $context = null;
        $keepalive = false;
        $heartbeat = 30;         //心跳
        $channel_rpc_timeout = 0.0;
        $ssl_protocol = null;

        $this->handler = new AMQPStreamConnection(
            $this->options['host'],
            $this->options['port'],
            $this->options['user'],
            $this->options['password'],
            $this->options['vhost'],
            $insist,
            $login_method,
            $login_response,
            $locale,
            $connection_timeout,
            $read_write_timeout,
            $context,
            $keepalive,
            $heartbeat,
            $channel_rpc_timeout,
            $ssl_protocol
        );

        $arguments = [];
        if (isset($config['options'])){
            $arguments = $config['options'];
        }
        $this->type = isset($arguments['type']) ? $arguments['type'] : AMQPExchangeType::DIRECT;;
        $this->passive = isset($arguments['passive']) ? $arguments['passive'] : false;
        $this->durable = isset($arguments['durable']) ? $arguments['durable'] : true;
        $this->exclusive = isset($arguments['exclusive']) ? $arguments['exclusive'] : false;
        $this->autoDelete = isset($arguments['autoDelete']) ? $arguments['autoDelete'] : false;
        $this->nolocal = isset($arguments['nolocal']) ? $arguments['nolocal'] : false;
        $this->nowait = isset($arguments['nowait']) ? $arguments['nowait'] : false;
        $this->noAck = isset($arguments['noAck']) ? $arguments['noAck'] : false;
    }

    public function handler(){
        return $this->handler;
    }

    public function channel($channelId = null){
        return $this->handler->channel($channelId);
    }
}