<?php


namespace app\services;


use app\common\payment\WalletPayment;
use app\common\Util;
use app\model\user\UserModel;
use app\model\WithdrawModel;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\PaymentPlatform;

class WithdrawServices
{

    const STATUS_WAIT = 1;//申请提现
    const STATUS_SUCCESS = 2;//审核成功
    const STATUS_FAIL = 3;//失败
    const STATUS_ARRIVAL = 4;//提现到账
    const STATUS_FAILED = 5;//到账失败

    public static $STATUS_NAME = [
        self::STATUS_WAIT => '申请提现',
        self::STATUS_SUCCESS => '审核成功',
        self::STATUS_FAIL => '失败',
        self::STATUS_ARRIVAL => '提现到账',
        self::STATUS_FAILED => '到账失败',
    ];

    /**
     * 添加提现记录
     * @param $user_id
     * @param $data
     * @return array
     */
    public static function addWithdraw($user_id, $data)
    {
        //  `id` int NOT NULL AUTO_INCREMENT,
        //  `created_at` int unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
        //  `updated_at` int DEFAULT NULL COMMENT '更新时间',
        //  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '订单号',
        //  `trade_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '第三方订单号',
        //  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
        //  `is_demo` tinyint(1) DEFAULT '0' COMMENT '是否是虚拟用户0不是 1是',
        //  `money` decimal(20,8) NOT NULL COMMENT '实际到账金额',
        //  `original_money` decimal(20,8) NOT NULL COMMENT '原始申请金额',
        //  `exchange_rate` decimal(20,8) NOT NULL DEFAULT '1.00000000' COMMENT '汇率',
        //  `from_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '平台地址',
        //  `to_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提现地址',
        //  `type` tinyint NOT NULL DEFAULT '0' COMMENT '类型 1USDT 转账 ',
        //  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态 1 待审核 2 通过 3 失败',
        //  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
        //  `pay_time` int DEFAULT '0' COMMENT '到账时间',
        //  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作IP',
        //  `admin_id` int DEFAULT '0' COMMENT '操作管理员id',
        //  `is_first` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是首提',
        //  `success_time` int DEFAULT NULL COMMENT '收到成功回调时间',
        //  `result` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提现结果',
        $title = '用户提现';
        $remarks = '用户提现减少';
        $money = $data['original_money'];
        $type = UserBillServices::TYPE_USER;
        $detail_type = 2;
        $ret = UserBillServices::addUserBill($user_id, $money, UserBillServices::CHANGE_SUB, $title, $remarks, $type, $detail_type);
        if ($ret['code'] == 0) {
            $is_demo = UserServices::getIsDemo($user_id);
            $insert_data = [
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
                'number' => Util::getRechargeNumber($user_id),
                'trade_no' => $data['trade_no'],
                'user_id' => $user_id,
                'is_demo' => $is_demo,
                'money' => $data['money'],
                'fees' => $data['fees'],
                'platform_id' => $data['platform_id'],
                'unit' => $data['unit'],
                'original_money' => $data['original_money'],
                'exchange_rate' => $data['exchange_rate'],
                'from_address' => $data['from_address'],
                'to_address' => $data['to_address'],
                'type' => $data['type'],
                'status' => self::STATUS_WAIT
            ];
            $id = WithdrawModel::insertGetId($insert_data);
            if ($id) {
                return [
                    'code' => 0,
                    'data' => $id
                ];
            }
        }
        return $ret;
    }


    /**
     * 获取用户待提现数量
     * @param $user_id
     * @return int
     */
    public static function getUserWaitNum($user_id)
    {
        $map = [
            'status' => self::STATUS_WAIT,
            'user_id' => $user_id
        ];
        return WithdrawModel::where($map)->count();
    }

    /**
     * 提现成功审核
     * @param $number
     * @param $remark
     * @return false
     */
    public static function successWithdraw($number, $remark = '')
    {
        if (empty($number)) {
            return false;
        }
        $withdraw = null;
        if (!empty($number)) {
            $withdraw = WithdrawModel::where('number', $number)->find();
            if (!$withdraw) {
                return false;
            }
        }
        if (!empty($withdraw)) {
            $user_id = $withdraw->user_id;
            $is_first = self::getIsFirst($user_id);
            // 调用第三方提现接口
            $domain = "https://api.hkxahxcoin.com";
            //判断提现to_address地址是TRC20还是BEP20
            if (!empty($withdraw->platform_id)) {
                $platform = PaymentPlatform::where('id', $withdraw->platform_id)->find();
            }
            if (empty($platform)) {
                return false;
            }
            if ($withdraw->type != 3) {
                $WalletPayment = new WalletPayment($platform, $domain);
                $input = [
                    'number' => $number,
                    'to_address' => $withdraw->to_address,
                    'amount' => $withdraw->original_money
                ];
                $ret_data = $WalletPayment->withdraw($input);
            } else {
                $ret_data = [
                    'code' => 0,
                    'trade_no' => '',
                    'msg' => '线下打款'
                ];
            }
            if ($ret_data['code'] == 0) {
                $withdraw->trade_no = $ret_data['trade_no'];
                $withdraw->updated_at = date('Y-m-d H:i:s');
                $withdraw->status = self::STATUS_SUCCESS;
                $withdraw->is_first = $is_first;
                $withdraw->pay_time = time();
                $withdraw->admin_id = admin_id();
                $withdraw->ip = Util::getRealIp();
                $withdraw->result = json_encode($ret_data);
                $withdraw->remark = $remark;
                $withdraw->day = date('Ymd');
                $withdraw->month = date("Ym");
                $withdraw->year = date("Y");
                $withdraw->save();
                return true;
            }
        }
        return false;
    }

