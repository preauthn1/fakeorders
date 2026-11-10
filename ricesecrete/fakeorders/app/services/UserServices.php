<?php


namespace app\services;


use app\common\email\MailgunEmail;
use app\common\Util;
use app\model\RechargeModel;
use app\model\user\UserLevelLogModel;
use app\model\user\UserLevelModel;
use app\model\user\UserLogModel;
use app\model\user\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use plugin\admin\app\services\LangServices;
use support\Cache;
use support\Redis;
use Webman\Event\Event;

class UserServices
{

    /**
     * 推广层级最大有效层级
     * @var int
     */
    const TOP_LEVEL = 20;

    /**
     * 手机号码类型
     * @var int
     */
    const MOBILE_TYPE = 1;
    /**
     * 邮箱类型
     * @var int
     */
    const EMAIL_TYPE = 2;

    /**
     * 登录类型名称
     * @var string[]
     */
    public static $TYPE_NAME = [
        self::MOBILE_TYPE => '手机',
        self::EMAIL_TYPE => '邮箱'
    ];

    /**
     * 登录
     * @param $username
     * @param $password
     * @param $mobile_code
     * @return array
     */
    public static function login($username, $password, $mobile_code)
    {
        $map = [
            'username' => $username
        ];
        if (!empty($mobile_code)) {
            $map['mobile_code'] = $mobile_code;
        }
        $hasUser = UserModel::where($map)->find();
        if (!$hasUser || !Util::passwordVerify($password, $hasUser['password'])) {
            // 密码错误
            return [
                'code' => 1,
                'msg' => '账号或密码错误'
            ];
        }
        //用户状态 0禁用 1启用
        // 判断用户状态
        if ($hasUser['status'] == 0) {
            return [
                'code' => 1,
                'msg' => '该账号已被禁用'
            ];
        }
        // 更新用户状态
        $ip = Util::getRealIp();
        $param = [
            'last_ip' => $ip,
            'last_time' => date("Y-m-d H:i:s")
        ];
        // 更新用户登录信息
        $res = UserModel::where('id', $hasUser['id'])->update($param);
        if ($res == false) {
            // 判断用户更新状态
            return [
                'code' => 1,
                'msg' => '用户登录信息更新失败'
            ];
        }
        //记录登录日志
        UserServices::insertLog($hasUser['id'], 0, $ip);
        return [
            'code' => 0,
            'data' => self::generateToken($hasUser['id'])
        ];
    }

    /**
     *  注册
     * @param array $params
     * @return array
     */
    public static function register($params = [])
    {
        //login_type 登录类型 1手机 2邮箱
        //invitation_code 邀请码
        //password 登录密码
        //password_repeat 确认密码
        //mobile 手机
        //mobile_code 手机区号
        //email 邮箱
        //pix 像素id
        //click_id kwai事件id
        $pix = isset($params['pix']) ? $params['pix'] : '';
        $platform = isset($params['platform']) ? $params['platform'] : '';
        $click_id = isset($params['click_id']) ? $params['click_id'] : '';
        $password = isset($params['password']) ? $params['password'] : '';
        $password_repeat = isset($params['password_repeat']) ? $params['password_repeat'] : '';
        if ($password != $password_repeat) {
            return [
                'code' => 1,
                'msg' => '密码不一致'
            ];
        }
        $login_type = isset($params['login_type']) ? $params['login_type'] : UserServices::MOBILE_TYPE;
        $email = isset($params['email']) ? $params['email'] : '';
        //校验验证码
        $verify_code = isset($params['verify_code']) ? $params['verify_code'] : '';
        //开启邮箱验证
        if ($login_type == UserServices::EMAIL_TYPE && SysServices::getSysConfigValue('web', 'reg_code_email') == 1) {
            $ret = MailgunEmail::verifyEmail($email, $verify_code);
            if ($ret == false) {
                return [
                    'code' => 1,
                    'msg' => '验证码错误'
                ];
            }
        }
        //开启手机验证码
        if ($login_type == UserServices::MOBILE_TYPE && SysServices::getSysConfigValue('web', 'reg_code_mobile') == 1) {
            $ret = true;
            if (!$ret) {
                return [
                    'code' => 1,
                    'msg' => '验证码错误'
                ];
            }
        }
        $lang = isset($params['lang']) ? $params['lang'] : LangServices::getDefaultLang();
        $reg_params = [
            'login_type' => $login_type,
            'invitation_code' => isset($params['invitation_code']) ? $params['invitation_code'] : '',
            'password' => isset($params['password']) ? $params['password'] : Util::getRandomString(10),
            'mobile' => isset($params['mobile']) ? $params['mobile'] : '',
            'mobile_code' => isset($params['mobile_code']) ? $params['mobile_code'] : '',
            'email' => $email,
            'lang' => $lang,
            'pix' => $pix,
            'platform' => $platform,
            'click_id' => $click_id,
        ];
        $res = self::addUser($reg_params);
        if ($res['code'] == 0) {
            return [
                'code' => 0,
                'data' => self::generateToken($res['data'])
            ];
        }
        return $res;
    }

