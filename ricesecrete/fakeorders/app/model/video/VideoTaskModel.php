<?php


namespace app\model\video;


use app\model\Base;

class VideoTaskModel extends Base
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'ex_video_task';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $pk = 'id';
    /**
     * 模型数据表对应的完整数据表名称 sql
     * @var string
     */
    protected $table_create_sql = "CREATE TABLE `ex_video_task` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
  `video_id` int DEFAULT '0' COMMENT '视频id',
  `user_id` int DEFAULT '0' COMMENT '用户id',
  `profit` decimal(20,8) DEFAULT '0.00000000' COMMENT '任务金额',
  `status` tinyint(1) DEFAULT '1' COMMENT '任务状态 0未开始 1进行中 2审核中 3审核失败 4已完成',
  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `video_id` (`video_id`),
  KEY `user_id` (`user_id`),
  KEY `user_id_2` (`user_id`,`status`),
  KEY `video_id_2` (`video_id`,`user_id`,`status`),
  KEY `user_id_3` (`user_id`,`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='视频任务';";
}