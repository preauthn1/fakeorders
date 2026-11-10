<?php


namespace app\common\payment;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use support\Log;

class PayRequest
{
    /**
     * 发起请求
     * @param array $data
     * @param string $url
     * @param string $method
     * @return mixed
     */
    public static function request(array $data, string $url, string $method = 'post')
    {
        $client = new Client();
        $options = [
            'json' => $data,
            'timeout' => 10,
        ];
        try {
            $response = $client->request($method, $url, $options);
        } catch (GuzzleException | \Exception $e) {
            Log::info('请求失败', [
                'title' => '请求失败',
                'msg' => $e->getMessage()
            ]);
            return ['code' => -1, 'msg' => $e->getMessage()];
        }

        $content = $response->getBody()->getContents();
        return json_decode($content, true);
    }
}