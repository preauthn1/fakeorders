<?php


namespace plugin\admin\app\services;

use app\common\Util;
use plugin\admin\app\model\FileExport;

class FileExportServices
{


    /**
     * 添加导出任务
     * @param $condition //条件
     * @param $export_fun //\plugin\admin\app\services\MessageServices::exportFile
     * @param int $page //1
     * @param int $page_size //100
     * @param int $admin_id
     * @return array
     */
    public static function addExportTask($condition, $export_fun, $page = 1, $page_size = 1000, $admin_id = 0)
    {
        // `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '名称',
        //  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '文件下载地址',
        //  `field` text COLLATE utf8mb4_general_ci COMMENT '字段对应关系 json',
        //  `page` int DEFAULT NULL COMMENT '页码',
        //  `data_fun` text COLLATE utf8mb4_general_ci COMMENT '获取数据方法',
        //  `admin_id` int DEFAULT '0' COMMENT '管理员',
        //  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未开始 1进行中 2已完成 3导出错误',
        $map = [
            'export_fun' => $export_fun
        ];
        $file = FileExport::where($map)->where('created_at', '>', date('Y-m-d H:i:s', strtotime('-1 minute')))->find();
        if ($file) {
            return [
                'code' => 1,
                'msg' => '不允许重复导出'
            ];
        }
        $in_data = [
            'number' => Util::getRandomString(16),
            'name' => '',
            'url' => '',
            'field' => '',
            'condition' => json_encode($condition),
            'file_page' => $page,
            'page_size' => $page_size,
            'export_fun' => $export_fun . "",
            'admin_id' => $admin_id,
            'status' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $id = FileExport::insertGetId($in_data);
        return [
            'code' => 0,
            'id' => $id,
            'msg' => '成功'
        ];
    }

    /**
     * 执行导出任务
     * @return array
     */
    public static function export()
    {

        $task_info = FileExport::where('status', '=', 1)->find();
        if (!$task_info) {
            $task_info = FileExport::where('status', '=', 0)->find();
            if (!$task_info) {
                return [
                    'code' => 4,
                    'msg' => '没有任务'
                ];
            }
        }
        $condition = json_decode($task_info->condition, true);
        //export_fun  方法 \plugin\admin\app\services\MessageServices::exportFile($map, 1, 1000);
        $fun_data = call_user_func($task_info->export_fun, $condition, $task_info->file_page, $task_info->page_size);
        $data = $fun_data['data'];
        $field = $fun_data['field'];
        if (empty($task_info['name'])) {
            $file_name = $fun_data['name'] . '_' . date('dHi') . rand(100, 999);
        } else {
            $file_name = $task_info['name'];
        }
        $task_info->field = json_encode($field);
        $is_end = false;
        if (empty($data)) {
            $is_end = true;
        } else {
            $task_info->file_page = $task_info->file_page + 1;
        }
        //0未开始 1进行中 2已完成 3导出错误
        try {
            $file_dir = public_path() . '/export/';
            if (!file_exists($file_dir)) {
                mkdir($file_dir, 0777, true);
            }
            //文件名
            $is_first = false;
            $file_path = $file_dir . $file_name . '.csv';
            if (!file_exists($file_path)) {
                touch($file_path);
                $is_first = true;
                $task_info['name'] = $file_name;
            }
            //追加文件
            $fp = fopen($file_path, 'a');
            if ($is_first) {
                fputcsv($fp, $field);
            }
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
            if ($is_end) {
                $task_info->updated_at = date('Y-m-d H:i:s');
                $task_info->url = str_replace(public_path(), '', $file_path);
                $task_info->status = 2;
                $task_info->save();
                return [
                    'code' => 2,//完成
                    'file_name' => $file_name,
                ];
            }
            $task_info->updated_at = date('Y-m-d H:i:s');
            $task_info->status = 1;
            $task_info->save();
            return [
                'code' => 1,//继续
                'file_name' => $file_name,
            ];
        } catch (\Exception $e) {
            $task_info->updated_at = date('Y-m-d H:i:s');
            $task_info->status = 3;
            $task_info->save();
            return [
                'code' => 3,//失败
                'file_name' => $file_name,
            ];
        }
    }
}