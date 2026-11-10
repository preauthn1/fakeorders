<?php

namespace app\controller\v1;

use app\common\BaseController;
use app\common\email\MailgunEmail;
use app\common\Locker;
use app\common\Util;
use app\model\user\UserModel;
use app\services\PromotionServices;
use app\services\RechargeRebatesServices;
use app\services\SysServices;
use app\services\UserBillServices;
use app\services\UserKycServices;
use app\services\UserServices;
use app\services\UserWalletServices;
use plugin\admin\app\model\Dict;
use plugin\admin\app\services\MessageServices;
use support\Request;

class UserController extends BaseController
{
    /**
     * 用户登录
     * @param Request $request
     * @return \support\Response
     */
    public function login(Request $request)
    {
        $login_type = $request->input('login_type', 'email');
        $mobile_code = '';
        if ($login_type == 'email') {
            $email = $request->input('email', '');
            // 判断长度
            if (strlen($email) > 254) {
                $out_data = [
                    'code' => 1,
                    'msg' => '邮箱地址过长，请更换邮箱地址',
                ];
                return self::returnMsg($out_data);
            }
            $username = $email;
        } elseif ($login_type == 'mobile') {
            $phone = $request->input('mobile', '');
            $phone = trim($phone);
            $phone = str_replace(' ', '', $phone);
            // 判断长度
            if (strlen($phone) > 20 || strlen($phone) < 7) {
                $out_data = [
                    'code' => 1,
                    'msg' => '请输入正确的手机号',
                ];
                return self::returnMsg($out_data);
            }
            $mobile_code = $request->input('mobile_code', '');
            // 判断区号是否存在
            $mobile_codes = Dict::get('mobilecode');
            $mobile_codes_arr = [];
            foreach ($mobile_codes as $item) {
                $mobile_codes_arr[] = $item['value'];
            }
            if (!in_array($mobile_code, $mobile_codes_arr)) {
                $out_data = [
                    'code' => 1,
                    'msg' => '请选择正确的区号',
                ];
                return self::returnMsg($out_data);
            }
            $username = $phone;
        } else {
            $out_data = [
                'code' => 1,
                'msg' => '请选择正确的登录方式',
            ];
            return self::returnMsg($out_data);
        }
        if (empty($username)) {
            $out_data = [
                'code' => 1,
                'msg' => '请输入账号',
            ];
            return self::returnMsg($out_data);
        }
        (new Locker('login_lock_' . md5($username)))->lock();
        $password = $request->input('password', '');
        $out_data = UserServices::login($username, $password, $mobile_code);
        return self::returnMsg($out_data);
    }

    /**
     * 用户注册
     * @param Request $request
     * @return \support\Response
     */
    public function register(Request $request)
    {
        //login_type 登录类型 mobile手机 email邮箱
        //invitation_code 邀请码
        //password 登录密码
        //password_repeat 确认密码
        //mobile 手机
        //mobile_code 手机区号
        //email 邮箱
        //verify_code 验证码
        $mobile = $request->input('mobile', '');
        $mobile_code = $request->input('mobile_code', '');
        $email = $request->input('email', '');
        $login_type_code = $request->input('login_type', 'mobile');
        if ($login_type_code == 'mobile') {
            $login_type = UserServices::MOBILE_TYPE;
            $email = '';
        } else {
            $login_type = UserServices::EMAIL_TYPE;
            $mobile = '';
            $mobile_code = '';
        }
        $pid = $request->input('pid', '');
        $promotion = PromotionServices::getPromotion($pid);
        $pix = $promotion['pix'];
        $platform = $promotion['platform'];
        $click_id = $request->input('click_id', '');
        $invitation_code = $request->input('invitation_code', '');
        $password = $request->input('password', '');
        $password_repeat = $request->input('password_repeat', '');
        $verify_code = $request->input('verify_code', '');
        $invitation = SysServices::getSysConfigValue('web', 'invitation');
        if ($invitation == 0) {
            $invitation_code = "";
        } else {
            $top_user = UserModel::where('invitation_code', $invitation_code)->find();
            if (!$top_user) {
                $out_data = [
                    'code' => 1,
                    'msg' => '邀请码错误',
                ];
                return self::returnMsg($out_data);
            }
        }
        $params = [
            'login_type' => $login_type,
            'invitation_code' => $invitation_code,
            'password' => $password,
            'password_repeat' => $password_repeat,
            'mobile' => $mobile,
            'mobile_code' => $mobile_code,
            'email' => $email,
            'verify_code' => $verify_code,
            'pix' => $pix,
            'platform' => $platform,
            'click_id' => $click_id
        ];
        $out_data = UserServices::register($params);
        return self::returnMsg($out_data);
    }

