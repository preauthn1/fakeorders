<?php


namespace app\services;


use app\common\Util;
use app\model\pledge\PledgeLongModel;
use app\model\pledge\PledgeProfitModel;
use think\facade\Db;

class PledgeLongServices
{
    /**
     * 添加理财
     * @param $user_id
     * @param $data //amount金额 ,cycle周期（天）,profit收益率(%)
     * @return array|mixed
     */
    public static function addPledgeLong($user_id, $data)
    {

        $is_demo = UserServices::getIsDemo($user_id);
        if (!$user_id) {
            return ['code' => 1, 'msg' => '用户id不能为空'];
        }
        if (!isset($data['amount']) || !isset($data['cycle']) || !isset($data['profit']) || empty($data['amount'])) {
            return ['code' => 1, 'msg' => '参数错误'];
        }
        //  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
        //  `number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '编号',
        //  `created_at` int DEFAULT NULL COMMENT '创建时间',
        //  `is_demo` tinyint(1) DEFAULT '0' COMMENT '是否是虚拟用户0不是 1是',
        //  `user_id` int DEFAULT '0' COMMENT '用户id',
        //  `admin_id` int DEFAULT '0' COMMENT '操作管理员id',
        //  `amount` decimal(20,8) DEFAULT '0.00000000' COMMENT '质押金额',
        //  `profit` float(8,2) DEFAULT '0.00' COMMENT '收益率 10.2%',
        //  `cycle` int DEFAULT '0' COMMENT '周期（天）',
        //  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0已结算 1已存款',
        //  `end_time` int DEFAULT '0' COMMENT '结束时间',
        //  `day` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月日',
        //  `month` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月',
        //  `year` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年',
        $profit = $data['profit'];//收益率(%)
        $cycle = $data['cycle'];//质押周期(天)
        $amount = $data['amount'];//质押金额
        $title = '理财';
        $remarks = '理财转入';
        $type = UserBillServices::TYPE_USER;
        $detail_type = 16;
        $data_bill = UserBillServices::addUserBill($user_id, $amount, UserBillServices::CHANGE_SUB, $title, $remarks, $type, $detail_type);
        if ($data_bill['code'] == 0) {
            $in_data = [
                'number' => Util::getPledgeNumber($user_id),
                'created_at' => date("Y-m-d H:i:s"),
                'updated_at' => date("Y-m-d H:i:s"),
                'is_demo' => $is_demo,
                'user_id' => $user_id,
                'amount' => $amount,
                'profit' => $profit,
                'cycle' => $cycle,
                'status' => 1,
                'end_time' => time() + ($cycle * 86400),
                'day' => date("Ymd"),
                'month' => date("Ym"),
                'year' => date("Y")
            ];
            $id = PledgeLongModel::insertGetId($in_data);
            if ($id) {
                return ['code' => 0, 'msg' => '成功', 'data' => ['id' => $id]];
            } else {
                // 退回
                $title = '理财';
                $remarks = '理财退回';
                $type = UserBillServices::TYPE_USER;
                $detail_type = 17;
                UserBillServices::addUserBill($user_id, $amount, UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
                return ['code' => 1, 'msg' => '失败'];
            }
        }
        return $data_bill;
    }

    /**
     * 理财结算
     * @param $user_id
     * @return array
     */
    public static function settlementPledgeLong($user_id)
    {
        $map = [
            'user_id' => $user_id,
            'status' => 1,
        ];
        $settlement_list = PledgeLongModel::where($map)->where('end_time', '<', time())->select();
        foreach ($settlement_list as $item) {
            $settlement_amount = $item['amount'] * $item['profit'] / 100;
            if ($settlement_amount > 0) {
                $title = '理财';
                $remarks = '理财结算';
                $type = UserBillServices::TYPE_USER;
                $detail_type = 18;
                $data_bill = UserBillServices::addUserBill($user_id, $item['amount'] + $settlement_amount, UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
                if ($data_bill['code'] == 0) {
                    $map['id'] = $item['id'];
                    $update = [
                        'status' => 0,
                        'settlement_amount' => $settlement_amount,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    PledgeLongModel::where($map)->where('end_time', '<', time())->update($update);
                    //  `user_id` int DEFAULT '0' COMMENT '用户id',
                    //  `amount` decimal(20,8) DEFAULT '0.00000000' COMMENT '收益金额',
                    //  `profit` float(8,4) DEFAULT '0.0000' COMMENT '收益率',
                    //  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1活期质押收益 2理财收益',
                    //  `day` varchar(8) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月日',
                    //  `month` varchar(6) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月',
                    //  `year` varchar(4) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年',
                    $in_profit = [
                        'created_at' => date("Y-m-d H:i:s"),
                        'user_id' => $user_id,
                        'amount' => $settlement_amount,
                        'profit' => $item['profit'],
                        'type' => 2,
                        'day' => date('Ymd'),
                        'month' => date('Ym'),
                        'year' => date('Y'),
                    ];
                    PledgeProfitModel::insert($in_profit);
                    return ['code' => 0, 'msg' => '成功'];
                } else {
                    return ['code' => 1, 'msg' => '失败'];
                }
            } else {
                $map['id'] = $item['id'];
                $update = [
                    'status' => 0,
                    'settlement_amount' => 0,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                PledgeLongModel::where($map)->where('end_time', '<', time())->update($update);
            }
        }

        return ['code' => 0, 'msg' => '成功'];
    }

    /**
     * 全部结算
     * @return array
     */
    public static function settlementPledgeCurrentAll()
    {
        $map = [
            'status' => 1,
        ];
        $user_list = PledgeLongModel::where($map)->field('user_id')->group('user_id')->select();
        if ($user_list) {
            foreach ($user_list as $item) {
                self::settlementPledgeLong($item->user_id);
            }
        }
        return ['code' => 0, 'msg' => '成功'];
    }

    /**
     * 获取理财列表
     * @param $user_id
     * @param $status //状态 0已结算 1已存款
     * @param $limit
     * @return array
     */
    public static function getPledgeLongList($user_id, $status, $limit)
    {
        $map = [
            'status' => $status,
            'user_id' => $user_id,
        ];
        $pledgeCurrentModel = new PledgeLongModel();
        $query = $pledgeCurrentModel->where($map)
            ->order('id', 'desc');
        $paginator = $query->paginate($limit);
        $total = $paginator->total();
        $items = $paginator->items();
        $message_item = [];
        foreach ($items as &$item) {
            $item = $item->toArray();
            $temp_item['id'] = $item['id'];
            $temp_item['number'] = $item['number'];
            $temp_item['amount'] = Util::currencyFormat($item['amount']);
            $temp_item['profit'] = empty($item['profit']) ? '--' : round($item['profit'], 2);
            $temp_item['cycle'] = $item['cycle'];//结算周期
            $temp_item['settlement_amount'] = Util::currencyFormat($item['settlement_amount']);
            $temp_item['expected_amount'] = Util::currencyFormat($item['amount'] + ($item['amount'] * $item['profit'] / 100));
            $temp_item['updated_at'] = empty($item['status']) ? $item['updated_at'] : '--';
            $temp_item['end_time'] = empty($item['end_time']) ? '--' : date("Y-m-d H:i:s", $item['end_time']);
            $temp_item['created_at'] = date("Y-m-d H:i:s", strtotime($item['created_at']));
            $message_item[] = $temp_item;
        }
        return [
            'total' => $total,
            'items' => $message_item
        ];
    }

    /**
     * 获取质押中 并且符合条件的质押总额度
     * @param $user_id
     * @param $days
     * @return int|mixed
     */
    public static function getPledgeLongIng($user_id, $days)
    {
        $map = [
            'user_id' => $user_id,
            'status' => 1
        ];
        return PledgeLongModel::where($map)->where('cycle', '>=', $days)->sum('amount');
    }

    //获取长线质押统计信息
    public static function getPledgeLongStat()
    {
        $list = Db::table('ex_pledge_long')
            ->group('cycle')
            ->field('cycle,sum(amount) as total_amount')
            ->select()->toArray();
        $data = [];
        if ($list) {
            foreach ($list as $item) {
                $data[] = [
                    'cycle' => $item['cycle'],
                    'total_amount' => Util::currencyFormat($item['total_amount'])
                ];
            }
        }
        return $data;
    }

    /**
     * 长线质押图表
     * @param int $days
     * @return array[]
     */
    public static function getPledgeLongChart($days = 7)
    {
        $start_day = date('Y-m-d', strtotime("-$days day"));
        $start_time = date('Y-m-d H:i:s', strtotime($start_day));
        $list = Db::table('ex_pledge_long')
            ->where('status', '=', 1)
            ->where('created_at', '>', $start_time)
            ->group('day')
            ->field('day,sum(amount) as total_amount')
            ->select()->toArray();
        $data = [];
        $timestamp = [];
        $total_amount = [];
        if ($list) {
            foreach ($list as $item) {
                $data[strtotime($item['day'])] = floatval($item['total_amount']);
            }
        }
        $start_time_int = strtotime($start_day);
        for ($i = $days; $i >= 0; $i--) {
            $key = $start_time_int;
            $timestamp[] = $key;
            $total_amount[] = isset($data[$key]) ? $data[$key] : 0;
            $start_time_int = $start_time_int + (3600 * 24);
        }
        return [
            'timestamp' => $timestamp,
            'total_amount' => $total_amount
        ];
    }
}