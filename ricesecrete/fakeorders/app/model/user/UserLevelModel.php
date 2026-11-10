<?php


namespace app\model\user;


use app\model\Base;

class UserLevelModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_user_level';

    /**
     * 模型数据表对应的完整数据表名称 sql
     * @var string
     */
    protected $table_create_sql = "CREATE TABLE `ex_user_level` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `number` int DEFAULT '0' COMMENT '用户等级编号',
  `name` char(60) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '用户等级名称',
  `price` decimal(10,2) DEFAULT '0.00' COMMENT '升级金额',
  `status` tinyint DEFAULT '1' COMMENT '状态',
  `sort` int DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户等级';";
}