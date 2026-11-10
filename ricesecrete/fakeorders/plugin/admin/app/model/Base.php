<?php

namespace plugin\admin\app\model;

use DateTimeInterface;
use think\Model;


class Base extends Model
{
    /**
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * 格式化日期
     *
     * @param DateTimeInterface $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    //创建时间和修改时间字段
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