    /**
     * 成功/失败 提现到账
     * @param $number
     * @param $status
     * @param $result ;回调数据
     * @return bool
     */
    public static function arrivalStatus($number, $status = self::STATUS_ARRIVAL, $result = [])
    {
        $order = WithdrawModel::where('number', $number)->find();
        if ($order) {
            if ($status == self::STATUS_ARRIVAL) {
                $order->status = $status;
                $order->result = json_encode($result);
                $order->success_time = time();
                $order->save();
                return true;
            }
            if ($status == self::STATUS_FAIL || $status == self::STATUS_FAILED) {
                if ($order->status == self::STATUS_SUCCESS || $order->status == self::STATUS_WAIT) {
                    $order->status = $status;
                    $order->result = json_encode($result);
                    $order->save();
                    $title = '用户提现';
                    $remarks = '到账失败退回';
                    $money = $order->original_money;
                    $type = UserBillServices::TYPE_USER;
                    $detail_type = 3;
                    $user_id = $order->user_id;
                    UserBillServices::addUserBill($user_id, $money, UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
                }
                return true;
            }
            return true;
        }
        return false;
    }

    /**
     * 提现失败审核
     * @param $number
     * @param string $remark
     * @return bool
     */
    public static function refuseWithdraw($number, $remark = '')
    {
        if (empty($number)) {
            return false;
        }
        $recharge = null;
        if (!empty($number)) {
            $recharge = WithdrawModel::where('number', $number)->find();
            if (!$recharge) {
                return false;
            }
        }
        if (!empty($recharge)) {
            $user_id = $recharge->user_id;
            $recharge->status = self::STATUS_FAIL;
            $recharge->updated_at = date('Y-m-d H:i:s');
            $recharge->remark = $remark;
            $recharge->admin_id = admin_id();
            $recharge->ip = Util::getRealIp();
            $ret = $recharge->save();
            if ($ret) {
                $title = '用户提现';
                $remarks = '拒绝提现退回';
                $money = $recharge->original_money;
                $type = UserBillServices::TYPE_USER;
                $detail_type = 3;
                UserBillServices::addUserBill($user_id, $money, UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
                return true;
            }
            // 调用第三方提现接口
        }
        return false;
    }

    /**
     * 获取用户提现列表
     * @param $where
     * @param $field
     * @param $order
     * @param int $limit
     * @return array
     */
    public static function getWithdrawList($where, $field, $order, $limit = 10)
    {
        $model = new WithdrawModel();
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                if ($value[0] === 'like' || $value[0] === 'not like') {
                    $model = $model->where($column, $value[0], "%$value[1]%");
                } elseif (in_array($value[0], ['>', '=', '<', '<>'])) {
                    $model = $model->where($column, $value[0], $value[1]);
                } elseif ($value[0] == 'in' && !empty($value[1])) {
                    $valArr = $value[1];
                    if (is_string($value[1])) {
                        $valArr = explode(",", trim($value[1]));
                    }
                    $model = $model->whereIn($column, $valArr);
                } elseif ($value[0] == 'not in' && !empty($value[1])) {
                    $valArr = $value[1];
                    if (is_string($value[1])) {
                        $valArr = explode(",", trim($value[1]));
                    }
                    $model = $model->whereNotIn($column, $valArr);
                } elseif ($value[0] == 'null') {
                    $model = $model->whereNull($column);
                } elseif ($value[0] == 'not null') {
                    $model = $model->whereNotNull($column);
                } elseif ($value[0] !== '' || $value[1] !== '') {
                    $model = $model->whereBetween($column, $value);
                }
            } else {
                $model = $model->where($column, $value);
            }
        }
        if ($field) {
            $model = $model->order($field, $order);
        }
        $paginator = $model->paginate($limit);
        $total = $paginator->total();
        $items = $paginator->items();
        return [
            'total' => $total,
            'items' => $items
        ];
    }

    /**
     * 获取用户是否存在充值
     * @param $user_id
     * @return int
     */
    public static function getIsFirst($user_id)
    {
        $is_first = WithdrawModel::where('user_id', $user_id)->count();
        return $is_first == 1 ? 1 : 0;
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
            'money' => '提现金额',
            'to_address' => '用户钱包地址',
            'unit' => '币种',
            'success_time' => '成功时间',
            'is_first' => '是否首提',
            'status' => '状态',
            'admin_name' => '操作管理员',
            'ip' => '操作IP',
            'leader0_user' => '顶推推广员',
            'leader1_user' => '上级推广员',
            'is_demo' => '是否是虚拟用户'
        ];
        $model = new WithdrawModel();
        $model = Util::doExportSelect($model, $map, 'id', 'desc');
        $list = $model->limit(($page - 1) * $limit, $limit)->select();
        $data = [];
        foreach ($list as &$item) {
            $user_info = [
                'id' => 0,
                'username' => '未知',
            ];
            $item['user_info'] = empty($item['user_id']) ? $user_info : UserModel::where('id', $item['user_id'])->field(['id', 'username', 'mobile_code', 'tree', 'login_type'])->find();
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
            $item['status_str'] = WithdrawServices::$STATUS_NAME[$item['status']];
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