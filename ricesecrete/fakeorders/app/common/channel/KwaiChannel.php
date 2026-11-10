<?php

namespace app\common\channel;

use app\services\PromotionServices;
use support\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class KwaiChannel
{
    public static function event($user, $event_name, $event_data = [])
    {
        try {
            $pixel_id = $user['pix'] ? $user['pix'] : PromotionServices::getDefaultPix('kwai');
            $promotion = PromotionServices::getAccessTokenTestEvent($pixel_id, 'kwai');
            $access_token = $promotion['access_token'];
            $test_event_code = $promotion['test_event_code'];
            self::send($user['click_id'], $pixel_id, $access_token, $event_name, $event_data, $test_event_code);
        } catch (\Exception $e) {
            Log::debug('KwaiChannel', [$e->getMessage()]);
            return;
        }
    }

    /**
     * 发送事件
     * @param $clickid
     * @param $pixel_id
     * @param $access_token
     * @param $event_name
     * @param array $event_data
     * @param string $test_event_code
     * @return bool
     */
    public static function send($clickid, $pixel_id, $access_token, $event_name, $event_data = [], $test_event_code = '')
    {
        $url = "http://www.adsnebula.com/log/common/api";
        if (!empty($test_event_code)) {
            $event_name = $test_event_code;
        }
        $data = [
            'access_token' => $access_token,
            'clickid' => $clickid,
            'event_name' => $event_name,
            'is_attributed' => 1,
            'mmpcode' => 'PL',
            'pixelId' => $pixel_id,
            'pixelSdkVersion' => '9.9.9',
            'properties' => json_encode($event_data),
            'testFlag' => false,
            'third_party' => 'shopline',
            'trackFlag' => true
        ];

        try {
            $client = new Client([
                'timeout' => 10,
                'connect_timeout' => 1,
            ]);

            $response = $client->post($url, [
                'headers' => [
                    'Accept' => 'application/json;charset=utf-8',
                    'Content-Type' => 'application/json',
                ],
                'json' => $data
            ]);

            $content = $response->getBody()->getContents();
            $temp = json_decode($content, true);

            if ($temp['result'] == 1) {
                return true;
            } else {
                return false;
            }
        } catch (RequestException $e) {
            Log::debug('KwaiChannel RequestException', [$e->getMessage()]);
            return false;
        } catch (\Exception $e) {
            Log::debug('KwaiChannel Exception', [$e->getMessage()]);
            return false;
        }
    }
}
