<?php

namespace app\controller\v1;

use app\common\BaseController;
use app\common\finance\HSI;
use app\common\GoogleTranslate;
use app\common\upload\S3;
use app\services\PledgeLongServices;
use app\services\SysServices;
use app\services\UserServices;
use plugin\admin\app\model\Content;
use plugin\admin\app\model\Lang;
use plugin\admin\app\services\FileExportServices;
use plugin\admin\app\services\LangServices;
use support\Cache;
use support\Request;
use Webman\Event\Event;
use Webman\RedisQueue\Redis;

class IndexController extends BaseController
{
    public function test(Request $request)
    {
//        $out_data = FileExportServices::addExportTask([], '\plugin\admin\app\services\MessageServices::exportFile');
//        $out_data['data'] = PledgeLongServices::getPledgeLongStat();
//        $queue = 'send_mail';
//        // 数据，可以直接传数组，无需序列化
//        $data = ['to' => 'tom@gmail.com', 'content' => 'hello'];
//        // 投递消息
//        Redis::send($queue, $data);
//        $data = PledgeCurrentServices::relievePledgeCurrent($uid, $amount);
//        $data = PledgeCurrentServices::settlementPledgeCurrent($uid,2);
//        Event::dispatch('recharge.success', [
//            'user_id' => 13,
//            'number' => 10023231002323,
//            'money' => 100
//        ]);
//        $amount = SysServices::getSysConfigValue('web', 'bonus_money');
//        print_r($amount);
        $out_data = [
            'code' => 0,
            'data' => []
        ];
        return self::returnMsg($out_data);
//        $type = $request->get('type', 'group');
//        if ($type == 'group') {
//            PushApi::sendGroupMessage('data', [
//                'title' => '指数数据',
//                'type' => 4,
//                'data' => [
//                    'title' => '指数数据',
//                    'day' => '2022-01-01'
//                ],
//                'content' => '测试消息内容'
//            ]);
//        } else {
//            PushApi::sendPrivateMessage($uid, [
//                'title' => '测试消息',
//                'type' => 1,
//                'content' => '测试消息内容'
//            ]);
//        }
//        $out_data = [
//            'code' => 0,
//            'msg' => '成功'
//        ];
//        return self::returnMsg($out_data);
    }

