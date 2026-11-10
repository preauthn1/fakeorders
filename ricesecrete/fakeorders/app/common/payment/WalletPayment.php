<?php


namespace app\common\payment;


class WalletPayment
{
    private $platform;
    private $domain;

    /**
     * @param $platform ;配置支付信息
     * @param $domain ;后台域名、配置回调地址
     **/
    public function __construct($platform, $domain)
    {
        $this->platform = $platform;
        $this->domain = $domain;
    }

    /**
     * 支付
     * @param $input ;输入参数
     * @return mixed
     */
    public function pay($input)
    {
        $obj = "\app\common\payment\\" . $this->platform->package;
        try {
            return (new $obj($this->platform, $this->domain))->pay($input);
        } catch (\Exception $e) {
            return [
                'code' => 10,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * 提现
     * @param $input ;输入参数
     * @return mixed
     */
    public function withdraw($input)
    {
        $obj = "\app\common\payment\\" . $this->platform->package;
        try {
            return (new $obj($this->platform, $this->domain))->withdraw($input);
        } catch (\Exception $e) {
            return [
                'code' => 10,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * notify回调
     * @param $input ;验证参数
     * @param int $type 回调类型 pay:支付 withdrawal:提现
     * @return array
     */
    public function notify($input, $type = 'pay')
    {
        try {
            $obj = "\app\common\payment\\" . $this->platform->package;
            if ($type == 'pay') {
                $rdata = (new $obj($this->platform, $this->domain))->pay_notify($input);
            } else {
                $rdata = (new $obj($this->platform, $this->domain))->withdrawal_notify($input);
            }
            return $rdata;
        } catch (\Exception $e) {
            return [
                'code' => 10,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * 订单查询
     * @param $input
     * @return mixed
     */
    public function query($input)
    {
        $obj = "\app\common\payment\\" . $this->platform->package;
        try {
            return (new $obj($this->platform, $this->domain))->query($input);
        } catch (\Exception $e) {
            return [
                'code' => 10,
                'msg' => $e->getMessage()
            ];
        }

    }

    /**
     * 退款
     * @param $input
     * @return mixed
     */
    public function refund($input)
    {
        $obj = "\app\common\payment\\" . $this->platform->package;
        try {
            return (new $obj($this->platform, $this->domain))->refund($input);
        } catch (\Exception $e) {
            return [
                'code' => 10,
                'msg' => $e->getMessage()
            ];
        }
    }
}