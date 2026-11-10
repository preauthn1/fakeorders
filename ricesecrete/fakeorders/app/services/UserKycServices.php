<?php


namespace app\services;


use app\model\user\UserKycModel;
use app\model\user\UserLevelModel;
use app\model\user\UserModel;

class UserKycServices
{

    /**
     * KYC状态 审核中
     * @var int
     */
    const KYC_STATUS_WAIT = 1;
    /**
     * KYC状态 成功
     * @var int
     */
    const KYC_STATUS_SUCCESS = 2;
    /**
     * KYC状态 失败
     * @var int
     */
    const KYC_STATUS_FAIL = 3;

    /**
     * KYC状态
     * @var string[]
     */
    static $STATUS_NAMES = [
        self::KYC_STATUS_WAIT => '审核中',
        self::KYC_STATUS_SUCCESS => '已认证',
        self::KYC_STATUS_FAIL => '认证失败'
    ];

    /**
     * 添加KYC认证信息
     * @param $user_id
     * @param $data
     * @return int
     */
    public static function addKyc($user_id, $data)
    {
        // `surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '姓',
        //  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '名',
        //  `id_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '证件号',
        //  `front` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '证件照正面照片',
        //  `back` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '证件照背面照片',
        //  `handheld` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '手持证件照',
        //  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0审核中 1已认证 2认证失败',
        $map = [
            'user_id' => $user_id
        ];
        $kyc = UserKycModel::where($map)->find();
        if (!empty($kyc)) {
            $kyc['surname'] = $data['surname'];
            $kyc['name'] = $data['name'];
            $kyc['id_number'] = $data['id_number'];
            $kyc['front'] = $data['front'];
            $kyc['back'] = $data['back'];
            $kyc['status'] = self::KYC_STATUS_WAIT;
            $kyc['handheld'] = $data['handheld'];
            $kyc['created_at'] = date('Y-m-d H:i:s');
            $kyc['created_at'] = date('Y-m-d H:i:s');
            $kyc->save();
            UserModel::where('id', $user_id)->update(['kyc_status' => self::KYC_STATUS_WAIT]);
            return $kyc->id;
        }
        $in_add = [
            'user_id' => $user_id,
            'surname' => $data['surname'],
            'name' => $data['name'],
            'id_number' => $data['id_number'],
            'front' => $data['front'],
            'back' => $data['back'],
            'handheld' => $data['handheld'],
            'status' => self::KYC_STATUS_WAIT,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        UserModel::where('id', $user_id)->update(['kyc_status' => self::KYC_STATUS_WAIT]);
        return UserKycModel::insertGetId($in_add);
    }

    /**
     * 获取KYC认证信息
     * @param $user_id
     * @return array
     */
    public static function getKyc($user_id)
    {
        $data = UserKycModel::where('user_id', $user_id)->find();
        if (empty($data)) {
            return [];
        }
        return $data->toArray();
    }

    /**
     * 审核认证KYC
     * @param $user_id
     * @param $status 1审核中 2已认证 3认证失败
     * @return array
     */
    public static function checkKyc($user_id, $status)
    {
        $kyc = UserKycModel::where('user_id', $user_id)->find();
        if (empty($kyc)) {
            return [
                'code' => 1,
                'msg' => '认证不存在'
            ];
        }
        if (in_array($status, [self::KYC_STATUS_SUCCESS, self::KYC_STATUS_FAIL])) {
            $kyc->status = $status;
            $kyc->updated_at = date('Y-m-d H:i:s');
            $kyc->save();
            UserModel::where('id', $user_id)->update(['kyc_status' => $status]);
            if ($status == self::KYC_STATUS_SUCCESS) {
                //升级
                $level = UserLevelModel::where('status', 1)->where('kyc', 1)->value('number');
                UserServices::levelUp($user_id, $level);
            }
        } else {
            return [
                'code' => 1,
                'msg' => '认证状态错误'
            ];
        }
        return [
            'code' => 0,
            'data' => $kyc->toArray()
        ];
    }
}