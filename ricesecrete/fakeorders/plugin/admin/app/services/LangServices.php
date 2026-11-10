<?php


namespace plugin\admin\app\services;


use app\common\GoogleTranslate;
use plugin\admin\app\model\Lang;
use plugin\admin\app\model\LangDetail;
use plugin\admin\app\model\LangItem;
use support\Cache;

class LangServices
{

    /**
     * 添加语言条目
     * @param $title
     * @param $type 0前端 1后台
     * @return int|mixed
     */
    public static function addItem($title, $type = 0)
    {
        $lang_item_model = new LangItem();
        $map = [
            'title' => $title,
            'type' => $type
        ];
        $temp = $lang_item_model->where($map)->find();
        if ($temp) {
            return $temp['id'];
        }
        $param = [
            'title' => $title,
            'type' => $type,
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
        ];
        $id = $lang_item_model->insertGetId($param);
        $param_temp = [];
        $langs = LangServices::getLangList();
        foreach ($langs as $lang) {
            $param_temp = [
                'lang_' . $lang['id'] => ""
            ];
        }
        LangServices::addDetail($id, $param_temp);
        return $id;
    }

    /**
     * 添加多语言翻译
     * @param $id
     * @param array $params
     * @return bool
     */
    public static function addDetail($id, $params = [])
    {
        $lang_detail_model = new LangDetail();
        foreach ($params as $key => $text) {
            //判断$key 是否是lang_开头
            if (str_starts_with($key, 'lang_')) {
                $lang_id = str_replace('lang_', '', $key);
                $map = [
                    'item_id' => $id,
                    'lang_id' => $lang_id,
                ];
                $temp = $lang_detail_model->where($map)->find();
                if ($temp) {
                    $lang_detail_model->where($map)->update(['text' => $text]);
                } else {
                    $in_data = [
                        'item_id' => $id,
                        'lang_id' => $lang_id,
                        'text' => $text,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $lang_detail_model->insert($in_data);
                }
            }
        }
        // 写入语言文件
        LangServices::writeLangFile(0);
        LangServices::writeLangFile(1);
        return true;
    }

    /**
     * 翻译指定条目
     * @param $item_id
     * @return bool
     */
    public static function translate($item_id)
    {
        $lang_item = LangItem::where('id', $item_id)->find();
        $lang_detail_model = new LangDetail();
        $langs = LangServices::getLangList();
        $title = $lang_item->title;
        foreach ($langs as $item) {
            $lang_id = $item['id'];
            $map = [
                'item_id' => $item_id,
                'lang_id' => $lang_id
            ];
            $lang_detail = $lang_detail_list = $lang_detail_model->where($map)->find();
            if ($lang_detail) {
                $text = $lang_detail['text'];
                if (empty($text)) {
                    $lang = isset($langs[$lang_id]) ? $langs[$lang_id]['lang'] : 'en';
                    $text = GoogleTranslate::translate('zh-CN', $lang, $title);
                    echo $lang . "->" . $text . "\n";
                    $lang_detail_model->where('item_id', $item_id)->where('lang_id', $lang_id)->update(['text' => $text]);
                }
            } else {
                $lang = isset($langs[$lang_id]) ? $langs[$lang_id]['lang'] : 'en';
                $text = GoogleTranslate::translate('zh-CN', $lang, $title);
                echo $lang . "->" . $text . "\n";
                $lang_detail_model->insert([
                    'item_id' => $item_id,
                    'lang_id' => $lang_id,
                    'text' => $text,
                    'created_at' => date("Y-m-d H:i:s"),
                    'updated_at' => date("Y-m-d H:i:s"),
                ]);
            }
        }
        return true;
    }

    /**
     * 获取语言列表
     * @return array|mixed
     */
    public static function getLangList()
    {
        $redis = Cache::store('redis');
        $lang_str = $redis->get('lang_list');
        if (!empty($lang_str)) {
            return json_decode($lang_str, true);
        }
        $lang_model = new Lang();
        $langs = $lang_model->where('status', 1)->order('sort', 'asc')->select();
        $lang_arr = [];
        foreach ($langs as $lang) {
            $lang_arr[$lang->id] = [
                'id' => $lang->id,
                'title' => $lang->title,
                'lang' => $lang->lang,
                'is_default' => $lang->is_default,
                'remark' => $lang->remark
            ];
        }
        $redis->set('lang_arr', json_encode($lang_arr), 300);
        return $lang_arr;
    }

    /**
     * 清除语言缓存
     * @return array|mixed
     */
    public static function setLangClear()
    {
        $redis = Cache::store('redis');
        $redis->delete('lang_list');
        return true;
    }

    /**
     * 写入语言文件
     * @param int $type
     */
    public static function writeLangFile($type = 0)
    {
        $langs = LangServices::getLangList();

        $items = LangItem::where('type', $type)->field('id, title')->select();
        $items_detail = LangDetail::field(['id', 'lang_id', 'item_id', 'text'])->select();
        $items_detail_arr = [];
        foreach ($items_detail as $item) {
            $items_detail_arr[$item['lang_id'] . "_" . $item['item_id']] = $item['text'];
        }
        $lang_config = [];
        foreach ($langs as $lang) {
            $lang_config[$lang['lang']] = [];
            foreach ($items as $item) {
                $lang_config[$lang['lang']][$item['title']] = $items_detail_arr[$lang['id'] . "_" . $item['id']] ?? $item['title'];
            }
        }

        foreach ($lang_config as $lang => $config) {
            if ($type == 0) {
                $path = base_path() . "/lang";
            } else {
                $path = base_path() . "/plugin/admin/lang";
            }
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
            $lang_file = $path . "/{$lang}/messages.php";
            if (!file_exists($path . "/{$lang}")) {
                mkdir($path . "/{$lang}", 0777, true);
            }
            $code = "<?php \r\nreturn " . var_export(array_filter($config), true) . ";";
            file_put_contents($lang_file, $code);
        }
        if ($type == 0) {
            Cache::set('lang_cache_time', time());
        } else {
            Cache::set('admin_lang_cache_time', time());
        }
    }

    /**
     * 获取默认语言
     * @return mixed|string
     */
    public static function getDefaultLang()
    {
        $langs = LangServices::getLangList();
        foreach ($langs as $lang) {
            if ($lang['is_default']) {
                return $lang['lang'];
            }
        }
        return "en";
    }

    /**
     * 获取语言包文件
     * @param string $lang
     * @param int $type 0:前端 1:后台
     * @return array
     */
    public static function getLangFile($lang = '', $type = 0)
    {
        if ($type == 0) {
            $path = base_path() . "/lang";
        } else {
            $path = base_path() . "/plugin/admin/lang";
        }
        $lang_list = [];
        if (empty($lang)) {
            $langs = LangServices::getLangList();
            foreach ($langs as $item) {
                $file = $path . "/{$item['lang']}/messages.php";
                if (!file_exists($file)) {
                    $lang_list[$item['lang']] = [];
                } else {
                    // @noinspection PhpIncludeInspection
                    $lang_list[$item['lang']] = include $file;
                }
            }
        } else {
            $file = $path . "/{$lang}/messages.php";
            if (!file_exists($file)) {
                $lang_list[$lang] = [];
            } else {
                // @noinspection PhpIncludeInspection
                $lang_list[$lang] = include $file;
            }
        }
        return $lang_list;
    }

    /**
     * 导入语言包
     * @param $file
     * @return array
     */
    public static function import($file)
    {
        if (empty($file)) {
            return [
                'code' => 1,
                'msg' => '请选择文件'
            ];
        }
        $tempDir = public_path();
        $fileName = 'lang_import.csv';
        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
        // 将文件移动到指定路径
        $info = $file->move($filePath);
        if ($info) {
            // 打开文件
            if (($handle = fopen($filePath, "r")) !== FALSE) {
                $data = [];
                // 读取文件内容并转换为 UTF-8 编码
                $fileContent = file_get_contents($filePath);
                $encoding = mb_detect_encoding($fileContent, 'UTF-8,GB2312,GBK,BIG5');
                $fileContent = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
                file_put_contents($filePath, $fileContent);
                // 重新打开文件
                $handle = fopen($filePath, "r");
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // 将每一行的数据转换为 UTF-8 编码
                    $data[] = $row;
                }
                fclose($handle);
                // 删除临时文件
                unlink($filePath);
                $item_detail_list = LangDetail::where('id', '>', 0)->select();
                $item_detail_arr = [];
                foreach ($item_detail_list as $item) {
                    $item_detail_arr[$item['lang_id'] . "_" . $item['item_id']] = $item['text'];
                }
                // 处理数据
                if (!empty($data)) {
                    $langs = self::getLangList();
                    $langs_code_id = [];
                    foreach ($langs as $lang) {
                        $langs_code_id[$lang['lang']] = $lang['id'];
                    }
                    // 假设第一行是标题行
                    $headers = array_shift($data);
                    $list_update = [];
                    $list_add = [];
                    foreach ($data as $row) {
                        $item = array_combine($headers, $row);
                        foreach ($item as $key => $value) {
                            if ($key != 'ID' && $key != '条目') {
                                $map = [
                                    'item_id' => $item["ID"],
                                    'lang_id' => $langs_code_id[$key],
                                ];
                                if (isset($item_detail_arr[$langs_code_id[$key] . "" . $item["ID"]])) {
                                    if ($item_detail_arr[$langs_code_id[$key] . "" . $item["ID"]] != $value) {
                                        $list_update[] = [
                                            'item_id' => $item["ID"],
                                            'lang_id' => $langs_code_id[$key],
                                            'text' => $value,
                                        ];
                                    }
                                } else {
                                    $temp = LangDetail::where($map)->find();
                                    if (!empty($temp)) {
                                        if ($temp['text'] != $value) {
                                            $list_update[] = [
                                                'item_id' => $item["ID"],
                                                'lang_id' => $langs_code_id[$key],
                                                'text' => $value,
                                            ];
                                        }
                                    } else {
                                        $list_add[] = [
                                            'item_id' => $item["ID"],
                                            'lang_id' => $langs_code_id[$key],
                                            'text' => $value
                                        ];
                                    }
                                }
                            }
                        }
                    }
                    foreach ($list_update as $item) {
                        $map = [
                            'item_id' => $item["item_id"],
                            'lang_id' => $item['lang_id'],
                        ];
                        $updata = [
                            'text' => $item['text'],
                        ];
                        //更新文本内容
                        LangDetail::where($map)->update($updata);
                    }
                    foreach ($list_add as $item) {
                        LangDetail::insert($item);
                    }
                    LangServices::writeLangFile(0);
                    LangServices::writeLangFile(1);
                    return [
                        'code' => 0,
                        'msg' => '导入成功'
                    ];
                } else {
                    return [
                        'code' => 1,
                        'msg' => '没有数据'
                    ];
                }
            } else {
                return [
                    'code' => 1,
                    'msg' => '打开文件失败'
                ];
            }
        } else {
            return [
                'code' => 1,
                'msg' => '上传失败'
            ];
        }
    }

