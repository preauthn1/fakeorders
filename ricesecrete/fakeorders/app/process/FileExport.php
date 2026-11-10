<?php


namespace app\process;


use app\services\PledgeLongServices;
use plugin\admin\app\services\FileExportServices;
use Workerman\Crontab\Crontab;

/**
 * 文件导出进程
 * Class FileExport
 * @package app\process
 */
class FileExport
{
    public function onWorkerStart()
    {
//        执行逻辑会阻塞进程
        // 每10秒执行一次
        new Crontab('*/10 * * * * *', function () {
//            echo "文件导出进程.\n";
            FileExportServices::export();
//            echo date('Y-m-d H:i:s') . "\n";
        });
    }
}