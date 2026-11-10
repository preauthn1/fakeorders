<?php


namespace app\services;


use app\common\Util;
use app\model\PrizeAwardLogModel;
use app\model\RechargeModel;
use app\model\user\UserLevelModel;
use app\model\user\UserModel;
use plugin\admin\app\model\Admin;
use Webman\Event\Event;

class RechargeServices
{

    const STATUS_WAIT = 1;//待支付
    const STATUS_SUCCESS = 2;//成功
    const STATUS_FAIL = 3;//失败

    public static $STATUS_NAME = [
        self::STATUS_WAIT => '待支付',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_FAIL => '失败',
    ];

    /**
     * 添加充值记录
     * @param $user_id
     * @param $data
     * @return int
     */
    public static function addRecharge($user_id, $data)
    {
        //  `id` int NOT NULL AUTO_INCREMENT,
        //  `created_at` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
        //  `updated_at` int DEFAULT NULL COMMENT '更新时间',
        //  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '订单号',
        //  `trade_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '第三方订单号',
        //  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
        //  `is_demo` tinyint(1) DEFAULT '0' COMMENT '是否是虚拟用户0不是 1是',
        //  `money` decimal(20,8) NOT NULL COMMENT '充值金额',
        //  `original_money` decimal(20,8) NOT NULL COMMENT '原始金额',
        //  `exchange_rate` decimal(20,8) NOT NULL DEFAULT '1.00000000' COMMENT '汇率',
        //  `from_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '发送地址',
        //  `to_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '接收地址',
        //  `type` tinyint NOT NULL DEFAULT '0' COMMENT '类型 1USDT 转账 ',
        //  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态 1 待支付 2 通过 3 失效',
        //  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
        //  `pay_time` int DEFAULT '0' COMMENT '付款时间',
        //  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作IP',
        //  `admin_id` int DEFAULT '0' COMMENT '操作管理员id',
        //  `is_first` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是首充',
        //  `success_time` int DEFAULT NULL COMMENT '收到成功回调时间',
        //  `result` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '充值结果',

//        $map = [
//            'trade_no' => $data['trade_no'],
//        ];
//        $recharge = RechargeModel::where($map)->find();
//        if ($recharge) {
//            return $recharge['number'];
//        }
        $is_demo = UserServices::getIsDemo($user_id);
        $insert_data = [
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s"),
            'number' => Util::getRechargeNumber($user_id),
            'trade_no' => $data['trade_no'],
            'user_id' => $user_id,
            'is_demo' => $is_demo,
            'money' => $data['money'],
            'platform_id' => $data['platform_id'],
            'unit' => $data['unit'],
            'original_money' => $data['original_money'],
            'exchange_rate' => $data['exchange_rate'],
            'from_address' => isset($data['from_address']) ? $data['from_address'] : "",
            'to_address' => isset($data['to_address']) ? $data['to_address'] : "",
            'pay_img' => isset($data['pay_img']) ? $data['pay_img'] : "",
            'admin_id' => isset($data['admin_id']) ? $data['admin_id'] : 0,
            'remark' => isset($data['remark']) ? $data['remark'] : '',
            'ip' => isset($data['ip']) ? $data['ip'] : '',
            'type' => $data['type'],
            'status' => self::STATUS_WAIT,
            'day' => date('Ymd'),
            'month' => date('Ym'),
            'year' => date('Y'),
        ];
        $id = RechargeModel::insertGetId($insert_data);
        if ($id) {
            return $insert_data['number'];
        }
        return '';
    }

