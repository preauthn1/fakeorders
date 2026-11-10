<?php


namespace app\common\push;


use support\Cache;
use Webman\Push\Api;

class PushApi
{
    /**
     * 发送公共消息
     * @param $key
     * @param $data
     */
    public static function sendGroupMessage($key, $data)
    {
//        $data = [
//            'title' => '指数数据',//标题
//            'type' => 4,//3:系统公告,4:指数数据
//            'data' => [],
//            'content' => '测试消息内容'// 内容
//        ];
        $api = new Api(
        // webman下可以直接使用config获取配置，非webman环境需要手动写入相应配置
            config('plugin.webman.push.app.api'),
            config('plugin.webman.push.app.app_key'),
            config('plugin.webman.push.app.app_secret')
        );
        // 给订阅 user-1 的所有客户端推送
        $api->trigger('group-' . $key, 'message', $data);
    }

    /**
     * 发送消息
     * @param $uid
     * @param $data
     */
    public static function sendPrivateMessage($uid, $data)
    {
//        $data = [
//            'title' => '测试消息',//标题
//            'type' => 1,//0自定义消息，1:充值到账 2:提现到账
//            'data' => [
//                'user_money' => 0,//基础钱包
//            ],
//            'content' => '测试消息内容'// 内容
//        ];
        $api = new Api(
        // webman下可以直接使用config获取配置，非webman环境需要手动写入相应配置
            config('plugin.webman.push.app.api'),
            config('plugin.webman.push.app.app_key'),
            config('plugin.webman.push.app.app_secret')
        );
        // 给订阅 user-1 的所有客户端推送 message 事件的消息
        $api->trigger('private-user-' . $uid, 'message', $data);
    }

    /**
     * 获取用户是否在线
     * @param $uid
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     * @throws \Webman\Push\PushException
     */
    public static function getOnlineUser($uid)
    {
        $key = 'webman_push_user_line_ids';
        $redis = Cache::store('redis');
        $user_line_ids = []; //在线用户的id
        if ($redis->has($key)) {
            $user_line_ids_json = $redis->get($key);
            $user_line_ids = json_decode($user_line_ids_json, true);
            if (in_array($uid, $user_line_ids)) {
                return true;
            }
        }
        $api = new Api(
            str_replace('0.0.0.0', '127.0.0.1', config('plugin.webman.push.app.api')),
            config('plugin.webman.push.app.app_key'),
            config('plugin.webman.push.app.app_secret')
        );
        $result = $api->get('/channels', ['filter_by_prefix' => 'private-user-']);

        if ($result || $result['status'] == 200) {
            $channels = $result['result']['channels'];
            foreach ($channels as $k => $v) {
                $user_line_ids[] = intval(ltrim($k, 'private-user-'));
            }
            $redis->set($key, json_encode($user_line_ids), 20);
        }
        return in_array($uid, $user_line_ids);
    }
}