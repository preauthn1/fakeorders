<?php


namespace app\common\upay;


class Config
{
    public static $validityPeriod = 2;//有效时间 单位小时 1小时
    /**
     * Etherscan API密钥
     * @var string
     */
    public static $apiEtherscanKey = 'R7IJ488UVBFEFC1QWS6TWN32UXKECEY5YQ';
    public static $apiEtherscanUrl = 'https://api.etherscan.io/v2/api';
    public static $apiEtherscanERCAddress = '0xdac17f958d2ee523a2206206994597c13d831ec7';
    public static $apiEtherscanBEPAddress = '0x55d398326f99059ff775485246999027b3197955';
    /**
     * TronGrid API密钥
     * @var string
     */
    public static $apiTrongridKey = 'ff3e835e-6a2e-44a2-b8c3-9cbf05efc4ce';
    public static $apiTrongridUrl = 'https://api.trongrid.io';
    public static $apiTrongridAddress = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';


}