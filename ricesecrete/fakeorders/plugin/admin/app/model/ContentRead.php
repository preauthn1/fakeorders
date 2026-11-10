<?php


namespace plugin\admin\app\model;


class ContentRead extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_content_read';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $pk = 'id';

    protected $autoWriteTimestamp = false;
}