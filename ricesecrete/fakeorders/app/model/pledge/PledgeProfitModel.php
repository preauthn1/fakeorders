<?php


namespace app\model\pledge;


use app\model\Base;
use function Symfony\Component\VarExporter\Internal\f;

class PledgeProfitModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_pledge_profit';

    protected $autoWriteTimestamp = false;

    /**
     * 模型数据表对应的完整数据表名称 sql
     * @var string
     */
    protected $table_create_sql = "CREATE TABLE `ex_pledge_profit` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `user_id` int DEFAULT '0' COMMENT '用户id',
  `amount` decimal(20,8) DEFAULT '0.00000000' COMMENT '收益金额',
  `profit` float(8,4) DEFAULT '0.0000' COMMENT '收益率',
  `type` tinyint(1) DEFAULT '1' COMMENT '类型 1活期质押收益 2理财收益',
  `day` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月日',
  `month` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年月',
  `year` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '年',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `user_id` (`user_id`) USING BTREE,
  KEY `type` (`type`) USING BTREE,
  KEY `user_id_2` (`user_id`,`type`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='质押收益记录';";
}