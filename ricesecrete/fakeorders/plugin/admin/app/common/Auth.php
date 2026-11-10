<?php
namespace plugin\admin\app\common;


use plugin\admin\app\model\AdminRole;
use plugin\admin\app\model\Role;

class Auth
{
    /**
     * 获取权限范围内的所有角色id
     * @param bool $with_self
     * @return array
     * @throws \Exception
     */
    public static function getScopeRoleIds(bool $with_self = false): array
    {
        if (!$admin = admin()) {
            return [];
        }
        $role_ids = $admin['roles'];
        $rules = Role::whereIn('id', $role_ids)->column('rules');
        if ($rules && in_array('*', $rules)) {
            return Role::column('id');
        }
        $roles = Role::select();
        $tree = new Tree($roles);
        $descendants = $tree->getDescendant($role_ids, $with_self);
        return array_column($descendants, 'id');
    }

    /**
     * 获取权限范围内的所有管理员id
     * @param bool $with_self
     * @return array
     * @throws \Exception
     */
    public static function getScopeAdminIds(bool $with_self = false): array
    {
        $role_ids = static::getScopeRoleIds();
        $admin_ids = AdminRole::whereIn('role_id', $role_ids)->column('admin_id');
        if ($with_self) {
            $admin_ids[] = admin_id();
        }
        return array_unique($admin_ids);
    }

    /**
     * 兼容旧版本
     * @param int $admin_id
     * @deprecated
     * @return bool
     */
    public static function isSupperAdmin(int $admin_id = 0): bool
    {
        return static::isSuperAdmin($admin_id);

    }

    /**
     * 是否是超级管理员
     * @param int $admin_id
     * @return bool
     * @throws \Exception
     */
    public static function isSuperAdmin(int $admin_id = 0): bool
    {
        if (!$admin_id) {
            if (!$roles = admin('roles')) {
                return false;
            }
        } else {
            $roles = AdminRole::where('admin_id', $admin_id)->column('role_id');
        }
        $rules = Role::whereIn('id', $roles)->column('rules');
        return $rules && in_array('*', $rules);
    }

}