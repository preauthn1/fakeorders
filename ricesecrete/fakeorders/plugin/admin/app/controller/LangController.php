<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\Lang;
use plugin\admin\app\model\User;
use plugin\admin\app\services\LangServices;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 语言管理
 */
class LangController extends Crud
{

    /**
     * @var User
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Lang();
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('lang/index');
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
            LangServices::setLangClear();
            return parent::insert($request);
        }
        return raw_view('lang/insert');
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
            LangServices::setLangClear();
            return parent::update($request);
        }
        return raw_view('lang/update');
    }

}
