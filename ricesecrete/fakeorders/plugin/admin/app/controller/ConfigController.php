<?php

namespace plugin\admin\app\controller;

use app\common\payment\YpUpay;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Option;
use plugin\admin\app\model\PaymentPlatform;
use support\Cache;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use function Carbon\int;

/**
 * 系统设置
 */
class ConfigController extends Base
{
    /**
     * 不需要验证权限的方法
     * @var string[]
     */
    protected $noNeedAuth = ['get'];

    /**
     * 账户设置
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('config/index');
    }

    /**
     * 获取配置
     * @return Response
     */
    public function get(): Response
    {
        $platform = PaymentPlatform::where('id', 1)->find();
        $WalletPayment = new YpUpay($platform, '');
        $temp = $WalletPayment->get_withdrawal_wallet();
        $data = $this->getByDefault();
        if ($temp['code'] == 0) {
            $data['withdrawal_list'] = $temp['data'];
        }
        return $this->json(0, 'ok', $data);
    }

    /**
     * 基于配置文件获取默认权限
     * @return mixed
     */
    protected function getByDefault()
    {
        $name = 'system_config';
        $config = Option::where('name', $name)->value('value');
        if (empty($config)) {
            $config = file_get_contents(base_path('plugin/admin/public/config/pear.config.json'));
            if ($config) {
                $option = new Option();
                $option->name = $name;
                $option->value = $config;
                $option->save();
            }
        }
        $config = json_decode($config, true);
        if (empty($config)) {
            $config = file_get_contents(base_path('plugin/admin/public/config/pear.config.json'));
            $config = json_decode($config, true);
        }
        return $config;
    }

    /**
     * 更改
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function update(Request $request): Response
    {
        $post = $request->post();
        $config = $this->getByDefault();
        $data = [];
        foreach ($post as $section => $items) {
//            if (!isset($config[$section])) {
//                continue;
//            }
            switch ($section) {
                case 'logo':
                    $data[$section]['title'] = htmlspecialchars($items['title'] ?? '');
                    $data[$section]['image'] = htmlspecialchars($items['image'] ?? '');
                    $data[$section]['timezone'] = htmlspecialchars($items['timezone'] ?? '');
                    $data[$section]['icon'] = htmlspecialchars($items['icon'] ?? '');
                    $data[$section]['footer_txt'] = htmlspecialchars($items['footer_txt'] ?? '');
                    $data[$section]['copyright'] = htmlspecialchars($items['copyright'] ?? '');
                    break;
                case 'web':
                    $data[$section]['title'] = htmlspecialchars($items['title'] ?? '');
                    $data[$section]['logo'] = htmlspecialchars($items['logo'] ?? '');
                    $data[$section]['reg_type'] = intval($items['reg_type'] ?? '0');
                    $data[$section]['reg_code_email'] = intval($items['reg_code_email'] ?? '0');
                    $data[$section]['reg_code_mobile'] = intval($items['reg_code_mobile'] ?? '0');
                    $data[$section]['reg_code_time'] = intval($items['reg_code_time'] ?? '180');
                    $data[$section]['invitation'] = intval($items['invitation'] ?? '0');
                    $data[$section]['bonus_money'] = floatval($items['bonus_money'] ?? '0');
                    $data[$section]['service'] = $items['service'] ?? '';
                    break;
                case 'wallet':
                    $data[$section]['collection'] = htmlspecialchars($items['collection'] ?? '');
                    $data[$section]['collection_type'] = htmlspecialchars($items['collection_type'] ?? '');
                    $data[$section]['withdraw_wait_num'] = htmlspecialchars($items['withdraw_wait_num'] ?? 0);
                    $data[$section]['recharge_rebates_rate'] = htmlspecialchars($items['recharge_rebates_rate'] ?? 0);
                    $data[$section]['recharge_rebates_cycle'] = floatval($items['recharge_rebates_cycle'] ?? 0);
                    break;
                case 'menu':
                    $data[$section]['data'] = Util::filterUrlPath($items['data'] ?? '');
                    $data[$section]['accordion'] = !empty($items['accordion']);
                    $data[$section]['collapse'] = !empty($items['collapse']);
                    $data[$section]['control'] = !empty($items['control']);
                    $data[$section]['controlWidth'] = (int)($items['controlWidth'] ?? 2000);
                    $data[$section]['select'] = (int)$items['select'] ?? 0;
                    $data[$section]['async'] = true;
                    break;
                case 'tab':
                    $data[$section]['enable'] = true;
                    $data[$section]['keepState'] = !empty($items['keepState']);
                    $data[$section]['preload'] = !empty($items['preload']);
                    $data[$section]['session'] = !empty($items['session']);
                    $data[$section]['max'] = Util::filterNum($items['max'] ?? '30');
                    $data[$section]['index']['id'] = Util::filterNum($items['index']['id'] ?? '0');
                    $data[$section]['index']['href'] = Util::filterUrlPath($items['index']['href'] ?? '');
                    $data[$section]['index']['title'] = htmlspecialchars($items['index']['title'] ?? '首页');
                    break;
                case 'theme':
                    $data[$section]['defaultColor'] = Util::filterNum($items['defaultColor'] ?? '2');
                    $data[$section]['defaultMenu'] = $items['defaultMenu'] ?? '' == 'dark-theme' ? 'dark-theme' : 'light-theme';
                    $data[$section]['defaultHeader'] = $items['defaultHeader'] ?? '' == 'dark-theme' ? 'dark-theme' : 'light-theme';
                    $data[$section]['allowCustom'] = !empty($items['allowCustom']);
                    $data[$section]['banner'] = !empty($items['banner']);
                    break;
                case 'colors':
                    foreach ($config['colors'] as $index => $item) {
                        if (!isset($items[$index])) {
                            $config['colors'][$index] = $item;
                            continue;
                        }
                        $data_item = $items[$index];
                        $data[$section][$index]['id'] = $index + 1;
                        $data[$section][$index]['color'] = $this->filterColor($data_item['color'] ?? '');
                        $data[$section][$index]['second'] = $this->filterColor($data_item['second'] ?? '');
                    }
                    break;

            }
            $name = 'system_config';
            $redis_key = $name . $section;
            Cache::store('file')->delete($redis_key);
        }

        $config = array_merge($config, $data);
        $name = 'system_config';
        Option::where('name', $name)->update([
            'value' => json_encode($config)
        ]);
        return $this->json(0);
    }

    /**
     * 颜色检查
     * @param string $color
     * @return string
     * @throws BusinessException
     */
    protected function filterColor(string $color): string
    {
        if (!preg_match('/\#[a-zA-Z]6/', $color)) {
            throw new BusinessException('参数错误');
        }
        return $color;
    }

}
