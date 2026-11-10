<?php


namespace app\common\payment;

use app\services\RechargeServices;
use app\services\WithdrawServices;
use support\Cache;
use support\Log;

/**
 * 云配Upay 支付
 * Class YpUpay
 * @package app\common\payment
 */
class YpUpay implements PayInterface
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

        $add_data = [
            'trade_no' => '',
            'money' => $money,
            'original_money' => $original_money,
            'exchange_rate' => $exchange_rate,
            'from_address' => isset($order['from_address']) ? $order['from_address'] : '',
            'to_address' => $this->platform['address'],
            'type' => $this->platform['type'],
            'unit' => $this->platform['unit'],
            'platform_id' => $this->platform['id'],
        ];
        $number = RechargeServices::getNumber($add_data['from_address'], $add_data['to_address'], $money);
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
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     */
    public function pay_notify($order): array
    {
        //{"address":"TJDH5fV1AgcNfk7w3vCiUeeZEUwcUWaMMK","amount":"1","chain":"TRC20","coin":"USDT","from":"TYhCSgsaSdLm8yv4rkF5omNu89DHBX5jWr","hash":"a5c0f5371e9473ea471633fc62b356046d74bd51bc3c58c31ffd98c70415bcf6","orderNo":"395796239825764352","sign":"68231a9b23af966fda7f18655ea354f6","status":"2","txid":"395796239825764353"}
        $chain = isset($order['chain']) ? $order['chain'] : '';
        $address = isset($order['address']) ? $order['address'] : '';
        $trade_no = isset($order['orderNo']) ? $order['orderNo'] : '';
        $status = isset($order['status']) ? $order['status'] : 0;
        $amount = isset($order['amount']) ? $order['amount'] : 0;
        $from = isset($order['from']) ? $order['from'] : '';
        $redis = Cache::store('redis');
        $ret = $redis->get('recharge_' . $trade_no);
        if (empty($ret)) {
            $redis->set('recharge_' . $trade_no, 1, 10);
        } else {
            Log::info('YpUpay', [
                'chain' => $chain,
                'address' => $address,
                'trade_no' => $trade_no,
            ]);
            return [
                'code' => 10,
                'msg' => '正在处理中，请勿重复提交',
            ];
        }

        // 如果状态不是成功，不处理
        /** 状态 1待确认2成功3失效*/
        if ($status != 2) {
            $out_data = [
                'code' => 1,
                'msg' => '成功'
            ];
            return [
                'code' => 0,
                'type' => 'json',
                'data' => $out_data,
                'msg' => '状态不正确'
            ];
        }
        $platform = $this->platform;
        //签名
//        $config = json_decode($platform->config, true);
//        $key = $config['key'];
//        $sign_temp = $order['sign'];
//        unset($order['sign']);
//        $sign = RechargeServices::generateSign($order, $key);
//        if ($sign != $sign_temp) {
//            return [
//                'code' => 10,
//                'msg' => '签名错误'
//            ];
//        }
        //平台归集地址
//        $to_address = SysServices::getSysConfigValue('wallet', 'collection');
        $exchange_rate = $platform->exchange_rate;
        $number = RechargeServices::getNumber($from, $address, floatval(floatval($amount) * floatval($exchange_rate)));
        if (!$number) {
            $out_data = [
                'code' => 1,
                'msg' => '成功'
            ];
            return [
                'code' => 0,
                'type' => 'json',
                'data' => $out_data,
                'msg' => '订单不存在'
            ];
        }
        $ret = RechargeServices::endRecharge($number, floatval(floatval($amount) * floatval($exchange_rate)), $trade_no, $order);
        if ($ret) {
            $out_data = [
                'code' => 1,
                'msg' => '成功'
            ];
            return [
                'code' => 0,
                'type' => 'json',
                'data' => $out_data,
                'msg' => '充值成功'
            ];
        }
        return [
            'code' => 10,
            'msg' => '充值失败'
        ];
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
        $orderNo = isset($order['number']) ? $order['number'] : '';
        $amount = isset($order['amount']) ? $order['amount'] : 0;
        if (empty($amount)) {
            return [
                'code' => 10,
                'msg' => '提现金额不能为空'
            ];
        }
        $platform = $this->platform;
        $to_address = isset($order['to_address']) ? $order['to_address'] : '';
        $config = json_decode($platform->config, true);
        $data = [
            'merchantNo' => $config['merchantNo'],
            'coin' => $config['coin'],
            'chain' => $config['chain'],
            'amount' => floatval($amount),
            'orderNo' => $orderNo,
            'address' => $to_address,
            'timestamp' => time(),
            'notifyUrl' => $config['withdraw_notify_url']
        ];

        $key = WithdrawServices::generateSign($data, $config['key']);
        $data['sign'] = $key;
        $url = $config['withdraw_url'];
        try {
            $res = PayRequest::request($data, $url);
            if ($res['code'] == 0) {
                //提现成功
                return [
                    'code' => 0,
                    'trade_no' => $res['data']['orderNo']
                ];
            } else {
                return [
                    'code' => 1,
                    'msg' => $res['msg']
                ];
            }
        } catch (\Exception $e) {
            Log::error("Withdrawal_YpUpay", [$url, $data, $e->getMessage()]);
            return ['code' => 1, 'message' => '请求异常'];
        }
    }

    /**
     * 提现提现回调
     * @param $order
     * @return array
     */
    public function withdrawal_notify($order): array
    {
        ////{"address":"TJDH5fV1AgcNfk7w3vCiUeeZEUwcUWaMMK","amount":"1","chain":"TRC20","coin":"USDT","from":"TYhCSgsaSdLm8yv4rkF5omNu89DHBX5jWr","hash":"a5c0f5371e9473ea471633fc62b356046d74bd51bc3c58c31ffd98c70415bcf6","orderNo":"395796239825764352","sign":"68231a9b23af966fda7f18655ea354f6","status":"2","txid":"395796239825764353"}
        $result = $order;
        $number = isset($order['orderNo']) ? $order['orderNo'] : '';
        $status = isset($order['status']) ? $order['status'] : 0;
        $amount = isset($order['amount']) ? $order['amount'] : 0;
        if (empty($amount)) {
            $out_data = [
                'code' => 1,
                'msg' => '成功'
            ];
            return [
                'code' => 0,
                'type' => 'json',
                'data' => $out_data,
                'msg' => '提现金额不能为空'
            ];
        }
        $status = intval($status);
        // 如果状态不是成功，不处理
        /** 状态 1待确认2成功3失效*/
        if ($status != 3) {
            if ($status == 1 || $status == 2) {
                $out_data = [
                    'code' => 2,
                    'msg' => '成功'
                ];
                return [
                    'code' => 0,
                    'type' => 'json',
                    'data' => $out_data,
                    'msg' => '等待确认'
                ];
            }
            WithdrawServices::arrivalStatus($number, WithdrawServices::STATUS_FAILED, $result);
            $out_data = [
                'code' => 1,
                'msg' => '成功'
            ];
            return [
                'code' => 0,
                'type' => 'json',
                'data' => $out_data,
                'msg' => '到账失败'
            ];
        }
//        $platform = $this->platform;
//        //签名
//        $config = json_decode($platform->config, true);
//        $key = $config['key'];
//        $sign_temp = $order['sign'];
//        unset($order['sign']);
//        $sign = WithdrawServices::generateSign($order, $key);
//        if ($sign != $sign_temp) {
//            return [
//                'code' => 10,
//                'msg' => '签名错误'
//            ];
//        }
        $ret = WithdrawServices::arrivalStatus($number, WithdrawServices::STATUS_ARRIVAL, $result);
        if ($ret) {
            $out_data = [
                'code' => 1,
                'msg' => '成功'
            ];
            return [
                'code' => 0,
                'type' => 'json',
                'data' => $out_data,
                'msg' => '充值成功'
            ];
        }
        $out_data = [
            'code' => 1,
            'msg' => '成功'
        ];
        return [
            'code' => 0,
            'type' => 'json',
            'data' => $out_data,
            'msg' => '充值失败'
        ];
    }

    /**
     * 获取提现钱包
     * @return array
     */
    public function get_withdrawal_wallet(): array
    {

        $platform = $this->platform;
        $config = json_decode($platform->config, true);
        $data = [
            'merchantNo' => $config['merchantNo'],
            'timestamp' => time()
        ];

        $key = WithdrawServices::generateSign($data, $config['key']);
        $data['sign'] = $key;
        $url = $config['withdraw_wallet_url'];
        try {
            $res = PayRequest::request($data, $url);
            if ($res['code'] == 0) {
                //提现成功
                return [
                    'code' => 0,
                    'data' => $res['data']
                ];
            } else {
                return [
                    'code' => 1,
                    'msg' => $res['msg']
                ];
            }
        } catch (\Exception $e) {
            Log::error("Withdrawal_YpUpay", [$url, $data, $e->getMessage()]);
            return ['code' => 1, 'message' => '请求异常'];
        }
    }
}