    /**
     * 导出
     * @return array
     */
    public static function export()
    {
        $langs = LangServices::getLangList();
        $lang_arr = [];
        $lang_arr[] = 'ID';
        $lang_arr[] = '条目';
        foreach ($langs as $lang) {
            $lang_arr[] = $lang['lang'];
        }
        $item_arr = LangItem::where('id', '>', 0)->field(['id', 'title'])->select();
        $item_detail_list = LangDetail::where('id', '>', 0)->select();
        $item_detail_arr = [];
        foreach ($item_detail_list as $item) {
            $item_detail_arr[$item['lang_id'] . "_" . $item['item_id']] = $item['text'];
        }
        $list_data = [];
        $list_data[] = $lang_arr;
        foreach ($item_arr as $value) {
            $line_arr = [];
            $line_arr[] = $value->id;
            $line_arr[] = $value->title;
            foreach ($langs as $lang) {
                $line_arr[] = $item_detail_arr[$lang['id'] . "_" . $value->id] ?? '';
            }
            $list_data[] = $line_arr;
        }
        //获取文件地址
        $file_path = public_path() . '/lang.csv';
        $output = fopen($file_path, 'w');
        // 将数据写入 CSV 文件
        foreach ($list_data as $row) {
            fputcsv($output, $row);
        }
        // 关闭输出流
        fclose($output);
        return [
            'code' => 0,
            'msg' => '导出成功',
            'data' => 'lang.csv'
        ];
    }

    public static function translateAll()
    {
        $item_ids = LangItem::where('id', '>', 0)->select();
        foreach ($item_ids as $item_id) {
            $item_id = $item_id->id;
            self::translate($item_id);
        }
        LangServices::writeLangFile(0);
        LangServices::writeLangFile(1);
    }
}