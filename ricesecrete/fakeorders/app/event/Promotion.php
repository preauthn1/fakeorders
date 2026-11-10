<?php

namespace app\event;

use app\common\channel\FacebookChannel;
use app\common\channel\KwaiChannel;
use app\common\channel\TikTokChannel;
use app\services\UserServices;

class Promotion
{
    public static array $event_name = [
        'kwai' => [
            'register' => 'EVENT_COMPLETE_REGISTRATION', // 注册事件
            'login' => '', // 登录事件
            'content_view' => 'EVENT_CONTENT_VIEW', // 内容浏览事件
            'first_deposit' => 'EVENT_FIRST_DEPOSIT', // 首充事件
        ],
        'tiktok' => [
            'register' => 'CompleteRegistration', // 注册事件
            'login' => '', // 登录事件
            'content_view' => 'ViewContent', // 内容浏览事件
            'first_deposit' => 'CompletePayment', // 首充事件
        ],
        'facebook' => [
            'register' => 'CompleteRegistration', // 注册事件
            'login' => '', // 登录事件
            'content_view' => 'PageView', // 内容浏览事件
            'first_deposit' => 'Purchase', // 首充事件
        ],
    ];

    /**
     * 成功
     * @param $data
     */
    function success($data)
    {
        //$user_id, 'recharge', ['value' => $amount],$test_event_code
        $user_id = $data['user_id'];
        $user = UserServices::getUserValue($user_id, ['pix', 'click_id', 'platform']);
        $event_name = $data['event_name'];
        $event_data = $data['event_data'];
        if (!empty($user['pix'])) {
            switch ($user['platform']) {
                case 'kwai':
                    KwaiChannel::event($data, self::$event_name[$user['platform']][$event_name],
                        $event_data);
                    break;
                case 'tiktok':
                    TikTokChannel::event($user, self::$event_name[$user['platform']][$event_name],
                        $event_data);
                    break;
                case 'facebook':
                    FacebookChannel::event($user, self::$event_name[$user['platform']][$event_name], $event_data);
                    break;
            }
        }
    }
}
