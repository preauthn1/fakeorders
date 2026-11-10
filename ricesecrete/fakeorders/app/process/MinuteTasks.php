<?php


namespace app\process;


use app\services\PledgeCurrentServices;
use app\services\PledgeLongServices;
use app\services\RechargeRebatesServices;
use Workerman\Crontab\Crontab;

/**
 * 没分钟结算任务
 * Class PledgeCurrent
 * @package app\process
 */
class MinuteTasks
{
    public function onWorkerStart()
    {
//        执行逻辑会阻塞进程
        // 每1分钟执行一次
        new Crontab('1 * * * * *', function () {
            //理财结算
            PledgeLongServices::settlementPledgeCurrentAll();
            // 充值返佣结算
            RechargeRebatesServices::settlementRebates();
//            echo date('Y-m-d H:i:s') . "\n";
        });
    }
}