    /**
     * 添加用户
     * @param array $params
     * @return array
     */
    public static function addUser($params = [])
    {
        $login_type = isset($params['login_type']) ? $params['login_type'] : UserServices::MOBILE_TYPE;
        $invitation_code = isset($params['invitation_code']) ? $params['invitation_code'] : '';
        $password = isset($params['password']) ? $params['password'] : Util::getRandomString(10);
        $mobile = isset($params['mobile']) ? $params['mobile'] : '';
        $mobile_code = isset($params['mobile_code']) ? $params['mobile_code'] : '';
        $email = isset($params['email']) ? $params['email'] : '';
        $status = isset($params['status']) ? $params['status'] : 1;
        $pix = isset($params['pix']) ? $params['pix'] : '';
        $click_id = isset($params['click_id']) ? $params['click_id'] : '';
        $platform = isset($params['platform']) ? $params['platform'] : "";
        $withdraw_status = isset($params['withdraw_status']) ? $params['withdraw_status'] : 1;
        if ($login_type == UserServices::MOBILE_TYPE) {
            $username = $mobile;
            if (empty($mobile_code)) {
                return ['code' => 1, 'msg' => '手机区号不能为空'];
            }
            $map = [
                'username' => $username,
                'mobile_code' => $mobile_code
            ];
            $hasUser = UserModel::where($map)->find();
            if ($hasUser) {
                return ['code' => 1, 'msg' => '手机已存在'];
            }
        } else {
            $username = $email;
            $map = [
                'username' => $username,
            ];
            $hasUser = UserModel::where($map)->find();
            if ($hasUser) {
                return ['code' => 1, 'msg' => '邮箱已存在'];
            }
        }
        if (empty($username)) {
            return ['code' => 1, 'msg' => '用户名不能为空'];
        }
        $lang = isset($params['lang']) ? $params['lang'] : LangServices::getDefaultLang();
        $in_data = [
            'login_type' => $params['login_type'] ?? UserServices::MOBILE_TYPE,
            'username' => $username,
            'password' => Util::passwordHash($password),
            'mobile' => $mobile,
            'mobile_code' => $mobile_code,
            'email' => $email,
            'nickname' => Util::getRandomString(6),
            'invitation_code' => self::getUserInvitationCode(),
            'join_time' => date("Y-m-d H:i:s"),
            'join_ip' => Util::getRealIp(),
            'status' => $status,
            'lang' => $lang,
            'created_at' => date("Y-m-d H:i:s"),
            'withdraw_status' => $withdraw_status,
            'pix' => $pix,
            'platform' => $platform,
            'click_id' => $click_id
        ];
        $user_id = UserModel::insertGetId($in_data);
        $ip = Util::getRealIp();
        UserServices::insertLog($user_id, 1, $ip);
        Event::dispatch('user.register', [
            'user_id' => $user_id,
            'invitation_code' => $invitation_code
        ]);
        Event::dispatch('promotion.success', [
            'user_id' => $user_id,
            'event_name' => 'register',
            'event_data' => []
        ]);
        return [
            'code' => 0,
            'msg' => '注册成功',
            'data' => $user_id
        ];
    }

