<?php


namespace app\model\user;


use app\model\Base;

class UserWalletModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_user_wallet';

    /**
     * 模型数据表对应的完整数据表名称 sql
     * @var string
     */
    protected $table_create_sql = "CREATE TABLE `ex_user_wallet` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `user_id` int DEFAULT '0' COMMENT '用户id',
  `withdraw` tinyint(1) DEFAULT '0' COMMENT '是否提现地址0不是 1是',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '钱包地址类型',
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '钱包地址',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `user_id_2` (`user_id`,`type`) USING BTREE,
  KEY `withdraw` (`withdraw`) USING BTREE,
  KEY `user_id_3` (`user_id`,`withdraw`) USING BTREE,
  KEY `user_id_4` (`user_id`,`withdraw`,`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户钱包地址信息';";
}