    /**
     * 发送邮箱验证码
     * @param Request $request
     * @return \support\Response
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function send_email(Request $request)
    {
        $email = $request->input('email', '');
        $smtp_email = new MailgunEmail();
        $code = rand(100000, 999999);
        $ret = $smtp_email->sendEmail($email, $code);
        return self::returnMsg($ret);
    }

    /**
     * 校验邮箱验证码 获取新验证码
     * @param Request $request
     * @return \support\Response
     */
    public function verify_email_code(Request $request)
    {
        $email = $request->input('email', '');
        $code = $request->input('code', '');
        $new_code = MailgunEmail::verifyEmail($email, $code);
        if ($new_code == false) {
            return self::returnMsg([
                'code' => 1,
                'msg' => '验证码错误'
            ]);
        }
        return self::returnMsg([
            'code' => 0,
            'msg' => 'success',
            'data' => $new_code
        ]);
    }

    /**
     * 重置密码
     * @param Request $request
     * @return \support\Response
     */
    public function reset_password(Request $request)
    {
        $login_type = $request->input('login_type', 'email');
        $mobile_code = '';
        if ($login_type == 'email') {
            $email = $request->input('email', '');
            $username = $email;
        } elseif ($login_type == 'mobile') {
            $mobile = $request->input('mobile', '');
            $mobile_code = $request->input('mobile_code', '');
            $username = $mobile;
        } else {
            $out_data = [
                'code' => 1,
                'msg' => '请选择正确的登录方式',
            ];
            return self::returnMsg($out_data);
        }
        $password = $request->input('password', '');
        $password_repeat = $request->input('password_repeat', '');
        $verify_code = $request->input('verify_code', '');
        if ($password != $password_repeat) {
            $out_data = [
                'code' => 1,
                'msg' => '密码不一致'
            ];
            return self::returnMsg($out_data);
        }
        $out_data = UserServices::resetPassword($username, $mobile_code, $password, $verify_code);
        return self::returnMsg($out_data);
    }

    /**
     * 设置语言
     * @param Request $request
     * @return \support\Response
     */
    public function set_lang(Request $request)
    {
        $lang = $request->input('lang', '');
        $out_data = UserServices::setLang($this->user_id, $lang);
        return self::returnMsg($out_data);
    }

