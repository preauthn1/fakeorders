<?php


namespace app\services;


use app\common\ExceptionCode;
use app\common\push\PushApi;
use app\common\Util;
use app\model\user\UserBillModel;
use app\model\user\UserModel;
use DateTime;
use support\Db;

class UserBillServices
{
    // `user_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '基础钱包(可转换到收益)',
    //  `bonus_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '体验金额(不可提现）',
    //  `frozen_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '提现冻结金额（提现审核中的金额）',
    //  `recharge_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '充值钱包',
    //  `brokerage_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '佣金金额（可直接提现）',
    //  `score` int NOT NULL DEFAULT '0' COMMENT '积分',
    const TYPE_USER = 1;//基础钱包(可转换到收益)
    const TYPE_BONUS = 2;//体验金额(不可提现）
    const TYPE_FROZEN = 3;//提现冻结金额（提现审核中的金额）
    const TYPE_RECHARGE = 4;//充值钱包
    const TYPE_BROKERAGE = 5;//佣金金额（可直接提现）
    const TYPE_SCORE = 6;//积分

    static $TYPE_FIELDS = [
        self::TYPE_USER => "user_money",
        self::TYPE_BONUS => "bonus_money",
        self::TYPE_FROZEN => "frozen_money",
        self::TYPE_RECHARGE => "recharge_money",
        self::TYPE_BROKERAGE => "brokerage_money",
        self::TYPE_SCORE => "score",
    ];
    static $TYPE_NAMES = [
        self::TYPE_USER => "基础钱包",
        self::TYPE_BONUS => "体验钱包",
        self::TYPE_FROZEN => "冻结金额",
        self::TYPE_RECHARGE => "充值钱包",
        self::TYPE_BROKERAGE => "佣金金额",
        self::TYPE_SCORE => "积分",
    ];

    const CHANGE_ADD = 1;//1增加
    const CHANGE_SUB = 2;//2减少

    static $DETAIL_TYPE_NAMES = [
        0 => '未定义',
        1 => '用户充值',
        2 => '提现申请',
        3 => '拒绝提现',
        4 => '注册送款',
        5 => '首充送款',
        6 => '快爆奖励',
        7 => '升级返佣',
        8 => '分享奖励',
        9 => '下级充值返利-1级别',
        10 => '下级充值返利-2级别',
        11 => '下级充值返利-3级别',
        16 => '理财',
        17 => '理财失败',
        18 => '理财结算',
    ];

    public static function addUserBill($user_id, $money, $change, $title, $remarks, $type, $detail_type, $admin_id = 0, $order_id = 0, $from_user_id = 0)
    {
        try {
            return Db::transaction(function () use ($user_id, $money, $change, $title, $remarks, $type, $detail_type, $admin_id, $order_id, $from_user_id) {
                if ($change == self::CHANGE_ADD) {
                    $money = abs($money);
                } else {
                    $money = -abs($money);
                }

                $userBillModel = new UserBillModel();
                $user_info = UserModel::where('id', $user_id)->find();

                if (!$user_info) {
                    throw new \Exception("用户不存在", ExceptionCode::USER_NOT_EXIST);
                }

                // 获取正确的字段名
                $field_name = self::$TYPE_FIELDS[$type] ?? null;
                if (!$field_name) {
                    throw new \Exception("无效的钱包类型", ExceptionCode::USER_BILL_TYPE_ERROR);
                }

                // 获取当前余额
                $before_money = $user_info[$field_name];
                $after_money = $before_money + $money;

                // 检查余额是否足够（减少操作时）
                if ($change == self::CHANGE_SUB && $after_money < 0) {
                    throw new \Exception("余额不足", ExceptionCode::USER_BILL_NOT_ENOUGH);
                }
                $data = [
                    'created_at' => time(),
                    'is_demo' => $user_info['is_demo'],
                    'user_id' => $user_id,
                    'change' => $change,
                    'title' => $title,
                    'remarks' => $remarks,
                    'money' => abs($money),
                    'before_money' => $before_money,
                    'after_money' => $after_money,
                    'type' => $type,
                    'detail_type' => $detail_type,
                    'admin_id' => $admin_id,
                    'order_id' => $order_id,
                    'from_user_id' => $from_user_id,
                    'day' => date("Ymd"),
                    'month' => date("Ym"),
                    'year' => date("Y"),
                ];
                // 插入账单记录
                $ret = $userBillModel->insertToMonthTable($data);
                if ($ret) {
                    // 更新用户余额
                    $up_data = [
                        $field_name => $after_money,
                    ];
                    $ret = UserModel::where('id', $user_id)->update($up_data);
                    if (!$ret) {
                        throw new \Exception("用户余额更新失败", ExceptionCode::USER_BILL_CHANGE_FAILED);
                    }
                    //余额变动发消息
                    if ($type == UserBillServices::TYPE_USER) {
                        PushApi::sendPrivateMessage($user_id, [
                            'title' => $title,
                            'data' => [
                                UserBillServices::$TYPE_FIELDS[$type] => Util::currencyFormat($after_money),
                            ],
                            'type' => 3,
                        ]);
                    }
                    return [
                        'code' => 0,
                        'msg' => '成功',
                    ];
                } else {
                    throw new \Exception("账单记录插入失败", ExceptionCode::USER_BILL_INSERT_FAILED);
                }
            });
        } catch (\Exception $e) {
            // 记录错误日志
            \support\Log::error("UserBill add failed: " . $e->getMessage());
            return [
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
            ];
        }
    }

    /**
     * 获取用户流水
     * @param $map
     * @param $field
     * @param $order
     * @param $page
     * @param $page_size
     * @return array
     * @throws \Exception
     */
    public function getBillList($map, $field, $order, $page, $page_size)
    {
        $userBillModel = new UserBillModel();
        return $userBillModel->getBillsByRawUnion($map, $field, $order, $page, $page_size);
    }

    /**
     * 删除账单
     * @param $id
     * @param $day
     * @return bool|mixed|null
     * @throws \Exception
     */
    public function delBills($id, $day)
    {
        $userBillModel = new UserBillModel();
        $current = new DateTime(date('Y-m-01', strtotime($day)));
        $userBillModel = $userBillModel->tableByMonth($current->format('Ym'));
        return $userBillModel->where('id', $id)->delete();
    }
}