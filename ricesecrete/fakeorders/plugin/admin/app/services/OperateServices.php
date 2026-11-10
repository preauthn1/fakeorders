<?php


namespace plugin\admin\app\services;


use plugin\admin\app\common\Tree;
use plugin\admin\app\model\Operate;
use plugin\admin\app\model\Rule;
use support\Cache;

class OperateServices
{
    public static function addOperateLog($admin_id, $path, $param, $method)
    {
        // `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
        //  `admin_id` int DEFAULT '0' COMMENT '管理员id',
        //  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '名称',
        //  `path` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '路由全称',
        //  `param` text COLLATE utf8mb4_general_ci COMMENT '请求参数',
        $param = json_encode($param);
        if (strlen($param) > 2000) {
            $param = substr($param, 0, 2000);
        }
        $path_s = strtolower($path);
        $path_s = str_replace('/', '_', $path_s);
        if (strpos($path_s, '_insert') || strpos($path_s, '_update') || strpos($path_s, '_delete')) {
            $getRulesTree = self::getRulesTree();
            $title = isset($getRulesTree[$path_s]) ? $getRulesTree[$path_s] : '未知';
            $model = new Operate();
            $model->admin_id = $admin_id;
            $model->title = $title;
            $model->path = $path;
            $model->param = $param;
            $model->method = $method;
            $model->save();
        }
    }

    public static function getRulesTree()
    {
        $cache = Cache::store('file');
        $name_list_str = $cache->get('admin_log_rules_tree');
        if (!empty($name_list_str)) {
            return json_decode($name_list_str, true);
        }
        $rules = Rule::where('id', '>', 0)->field(['id', 'title', 'key', 'pid'])->select();
        $Tree = new Tree($rules);
        $menuTree = $Tree->getTree();
        $name_list_arr = [];
        foreach ($menuTree as $k => $v) {
            $url1 = OperateServices::routeToPath($v['key']);
            $url1 = str_replace('/', '_', $url1);
            $name_list_arr[$url1] = $v['title'];
            if (!empty($v['children'])) {
                foreach ($v['children'] as $k1 => $v1) {
                    $url2 = OperateServices::routeToPath($v1['key']);
                    $url2 = str_replace('/', '_', $url2);
                    $name_list_arr[$url2] = $v['title'] . "->" . $v1['title'];
                    if (!empty($v1['children'])) {
                        foreach ($v1['children'] as $k2 => $v2) {
                            $url3 = OperateServices::routeToPath($v2['key']);
                            $url3 = str_replace('/', '_', $url3);
                            $name_list_arr[$url3] = $v['title'] . "->" . $v1['title'] . "->" . $v2['title'];
                            if (!empty($v2['children'])) {
                                foreach ($v2['children'] as $k3 => $v3) {
                                    $url4 = OperateServices::routeToPath($v3['key']);
                                    $url4 = str_replace('/', '_', $url4);
                                    $name_list_arr[$url4] = $v['title'] . "->" . $v1['title'] . "->" . $v2['title'] . "->" . $v3['title'];
                                }
                            }
                        }
                    }
                }
            }
            if (empty($url1)) {
                unset($name_list_arr[$url1]);
            }
        }
        $cache->set('admin_log_rules_tree', json_encode($name_list_arr), 3600);
        return $name_list_arr;
    }

    /**
     * 清空缓存
     */
    public static function clearRulesTree()
    {
        $cache = Cache::store('file');
        $cache->delete('admin_log_rules_tree');
    }

    /**
     * 路由转路径
     * @param $controllerPath
     * @return string
     */
    public static function routeToPath($controllerPath)
    {
        // 匹配模式: plugin\admin\app\controller\{ControllerName}Controller@{action}
        $pattern = '/plugin\\\admin\\\app\\\controller\\\([A-Za-z]+)Controller@([a-zA-Z0-9_]+)/';
        if (preg_match($pattern, $controllerPath, $matches)) {
            $controllerName = strtolower($matches[1]); // message
            $actionName = $matches[2]; // select
            return '/app/admin/' . $controllerName . '/' . $actionName;
        }
        return '';
    }
}