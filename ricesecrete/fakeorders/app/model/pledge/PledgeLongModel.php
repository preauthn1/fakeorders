<?php


namespace app\model\pledge;


use app\model\Base;

class PledgeLongModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_pledge_long';

    /**
     * 模型数据表对应的完整数据表名称 sql
     * @var string
     */
    protected $table_create_sql = "CREATE TABLE `ex_pledge_long` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '编号',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `is_demo` tinyint(1) DEFAULT '0' COMMENT '是否是虚拟用户0不是 1是',
  `user_id` int DEFAULT '0' COMMENT '用户id',
  `admin_id` int DEFAULT '0' COMMENT '操作管理员id',
  `amount` decimal(20,8) DEFAULT '0.00000000' COMMENT '质押金额',
  `profit` float(8,2) DEFAULT '0.00' COMMENT '收益率 10.2%',
  `settlement_amount` decimal(20,8) DEFAULT '0.00000000' COMMENT '结算金额（包含本金）',
  `cycle` int DEFAULT '0' COMMENT '周期（天）',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0已结算 1已存款',
  `end_time` int DEFAULT '0' COMMENT '结束时间',
  `day` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月日',
  `month` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月',
  `year` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `is_demo` (`is_demo`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `admin_id` (`admin_id`) USING BTREE,
  KEY `created_at` (`created_at`,`user_id`) USING BTREE,
  KEY `user` (`is_demo`,`user_id`) USING BTREE,
  KEY `day` (`day`) USING BTREE,
  KEY `month` (`month`) USING BTREE,
  KEY `year` (`year`) USING BTREE,
  KEY `number` (`number`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='活期质押';";
}