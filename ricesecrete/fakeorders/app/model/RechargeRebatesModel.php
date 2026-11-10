<?php


namespace app\model;


class RechargeRebatesModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_recharge_rebates';

    protected $table_create_sql = "CREATE TABLE `ex_recharge_rebates` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `user_id` int DEFAULT '0' COMMENT '用户id',
  `top_id` int DEFAULT NULL COMMENT '上级用户id',
  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '订单编号',
  `money` decimal(20,8) DEFAULT '0.00000000' COMMENT '充值金额',
  `settlement_money` decimal(20,8) DEFAULT '0.00000000' COMMENT '佣金',
  `condition_money` decimal(20,8) DEFAULT NULL COMMENT '结算需要余额',
  `rate` float(10,2) DEFAULT NULL COMMENT '返佣比例 %',
  `status` tinyint(1) DEFAULT '0' COMMENT '结算状态 0未结算 1已结算',
  `settlement_time` int DEFAULT NULL COMMENT '结算时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `top_id` (`top_id`) USING BTREE,
  KEY `status` (`status`) USING BTREE,
  KEY `settlement_time` (`settlement_time`) USING BTREE,
  KEY `top_id_2` (`top_id`,`status`,`settlement_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC COMMENT='充值返利表';";
}