<?php


namespace app\common\payment;


interface PayInterface
{
    /**
     * 发起支付
     * @param $order
     * @return array
     */
    public function pay($order): array;

    /**
     * 支付回调
     * @param $order
     * @return array
     */
    public function pay_notify($order): array;

    /**
     * 退款
     * @param $order
     * @return array
     */
    public function refund($order): array;

    /**
     * 订单查询
     * @param $order
     * @return array
     */
    public function query($order): array;

    /**
     * 提现
     * @param $order
     * @return array
     */
    public function withdraw($order): array;

    /**
     * 提现回调
     * @param $order
     * @return array
     */
    public function withdrawal_notify($order): array;
}