<?php

namespace app\controller\v1;

use app\common\BaseController;
use app\common\payment\WalletPayment;
use app\common\Util;
use app\services\SysServices;
use app\services\UserServices;
use app\services\UserWalletServices;
use app\services\WithdrawServices;
use plugin\admin\app\model\PaymentPlatform;
use support\Log;
use support\Request;

class PaymentController extends BaseController
{

    /**
     * 充值回调
     * @param Request $request
     * @return \support\Response
     */
    public function pay_notify(Request $request)
    {
        //支付通道ID
        $id = $request->input('id', '');
        $platform = PaymentPlatform::where('id', $id)->find();
        if (empty($platform)) {
            $platform = PaymentPlatform::where('name', 'like', $request->input('chain', '') . '%')->find();
        }
        if (!$platform) {
            $address = $request->input('address', '');
            $platform = PaymentPlatform::where('address', $address)->find();
            if (!$platform) {
                $out_data = [
                    'code' => 10,
                    'msg' => '支付通道不存在'
                ];
                return self::returnMsg($out_data);
            }
        }
        $domain = $request->host();
        $WalletPayment = new WalletPayment($platform, $domain);
        Log::debug('pay_notify', $request->all());
        $out_data = $WalletPayment->notify($request->all(), 'pay');
//        print_r($out_data);
        if ($out_data['code'] == 0) {
            // 'type' => 'json', 如果
            //  'data' => $out_data,
            // 处理成功
            if (isset($out_data['type']) && $out_data['type'] == 'json') {
                // 输出json
                return self::returnMsg($out_data['data']);
            } else {
                //输出文本
                return response($out_data['data']);
            }
        }
        return response('error');
    }

    /**
     * 提现回调
     * @param Request $request
     * @return \support\Response
     */
    public function withdraw_notify(Request $request)
    {
        //支付通道ID
        $id = $request->input('id', '');
        $platform = PaymentPlatform::where('id', $id)->find();
        if (empty($platform)) {
            $platform = PaymentPlatform::where('name', 'like', $request->input('chain', '') . '%')->find();
        }
        if (!$platform) {
            $out_data = [
                'code' => 10,
                'msg' => '支付通道不存在'
            ];
            return self::returnMsg($out_data);
        }
        $domain = $request->host();
        $WalletPayment = new WalletPayment($platform, $domain);
        Log::debug('withdraw_notify', $request->all());
        $out_data = $WalletPayment->notify($request->all(), 'withdrawal');
        if ($out_data['code'] == 0) {
            // 'type' => 'json', 如果
            //  'data' => $out_data,
            // 处理成功
            if (isset($out_data['type']) && $out_data['type'] == 'json') {
                // 输出json
                return self::returnMsg($out_data['data']);
            } else {
                //输出文本
                return response($out_data['data']);
            }
        }
        return response('error');
    }

