<?php

namespace app\common\upay;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Transactions
{
    /**
     * 获取TRC20 USDT交易记录
     * @param string $address
     * @return array
     */
    public static function getTRC20Transactions($address)
    {
        // TRC20地址处理
        $transactions = [];
        // TronGrid API端点
        $apiUrl = Config::$apiTrongridUrl . "/v1/accounts/{$address}/transactions/trc20";
        $params = [
            'contract_address' => Config::$apiTrongridAddress, // USDT合约地址
            'limit' => 50,
            'only_confirmed' => true,
            'order_by' => 'block_timestamp,desc'
        ];
        // 构建完整的URL
        $url = $apiUrl . '?' . http_build_query($params);

        // 获取API密钥
        $apiKey = Config::$apiTrongridKey;

        try {
            // 使用 GuzzleHttp\Client 发起请求
            $client = new Client();
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'PHP Wallet Scanner',
                    'TRON-PRO-API-KEY' => $apiKey
                ],
                'timeout' => 30
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // error_log("TronGrid API 请求错误: " . $e->getMessage());
            return $transactions;
        } catch (\Exception $e) {
            // error_log("TronGrid API 错误: " . $e->getMessage());
            return $transactions;
        }

        // 检查API响应
        if (isset($data['success']) && $data['success'] && isset($data['data'])) {
            foreach ($data['data'] as $item) {
                // 只处理转入的交易（接收方是监控地址）
                if (strtolower($item['to']) === strtolower($address)) {
                    if (($item['block_timestamp'] / 1000) >= (time() - 3600 * Config::$validityPeriod)) {
                        $transactions[] = [
                            'txid' => $item['transaction_id'],
                            'from' => $item['from'],
                            'to' => $item['to'],
                            'amount' => floatval($item['value'] / pow(10, $item['token_info']['decimals'])), // 动态计算小数位
                            'timestamp' => $item['block_timestamp'] / 1000,
                            'date' => date("Y-m-d H:i:s", $item['block_timestamp'] / 1000),
                            'type' => 'TRC20',
                            'tokenSymbol' => $item['token_info']['symbol'],
                            'tokenDecimal' => $item['token_info']['decimals']
                        ];
                    }
                }
            }
        } else {
            $errorMessage = isset($data['error']) ? $data['error'] : '未知错误';
            // error_log("TronGrid API错误: " . $errorMessage);
        }
        return $transactions;
    }

    /**
     * 获取ERC20 USDT交易记录 (使用Etherscan API V2)
     * @param string $address
     * @return array
     */
    public static function getERC20Transactions($address)
    {
        // 使用Etherscan API V2获取交易记录
        $transactions = [];
        // Etherscan V2 Multichain API端点
        $apiUrl = Config::$apiEtherscanUrl;
        $params = [
            'chainid' => '1', // 以太坊主网
            'module' => 'account',
            'action' => 'tokentx',
            'contractaddress' => Config::$apiEtherscanERCAddress, // USDT合约地址
            'address' => $address,
            'page' => 1,
            'offset' => 50,
            'sort' => 'desc'
        ];

        // 从配置或环境变量中获取API密钥
        $apiKey = Config::$apiEtherscanKey;

        if (!$apiKey) {
            error_log("缺少Etherscan API密钥");
            return $transactions;
        }

        $params['apikey'] = $apiKey;

        // 构建完整的URL
        $url = $apiUrl . '?' . http_build_query($params);

        try {
            // 使用 GuzzleHttp\Client 发起请求
            $client = new Client();
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'PHP Wallet Scanner'
                ],
                'timeout' => 30
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // error_log("Etherscan API 请求错误: " . $e->getMessage());
            return $transactions;
        } catch (\Exception $e) {
            // error_log("Etherscan API 错误: " . $e->getMessage());
            return $transactions;
        }

        // 检查API响应状态
        if (isset($data['status']) && $data['status'] == '1' && isset($data['result'])) {
            foreach ($data['result'] as $item) {
                if (($item['timeStamp'] / 1000) >= (time() - 3600 * Config::$validityPeriod)) {
                    $transactions[] = [
                        'txid' => $item['hash'],
                        'from' => $item['from'],
                        'to' => $item['to'],
                        'amount' => floatval($item['value'] / pow(10, $item['tokenDecimal'])), // 使用tokenDecimal动态计算
                        'timestamp' => $item['timeStamp'],
                        'date' => date("Y-m-d H:i:s", $item['timeStamp'] / 1000),
                        'type' => 'ERC20',
                        'tokenSymbol' => $item['tokenSymbol'],
                        'tokenDecimal' => $item['tokenDecimal']
                    ];
                }
            }
        } else if (isset($data['message'])) {
            // error_log("Etherscan API错误: " . $data['message']);
        }

        return $transactions;
    }

    /**
     * 获取BEP20 USDT交易记录 (使用Etherscan V2 Multichain API)
     * @param string $address
     * @return array
     */
    public static function getBEP20Transactions($address)
    {
        // BEP20地址处理
        $transactions = [];

        // Etherscan V2 Multichain API端点
        $apiUrl = Config::$apiEtherscanUrl;
        $params = [
            'chainid' => '56', // BNB Smart Chain主网ID
            'module' => 'account',
            'action' => 'tokentx',
            'contractaddress' => Config::$apiEtherscanBEPAddress, // BUSDT合约地址
            'address' => $address,
            'page' => 1,
            'offset' => 50,
            'sort' => 'desc',
        ];

        // 获取API密钥
        $apiKey = Config::$apiEtherscanKey;
        if (!$apiKey) {
            error_log("缺少Etherscan API密钥");
            return $transactions;
        }

        $params['apikey'] = $apiKey;

        // 构建完整的URL
        $url = $apiUrl . '?' . http_build_query($params);

        try {
            // 使用 GuzzleHttp\Client 发起请求
            $client = new Client();
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'PHP Wallet Scanner'
                ],
                'timeout' => 30
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            // error_log("Etherscan V2 API 请求错误: " . $e->getMessage());
            return $transactions;
        } catch (\Exception $e) {
            // error_log("Etherscan V2 API 错误: " . $e->getMessage());
            return $transactions;
        }

        // 检查API响应状态
        if (isset($data['status']) && $data['status'] == '1' && isset($data['result'])) {
            foreach ($data['result'] as $item) {
                // 只处理转入的交易（接收方是监控地址）
                if (strtolower($item['to']) === strtolower($address)) {
                    if ($item['timeStamp'] >= (time() - 3600 * Config::$validityPeriod)) {
                        $transactions[] = [
                            'txid' => $item['hash'],
                            'from' => $item['from'],
                            'to' => $item['to'],
                            'amount' => floatval($item['value'] / pow(10, $item['tokenDecimal'])), // 使用tokenDecimal动态计算
                            'timestamp' => $item['timeStamp'],
                            'date' => date("Y-m-d H:i:s", $item['timeStamp']),
                            'type' => 'BEP20',
                            'tokenSymbol' => $item['tokenSymbol'],
                            'tokenDecimal' => $item['tokenDecimal']
                        ];
                    }
                }
            }
        } else if (isset($data['message'])) {
            // error_log("Etherscan V2 API错误: " . $data['message']);
        }
        return $transactions;
    }
}
