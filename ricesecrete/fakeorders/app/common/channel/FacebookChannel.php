<?php

namespace app\common\channel;

use app\services\PromotionServices;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use support\Log;

class FacebookChannel
{
    public static function event($user, $event_name, $event_data = [])
    {
        try {
            $pixel_id = $user['pix'] ? $user['pix'] : PromotionServices::getDefaultPix('facebook');
            $promotion = PromotionServices::getAccessTokenTestEvent($pixel_id, 'facebook');
            $access_token = $promotion['access_token'];
            $test_event_code = $promotion['test_event_code'];
        } catch (\Exception $e) {
            Log::debug('FacebookChannel', [$e->getMessage()]);
            return;
        }

        try {
            Api::init(null, null, $access_token);
            $user_data = (new UserData());
            $result = filter_var($user->user_name, FILTER_SANITIZE_EMAIL);
            if (filter_var($result, FILTER_VALIDATE_EMAIL)) {
                $user_data->setEmail($user->user_name);
            } else {
                $user_data->setPhone($user->mobile);
            }
            $custom_data = new CustomData();
            if ($event_name == 'first_deposit') {
                $custom_data = $custom_data->setValue($event_data['value']);
            }
            $event = (new Event())
                ->setEventName($event_name)
                ->setEventTime(time())
                ->setUserData($user_data)
                ->setCustomData($custom_data);
            $request = (new EventRequest($pixel_id))->setEvents(array($event));
            if ($test_event_code) {
                $request->setTestEventCode($test_event_code);
            }
            $response = $request->execute();
        } catch (\Exception $e) {
            Log::debug("FacebookChannel", [$e->getMessage()]);
            return;
        }
        Log::debug("FacebookChannel", [json_encode($response->getMessages()) . '--' . $response->getFbTraceId()]);
    }
}
