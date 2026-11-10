<?php

namespace app\common\channel;

use app\services\PromotionServices;
use support\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class TikTokChannel
{
    public static function event($user, $event_name, $event_data = [])
    {
        try {
            $pixel_id = $user['pix'] ? $user['pix'] : PromotionServices::getDefaultPix('tiktok');
            $promotion = PromotionServices::getAccessTokenTestEvent($pixel_id, 'tiktok');
            $access_token = $promotion['access_token'];
            $test_event_code = $promotion['test_event_code'];
            self::send($pixel_id, $access_token, $event_name, $event_data, $test_event_code);
        } catch (\Exception $e) {
            Log::debug('TiktokChannel', [$e->getMessage()]);
            return;
        }
    }

    /**
     * 发送事件
     * @param $pixel_id
     * @param $access_token
     * @param $event_name
     * @param array $event_data
     * @param string $test_event_code
     * @return bool
     */
    public static function send($pixel_id, $access_token, $event_name, $event_data = [], $test_event_code = '')
    {
        //curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/event/track/'     --header 'Access-Token: <<ACCESS TOKEN>>'     --header 'Content-Type: application/json'     --data-raw '{
        //    "event_source": "web",
        //    "event_source_id": "CR6PHPBC77UFSKJEKQS0",
        //    "data": [
        //        {
        //            "event": "CompletePayment",
        //            "event_time": 1724756811,
        //            "user": {},
        //            "properties": {
        //                "value": 100
        //            },
        //            "page": {}
        //        }
        //    ]
        //}'
        $url = "https://business-api.tiktok.com/open_api/v1.3/event/track/";
        if (!empty($test_event_code)) {
            $event_name = $test_event_code;
        }
        $data = [
            'event_source' => 'web',
            'event_source_id' => $pixel_id,
            'data' => [
                [
                    'event' => $event_name,
                    'event_time' => time(),
                    'properties' => $event_data,
                ]
            ]
        ];

        try {
            $client = new Client([
                'timeout' => 10,
                'connect_timeout' => 1,
            ]);

            $response = $client->post($url, [
                'headers' => [
                    'Access-Token' => $access_token,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data
            ]);

            $content = $response->getBody()->getContents();
            $temp = json_decode($content, true);

            if ($temp['code'] == 0) {
                return true;
            } else {
                return false;
            }
        } catch (RequestException $e) {
            Log::debug('TiktokChannel RequestException', [$e->getMessage()]);
            return false;
        } catch (\Exception $e) {
            Log::debug('TiktokChannel Exception', [$e->getMessage()]);
            return false;
        }
    }
}
