<?php


namespace app\services;


use app\common\upay\Transactions;
use app\model\TransactionsLogModel;
use plugin\admin\app\model\PaymentPlatform;
use support\Cache;

class TransactionsLogServices
{
    /**
     * 添加交易记录
     * @param $data
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function addTransactionsLog($data)
    {
        if (!isset($data['txid'])) {
            return false;
        }
        $txid = $data['txid'];
        $map = [
            'txid' => $txid
        ];
        $cache = Cache::store('redis');
        if ($cache->has($txid)) {
            return true;
        }
        $transactionsLog = TransactionsLogModel::where($map)->find();
        if ($transactionsLog) {
            return true;
        }
        $in_data = [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'txid' => $txid,
            'from_address' => $data['from'],
            'to_address' => $data['to'],
            'amount' => $data['amount'],
            'timestamp' => $data['timestamp'],
            'type' => $data['type'],
            'tokenSymbol' => $data['tokenSymbol'],
            'tokenDecimal' => $data['tokenDecimal']
        ];
        $ret = TransactionsLogModel::insert($in_data);
        if ($ret) {
            $cache->set($txid, 1, 60 * 60 * 24);
            return true;
        }
        return false;
    }

    /**
     * 处理交易记录
     * @return TransactionsLogModel|array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getTransactionsLog()
    {
        $map = [
            'status' => 0
        ];
        $list = TransactionsLogModel::where($map)->order('id', 'asc')->limit(500)->select();
        foreach ($list as $item) {

            $up_data = [
                'status' => 1
            ];
            TransactionsLogModel::update($up_data, ['id' => $item['id']]);
        }
        return true;
    }

    /**
     * 扫描地址
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \RedisException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function scanAddress()
    {
        $platform = PaymentPlatform::where('address', '>', '')
            ->where('status', 1)->where('recharge_is_show', 1)->select();
        $trc20_address = "";
        $bep20_address = "";
        $erc20_address = "";
        foreach ($platform as $item) {
            if (str_starts_with($item['name'], 'TRC20')) {
                $trc20_address = $item['address'];
            }
            if (str_starts_with($item['name'], 'BEP20')) {
                $bep20_address = $item['address'];
            }
            if (str_starts_with($item['name'], 'ERC20')) {
                $erc20_address = $item['address'];
            }
        }
        $list = [];
        if (!empty($trc20_address)) {
            $list_trc = Transactions::getTRC20Transactions($trc20_address);
            $list = array_merge($list, $list_trc);
        }
        if (!empty($bep20_address)) {
            $list_bep = Transactions::getBEP20Transactions($bep20_address);
            $list = array_merge($list, $list_bep);
        }
        if (!empty($erc20_address)) {
            $list_erc = Transactions::getERC20Transactions($erc20_address);
            $list = array_merge($list, $list_erc);
        }
        foreach ($list as $item) {
            self::addTransactionsLog($item);
        }
        return $list;
    }
}