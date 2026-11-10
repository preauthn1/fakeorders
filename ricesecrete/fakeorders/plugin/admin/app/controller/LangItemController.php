<?php

namespace plugin\admin\app\controller;

use plugin\admin\app\model\LangDetail;
use plugin\admin\app\model\LangItem;
use plugin\admin\app\services\LangServices;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 语言条目管理
 */
class LangItemController extends Crud
{

    /**
     * @var LangItem
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new LangItem();
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     */
    public function select(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $is_file = $request->post('is_file', '');
            if ($is_file == 'file') {
                $out_data = LangServices::export();
                return json($out_data);
            }
        }
        [$where, $format, $limit, $field, $order, $page] = $this->selectInput($request);
        if ($request->get('order', '') == '') {
            $order = 'DESC';
        }
        if (empty($field)) {
            $field = 'id';
        }
        $query = $this->doSelect($where, $field, $order);
        $methods = [
            'select' => 'formatSelect',
            'tree' => 'formatTree',
            'table_tree' => 'formatTableTree',
            'normal' => 'formatNormal',
        ];
        $paginator = $query->paginate($limit);
        $total = $paginator->total();
        $items = $paginator->items();
        $langs = LangServices::getLangList();
        $item_ids = [];
        foreach ($items as &$item) {
            $item_ids[] = $item['id'];
        }
        $lang_detail_model = new LangDetail();
        $lang_detail_query = $lang_detail_model->whereIn('item_id', $item_ids)->field('item_id,lang_id,text');
        $lang_detail_arr = [];
        $lang_detail_list = $lang_detail_query->select();
        foreach ($lang_detail_list as $lang_detail) {
            $lang_detail_arr['text_' . $lang_detail['item_id'] . $lang_detail['lang_id']] = $lang_detail['text'];
        }
        foreach ($items as &$item) {
            foreach ($langs as $lang) {
                if (isset($lang_detail_arr['text_' . $item['id'] . $lang['id']])) {
                    $item['text_' . $lang['id']] = $lang_detail_arr['text_' . $item['id'] . $lang['id']];
                } else {
                    $item['text_' . $lang['id']] = "-";
                }
            }
        }
        if (method_exists($this, "afterQuery")) {
            $items = call_user_func([$this, "afterQuery"], $items);
        }
        $format_function = $methods[$format] ?? 'formatNormal';
        return call_user_func([$this, $format_function], $items, $total);
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        $columns = [
            [
                'type' => 'checkbox',
            ], [
                'title' => '主键',
                'field' => 'id'
            ], [
                'title' => '类型',
                'field' => 'type'
            ],
            [
                'title' => '条目名称',
                'field' => 'title'
            ]
        ];
        $langs = LangServices::getLangList();
        foreach ($langs as $lang) {
            $columns[] = [
                'title' => $lang['remark'],
                'field' => 'text_' . $lang['id'],
            ];
        }
        $columns[] = [
            'title' => '更新时间',
            'field' => 'updated_at'
        ];
        $columns[] = [
            'title' => '操作',
            'toolbar' => '#table-bar',
            'align' => 'center',
            'fixed' => 'right',
            'width' => 130,
        ];
        return raw_view('langitem/index', ['columns' => json_encode($columns)]);
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
            $is_file = $request->post('is_file', '');
            if ($is_file == 'file') {
                $file = $request->file('file');
                $out_data = LangServices::import($file);
                return json($out_data);
            }
            $title = $request->post('title', '');
            $type = $request->post('type', 0);
            if (empty($title)) {
                return $this->json(1, '请填写条目名称');
            }
            $map = [
                'title' => $title,
                'type' => $type
            ];
            $temp = $this->model->where($map)->find();
            if ($temp) {
                return $this->json(1, '条目名称已存在');
            }
            $data = $this->insertInput($request);
            $id = $this->doInsert($data);
            if (empty($id)) {
                return $this->json(1, '添加失败');
            } else {
                $params = $request->post();
                LangServices::addDetail($id, $params);
            }
            return $this->json(0, 'ok');
        }
        $langs = LangServices::getLangList();
        $columns = [];
        foreach ($langs as $lang) {
            $columns[] = [
                'title' => $lang['remark'],
                'field' => 'lang_' . $lang['id'],
            ];
        }
        return raw_view('langitem/insert', ['columns' => $columns]);
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
            [$id, $data] = $this->updateInput($request);
            $this->doUpdate($id, $data);
            if (empty($id)) {
                return $this->json(1, '添加失败');
            } else {
                $params = $request->post();
                LangServices::addDetail($id, $params);
            }
            return $this->json(0, 'ok');
        }
        $id = $request->get('id');
        $langs = LangServices::getLangList();
        $columns = [];
        $lang_detail_model = new LangDetail();
        $lang_detail_list = $lang_detail_model->whereIn('item_id', [$id])->field(['item_id', 'lang_id', 'text'])->select();
        $lang_detail_arr = [];
        foreach ($lang_detail_list as $lang_detail) {
            $lang_detail_arr['lang_' . $lang_detail['item_id'] . $lang_detail['lang_id']] = $lang_detail['text'];
        }
        foreach ($langs as $lang) {
            $text = isset($lang_detail_arr['lang_' . $id . $lang['id']]) ? $lang_detail_arr['lang_' . $id . $lang['id']] : "";
            $columns[] = [
                'title' => $lang['remark'],
                'field' => 'lang_' . $lang['id'],
                'text' => $text,
            ];
        }
        return raw_view('langitem/update', ['columns' => $columns]);
    }


}
