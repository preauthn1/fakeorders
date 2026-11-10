<?php


namespace app\model;


class RechargeModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_recharge';

    protected $table_create_sql = "CREATE TABLE `ex_recharge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '订单号',
  `trade_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '第三方订单号',
  `user_id` int unsigned DEFAULT '0' COMMENT '会员ID',
  `is_demo` tinyint(1) DEFAULT '0' COMMENT '是否是虚拟用户0不是 1是',
  `money` decimal(20,8) DEFAULT NULL COMMENT '充值金额',
  `original_money` decimal(20,8) DEFAULT NULL COMMENT '原始金额',
  `exchange_rate` decimal(20,8) DEFAULT '1.00000000' COMMENT '汇率',
  `from_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '发送地址',
  `to_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '接收地址',
  `type` tinyint DEFAULT '0' COMMENT '类型 1USDT 转账 ',
  `platform_id` int DEFAULT '0' COMMENT '支付平台id',
  `unit` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '币种',
  `status` tinyint unsigned DEFAULT '1' COMMENT '状态 1 待支付 2 通过 3 失效',
  `remark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '备注',
  `pay_time` int DEFAULT '0' COMMENT '付款时间',
  `ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '操作IP',
  `admin_id` int DEFAULT '0' COMMENT '操作管理员id',
  `is_first` tinyint(1) DEFAULT '0' COMMENT '是否是首充',
  `success_time` int DEFAULT NULL COMMENT '收到成功回调时间',
  `result` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '充值结果',
  `day` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '年月日',
  `month` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '年月',
  `year` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '年',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `number` (`number`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `from_address` (`from_address`) USING BTREE,
  KEY `to_address` (`to_address`) USING BTREE,
  KEY `trade_no` (`trade_no`) USING BTREE,
  KEY `day` (`day`) USING BTREE,
  KEY `month` (`month`) USING BTREE,
  KEY `year` (`year`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC COMMENT='充值记录表';";
}