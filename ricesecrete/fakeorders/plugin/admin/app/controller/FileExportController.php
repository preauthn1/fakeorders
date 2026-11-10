<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\FileExport;
use support\Request;
use support\Response;
use Throwable;

/**
 * 导出文件管理
 */
class FileExportController extends Crud
{

    /**
     * @var FileExport
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new FileExport();
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('fileexport/index');
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $ids = $this->deleteInput($request);
        $primary_key = $this->model->getKeyName();
        $this->model->whereIn($primary_key, $ids)->each(function ($model) {
            if ($model->url) {
                //删除文件
                $file = public_path($model->url);
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        });
        $this->doDelete($ids);
        return $this->success();
    }
}
