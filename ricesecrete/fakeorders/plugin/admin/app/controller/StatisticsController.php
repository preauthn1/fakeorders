<?php


namespace plugin\admin\app\controller;


use app\model\RechargeModel;
use plugin\admin\app\services\StatisticsServices;
use support\Request;

class StatisticsController extends Crud
{
    protected $noNeedAuth = ['get'];
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new RechargeModel();
    }

    /**
     * 充值提现统计
     * @param Request $request
     * @return \support\Response
     * @throws \Throwable
     */
    public function recharge_withdraw(Request $request)
    {
        if ($request->isPost()) {
            $start = $request->post('start');
            if (empty($start)) {
                $start = date('Y-m-d', strtotime('-30 day')) . " 00:00:00";
            }
            $end = $request->post('end');
            if (empty($end)) {
                $end = date('Y-m-d H:i:s');
            }
            $data = StatisticsServices::getRechargeWithdraw($start, $end);
            return $this->json(0, 'ok', $data);
        }
        return raw_view('statistics/recharge_withdraw');
    }
}