<?php


namespace app\model;


class WithdrawModel extends Base
{
    /**
     * 与模型关联的表名
     * @var string
     */
    protected $table = 'ex_withdraw';

    protected $table_create_sql = "CREATE TABLE `ex_withdraw` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '订单号',
  `trade_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '第三方订单号',
  `user_id` int unsigned NOT NULL DEFAULT '0' COMMENT '会员ID',
  `is_demo` tinyint(1) DEFAULT '0' COMMENT '是否是虚拟用户0不是 1是',
  `money` decimal(20,8) NOT NULL COMMENT '实际到账金额',
  `original_money` decimal(20,8) NOT NULL COMMENT '原始申请金额',
  `exchange_rate` decimal(20,8) NOT NULL DEFAULT '1.00000000' COMMENT '汇率',
  `from_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '平台地址',
  `to_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提现地址',
  `type` tinyint NOT NULL DEFAULT '0' COMMENT '类型 1USDT 转账 ',
  `platform_id` int DEFAULT '0' COMMENT '支付平台id',
  `unit` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '货币符号',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '状态 1 待审核 2 通过 3 失败',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `pay_time` int DEFAULT '0' COMMENT '到账时间',
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作IP',
  `admin_id` int DEFAULT '0' COMMENT '操作管理员id',
  `is_first` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是首提',
  `success_time` int DEFAULT NULL COMMENT '收到成功回调时间',
  `result` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '提现结果',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `number` (`number`),
  KEY `user_id` (`user_id`),
  KEY `from_address` (`from_address`),
  KEY `to_address` (`to_address`),
  KEY `trade_no` (`trade_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现记录表';";
}