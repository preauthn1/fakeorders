<?php


namespace app\model\user;


use app\model\Base;

class UserModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_user';

    /**
     * 模型数据表对应的完整数据表名称 sql
     * @var string
     */
    protected $table_create_sql = "CREATE TABLE `ex_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '用户名',
  `nickname` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '昵称',
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '密码',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '头像',
  `email` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '邮箱',
  `mobile` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '手机',
  `mobile_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '手机区号',
  `login_type` tinyint(1) DEFAULT NULL COMMENT '账号类型',
  `level` tinyint NOT NULL DEFAULT '0' COMMENT '等级',
  `user_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '基础钱包(可转换到收益)',
  `bonus_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '体验金额(不可提现）',
  `frozen_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '提现冻结金额（提现审核中的金额）',
  `recharge_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '充值钱包',
  `brokerage_money` decimal(20,8) NOT NULL DEFAULT '0.00000000' COMMENT '佣金金额（可直接提现）',
  `score` int NOT NULL DEFAULT '0' COMMENT '积分',
  `last_time` datetime DEFAULT NULL COMMENT '登录时间',
  `last_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '登录ip',
  `join_time` datetime DEFAULT NULL COMMENT '注册时间',
  `join_ip` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '注册ip',
  `top_id` int DEFAULT NULL COMMENT '推广用户id',
  `top_time` int DEFAULT '0' COMMENT '推广绑定时间',
  `tree` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '用户推广关系树结构',
  `invitation_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '邀请码',
  `leader0` int DEFAULT '0' COMMENT '顶级推广用户id',
  `leader1` int DEFAULT '0' COMMENT '一级推广用户id',
  `leader2` int DEFAULT '0' COMMENT '二级推广用户id',
  `leader3` int DEFAULT '0' COMMENT '三级推广用户id',
  `leader4` int DEFAULT '0' COMMENT '四级推广用户id',
  `leader5` int DEFAULT '0' COMMENT '五级推广用户id',
  `leader6` int DEFAULT '0' COMMENT '六级推广用户id',
  `leader7` int DEFAULT '0' COMMENT '七级推广用户id',
  `leader8` int DEFAULT '0' COMMENT '八级推广用户id',
  `leader9` int DEFAULT '0' COMMENT '九级推广用户id',
  `login_key` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '登录key',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  `pix` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '像素id',
  `platform` varchar(30) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '推广平台类型',
  `click_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT 'kwai事件id',
  `status` tinyint NOT NULL DEFAULT '1' COMMENT '用户状态 0禁用 1启用',
  `withdraw_status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '提现状态 0禁用 1启用',
  `kyc_status` tinyint(1) DEFAULT '0' COMMENT 'KYC认证状态 0 未认证 1认证中 2认证成功 3认证失败',
  `lang` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '语言',
  `is_demo` tinyint(1) DEFAULT '0' COMMENT '是否是虚拟用户0不是 1是',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `join_time` (`join_time`),
  KEY `mobile` (`mobile`),
  KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `is_demo` (`is_demo`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='用户表';";
}