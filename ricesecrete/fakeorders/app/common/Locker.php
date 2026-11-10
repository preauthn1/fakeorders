<?php


namespace app\common;


use app\exception\MyBusinessException;
use support\Cache;

class Locker
{
    private $key;

    public function __construct($key = null)
    {
        $this->key = $key ?? md5(Util::getRandomString(15));
    }

    public function lock($timeout = 3)
    {
        $redis = Cache::store('redis');
        $key = $this->key;
        if ($redis->get($key)) {
            throw new MyBusinessException(lang('系统繁忙，请稍后再试'), 1);
        }
        $redis->set($key, 1, $timeout);
    }

    public function unLock()
    {
        $redis = Cache::store('redis');
        $redis->delete($this->key);
    }
}