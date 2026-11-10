<?php


namespace app\common;


class ExceptionCode
{
    //成功
    const SUCCESS = 0;
    //错误
    const ERROR = 1;
    //系统错误
    const SYS_ERROR = 500;

    //用户不存在
    const USER_NOT_EXIST = 1001;
    //无效的钱包类型
    const USER_BILL_TYPE_ERROR = 1002;
    //余额不足
    const USER_BILL_NOT_ENOUGH = 1003;
    //用户余额更新失败
    const USER_BILL_CHANGE_FAILED = 1004;
    //账单记录插入失败
    const USER_BILL_INSERT_FAILED = 1005;

    //文件错误
    const S3_FILE_ERROR = 3001;

    //token错误
    const TOKEN_ERROR = 4001;

    static public $code = [
        self::SUCCESS => 'Operation successful',
        self::ERROR => 'Operation failed',
        self::SYS_ERROR => 'System error',
        self::USER_NOT_EXIST => 'User does not exist',
        self::USER_BILL_TYPE_ERROR => 'Invalid wallet type',
        self::USER_BILL_NOT_ENOUGH => 'Insufficient balance',
        self::USER_BILL_CHANGE_FAILED => 'Failed to update user balance',
        self::USER_BILL_INSERT_FAILED => 'Failed to insert bill record',
        self::S3_FILE_ERROR => 'File upload failed',
        self::TOKEN_ERROR => 'Token error'
    ];
}