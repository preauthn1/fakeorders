<?php


namespace plugin\admin\app\services;


use plugin\admin\app\common\Tree;
use plugin\admin\app\model\AdminRole;
use plugin\admin\app\model\Role;
use plugin\admin\app\model\Rule;
use support\think\Db;

class AdminServices
{
    /**
     * 获取管理员角色权限信息
     * @param $admin_id
     * @return array
     */
    public static function getAdminRoles($admin_id)
    {
        $roles_ids = AdminRole::where('admin_id', $admin_id)->field('role_id')->select();
        if (empty($roles_ids)) {
            return [];
        }
        $roles_ids = $roles_ids->toArray();
        $roles_id = array_column($roles_ids, 'role_id');
        $roles_arr = Role::whereIn('id', $roles_id)->field(['rules'])->select();
        if (empty($roles_arr)) {
            return [];
        }
        $roles_arr = $roles_arr->toArray();
        $roles = array_column($roles_arr, 'rules');
        return array_unique(array_filter(explode(',', implode(',', $roles))));
    }

    /**
     * 刷新权限菜单
     * @throws \think\db\exception\DbException
     */
    public static function updateMenu()
    {
        $items = Rule::where('id', ">", 0)->select();
        // `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '标题',
        //  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '图标',
        //  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '标识',
        //  `pid` int unsigned DEFAULT '0' COMMENT '上级菜单',
        //  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
        //  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
        //  `href` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'url',
        //  `type` int DEFAULT '1' COMMENT '类型',
        //  `weight` int DEFAULT '0' COMMENT '排序',
        $items_arr = $items->toArray();
        $tree = new Tree($items_arr);
        //
        $tree_arr = $tree->getTree();
        //更具 id 大小排序
        usort($tree_arr, function ($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        Db::table('wa_rules1')->where('id', ">", 0)->delete();
        foreach ($tree_arr as $item) {
            $in_data = [
                'title' => $item['title'],
                'icon' => $item['icon'],
                'key' => $item['key'],
                'pid' => $item['pid'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'href' => $item['href'],
                'type' => $item['type'],
                'weight' => $item['weight'],
            ];
            $pid = Db::table('wa_rules1')->insertGetId($in_data);
            $children = isset($item['children']) ? $item['children'] : [];
            if (!empty($children)) {
                foreach ($children as $child) {
                    $in_data_c = [
                        'title' => $child['title'],
                        'icon' => $child['icon'],
                        'key' => $child['key'],
                        'pid' => $pid,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'href' => $child['href'],
                        'type' => $child['type'],
                        'weight' => $child['weight'],
                    ];
                    $pidc = Db::table('wa_rules1')->insertGetId($in_data_c);
                    $childrens = isset($child['children']) ? $child['children'] : [];
                    if (!empty($childrens)) {
                        $in_data_ss = [];
                        foreach ($childrens as $childs) {
                            $in_data_ss[] = [
                                'title' => $childs['title'],
                                'icon' => $childs['icon'],
                                'key' => $childs['key'],
                                'pid' => $pidc,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s'),
                                'href' => $childs['href'],
                                'type' => $childs['type'],
                                'weight' => $childs['weight'],
                            ];
                        }
                        Db::table('wa_rules1')->insertAll($in_data_ss);
                    }
                }
            }
        }
    }
}