<?php


namespace app\model;


class TransactionsLogModel extends Base
{
    protected $table = 'ex_transactions_log';

    /**
     * 创建表sql
     * @var string
     */
    protected $table_create_sql = "CREATE TABLE `ex_transactions_log` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `txid` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '交易哈希',
  `from_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '发送地址',
  `to_address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '接收地址',
  `amount` decimal(30,18) DEFAULT '0.000000000000000000' COMMENT '交易金额',
  `timestamp` int DEFAULT NULL COMMENT '交易时间戳',
  `type` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '链类型 BEP20 TRC20 ERC20',
  `tokenSymbol` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '交易币种  USDT BSC-USD',
  `tokenDecimal` int DEFAULT '6' COMMENT '交易精度位数',
  PRIMARY KEY (`id`),
  KEY `txid` (`txid`),
  KEY `to_address` (`to_address`),
  KEY `from_address` (`from_address`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='数字货币交易记录';";
}