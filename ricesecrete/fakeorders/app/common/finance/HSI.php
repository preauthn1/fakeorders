<?php


namespace app\common\finance;

use app\common\push\PushApi;
use support\Cache;

class HSI
{
    public static $redis_key = 'hsi_data_';
    // 指数接口
    public static $url = 'https://query1.finance.yahoo.com/v8/finance/chart/^HSI';

    /**
     * 获取涨跌情况
     * @return false|float|int
     */
    public static function getProfitClose($day = '2025-08-14')
    {
        $period1 = strtotime(date("Y-m-d", strtotime('-7 day', strtotime($day))));
        $period2 = strtotime($day . " 23:59:59");
        $url = self::$url . "?interval=1d&period1=" . $period1 . "&period2=" . $period2;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Content-Type: application/json;charset=utf-8',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($res, true);
        if (!isset($res['chart']['result'][0])) {
            return false;
        }
        try {
            $timestamp = $res['chart']['result'][0]['timestamp'];
            $quotes = $res['chart']['result'][0]['indicators']['quote'][0];
            $close = $quotes['close'];
            $now_day = date("Ymd", strtotime($day));
            $profit = false;
            foreach ($timestamp as $key => $value) {
                $day_key = date("Ymd", $value);
                if ($day_key == $now_day) {
                    if (!isset($close[$key - 1])) {
                        // 没有数据
                        break;
                    }
                    $top_Close = $close[$key - 1];
                    $yes_Close = $close[$key];
                    //涨跌情况 %
                    $profit = ($yes_Close - $top_Close) / $top_Close * 100;
                }
            }
            return $profit;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取指数 K线数据
     * @param string $interval // 5m, 15m, 30m, 60m, 1h, 1d, 1wk, 1mo
     * @param string $range //1d, 5d, 1mo, 3mo, 6mo, 1y, 5y, 10y, ytd, max
     */
    public static function getHSI($interval = '5m', $range = '5d')
    {
        $redis_key = self::$redis_key . $interval . '_' . $range;
        $redis = Cache::store('file');
        $temp = $redis->get($redis_key);
        if ($temp) {
            return json_decode($temp, true);
        }

        $url = self::$url . "?interval=" . $interval . "&range=" . $range;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Content-Type: application/json;charset=utf-8',
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        //$res 是zip 文件的二进制数据
        if ($res) {
            $res = gzdecode($res);
        }
        $res = json_decode($res, true);

        if (isset($res['chart']['result'][0])) {
            $data = $res['chart']['result'][0];
            $quote = $data['indicators']['quote'][0];
            unset($data['indicators']);
            unset($quote['volume']);
            foreach ($quote as $key => $value) {
                $now_value = 0;
                foreach ($value as $k => $v) {
                    if ($v == null) {
                        $quote[$key][$k] = $now_value;
                    } else {
                        $now_value = $v;
                    }
                }
            }
            $data['data'] = $quote;
            $redis->set($redis_key, json_encode($data), 300);
            $push_data = [
                'title' => '指数数据',//标题
                'type' => 4,//3:系统公告,4:指数数据
                'content' => '指数'// 内容
            ];
            PushApi::sendGroupMessage('data', $push_data);
            return $data;
        }
        return [];
    }
}