    /**
     * 获取用户上级
     * @param $id
     * @param array $visited
     * @return mixed
     */
    public static function getUserTop($id, &$visited = [])
    {
        $redis = Cache::store('redis');
        $temp_redis = $redis->get('user_top_' . $id);
        if ($temp_redis) {
            $visited = json_decode($temp_redis, true);
            return $id;
        }
        $maxDepth = UserServices::TOP_LEVEL;
        $currentId = $id;
        $depth = 0;
        // 检查是否出现循环引用
        if (in_array($currentId, $visited)) {
            $redis->set('user_top_' . $id, json_encode([$currentId]), 60);
            return $currentId; // 避免无限循环
        }
        // 使用迭代方式向上查找
        while ($depth < $maxDepth) {
            // 记录已访问的用户
            $visited[] = $currentId;
            // 获取当前用户的上级ID
            $userTopId = UserModel::where('id', $currentId)->value('top_id');
            // 如果没有上级或者上级为0，说明已经找到顶级
            if (!$userTopId || $userTopId == 0) {
                break;
            }
            // 检查是否出现循环引用
            if (in_array($userTopId, $visited)) {
                break;
            }
            // 移动到上级用户
            $currentId = $userTopId;
            $depth++;
        }
        $redis->set('user_top_' . $id, json_encode([$currentId]), 60);
        // 达到最大深度，返回当前ID
        return $currentId;
    }

    /**
     * 更新用户推广关系
     * @param $id
     * @param $invitation_code
     * @return bool
     */
    public static function userRelationshipTree($id, $invitation_code = '')
    {
        $top_user = UserModel::where('invitation_code', $invitation_code)->find();
        if (empty($top_user) || $top_user->kyc_status != 2) {
            //如果用户没有KYC认证  推广无效
            $top_id = 0;
        } else {
            $top_id = $top_user->id;
        }
        //  `leader0` int DEFAULT '0' COMMENT '顶级推广用户id',
        //  `leader1` int DEFAULT '0' COMMENT '一级推广用户id',
        //  `leader2` int DEFAULT '0' COMMENT '二级推广用户id',
        //  `leader3` int DEFAULT '0' COMMENT '三级推广用户id',
        //  `leader4` int DEFAULT '0' COMMENT '四级推广用户id',
        //  `leader5` int DEFAULT '0' COMMENT '五级推广用户id',
        //  `leader6` int DEFAULT '0' COMMENT '六级推广用户id',
        //  `leader7` int DEFAULT '0' COMMENT '七级推广用户id',
        //  `leader8` int DEFAULT '0' COMMENT '八级推广用户id',
        //  `leader9` int DEFAULT '0' COMMENT '九级推广用户id',
        if (!empty($top_id)) {
            $user_top_ids = [];
            //最多20级
            self::getUserTop($top_id, $user_top_ids);
            //把$user_top_ids 分割 成 /1/2/3/
            $temp_top = array_reverse($user_top_ids);
            $temp_top[] = $id;
            $user_tree = "/" . implode('/', $temp_top) . "/";
        } else {
            //把$user_top_ids 分割 成 /1/2/3/
            $user_tree = "/" . $id . "/";
        }
        $up_data['top_id'] = $top_id;
        $up_data['top_time'] = empty($top_id) ? 0 : time();
        $up_data['tree'] = $user_tree;
        //更新对应等级
        for ($i = 0; $i <= 9; $i++) {
            $temp_id = isset($user_top_ids[$i]) ? $user_top_ids[$i] : 0;
            if ($temp_id != $id) {
                $up_data['leader' . $i] = $temp_id;
            }
        }
        $ret = UserModel::where('id', $id)->update($up_data);
        if ($ret != false) {
            return true;
        }
        return false;
    }

    /**
     * 获取用户邀请码
     * @param int $lenx
     * @return int
     */
    public static function getUserInvitationCode($lenx = 8)
    {
        $user_id = UserModel::order('id', 'desc')->value('id');
        $len = $lenx - strlen($user_id);
        $code = Util::getRandomString($len, '0123456789');
        $code = (int)$code;
        $code_str = $code . $user_id;
        if (strlen($code_str) < $lenx) {
            $len = $lenx - strlen($code_str);
            $code_str = Util::getRandomString($len, '0123456789') . $code_str;
        }
        return (int)$code_str;
    }

