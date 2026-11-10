<?php


namespace app\queue\redis;


use Webman\RedisQueue\Consumer;

class Email implements Consumer
{

    // $queue = 'send_mail';
    //        // 数据，可以直接传数组，无需序列化
    //        $data = ['to' => 'tom@gmail.com', 'content' => 'hello'];
    //        // 投递消息
    //        Webman\RedisQueue\Redis::send($queue, $data);
    // 要消费的队列名
    public $queue = 'send_mail';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    public function consume($data)
    {
        //处理队列数据
        print_r($data);
        return true;
    }

    public function onConsumeFailure(\Throwable $e, $package)
    {
        echo "consume failure\n";
        echo $e->getMessage() . "\n";
        // 无需反序列化
        print_r($package);
    }
}