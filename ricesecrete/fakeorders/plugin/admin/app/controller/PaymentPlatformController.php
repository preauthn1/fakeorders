<?php

namespace plugin\admin\app\controller;

use app\services\SysServices;
use plugin\admin\app\model\PaymentPlatform;
use plugin\admin\app\model\User;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 支付平台管理
 */
class PaymentPlatformController extends Crud
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
        $this->model = new PaymentPlatform();
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('paymentplatform/index');
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
            SysServices::getPaymentList(false);
            return $data;
        }
        return raw_view('paymentplatform/insert');
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
            SysServices::getPaymentList(false);
            return $data;
        }
        return raw_view('paymentplatform/update');
    }

}
