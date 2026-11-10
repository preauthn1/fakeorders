<?php


namespace app\event;


use app\services\RechargeRebatesServices;
use app\services\UserServices;

class Recharge
{
    function success($data)
    {
        // 充值成功回调
        $user_id = isset($data['user_id']) ? $data['user_id'] : 0;
        $number = isset($data['number']) ? $data['number'] : 0;
        $money = isset($data['money']) ? $data['money'] : 0;
        // 添加充值上级返水
        RechargeRebatesServices::addRechargeRebates($user_id, [
            'number' => $number,
            'money' => $money
        ]);
        // 更新上级会员等级
        UserServices::conditionTopLevel($user_id);
    }
}