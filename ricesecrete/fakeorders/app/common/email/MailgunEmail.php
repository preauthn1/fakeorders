<?php


namespace app\common\email;


use app\services\SysServices;
use support\Cache;
use Mailgun\Mailgun;

class MailgunEmail
{
    /**
     * 发送邮件
     * @param $to
     * @param string $code
     * @return array
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public static function sendEmail($to, $code = '')
    {
        $to = trim($to);
        $redis_key = 'mailgun_email_' . md5($to);
        $redis = Cache::store('redis');
        if ($redis->get($redis_key)) {
            return [
                'code' => 1,
                'msg' => '请勿频繁发送'
            ];
        }
        if (!self::validEmail($to)) {
            return [
                'code' => 1,
                'msg' => '邮箱格式有误'
            ];
        }
        $mg = Mailgun::create('REDACTED'); // For EU servers
        $ret = $mg->messages()->send('email.hkxahxcoin.com', [
            'from' => 'HKXA@hkxahxcoin.com',
            'to' => $to,
            'subject' => 'HKXA Verification',
            'text' => "[HKXA] Your Code is: {$code}"
        ]);
//        $message = $ret->getMessage();
        $re_code = $ret->getStatusCode();
        if ($re_code == 200) {
            $time = SysServices::getSysConfigValue('web', 'reg_code_time', 180);
            $redis->set($redis_key, $code, $time);
            return [
                'code' => 0,
                'msg' => 'success'
            ];
        }
        return [
            'code' => 1,
            'msg' => $ret->getMessage()
        ];
    }

    /**
     * 验证邮箱验证码
     * @param $to
     * @param string $code
     * @return bool|string
     */
    public static function verifyEmail($to, $code = '')
    {
        $to = trim($to);
        $redis_key = 'mailgun_email_' . md5($to);
        $redis = Cache::store('redis');
        if (!$redis->get($redis_key)) {
            return false;
        }
        if ($redis->get($redis_key) != $code) {
            return false;
        }
        $redis->delete($redis_key);
        $code = rand(100000, 999999);
        $time = SysServices::getSysConfigValue('web', 'reg_code_time', 180);
        $redis->set($redis_key, $code, $time);
        return $code;
    }

    /**
     * 验证邮箱格式
     * @param string $email
     * @return boolean
     */
    public static function validEmail($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } else if (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } else if (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
                // character not valid in local part unless
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
                    $isValid = false;
                }
            }
            if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                // domain not found in DNS
                //$isValid = false;
            }
        }
        return $isValid;
    }
}
