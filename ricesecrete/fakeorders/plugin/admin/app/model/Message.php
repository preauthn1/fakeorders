<?php


namespace plugin\admin\app\model;


class Message extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_message';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $pk = 'id';
}