    public function index(Request $request)
    {
        $redis = Cache::store('redis');
        $redis->delete('admin_log_rules_tree');
        $out_data = [
            'code' => 1,
            'msg' => '确认'
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 翻译
     * @param Request $request
     * @return \support\Response
     */
    public function translate(Request $request)
    {
        $source = 'zh-CN';//Source language, 源语言
        $text = $request->input('text', '');
        if (!$text) {
            return self::returnMsg(1, [], 'text is empty');
        }
        $lang_model = new Lang();
        $langs = $lang_model->where('status', 1)->order('sort', 'asc')->select();
        $data = [];
        foreach ($langs as $lang) {
            if ($lang->lang == 'zh-cn') {
                $lang_text = "zh-CN";
            } else if ($lang->lang == 'zh-tw') {
                $lang_text = "zh-TW";
            } else {
                $lang_text = $lang->lang;
            }
            $result = GoogleTranslate::translate($source, $lang_text, $text);
            $data['lang_' . $lang->id] = $result;
        }
        $out_data = [
            'code' => 0,
            'data' => $data
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 虚拟图表数据
     * @return \support\Response
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     */
    public function virtual_chart()
    {
        //"name": "\u5047\u6570\u636e\u56fe\u8868",
        //	"market": "18882,19381,22312,36923,48999,59191,67131",
        //	"price": "1812,2381,3312,3623,4999,5191,7131",
        //	"circulating": "1812,2381,3312,3623,4999,5191,7131",
        //	"pledge": "18882,19381,22312,36923,48999,59191,67131"
        $virtual_chart = SysServices::getConfigValue('virtual_chart');
        $market = [];
        $price = [];
        $circulating = [];
        $pledge = [];
        $day = 7;
        $days = [];
        for ($i = $day; $i > 0; $i--) {
            $days[] = strtotime(date('Y-m-d', strtotime("-$i day")));
        }
        if (empty($virtual_chart)) {
            //模拟数据
            foreach ($days as $day) {
                $market[] = rand(1000, 9999);
                $price[] = rand(1000, 9999);
                $circulating[] = rand(1000, 9999);
                $pledge[] = rand(1000, 9999);
            }
        } else {
            $market = array_map('floatval', explode(',', $virtual_chart['market']));
            $price = array_map('floatval', explode(',', $virtual_chart['price']));
            $circulating = array_map('floatval', explode(',', $virtual_chart['circulating']));
            $pledge = array_map('floatval', explode(',', $virtual_chart['pledge']));
        }
        $out_data = [
            'code' => 0,
            'data' => [
                'day' => $days,
                'market' => $market,
                'price' => $price,
                'circulating' => $circulating,
                'pledge' => $pledge,
            ]
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 获取语言包
     * @return \support\Response
     */
    public function lang_list()
    {
        $lang_data = LangServices::getLangFile('', 0);
        if (!$lang_data) {
            $out_data = [
                'code' => 1,
                'msg' => '没有语言包'
            ];
            return self::returnMsg($out_data);
        }
        $out_data = [
            'code' => 0,
            'data' => $lang_data
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 添加未翻译的条目
     * @param Request $request
     * @return \support\Response
     */
    public function add_lang(Request $request)
    {
        $title = $request->input('title', '');
        if (empty($title)) {
            $out_data = [
                'code' => 0,
                'msg' => '成功'
            ];
            return self::returnMsg($out_data);
        }
        $auto_lang = config('app.auto_lang');
        if ($auto_lang) {
            if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $title)) {
                LangServices::addItem($title, 0);
            }
        }
        $out_data = [
            'code' => 0,
            'msg' => '成功'
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 获取系统配置
     * @return \support\Response
     */
    public function config()
    {
        $logo = SysServices::getSysConfig('logo', false);
        $web = SysServices::getSysConfig('web', false);
        if (empty($logo)) {
            $out_data = [
                'code' => 1,
                'msg' => '没有配置项'
            ];
            return self::returnMsg($out_data);
        }
        $langs = LangServices::getLangList();
        $default_lang = "en";
        foreach ($langs as $lang) {
            if ($lang['is_default'] == 1) {
                $default_lang = $lang['lang'];
            }
        }
        $lang_cache_time = Cache::get('lang_cache_time', time());
        $wallet = SysServices::getPaymentList();
        $wallet_online = [];
        $wallet_third = [];
        $wallet_offline = [];
        foreach ($wallet as $wallet_item) {
            if ($wallet_item['type'] == 1) {
                $wallet_online[] = $wallet_item;
            }
            if ($wallet_item['type'] == 2) {
                $wallet_third[] = $wallet_item;
            }
            if ($wallet_item['type'] == 3) {
                $wallet_offline[] = $wallet_item;
            }
        }

        $level = UserServices::levelList();
        $contact_us = SysServices::contactUs();
        if (isset($logo['footer_txt'])) {
            if (!empty($logo['footer_txt'])) {
                $logo['footer_txt'] = lang($logo['footer_txt']);
            }
        }
        $out_data = [
            'code' => 0,
            'data' => [
                'config' => $logo,
                'contact_us' => $contact_us,
                'web' => $web,
                'lang' => $langs,
                'wallet' => $wallet,
                'wallet_online' => $wallet_online,
                'wallet_third' => $wallet_third,
                'wallet_offline' => $wallet_offline,
                'level' => $level,
                'default_lang' => $default_lang,
                'lang_cache_time' => $lang_cache_time
            ]
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 上传文件
     * @param Request $request
     * @return \support\Response
     */
    public function upload(Request $request)
    {
        $data = S3::uploadFile($request);
        return self::returnMsg($data);
    }

    /**
     * 获取公告内容
     * @param Request $request
     * @return \support\Response
     */
    public function content(Request $request)
    {
        $type = $request->input('type', '');
        $format = $request->input('format', 'one');
        $lang = $request->input('lang', 'en');
        if ($format == 'one') {
            $data = Content::where('type', $type)->where('lang', $lang)->find();
        } else {
            $data = Content::where('type', $type)->where('lang', $lang)->select();
        }
        if (!$data) {
            $out_data = [
                'code' => 1,
                'msg' => '没有内容'
            ];
            return self::returnMsg($out_data);
        }
        $out_data = [
            'code' => 0,
            'data' => $data
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 获取恒生指数K线数据
     * @param Request $request
     * @return \support\Response
     */
    public function hsi_list(Request $request)
    {
        $interval = $request->input('interval', '5m');
        $range = $request->input('range', '5d');
        $data = HSI::getHSI($interval, $range);
        $out_data = [
            'code' => 0,
            'data' => $data
        ];
        return self::returnMsg($out_data);
    }
}
