<?php


namespace app\services;


use app\model\user\UserWalletModel;

class UserWalletServices
{


    /**
     * 获取用户提现地址
     * @param $user_id
     * @param $address
     * @return array|mixed
     */
    public static function getWithdrawWallet($user_id, $address = '')
    {
        if (empty($address)) {
            $map = [
                'user_id' => $user_id,
                'withdraw' => 1
            ];
        } else {
            $map = [
                'user_id' => $user_id
            ];
        }
        $wallet = UserWalletModel::where($map)->order('id', 'desc')->find();
        if (empty($wallet)) {
            return [];
        }
        return json_decode(json_encode($wallet), true);
    }

    /**
     * 获取用户钱包
     * @param $user_id
     * @param int $withdraw
     * @param string $type
     * @return array|mixed
     */
    public static function getWalletOne($user_id, $withdraw = 0, $type = 'eip155')
    {
        $map = [
            'user_id' => $user_id,
            'withdraw' => $withdraw
        ];
        if (!empty($type)) {
            $map['type'] = $type;
        }
        $wallet = UserWalletModel::where($map)->order('id', 'desc')->find();
        if (empty($wallet)) {
            return '';
        }
        return $wallet->address;
    }

    /**
     * 获取用户钱包
     * @param $user_id
     * @param string $type
     * @return array|mixed
     */
    public static function getWallet($user_id, $type = 'eip155')
    {
        $map = [
            'user_id' => $user_id,
            'withdraw' => 0
        ];
        if (!empty($type)) {
            $map['type'] = $type;
        }
        $wallet = UserWalletModel::where($map)->select();
        if (empty($wallet)) {
            return [];
        }
        return json_decode(json_encode($wallet), true);
    }

    /**
     * 获取用户钱包地址对应用户id
     * @param $address
     * @return int|mixed
     */
    public static function getAddressUserId($address)
    {
        if (empty($address)) {
            return 0;
        }
        $user_id = UserWalletModel::where('address', $address)->value('user_id');
        if (empty($user_id)) {
            return 0;
        }
        return $user_id;
    }

    /**
     * 绑定钱包地址
     * @param $user_id
     * @param $type
     * @param $address
     * @param $withdraw
     * @return array
     */
    public static function addWallet($user_id, $type, $address, $withdraw = 0)
    {
        $map = [
            'type' => $type,
            'address' => $address
        ];
        $wallet = UserWalletModel::where($map)->find();
        if (!empty($wallet)) {
            if ($wallet->user_id == $user_id) {
                return [
                    'code' => 0,
                    'msg' => '钱包已存在'
                ];
            }
            return [
                'code' => 1,
                'msg' => '钱包已存在'
            ];
        }
        $in_add = [
            'user_id' => $user_id,
            'type' => $type,
            'address' => $address,
            'withdraw' => $withdraw,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $id = UserWalletModel::insertGetId($in_add);
        return [
            'code' => 0,
            'data' => [
                'id' => $id
            ]
        ];
    }
}