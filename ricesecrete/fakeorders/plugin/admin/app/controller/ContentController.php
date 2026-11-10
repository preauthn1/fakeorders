<?php

namespace plugin\admin\app\controller;

use app\common\push\PushApi;
use plugin\admin\app\model\Content;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 用户等级管理
 */
class ContentController extends Crud
{

    /**
     * @var Content
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Content();
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {

        return raw_view('content/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $data = $this->insertInput($request);
            $id = $this->doInsert($data);
            if ($data['type'] == 1 && $data['status'] == 1) {
                //系统公告
                $data['id'] = $id;
                $push_data = [
                    'title' => '系统公告',//标题
                    'type' => 3,//3:系统公告,4:指数数据
                    'data' => $data,
                ];
                PushApi::sendGroupMessage('data', $push_data);
            }
            return $this->json(0, 'ok', ['id' => $id]);
        }
        return raw_view('content/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        return raw_view('content/update');
    }

}
