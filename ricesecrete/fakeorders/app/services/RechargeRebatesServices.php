<?php


namespace app\services;


use app\common\Util;
use app\model\RechargeModel;
use app\model\RechargeRebatesModel;
use app\model\user\UserModel;

class RechargeRebatesServices
{
    /**
     * 添加充值返水
     * @param $user_id
     * @param $data
     * @return array
     */
    public static function addRechargeRebates($user_id, $data)
    {
        $number = $data['number'];
        $recharge = RechargeModel::where('number', $number)->find();
        if ($recharge) {
            return [
                'code' => 1,
                'msg' => '充值订单已存在'
            ];
        }
        $money = $data['money'];
        if ($money <= 0) {
            return [
                'code' => 1,
                'msg' => '充值金额不符合规则'
            ];
        }
        //获取充值返佣比例
        $recharge_rebates_rate = SysServices::getSysConfigValue('wallet', 'recharge_rebates_rate');
        $recharge_rebates_rate = floatval($recharge_rebates_rate);
        if ($recharge_rebates_rate <= 0) {
            return [
                'code' => 1,
                'msg' => '充值返佣比例不符合规则'
            ];
        }
        $user_info = UserServices::getUserValue($user_id, ['user_money', 'top_id']);
        // `user_id` int DEFAULT '0' COMMENT '用户id',
        //  `top_id` int DEFAULT NULL COMMENT '上级用户id',
        //  `number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '订单编号',
        //  `money` decimal(20,8) DEFAULT '0.00000000' COMMENT '充值金额',
        //  `condition_money` decimal(20,8) DEFAULT NULL COMMENT '结算需要余额',
        //  `rate` float(10,2) DEFAULT NULL COMMENT '返佣比例 %',
        //  `status` tinyint(1) DEFAULT '0' COMMENT '结算状态 0未结算 1已结算',
        //  `settlement_time` int DEFAULT NULL COMMENT '结算时间',
        $in_data = [
            'user_id' => $user_id,
            'top_id' => empty($user_info['top_id']) ? 0 : $user_info['top_id'],
            'number' => $number,
            'money' => $money,
            'settlement_money' => floatval($money * ($recharge_rebates_rate / 100)),
            'condition_money' => empty($user_info['user_money']) ? 0 : $user_info['user_money'],
            'rate' => $recharge_rebates_rate,
            'status' => empty($user_info['top_id']) ? 1 : 0,
            'settlement_time' => empty($user_info['top_id']) ? time() : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $id = RechargeRebatesModel::insertGetId($in_data);
        return [
            'code' => 0,
            'data' => $id
        ];
    }

    /**
     * 获取充值返佣情况返佣
     * @param $user_id
     * @return array
     */
    public static function getTopRechargeRebates($user_id)
    {
        $map = [
            'top_id' => $user_id,
            'status' => 0
        ];
        //获取未结算佣金
        $settlement_money = RechargeRebatesModel::where($map)->sum('settlement_money');
        $map = [
            'top_id' => $user_id,
            'status' => 1
        ];
        //获取已结算佣金
        $end_settlement_money = RechargeRebatesModel::where($map)->sum('settlement_money');
        //团队人数
        $people_number = UserModel::where('top_id', $user_id)->count();
        return [
            'people_number' => $people_number,
            'settlement_money' => Util::currencyFormat($settlement_money),
            'end_settlement_money' => Util::currencyFormat($end_settlement_money)
        ];
    }

    /**
     * 获取充值返佣情况返佣
     * @param $user_id
     * @return array
     */
    public static function getRechargeRebates($user_id)
    {
        $map = [
            'user_id' => $user_id,
            'status' => 0
        ];
        //获取未结算佣金
        $settlement_money = RechargeRebatesModel::where($map)->sum('settlement_money');
        $map = [
            'user_id' => $user_id,
            'status' => 1
        ];
        //获取已结算佣金
        $end_settlement_money = RechargeRebatesModel::where($map)->sum('settlement_money');
        return [
            'settlement_money' => $settlement_money,
            'end_settlement_money' => $end_settlement_money
        ];
    }

    /**
     * 获取用户返佣团队
     * @param $user_id
     * @param $limit
     * @return array
     */
    public static function getRebatesTeam($user_id, $limit)
    {
        $user_model = new UserModel();
        $map = [
            'top_id' => $user_id
        ];
        $user_model = $user_model->where($map)->field('id,username,user_money, join_time');
        $paginator = $user_model->paginate($limit);
        $total = $paginator->total();
        $items = $paginator->items();
        $temp_team = [];
        foreach ($items as $item) {
            $settlement = self::getRechargeRebates($item['id']);
            $temp_team[] = [
                'id' => $item['id'],
                'username' => $item['username'],
                'user_money' => Util::currencyFormat($item['user_money']),
                'join_time' => $item['join_time'],
                'settlement_money' => Util::currencyFormat($settlement['settlement_money']),
                'end_settlement_money' => Util::currencyFormat($settlement['end_settlement_money']),
                'all_settlement_money' => Util::currencyFormat($settlement['settlement_money'] + $settlement['end_settlement_money'])
            ];
        }
        return [
            'total' => $total,
            'items' => $temp_team,
        ];
    }

    /**
     * 结算充值返佣
     */
    public static function settlementRebates()
    {
        $recharge_rebates_cycle = SysServices::getSysConfigValue('wallet', 'recharge_rebates_cycle');
        if (floatval($recharge_rebates_cycle) <= 0) {
            return [
                'code' => 1,
                'msg' => '充值返佣周期不符合规则'
            ];
        }
        $time = (int)(time() - (floatval($recharge_rebates_cycle) * 86400));
        $days = date("Y-m-d H:i:s", $time);
        $list = RechargeRebatesModel::where('status', 0)->where('created_at', '<', $days)->limit(300)->select();
        foreach ($list as $item) {
            //判断余额是否满足条件
            $user_money = UserServices::getFieldValue($item['user_id'], 'user_money');
            if ($user_money >= $item['condition_money']) {
                $title = '充值返利返佣';
                $remarks = '下级充值返利-1级别';
                $type = UserBillServices::TYPE_USER;
                $detail_type = 9;
                $data = UserBillServices::addUserBill($item['user_id'], $item['settlement_money'], UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
                if ($data['code'] == 0) {
                    $update_data = [
                        'status' => 1,
                        'settlement_time' => time()
                    ];
                    RechargeRebatesModel::where('id', $item['id'])->update($update_data);
                }
            }
        }
        return [
            'code' => 0,
            'msg' => '成功'
        ];
    }
}