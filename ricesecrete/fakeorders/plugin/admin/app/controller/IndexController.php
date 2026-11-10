<?php

namespace plugin\admin\app\controller;

use app\model\user\UserModel;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Option;
use plugin\admin\app\services\LangServices;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use Workerman\Worker;

class IndexController
{

    /**
     * 无需登录的方法
     * @var string[]
     */
    protected $noNeedLogin = ['index', 'add_lang', 'get_lang'];

    /**
     * 不需要鉴权的方法
     * @var string[]
     */
    protected $noNeedAuth = ['dashboard'];

    /**
     * 后台主页
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function index(Request $request): Response
    {
        clearstatcache();
        if (!is_file(base_path('plugin/admin/config/database.php'))) {
            return raw_view('index/install');
        }
        $admin = admin();
        if (!$admin) {
            $name = 'system_config';
            $config = Option::where('name', $name)->value('value');
            $config = json_decode($config, true);
            $title = $config['logo']['title'] ?? 'webman admin';
            $logo = $config['logo']['image'] ?? '/app/admin/admin/images/logo.png';
            return raw_view('account/login', ['logo' => $logo, 'title' => $title]);
        }
        return raw_view('index/index');
    }

    /**
     * 仪表板
     * @param Request $request
     * @return Response
     * @throws Throwable
     */
    public function dashboard(Request $request): Response
    {
        // 今日新增用户数
        $today_user_count = UserModel::where('created_at', '>', date('Y-m-d') . ' 00:00:00')->count();
        // 7天内新增用户数
        $day7_user_count = UserModel::where('created_at', '>', date('Y-m-d H:i:s', time() - 7 * 24 * 60 * 60))->count();
        // 30天内新增用户数
        $day30_user_count = UserModel::where('created_at', '>', date('Y-m-d H:i:s', time() - 30 * 24 * 60 * 60))->count();
        // 总用户数
        $user_count = UserModel::count();
        // mysql版本
        $version = Util::db()->select('select VERSION() as version');
        $mysql_version = $version[0]->version ?? 'unknown';

        $day7_detail = [];
        $now = time();
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', $now - 24 * 60 * 60 * $i);
            $day7_detail[substr($date, 5)] = UserModel::where('created_at', '>', "$date 00:00:00")
                ->where('created_at', '<', "$date 23:59:59")->count();
        }

        return raw_view('index/dashboard', [
            'today_user_count' => $today_user_count,
            'day7_user_count' => $day7_user_count,
            'day30_user_count' => $day30_user_count,
            'user_count' => $user_count,
            'php_version' => PHP_VERSION,
            'workerman_version' => Worker::VERSION,
            'webman_version' => Util::getPackageVersion('workerman/webman-framework'),
            'admin_version' => Util::getPackageVersion('webman/admin'),
            'mysql_version' => $mysql_version,
            'os' => PHP_OS,
            'day7_detail' => array_reverse($day7_detail),
        ]);
    }

    /**
     * 获取语言包
     * @param Request $request
     * @return Response
     */
    public function get_lang(Request $request)
    {
        $lang_data = LangServices::getLangFile('', 1);
        if (!$lang_data) {
            $out_data = [
                'code' => 1,
                'msg' => '没有语言包'
            ];
            return json($out_data);
        }
        $lang = LangServices::getLangList();
        $out_data = [
            'code' => 0,
            'data' => [
                'list' => $lang_data,
                'lang' => $lang
            ]
        ];
        return json($out_data);
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
            return json([
                'code' => 0,
                'msg' => '成功'
            ]);
        }
        $auto_lang = config('app.auto_lang');
        if ($auto_lang) {
            if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $title)) {
                LangServices::addItem($title, 1);
            }
        }
        return json([
            'code' => 0,
            'msg' => '成功'
        ]);
    }
}
