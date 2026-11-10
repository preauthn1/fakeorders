<?php


namespace app\services;


use app\model\PromotionModel;

class PromotionServices
{
    /**
     * 获取像素信息
     * @param $id
     * @return mixed
     */
    public static function getPromotion($id)
    {
        $data = PromotionModel::where('id', $id)->field('pix,platform')->find();
        if (empty($data)) {
            return [
                'pix' => "",
                'platform' => ""
            ];
        }
        return $data;
    }

    /**
     * 获取默认的像素id
     * @param string $platform
     * @return mixed
     */
    public static function getDefaultPix($platform = 'facebook')
    {
        if (empty($platform)) {
            $platform = 'facebook';
        }
        $pixel_id = PromotionModel::where('is_default', 1)
            ->where('platform', $platform)->value('pix');
        if (!$pixel_id) {
            $pixel_id = PromotionModel::where('status', 1)
                ->where('platform', $platform)->value('pix');
        }
        return $pixel_id;
    }

    /**
     * 获取像素的access_token
     * @param $pixel_id
     * @param $platform
     * @return mixed|string
     */
    public static function getAccessToken($pixel_id, $platform)
    {
        $access_token = PromotionModel::where('pix', $pixel_id)->where('platform', $platform)->value('access_token');
        if (!$access_token) {
            return "";
        }
        return $access_token;
    }

    /**
     * 获取像素的access_token 与 测试事件
     * @param $pixel_id
     * @param $platform
     * @return PromotionModel|array|mixed|string[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getAccessTokenTestEvent($pixel_id, $platform)
    {
        $access_token = PromotionModel::where('pix', $pixel_id)->where('platform', $platform)->field('access_token,test_event_code')->find();
        if (!$access_token) {
            return [
                'access_token' => "",
                'test_event_code' => ""
            ];
        }
        return $access_token;
    }

    /**
     * 获取推广链接
     * @param $user_id
     * @param $domain
     * @param $pid
     * @return string
     */
    public static function getUrl($user_id, $domain, $pid)
    {
        $user = UserServices::getUserValue($user_id, ['invitation_code']);
        return "{$domain}?ic=" . $user['invitation_code'] . "&pid=" . $pid;
    }
}