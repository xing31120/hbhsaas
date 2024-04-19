<?php

namespace app\command;

use app\common\amqp\UserConsumer;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\amqp\BizConsumer;

class pushMsg extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('pushmsg')
            ->setDescription('push MSG to bizSystem');;
        // 设置参数
        
    }

    protected function execute(Input $input, Output $output)
    {
    	// 指令输出
    	$output->writeln('pushmsg');

        $BizConsumer = new BizConsumer();
        $BizConsumer->start();
    }
}
