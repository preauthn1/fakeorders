<?php


namespace app\common\payment;

use app\services\RechargeServices;

/**
 * 线下支付
 * Class OfflinePay
 * @package app\common\payment
 */
class OfflinePay implements PayInterface
{
    private $platform;
    private $domain;

    public function __construct($platform, $domain)
    {
        $this->platform = $platform;
        $this->domain = $domain;
    }

    /**
     * 发起支付
     * @param $order
     * @return array
     */
    public function pay($order): array
    {
        $exchange_rate = $this->platform['exchange_rate'];
        $original_money = floatval($order['money']);
        $money = floatval(floatval($original_money) / floatval($exchange_rate));
        if ($money <= 0) {
            return [
                'code' => 10,
                'msg' => '充值金额不能小于0'
            ];
        }
        if ($money < $this->platform['min_money']) {
            return [
                'code' => 10,
                'msg' => '充值金额不能小于限制'
            ];
        }
        $pay_img = isset($order['pay_img']) ? $order['pay_img'] : [];
        $add_data = [
            'trade_no' => '',
            'money' => $money,
            'original_money' => $original_money,
            'exchange_rate' => $exchange_rate,
            'pay_img' => json_encode($pay_img),
            'to_address' => $this->platform['address'],
            'type' => $this->platform['type'],
            'unit' => $this->platform['unit'],
            'platform_id' => $this->platform['id'],
        ];
        $number = RechargeServices::getUserNumber($order['user_id'], $money);
        if (empty($number)) {
            $number = RechargeServices::addRecharge($order['user_id'], $add_data);
        }
        return [
            'code' => 0,
            'data' => [
                'pay_url' => '',
                'number' => $number
            ]
        ];
    }

    /**
     * 支付回调
     * @param $order
     * @return array
     */
    public function pay_notify($order): array
    {
        return [];
    }

    public function refund($order): array
    {
        return [];
    }

    public function query($order): array
    {
        return [];
    }

    /**
     * 发起提现请求
     * @param $order ;订单信息 number订单编号 amount订单金额 to_address打款地址
     * @return array
     */
    public function withdraw($order): array
    {
        return [];
    }

    /**
     * 提现提现回调
     * @param $order
     * @return array
     */
    public function withdrawal_notify($order): array
    {
        return [];
    }
}