    /**
     * 获取用户信息
     * @return \support\Response
     */
    public function info()
    {
        $user_info = UserServices::getUserInfo($this->user_id, false);
        $user_info['unread_count'] = MessageServices::getUnreadCount($this->user_id);
        $user_info['user_money'] = Util::currencyFormat($user_info['user_money']);
        $out_data = [
            'code' => 0,
            'data' => $user_info
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 获取KYC信息
     * @return \support\Response
     */
    public function get_kyc()
    {
        $user_id = $this->user_id;
        $data = UserKycServices::getKyc($user_id);
        $out_data = [
            'code' => 0,
            'data' => $data
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 上传KYC凭证
     * @param Request $request
     * @return \support\Response
     */
    public function upload_kyc(Request $request)
    {
        // `surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '姓',
        //  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '名',
        //  `id_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '证件号',
        //  `front` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '证件照正面照片',
        //  `back` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '证件照背面照片',
        //  `handheld` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '' COMMENT '手持证件照',
        $surname = $request->input('surname', '');
        $name = $request->input('name', '');
        $id_number = $request->input('id_number', '');
        $front = $request->input('front', '');
        $back = $request->input('back', '');
        $handheld = $request->input('handheld', '');
        $data = [
            'surname' => $surname,
            'name' => $name,
            'id_number' => $id_number,
            'front' => $front,
            'back' => $back,
            'handheld' => $handheld
        ];
        $kyc = UserKycServices::getKyc($this->user_id);
        if (!empty($kyc)) {
            if ($kyc['status'] != UserKycServices::KYC_STATUS_FAIL) {
                $out_data = [
                    'code' => 1,
                    'msg' => '请勿重复提交'
                ];
                return self::returnMsg($out_data);
            }
        }
        $id = UserKycServices::addKyc($this->user_id, $data);
        $out_data = [
            'code' => 0,
            'data' => [
                'id' => $id
            ]
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 绑定钱包
     * @param Request $request
     * @return \support\Response
     */
    public function bind_wallet(Request $request)
    {
        $type = $request->input('type', '');
        $address = $request->input('address', '');
        if (empty($type) || empty($address)) {
            $out_data = [
                'code' => 1,
                'msg' => '参数错误'
            ];
            return self::returnMsg($out_data);
        }
        $user_id = $this->user_id;
        $wallet = UserWalletServices::addWallet($user_id, $type, $address);
        return self::returnMsg($wallet);
    }

    /**
     * 获取用户钱包列表
     * @param Request $request
     * @return \support\Response
     */
    public function wallet_list(Request $request)
    {
        $type = $request->input('type');
        $user_id = $this->user_id;
        $wallet = UserWalletServices::getWallet($user_id, $type);
        $out_data = [
            'code' => 0,
            'data' => $wallet
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 账单列表
     * @param Request $request
     * @return \support\Response
     * @throws \Exception
     */
    public function bill_list(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $change = $request->input('change', 1);//1增加 2减少
        $start_time = $request->input('start_time', '');//开始日期
        $end_time = $request->input('end_time', '');//结束日期
        $where = [
            'change' => $change,
            'user_id' => $this->user_id
        ];
        $field = 'created_at';
        $order = 'DESC';
        $UserBillServices = new UserBillServices();
        if (empty($start_time) || empty($end_time)) {
            $where['created_at'][0] = strtotime('-7 days');
            $where['created_at'][1] = time();
        } else {
            $where['created_at'][0] = strtotime(date('Y-m-d 00:00:00', strtotime($start_time)));
            $where['created_at'][1] = strtotime(date('Y-m-d 23:59:59', strtotime($end_time)));
        }
        if ($where['created_at'][1] - $where['created_at'][0] > (3600 * 24 * 60)) {
            $out_data = [
                'code' => 1,
                'msg' => '时间范围不能超过60天'
            ];
            return self::returnMsg($out_data);
        }
        [$items, $total] = $UserBillServices->getBillList($where, $field, $order, $page, $limit);
        $item_list = [];
        foreach ($items as &$item) {
            $item['money'] = Util::currencyFormat($item['money']);
            $item['created_at'] = date('Y-m-d H:i:s', $item['created_at']);
//            $item['title'] = lang($item['title']);
            $item_list[] = $item;
        }
        $out_data = [
            'code' => 0,
            'data' => [
                'items' => $item_list,
                'total' => $total
            ]
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 获取团队充值返利详情
     * @param Request $request
     * @return \support\Response
     */
    public function rebates_team(Request $request)
    {
        $user_id = $request->user_id;
        $data = RechargeRebatesServices::getTopRechargeRebates($user_id);
        $out_data = [
            'code' => 0,
            'data' => $data
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 获取团队充值返利列表
     * @param Request $request
     * @return \support\Response
     */
    public function rebates_team_list(Request $request)
    {
        $user_id = $request->user_id;
        $limit = $request->input('limit', 10);
        $list = RechargeRebatesServices::getRebatesTeam($user_id, $limit);
        $out_data = [
            'code' => 0,
            'data' => [
                'items' => $list['items'],
                'total' => $list['total']
            ]
        ];
        return self::returnMsg($out_data);
    }
}