    /**
     * 支付成功回调
     * @param $number
     * @param $money
     * @param string $trade_no
     * @param array $result
     * @return false
     */
    public static function endRecharge($number, $money, $trade_no = '', $result = [])
    {
        if (!empty($trade_no)) {
            $map = [
                'trade_no' => $trade_no,
                'status' => RechargeServices::STATUS_SUCCESS
            ];
            $recharge = RechargeModel::where($map)->find();
            if (!empty($recharge)) {
                return true;
            }
        }
        if (empty($number)) {
            return false;
        }
        $recharge = null;
        $recharge = RechargeModel::where('number', $number)->find();
        if (empty($recharge)) {
            return false;
        }
        if (!empty($trade_no)) {
            if ($recharge->status != self::STATUS_WAIT) {
                return false;
            }
        }
        $temp_money = $money;
        $user_id = $recharge->user_id;
        $money = $recharge->money;
        if (round($temp_money, 4) > round($money, 4)) {
            return false;
        }
        $is_first = self::getIsFirst($user_id);
        $recharge->updated_at = date('Y-m-d H:i:s');
        $recharge->status = self::STATUS_SUCCESS;
        $recharge->trade_no = $trade_no;
        $recharge->is_first = $is_first;
        $recharge->pay_time = time();
        $recharge->success_time = time();
        $recharge->result = json_encode($result);
        $ret = $recharge->save();
        if ($ret) {
            $title = '用户充值';
            $remarks = '用户充值增加';
            $type = UserBillServices::TYPE_USER;
            $detail_type = 1;
            UserBillServices::addUserBill($user_id, $money, UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
            Event::dispatch('recharge.success', [
                'user_id' => $user_id,
                'number' => $number,
                'money' => $money
            ]);
            if ($is_first == 1) {
                Event::dispatch('promotion.success', [
                    'user_id' => $user_id,
                    'event_name' => 'first_deposit',
                    'event_data' => ['value' => $money]
                ]);
            }
            return true;
        }
        return false;
    }

    /**
     * 获取充值单号
     * @param $from_address
     * @param $to_address
     * @param $money
     * @return mixed|string
     */
    public static function getNumber($from_address, $to_address, $money)
    {
        $map = [
            'from_address' => $from_address,
            'to_address' => $to_address,
            'money' => $money,
            'status' => self::STATUS_WAIT,
        ];
        $number = RechargeModel::where($map)->order('id', 'desc')->value('number');
        if (empty($number)) {
            return "";
        }
        return $number;
    }

    /**
     * 获取充值单号
     * @param $user_id
     * @param $money
     * @return mixed|string
     */
    public static function getUserNumber($user_id, $money)
    {
        $map = [
            'user_id' => $user_id,
            'money' => $money,
            'status' => self::STATUS_WAIT,
        ];
        $number = RechargeModel::where($map)->order('id', 'desc')->value('number');
        if (empty($number)) {
            return "";
        }
        return $number;
    }

    /**
     * 无需拉起充值成功
     * @param $user_id
     * @param $data
     * @param $result ;充值结果
     * @return bool
     */
    public static function addUserRecharge($user_id, $data, $result = [])
    {
        $add_data = [
            'trade_no' => isset($data['trade_no']) ? $data['trade_no'] : '',
            'money' => isset($data['money']) ? $data['money'] : '',
            'original_money' => isset($data['original_money']) ? $data['original_money'] : '',
            'exchange_rate' => isset($data['exchange_rate']) ? $data['exchange_rate'] : '',
            'from_address' => isset($data['from_address']) ? $data['from_address'] : '',
            'to_address' => isset($data['to_address']) ? $data['to_address'] : '',
            'type' => isset($data['type']) ? $data['type'] : 1,
            'unit' => isset($data['unit']) ? $data['unit'] : '',
            'platform_id' => isset($data['platform_id']) ? $data['platform_id'] : '',
        ];
        $number = self::addRecharge($user_id, $add_data);
        if (!empty($number)) {
            return self::endRecharge($number, $add_data['money'], '', $result);
        }
        return false;
    }

    /**
     * 获取用户是否存在充值
     * @param $user_id
     * @return int
     */
    public static function getIsFirst($user_id)
    {
        $is_first = RechargeModel::where('user_id', $user_id)->where('status', 2)->count();
        return $is_first > 0 ? 0 : 1;
    }

    /**
     * 获取充值总额
     * @return int|mixed
     */
    public static function getAllRecharge()
    {
        return RechargeModel::where('status', 2)->sum('money');
    }

    /**
     * 获取最近7天充值总额
     * @param string $day
     * @return array
     */
    public static function getWeekRechargeList($day = '2025-08-13')
    {
        $week_end = strtotime($day);
        $week_start = strtotime('-7 day', $week_end);
        //获取最近7天充值每日充值总额
        $week_list = [];
        for ($i = 1; $i <= 7; $i++) {
            $start_time = strtotime("+$i day", $week_start);
            $end_time = strtotime('+1 day', $start_time); // 当天结束时间
            $date = strtotime(date('Y-m-d', $start_time));
            $money = RechargeModel::where('status', 2)
                ->whereBetween('success_time', [$start_time, $end_time])
                ->sum('money');
            $week_list[$date] = floatval($money);
        }
        return $week_list;
    }

    /**
     * 获取理财金额总量（符合条件的）
     * @param $user_id
     * @param $condition
     * @return int|mixed
     */
    public static function getUserAwardLongAmount($user_id, $condition)
    {
        //condition = G2,40
        $condition = explode(',', $condition);
        $level_name = $condition[0];
        $days = $condition[1];
        $level = UserLevelModel::where('name', $level_name)->value('number');
        $user_level = UserServices::getFieldValue($user_id, 'level');
        if ($user_level < $level) {
            //判断用户等级是否满足条件
            return 0;
        }
        $pledge_long_amount = PledgeLongServices::getPledgeLongIng($user_id, $days);
        if ($pledge_long_amount < 0) {
            //判断用户质押金额是否满足条件
            return 0;
        }
        return $pledge_long_amount;
    }

    /**
     * 添加领取记录
     * @param $user_id
     * @param $number
     * @param $money
     * @param $condition
     * @return array
     */
    public static function addPrizeAward($user_id, $number, $money, $condition)
    {
        $map = [
            'user_id' => $user_id,
            'number' => $number
        ];
        $award = PrizeAwardLogModel::where($map)->find();
        if ($award) {
            return [
                'code' => 1,
                'msg' => '已领取'
            ];
        }
        $data = [
            'user_id' => $user_id,
            'number' => $number,
            'money' => $money,
            'condition' => $condition,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $id = PrizeAwardLogModel::insertGetId($data);
        if ($id) {
            $title = '快爆奖励';
            $remarks = '快爆奖励领取';
            $type = UserBillServices::TYPE_USER;
            $detail_type = 6;
            $data_bill = UserBillServices::addUserBill($user_id, $money, UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
            if ($data_bill['code'] == 0) {
                return [
                    'code' => 0,
                    'msg' => '领取成功'
                ];
            }
            PrizeAwardLogModel::where('id', $id)->delete();
            return $data_bill;
        }
        return [
            'code' => 1,
            'msg' => '领取失败'
        ];
    }

    /**
     * 生成签名
     * @param $data
     * @param $key
     * @return string
     */
    public static function generateSign($data, $key): string
    {
        ksort($data);
        $temp_str = '';
        foreach ($data as $k => $v) {
            $temp_str .= $k . '=' . $v . '&';
        }
        $temp_str .= 'key=' . $key;
        return md5($temp_str);
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
        $name = '充值列表';
        $field = [
            'id' => '订单ID',
            'number' => '订单号',
            'trade_no' => '三方单号',
            'username' => '用户名',
            'money' => '充值金额',
            'from_address' => '用户钱包地址',
            'unit' => '币种',
            'success_time' => '成功时间',
            'is_first' => '是否首充',
            'status' => '状态',
            'admin_name' => '操作管理员',
            'ip' => '操作IP',
            'leader0_user' => '顶推推广员',
            'leader1_user' => '上级推广员',
            'is_demo' => '是否是虚拟用户'
        ];
        $model = new RechargeModel();
        $model = Util::doExportSelect($model, $map, 'id', 'desc');
        $list = $model->limit(($page - 1) * $limit, $limit)->select();
//        print_r($model->toRawSql());
        $data = [];
        foreach ($list as &$item) {
            $user_info = [
                'id' => 0,
                'username' => '未知',
            ];
            $item['user_info'] = empty($item['user_id']) ? $user_info : UserModel::where('id', $item['user_id'])->field('id,username,mobile_code,tree,login_type')->find();
            $tree = $item['user_info']['tree'];
            //去除数组中的空元素
            $tree_arr = array_filter(explode('/', $tree));
            //去掉最后一个元素
            array_pop($tree_arr);
            for ($i = 0; $i <= 1; $i++) {
                $item['user_info']['leader' . $i . '_user'] = "-/-";
            }
            if (!empty($tree_arr)) {
                //获取第一个元素
                $first_element = array_shift($tree_arr);
                $item['user_info']['leader0_user'] = UserServices::getInvUser($first_element);
                $tree_arr = array_reverse($tree_arr);
                if (isset($tree_arr[1])) {
                    $item['user_info']['leader' . 1 . '_user'] = UserServices::getInvUser($tree_arr[1]);
                }
            }
            $item['status_str'] = RechargeServices::$STATUS_NAME[$item['status']];
            $item['success_time'] = empty($item['success_time']) ? '--' : date('Y-m-d H:i:s', $item['success_time']);
            $item['admin_name'] = "";
            if (!empty($item['admin_id'])) {
                $item['admin_name'] = Admin::where('id', $item['admin_id'])->value('username');
            }
            $data[] = [
                'id' => $item->id,
                'number' => $item->number,
                'trade_no' => $item->trade_no,
                'username' => $item->user_info['username'],
                'money' => $item->money,
                'from_address' => $item->from_address,
                'unit' => $item->unit,
                'success_time' => $item->success_time,
                'is_first' => $item->is_first == 1 ? '是' : '否',
                'status' => $item->status_str,
                'admin_name' => $item->admin_name,
                'ip' => $item->ip,
                'leader0_user' => $item->user_info['leader0_user'],
                'leader1_user' => $item->user_info['leader1_user'],
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