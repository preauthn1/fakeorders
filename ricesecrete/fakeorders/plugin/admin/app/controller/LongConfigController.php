<?php


namespace plugin\admin\app\controller;


use app\services\SysServices;
use plugin\admin\app\model\Option;
use support\exception\BusinessException;
use support\Request;
use support\Response;

class LongConfigController extends Base
{
    /**
     * 不需要验证权限的方法
     * @var string[]
     */
    protected $noNeedAuth = ['get'];

    protected $name = 'pledge_long';

    /**
     * 活期质押配置
     * @return Response
     * @throws \Throwable
     */
    public function index(): Response
    {
        return raw_view('longconfig/index');
    }

    /**
     * 获取配置
     * @return Response
     */
    public function get(): Response
    {
        return $this->json(0, 'ok', $this->getByDefault());
    }

    /**
     * 基于配置文件获取默认权限
     * @return mixed
     */
    protected function getByDefault()
    {
        $name = $this->name;
        $config = Option::where('name', $name)->value('value');
        $config = json_decode($config, true);
        if (empty($config)) {
            $config = [
                'name' => "",
                'settlement_time' => "12:30",
            ];
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
        $name = $this->name;
        $post = $request->post();
        $config = $this->getByDefault();
        $config = array_merge($config, $post);
        Option::where('name', $name)->update([
            'value' => json_encode($config)
        ]);
        SysServices::getConfigValue($name, false);
        return $this->json(0);
    }
}