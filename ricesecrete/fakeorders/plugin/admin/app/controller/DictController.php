<?php

namespace plugin\admin\app\controller;

use app\common\GoogleTranslate;
use app\common\Util;
use GuzzleHttp\Client;
use plugin\admin\app\model\Dict;
use plugin\admin\app\model\Lang;
use plugin\admin\app\model\Option;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 字典管理
 */
class DictController extends Base
{
    /**
     * 不需要授权的方法
     */
    protected $noNeedAuth = ['get', 'options', 'translate'];

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('dict/index');
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     */
    public function select(Request $request): Response
    {
        $name = $request->get('name', '');
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        $offset = ($page - 1) * $limit;
        if ($name && is_string($name)) {
            $items = Option::where('name', 'like', "dict_$name%")->limit($offset, $limit)->select()->toArray();
        } else {
            $items = Option::where('name', 'like', 'dict_%')->limit($offset, $limit)->select()->toArray();
        }
        $get_items = Option::where('name', 'like', "dict_$name%")->select()->toArray();
        $count = count($get_items);
        foreach ($items as &$item) {
            $item['name'] = Dict::optionNameTodictName($item['name']);
        }
        return json(['code' => 0, 'msg' => 'ok', 'count' => $count, 'data' => $items]);
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $name = $request->post('name');
            if (Dict::get($name)) {
                return $this->json(1, '字典已经存在');
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                return $this->json(2, '字典名称只能是字母数字下划线的组合');
            }
            $values = (array)$request->post('value', []);
            Dict::save($name, $values);
        }
        return raw_view('dict/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $name = $request->post('name');
            if (!Dict::get($name)) {
                return $this->json(1, '字典不存在');
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
                return $this->json(2, '字典名称只能是字母数字下划线的组合');
            }
            Dict::save($name, $request->post('value'));
        }
        return raw_view('dict/update');
    }

    /**
     * 删除
     * @param Request $request
     * @return Response
     */
    public function delete(Request $request): Response
    {
        $names = (array)$request->post('name');
        Dict::delete($names);
        return $this->json(0);
    }

    /**
     * 获取
     * @param Request $request
     * @param $name
     * @return Response
     */
    public function get(Request $request, $name): Response
    {
        return $this->json(0, 'ok', (array)Dict::get($name));
    }

    /**
     * 获取选项
     * @param Request $request
     * @return Response
     */
    public function options(Request $request): Response
    {
        $table = $request->get('table');
        $field = $request->get('field', 'name');
        $key = $request->get('key', 'id');
        //怎么动态引入 use plugin\admin\app\model\Option;
        //首字母转大写
        $table = '\plugin\admin\app\model\\' . ucfirst($table);
        $model = new $table();
        $options = $model->select()->toArray();
        $data = [];
        foreach ($options as $option) {
            $data[] = [
                'value' => $option[$key],
                'name' => $option[$field]
            ];
        }
        return $this->json(0, 'ok', $data);
    }

    /**
     * 翻译
     * @param Request $request
     * @return Response
     */
    public function translate(Request $request)
    {
        $source = 'zh-CN';//Source language, 源语言
        $data = [];
        $text = $request->get('text', '');
        if (empty($text)) {
            $text = $request->post('text', '');
        }
        if (!$text) {
            return $this->json(1, 'text is empty');
        }
//        $client = new Client([
//            'timeout' => 30,
//            'verify' => true,
//            'curl' => [
//                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
//                CURLOPT_SSL_VERIFYPEER => true,
//                CURLOPT_SSL_VERIFYHOST => 2,
//            ]
//        ]);
//        $response = $client->request('get', 'https://api.hkxahxcoin.com/wallet/v1/translate?text=' . $text);
//        $str = $response->getBody()->getContents();
//        $json = json_decode($str, true);
//        return json($json);
        $lang_model = new Lang();
        $langs = $lang_model->where('status', 1)->order('sort', 'asc')->select();
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
        return $this->json(0, 'ok', $data);
    }
}