    /**
     * 插入日志
     * @param $user_id
     * @param $type
     * @param $ip
     */
    public static function insertLog($user_id, $type, $ip)
    {
        $uuid = request()->header('uuid', '');
        $device = Util::getClientBrowserInfo();
        $login_log = [
            'user_id' => $user_id,
            'ip' => $ip,
            'type' => $type,
            'area' => "",
            'device' => $device,
            'uuid' => $uuid, // 设备唯一标识
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
        ];
        UserLogModel::insert($login_log);
    }

    /**
     * 获取用户名称
     * @param $user_id
     * @return string
     */
    public static function getInvUser($user_id)
    {
        $redis = Cache::store('redis');
        $user_name = $redis->get('user_name_' . $user_id);
        if ($user_name) {
            return $user_id . "/" . $user_name;
        } else {
            $user = UserModel::where('id', $user_id)->find();
            if ($user['login_type'] == 1) {
                $user_name = "(" . $user['mobile_code'] . ")" . $user['username'];
            } else {
                $user_name = $user['username'];
            }
            $redis->set('user_name_' . $user_id, $user_name, 60);
            return $user_id . "/" . $user_name;
        }
    }

    /**
     * 刷新token
     * @param $refresh_token
     * @return array
     */
    public static function refreshToken($refresh_token)
    {
        if (empty($refresh_token)) {
            return [
                'code' => 1,
                'msg' => '参数错误'
            ];
        }
        $out_data = self::checkToken($refresh_token, 'refresh');
        if ($out_data['code'] != 0) {
            return $out_data;
        }
        return [
            'code' => 0,
            'data' => self::generateToken($out_data['data'])
        ];
    }

    /**
     * 验证token
     * @param $token
     * @param $type ;login/refresh
     * @return array
     */
    public static function checkToken($token, $type = 'login')
    {
        try {
            $jwtKey = config('login.jwt_key');
            $decoded = JWT::decode($token, new Key($jwtKey, 'HS256'));
            // 验证是否是刷新token
            if (!isset($decoded->type) || $decoded->type !== $type) {
                return [
                    'code' => 1,
                    'msg' => '无效的刷新令牌'
                ];
            }

            // 验证token是否过期
            if (!isset($decoded->create_time) || !isset($decoded->expires_in) ||
                ($decoded->create_time + $decoded->expires_in) < time()) {
                return [
                    'code' => 1,
                    'msg' => '刷新令牌已过期'
                ];
            }

            // 验证用户是否存在
            $user = UserModel::where('id', $decoded->user_id)->find();
            if (empty($user)) {
                return [
                    'code' => 1,
                    'msg' => '用户不存在'
                ];
            }
            // 验证login_key是否匹配
            if (!isset($decoded->login_key) || $decoded->login_key !== $user->login_key) {
                return [
                    'code' => 1,
                    'msg' => '无效的刷新令牌'
                ];
            }
            return [
                'code' => 0,
                'data' => $user->id
            ];
        } catch (\Exception $e) {
            return [
                'code' => 1,
                'msg' => '无效的令牌'
            ];
        }
    }

    /**
     * 获取用户信息
     * @param $user_id
     * @param $is_cache
     * @return array|mixed
     */
    public static function getUserInfo($user_id, $is_cache = true)
    {
        $redis = Cache::store('redis');
        $user_info = $redis->get('user_info_' . $user_id);
        if ($user_info && $is_cache) {
            return json_decode($user_info, true);
        } else {
            $field = ['id', 'username', 'nickname', 'avatar', 'email', 'mobile', 'mobile_code', 'user_money', 'login_type', 'level', 'invitation_code', 'status', 'withdraw_status', 'kyc_status', 'lang'];
            $user_info = UserModel::where('id', $user_id)->field($field)->find();
            $redis->set('user_info_' . $user_id, json_encode($user_info), 300);
            return $user_info;
        }
    }

