<?php


namespace app\services;


use plugin\admin\app\model\ContactUs;
use plugin\admin\app\model\PaymentPlatform;
use support\Cache;
use plugin\admin\app\model\Option;

class SysServices
{

    /**
     * 获取系统配置
     * @param $key
     * @param $cache
     * @return mixed|string
     */
    public static function getSysConfig($key, $cache = true)
    {
        $name = 'system_config';
        $redis_key = $name . $key;
        if (Cache::store('file')->has($redis_key) && $cache) {
            return json_decode(Cache::store('file')->get($redis_key), true);
        }
        $config = Option::where('name', $name)->value('value');
        $data = json_decode($config, true);
        $data_key = $data[$key] ?? '';
        if (!$data_key) {
            return '';
        }
        Cache::store('file')->set($redis_key, json_encode($data_key), 3600);
        return $data[$key] ?? '';
    }

    /**
     * 获取配置
     * @param $name
     * @param $key
     * @param string $default
     * @return mixed|string
     */
    public static function getSysConfigValue($name, $key, $default = '')
    {
        $data = self::getSysConfig($name);
        return $data[$key] ?? $default;
    }

    /**
     * 获取配置
     * @param $name
     * @param bool $cache
     * @return mixedss
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     */
    public static function getConfigValue($name, $cache = true)
    {
        $redis_key = "config_value_" . $name;
        $redis = Cache::store('file');
        $data = $redis->get($redis_key);
        if (empty($data) || $cache == false) {
            $data = Option::where('name', $name)->value('value');
            $redis->set($redis_key, $data, 3600);
            $data = json_decode($data, true);
            return $data;
        }
        return json_decode($data, true);
    }

    /**
     * 获取支付方式列表
     * @param bool $cache
     * @return array|mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     */
    public static function getPaymentList($cache = true)
    {
        $key = 'payment_list';
        $redis = Cache::store('file');
        $data = $redis->get($key);
        if ($data && $cache) {
            return json_decode($data, true);
        }
        $data = PaymentPlatform::where('status', 1)
            ->field('id,name,type,image,min_recharge_money,max_recharge_money,recharge_is_show,withdraw_is_show,address,exchange_rate,fees')->select();
        if (!empty($data)) {
            $data = $data->toArray();
            $redis->set($key, json_encode($data), 3600);
        } else {
            $data = [];
        }
        return $data;
    }

    /**
     * 获取联系我们
     * @param bool $cache
     * @return array|mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     */
    public static function contactUs($cache = true)
    {
        $key = 'contact_us';
        $redis = Cache::store('file');
        $data = $redis->get($key);
        if ($data && $cache) {
            return json_decode($data, true);
        }
        $data = ContactUs::where('status', 1)->order('sort', 'desc')->select();
        if (!empty($data)) {
            $data = $data->toArray();
            $redis->set($key, json_encode($data), 3600);
        } else {
            $data = [];
        }
        return $data;
    }
}