    /**
     * 添加充值订单
     * @param Request $request
     * @return \support\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add_recharge(Request $request)
    {
        $user_id = $this->user_id;
        $id = $request->input('id', '');
        if (empty($id)) {
            $to_address = $request->input('to_address', '');
            $platform = PaymentPlatform::where('address', $to_address)->find();
        } else {
            $platform = PaymentPlatform::where('id', $id)->find();
        }
        if (!$platform) {
            $out_data = [
                'code' => 10,
                'msg' => '支付通道不存在'
            ];
            return self::returnMsg($out_data);
        }
        $domain = $request->host();
        $WalletPayment = new WalletPayment($platform, $domain);
        $data = $request->all();
        $data['user_id'] = $user_id;
        $out_data = $WalletPayment->pay($data);
        return self::returnMsg($out_data);
    }

    /**
     * 用户申请提现
     * @param Request $request
     * @return \support\Response
     */
    public function add_withdraw(Request $request)
    {
        $user_id = $this->user_id;
        $withdraw_status = UserServices::getFieldValue($user_id, 'withdraw_status');
        if ($withdraw_status != 1) {
            $out_data = [
                'code' => 1,
                'msg' => '提现功能未开启'
            ];
            return self::returnMsg($out_data);
        }
        $money = $request->input('money', 0);
        $type = $request->input('type', 1);//类型 1 TRC20,2 BSC,3线下提现
        $address = $request->input('address', '');
        $id = $request->input('id', '');
        if ($type == 3) {
            $user_address = $address;
        } else {
            $user_info = UserWalletServices::getWithdrawWallet($user_id, $address);
            $user_address = isset($user_info['address']) ? $user_info['address'] : '';
            if (empty($user_address)) {
                $out_data = [
                    'code' => 1,
                    'msg' => '请绑定提现地址'
                ];
                return self::returnMsg($out_data);
            }
        }
        //  'money' => $data['money'],
        //            'original_money' => $data['original_money'],
        //            'exchange_rate' => $data['exchange_rate'],
        //            'from_address' => $data['from_address'],
        //            'to_address' => $data['to_address'],
        //            'type' => $data['type'],
        if ($type == 1) {
            $type_str = 'TRC20';
            $platform = PaymentPlatform::where('name', 'like', $type_str . '%')->find();
        } else if ($type == 2) {
            $type_str = 'BEP20';
            $platform = PaymentPlatform::where('name', 'like', $type_str . '%')->find();
        } else {
            $platform = PaymentPlatform::where('id', $id)->find();
        }
        if (!$platform) {
            $out_data = [
                'code' => 10,
                'msg' => '币种不存在'
            ];
            return self::returnMsg($out_data);
        }
        if ($platform->status != 1) {
            $out_data = [
                'code' => 10,
                'msg' => '币种已关闭'
            ];
            return self::returnMsg($out_data);
        }
        $exchange_rate = $platform->exchange_rate;
        $from_address = SysServices::getSysConfigValue('wallet', 'collection');
        $withdraw_wait_num = SysServices::getSysConfigValue('wallet', 'withdraw_wait_num');
        $user_wait_num = WithdrawServices::getUserWaitNum($user_id);
        if ($withdraw_wait_num > 0 && $user_wait_num >= $withdraw_wait_num) {
            $out_data = [
                'code' => 10,
                'msg' => '有提现申请正在处理中，请勿重复提现'
            ];
            return self::returnMsg($out_data);
        }
        $reality = floatval($money * $exchange_rate);
        $min_withdraw_money = $platform->min_withdraw_money;
        $max_withdraw_money = $platform->max_withdraw_money;
        if ($reality < $min_withdraw_money || $reality > $max_withdraw_money) {
            $out_data = [
                'code' => 10,
                'msg' => '提现金额不在允许范围内'
            ];
            return self::returnMsg($out_data);
        }
        $fees = $reality * ($platform->fees / 100);
        $data = [
            'trade_no' => '',
            'money' => $reality - $fees,
            'fees' => $fees,
            'original_money' => $money,
            'exchange_rate' => $exchange_rate,
            'from_address' => $from_address,
            'to_address' => $user_address,
            'type' => $type,
            'unit' => $platform->unit,
            'platform_id' => $platform->id,
            'remark' => '',
            'ip' => Util::getRealIp()
        ];
        $out_data = WithdrawServices::addWithdraw($user_id, $data);
        return self::returnMsg($out_data);
    }

    /**
     * 提现地址
     * @return \support\Response
     */
    public function withdraw_address()
    {
        $user_id = $this->user_id;
        $user_address = UserWalletServices::getWithdrawWallet($user_id, '');
        $out_data = [
            'code' => 0,
            'data' => $user_address
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 获取提现记录
     * @param Request $request
     * @return \support\Response
     */
    public function withdraw_record(Request $request)
    {
        $limit = $request->get('limit', 10);
        $status = $request->get('status', '');
        $where = [];
        if (!empty($status)) {
            $where[] = ['status', '=', $status];
        }
        $data = WithdrawServices::getWithdrawList($where, 'id', "DESC", $limit);
        $out_data = [
            'code' => 0,
            'data' => $data
        ];
        return self::returnMsg($out_data);
    }
}