    /**
     * 设置用户语言
     * @param $user_id
     * @param $lang
     * @return array
     */
    public static function setLang($user_id, $lang)
    {
        if (empty($lang) || empty($user_id)) {
            return [
                'code' => 1,
                'msg' => '修改失败'
            ];
        }
        $ret = UserModel::where('id', $user_id)->update(['lang' => $lang]);
        if ($ret) {
            return [
                'code' => 0,
                'msg' => '修改成功'
            ];
        }
        return [
            'code' => 1,
            'msg' => '修改失败'
        ];
    }

    /**
     * 修改用户等级
     * @param $user_id
     * @param $level
     * @return array
     */
    public static function levelUp($user_id, $level)
    {
        $from_level = UserModel::where('id', $user_id)->value('level');
        if ($from_level == $level) {
            return [
                'code' => 1,
                'msg' => '已经是该等级'
            ];
        }
        $level_data = UserModel::where('id', $user_id)->update(['level' => $level]);
        if ($level_data) {
            $log = [
                'user_id' => $user_id,
                'level' => $level,
                'from_level' => $from_level,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            UserLevelLogModel::insert($log);
            return [
                'code' => 0,
                'msg' => '升级成功'
            ];
        }
        return [
            'code' => 1,
            'msg' => '升级失败'
        ];
    }

    /**
     * 获取用户等级列表
     * @param bool $cahce
     * @return array|mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     */
    public static function levelList($cahce = true)
    {
        // `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
        //  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
        //  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
        //  `number` int DEFAULT '0' COMMENT '用户等级编号',
        //  `name` char(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '用户等级名称',
        //  `price` decimal(10,2) DEFAULT '0.00' COMMENT '金额',
        //  `pop_num` int DEFAULT '0' COMMENT '有效推广人数',
        //  `award` decimal(20,8) DEFAULT '0.00000000' COMMENT '奖励金额',
        //  `kyc` tinyint(1) DEFAULT '0' COMMENT '校验KYC认证状态0不校验 1校验',
        //  `status` tinyint DEFAULT '1' COMMENT '状态  0禁用 1启用',
        //  `sort` int DEFAULT '0' COMMENT '排序',
        $key = 'level_list';
        $redis = Cache::store('redis');
        $list = $redis->get($key);
        if ($list && $cahce) {
            return json_decode($list, true);
        }
        $list = UserLevelModel::field(['id', 'name', 'number', 'price', 'pop_num', 'award', 'kyc'])->order('number', 'asc')->select()->toArray();
        $redis->set($key, json_encode($list), 300);
        return $list;

    }

    /**
     * 判断上级是否满足升级条件
     * @param $user_id
     * @return array
     */
    public static function conditionTopLevel($user_id)
    {
        $top_id = UserModel::where('id', $user_id)->value('top_id');
        if (empty($top_id)) {
            return [
                'code' => 1,
                'msg' => '上级不存在'
            ];
        }
        $user_data = UserModel::where('id', $top_id)->find();
        if (empty($user_data)) {
            return [
                'code' => 1,
                'msg' => '用户不存在'
            ];
        }
        $level = $user_data->level;
        $next_level = UserLevelModel::where('number', '>', $level)->order('number', 'asc')->find();
        if (empty($next_level)) {
            return [
                'code' => 1,
                'msg' => '已经是最高级'
            ];
        }
        //上级用户
        //有效充值金额
        $price = $next_level->price;
        //有效推广人数
        $pop_num = $next_level->pop_num;
        $top_pop_num = UserServices::getPopNum($top_id, $price);
        if ($top_pop_num < $pop_num) {
            return [
                'code' => 1,
                'msg' => '推广人数不足'
            ];
        }
        //满足条件可以升级
        $next_number = $next_level->number;
        return UserServices::levelUp($top_id, $next_number);
    }

    /**
     * 获取有效推广人数
     * @param $user_id
     * @param $money
     * @return int
     */
    public static function getPopNum($user_id, $money = 0)
    {
        // 获取下级用户id
        $next_ids = UserModel::where('top_id', $user_id)->field('id')->select();
        $next_ids_arr = [];
        foreach ($next_ids as $next_id) {
            $next_ids_arr[] = $next_id->id;
        }
        $map = [
            'status' => RechargeServices::STATUS_SUCCESS,
            'is_demo' => 0
        ];
        // 使用子查询方式
        return RechargeModel::whereIn('user_id', $next_ids_arr)
            ->where($map)
            ->group('user_id')
            ->having('SUM(money) >= ' . $money)
            ->field('user_id')
            ->count();
    }

    /**
     * 重置密码
     * @param $username
     * @param $mobile_code
     * @param $password
     * @param $verify_code
     * @return array
     */
    public static function resetPassword($username, $mobile_code, $password, $verify_code)
    {
        if (empty($mobile_code)) {
            $ret = MailgunEmail::verifyEmail($username, $verify_code);
            if ($ret == false) {
                return [
                    'code' => 1,
                    'msg' => '验证码错误'
                ];
            }
            $user = UserModel::where('username', $username)->find();
            if (!$user) {
                return [
                    'code' => 1,
                    'msg' => '用户不存在'
                ];
            }
            $user->password = Util::passwordHash($password);
            $user->login_key = Util::getRandomString(32) . time();
            $user->save();
        } else {
            $user = UserModel::where('username', $username)->where('mobile_code', $mobile_code)->find();
            if (!$user) {
                return [
                    'code' => 1,
                    'msg' => '用户不存在'
                ];
            }
            $user->password = Util::passwordHash($password);
            $user->login_key = Util::getRandomString(32) . time();
            $user->save();
        }
        return [
            'code' => 0,
            'msg' => '密码重置成功'
        ];
    }

    /**
     * 获取是否是虚拟 用户
     * @param $user_id
     * @return int|string
     */
    public static function getIsDemo($user_id)
    {
        $redis_key = 'is_demo_' . $user_id;
        $is_demo = Redis::get($redis_key);
        if (is_numeric($is_demo)) {
            return $is_demo;
        }
        $is_demo = UserModel::where('id', $user_id)->value('is_demo');
        if (is_numeric($is_demo)) {
            Redis::set($redis_key, $is_demo, 300);
        } else {
            $is_demo = 0;
        }
        return $is_demo;
    }

    /**
     * 获取用户指定字段值
     * @param $user_id
     * @param $field
     * @return mixed
     */
    public static function getFieldValue($user_id, $field)
    {
        return UserModel::where('id', $user_id)->value($field);
    }

    /**
     * 获取用户指定字段值
     * @param $user_id
     * @param $field
     * @return mixed
     */
    public static function getUserValue($user_id, $field)
    {
        $user = UserModel::where('id', $user_id)->field($field)->find();
        if (empty($user)) {
            $temp_arr = [];
            foreach ($field as $item) {
                $temp_arr[$item] = "";
            }
            return $temp_arr;
        }
        return $user->toArray();
    }

    /**
     * 赠送彩金
     * @param $user_id
     * @param $amount
     * @return array
     */
    public static function giveCaiJin($user_id, $amount)
    {
        $amount = floatval($amount);
        if ($amount <= 0) {
            return [
                'code' => 1,
                'msg' => '金额错误'
            ];
        }
        $user = UserModel::where('id', $user_id)->find();
        if ($user) {
            $user->bonus_money = $user->bonus_money + $amount;
            $ret = $user->save();
            if ($ret) {
                $title = '赠送彩金';
                $remarks = '注册送彩金';
                $type = UserBillServices::TYPE_USER;
                $detail_type = 4;
                $data_bill = UserBillServices::addUserBill($user_id, $amount, UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
                if ($data_bill['code'] == 0) {
                    return [
                        'code' => 0,
                        'msg' => '成功'
                    ];
                }
            }
        }
        return [
            'code' => 1,
            'msg' => '失败'
        ];
    }

    /**
     * 生成token
     * @param $user_id
     * @return array
     */
    private static function generateToken($user_id)
    {
        // 生成新的login_key
        $login_key = Util::getRandomString(32) . time();
        // 更新用户的login_key
        $user = UserModel::where('id', $user_id)->find();
        if ($user) {
            $user->login_key = $login_key;
            $user->save();
        }
        $expires_in = config('login.access_expires');

        $jwtKey = config('login.jwt_key');
        $access_token = JWT::encode([
            'user_id' => $user_id,
            'create_time' => time(),
            'expires_in' => $expires_in,
            'type' => 'login',
            'login_key' => $login_key
        ], $jwtKey, 'HS256');

        $refresh_in = config('login.refresh_expires');
        $refresh_token = JWT::encode([
            'user_id' => $user_id,
            'create_time' => time(),
            'expires_in' => $refresh_in,
            'type' => 'refresh',
            'login_key' => $login_key
        ], $jwtKey, 'HS256');

        return [
            'token' => $access_token,
            'refresh_token' => $refresh_token,
            'expires_time' => $expires_in
        ];
    }

    /**
     * 导出文件数据
     * @param $map
     * @param $page
     * @param int $limit
     * @return array
     */
    public static function exportFile($map, $page, $limit = 1000)
    {
        $name = '用户列表';
        $field = [
            'id' => '用户ID',
            'username' => '用户名',
            'nickname' => '昵称',
            'email' => '邮箱',
            'mobile' => '手机号',
            'login_type' => '账号类型',
            'level' => '等级',
            'user_money' => '钱包余额',
            'last_time' => '登录时间',
            'last_ip' => '登录IP',
            'leader0_user' => '顶推推广员',
            'leader1_user' => '上级推广员',
            'invitation_code' => '邀请码',
            'status' => '状态',
            'withdraw_status' => '提现状态',
            'kyc_status' => 'KYC认证状态',
            'lang' => '语言',
            'is_demo' => '是否是虚拟用户'
        ];
        $model = new UserModel();
        $model = Util::doExportSelect($model, $map, 'id', 'desc');
        $list = $model->limit(($page - 1) * $limit, $limit)->select();
        $data = [];
        foreach ($list as &$item) {
            $tree = $item['tree'];
            //去除数组中的空元素
            $tree_arr = array_filter(explode('/', $tree));
            //去掉最后一个元素
            array_pop($tree_arr);
            for ($i = 0; $i <= 1; $i++) {
                $item['leader' . $i . '_user'] = "-/-";
            }
            if (!empty($tree_arr)) {
                //获取第一个元素
                $first_element = array_shift($tree_arr);
                $item['leader0_user'] = UserServices::getInvUser($first_element);
                $tree_arr = array_reverse($tree_arr);
                for ($i = 1; $i <= 1; $i++) {
                    if (isset($tree_arr[$i])) {
                        $item['leader' . $i . '_user'] = UserServices::getInvUser($tree_arr[$i]);
                    }
                }
            }
            $kyc_status = "";
            //0 未认证 1认证中 2认证成功 3认证失败
            switch ($item->kyc_status) {
                case 0:
                    $kyc_status = "未认证";
                    break;
                case 1:
                    $kyc_status = "认证中";
                    break;
                case 2:
                    $kyc_status = "认证成功";
                    break;
                case 3:
                    $kyc_status = "认证失败";
                    break;
            }
            $data[] = [
                'id' => $item->id,
                'username' => $item->username,
                'nickname' => $item->nickname,
                'email' => $item->email,
                'mobile' => $item->mobile,
                'login_type' => self::$TYPE_NAME[$item->login_type],
                'level' => $item->level,
                'user_money' => $item->user_money,
                'last_time' => $item->last_time,
                'last_ip' => $item->last_ip,
                'leader0_user' => $item->leader0_user,
                'leader1_user' => $item->leader1_user,
                'invitation_code' => $item->invitation_code,
                //0禁用 1启用
                'status' => $item->status == 0 ? '禁用' : '启用',
                'withdraw_status' => $item->withdraw_status == 0 ? '禁用' : '启用',
                'kyc_status' => $kyc_status,
                'lang' => $item->lang,
                'is_demo' => $item->is_demo == 1 ? '是' : '否'
            ];
        }
        return [
            'name' => $name,
            'field' => $field,
            'data' => $data,
        ];
    }
}