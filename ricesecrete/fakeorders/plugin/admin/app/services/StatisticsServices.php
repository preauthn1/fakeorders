<?php


namespace plugin\admin\app\services;


use app\common\push\PushApi;
use app\model\RechargeModel;
use app\model\WithdrawModel;
use app\services\UserServices;
use plugin\admin\app\model\Content;
use plugin\admin\app\model\ContentRead;
use plugin\admin\app\model\Dict;
use plugin\admin\app\model\Message;

class StatisticsServices
{
    /**
     * 获取充值提现数据
     * @param $start_time
     * @param $end_time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getRechargeWithdraw($start_time, $end_time)
    {
        $start_time = empty($start_time) ? date('Y-m-d 00:00:00') : $start_time;
        $end_time = empty($end_time) ? date('Y-m-d 23:59:59') : $end_time;
        //充值统计
        $recharge_list = RechargeModel::where('status', 2)->whereBetween('updated_at', [$start_time, $end_time])->group('day')->field('day,sum(money) as all_money')->order('day', 'desc')->select();
        //提现统计
        $withdraw_list = WithdrawModel::where('status', 2)->whereBetween('updated_at', [$start_time, $end_time])->group('day')->field('day,sum(money) as all_money')->order('day', 'desc')->select();
        $day = [
            'recharge' => 0,
            'withdraw' => 0
        ];
        $day7 = [
            'recharge' => 0,
            'withdraw' => 0
        ];
        $day30 = [
            'recharge' => 0,
            'withdraw' => 0
        ];
        $day7_list = [
            'recharge' => [],
            'withdraw' => []
        ];
        $day7_recharge = [];
        $day7_withdraw = [];
        $day7_day = [];
        for ($i = 0; $i < 7; $i++) {
            $day7_day[] = date('m-d', strtotime('-' . $i . ' day'));
            $day7_recharge[date('Ymd', strtotime('-' . $i . ' day'))] = 0;
            $day7_withdraw[date('Ymd', strtotime('-' . $i . ' day'))] = 0;
            $day7_list['recharge'][date('Ymd', strtotime('-' . $i . ' day'))] = 0;
            $day7_list['withdraw'][date('Ymd', strtotime('-' . $i . ' day'))] = 0;
        }
        $recharge_list = $recharge_list->toArray();
        foreach ($recharge_list as $item) {
            if ($item['day'] == date('Ymd')) {
                $day['recharge'] = $item['all_money'];
            }
            if ($item['day'] > date('Ymd', strtotime('-7 day'))) {
                $day7['recharge'] += $item['all_money'];
                $day7_recharge[$item['day']] = floatval($item['all_money']);
            }
            if ($item['day'] > date('Ymd', strtotime('-30 day'))) {
                $day30['recharge'] += $item['all_money'];
            }
        }
        $withdraw_list = $withdraw_list->toArray();
        foreach ($withdraw_list as $item) {
            if ($item['day'] == date('Ymd')) {
                $day['withdraw'] = $item['all_money'];
            }
            if ($item['day'] > date('Ymd', strtotime('-7 day'))) {
                $day7['withdraw'] += $item['all_money'];
                $day7_withdraw[$item['day']] = floatval($item['all_money']);
            }
            if ($item['day'] > date('Ymd', strtotime('-30 day'))) {
                $day30['withdraw'] += $item['all_money'];
            }
        }
        //去除键
        $day7_recharge = array_values($day7_recharge);
        $day7_withdraw = array_values($day7_withdraw);
        return [
            'day' => $day,
            'day7' => $day7,
            'day30' => $day30,
            'day7_day' => $day7_day,
            'day7_recharge' => $day7_recharge,
            'day7_withdraw' => $day7_withdraw,
            'recharge' => $recharge_list,
            'withdraw' => $withdraw_list
        ];
    }
}