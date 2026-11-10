<?php


namespace app\event;


use app\services\SysServices;
use app\services\UserServices;

class User
{
    function register($data)
    {
        // 注册成功
        //更新推广关系
        UserServices::userRelationshipTree($data['user_id'], $data['invitation_code']);
        //赠送彩金
        $amount = SysServices::getSysConfigValue('web', 'bonus_money');
        $amount = floatval($amount);
        if ($amount > 0) {
            UserServices::giveCaiJin($data['user_id'], $amount);
        }
    }
}