<?php

namespace plugin\admin\app\controller;

use app\model\user\UserLevelModel;
use app\services\SysServices;
use app\services\UserServices;
use plugin\admin\app\model\ContactUs;
use plugin\admin\app\model\User;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 联系我们管理
 */
class ContactUsController extends Crud
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
        $this->model = new ContactUs();
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('contactus/index');
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
            $data = parent::insert($request);
            SysServices::contactUs(false);
            return $data;
        }
        return raw_view('contactus/insert');
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
            $data = parent::update($request);
            SysServices::contactUs(false);
            return $data;
        }
        return raw_view('contactus/update');
    }

}
