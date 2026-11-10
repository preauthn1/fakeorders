<?php

namespace app\common;

class Util
{
    /**
     * 密码哈希
     * @param $password
     * @param string $algo
     * @return false|string|null
     */
    public static function passwordHash($password, string $algo = PASSWORD_DEFAULT)
    {
        return password_hash($password, $algo);
    }

    /**
     * 验证密码哈希
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function passwordVerify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * 获取随机字符串
     * @param $len
     * @param null $chars
     * @return string
     */
    public static function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }
        $charactersLength = strlen($chars);
        $randomString = '';
        for ($i = 0; $i < $len; $i++) {
            $randomString .= $chars[mt_rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * 获取真实IP
     * @return mixed|string
     */
    public static function getRealIp()
    {
        $request = request();
        $ip = $request->getRealIp();
        return $ip ?? '';
    }

    /**
     * 金额格式化
     * @param $money
     * @param int $decimals
     * @param string $dec_point
     * @param string $thousands_sep
     * @return string
     */
    public static function currencyFormat($money, $decimals = 2, $dec_point = '.', $thousands_sep = ',')
    {
        $money = floatval($money);
        return number_format($money, $decimals, $dec_point, $thousands_sep);
    }

    /**
     * 获取浏览器信息
     * @return string
     */
    public static function getClientBrowserInfo()
    {
        $request = request();
        // 获取客户端浏览器信息 HTTP_USER_AGENT
        $sys = $request->header('user-agent');
        try {
            if (strpos($sys, 'iPhone') !== false) {
                preg_match('/Version\/([\d\.]+)/i', $sys, $iphone);
                $exp[0] = "iPhone";
                if ($iphone) {
                    $exp[1] = 'iOS' . $iphone[1];
                } else {
                    // 从 $sys 中 获取 16_7_11
                    if (preg_match('/CPU iPhone OS (\d+_\d+_\d+)/', $sys, $iphone)) {
                        if ($iphone) {
                            $exp[1] = 'iOS' . $iphone[1];
                        } else {
                            $exp[1] = 'iOS 未知版本';
                        }
                    } else {
                        $exp[1] = 'iOS 未知版本';
                    }
                }
            } elseif (strpos($sys, 'Android') !== false) {
                preg_match('/Android\s(\d+(?:\.\d+)?);\s([^;\s]+)/i', $sys, $android);
                if (!$android) {
                    preg_match('/Android\s([\d\.]+);\s([^;\s]+)/i', $sys, $android);
                }
                $exp[0] = "Android";
                $exp[1] = $android[1] . ' ' . $android[2];
            } elseif (stripos($sys, "Firefox/") > 0) {
                preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
                $exp[0] = "Firefox";
                $exp[1] = $b[1];  //获取火狐浏览器的版本号
            } elseif (stripos($sys, "Maxthon") > 0) {
                preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
                $exp[0] = "傲游浏览器";
                $exp[1] = $aoyou[1];
            } elseif (stripos($sys, "MSIE") > 0) {
                preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
                $exp[0] = "IE浏览";
                $exp[1] = $ie[1];  //获取IE的版本号
            } elseif (stripos($sys, "OPR") > 0) {
                preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
                $exp[0] = "Opera";
                $exp[1] = $opera[1];
            } elseif (stripos($sys, "Edge") > 0) {
                //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
                preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
                $exp[0] = "Edge";
                $exp[1] = $Edge[1];
            } elseif (stripos($sys, "Chrome") > 0) {
                preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
                $exp[0] = "Chrome";
                $exp[1] = $google[1];  //获取google chrome的版本号
            } elseif (stripos($sys, 'rv:') > 0 && stripos($sys, 'Gecko') > 0) {
                preg_match("/rv:([\d\.]+)/", $sys, $IE);
                $exp[0] = "IE";
                $exp[1] = $IE[1];
            } elseif (strpos($sys, 'MicroMessenger') !== false) {
                preg_match('/MicroMessenger\/([\d\.]+)/i', $sys, $weixn);
                $exp[0] = "微信浏览器";
                $exp[1] = $weixn[1];
            } else {
                $exp[0] = "未知浏览器";
                $exp[1] = "";
                return $exp[0];
            }
            //$exp[1] 最长20字符
            if (strlen($exp[1]) > 20) {
                $exp[1] = mb_substr($exp[1], 0, 20);
            }
            return $exp[0] . '(' . $exp[1] . ')';
        } catch (\Exception $e) {
            return '未知浏览器';
        }
    }

    /**
     * 获取编号
     * @param $user_id
     * @return string
     */
    public static function getPledgeNumber($user_id)
    {
        //0808193400015638
        $datePart = date("mdHi"); // 10位日期时间
        return sprintf('%s%04d%04d', $datePart, $user_id, rand(1000, 9999));
    }

    /**
     * 获取充值编号
     * @param $user_id
     * @return string
     */
    public static function getRechargeNumber($user_id)
    {
        //0808193400015638
        $datePart = date("mdHi"); // 10位日期时间
        return sprintf('%s%04d%04d', $datePart, $user_id, rand(1000, 9999));
    }

    /**
     * 查询
     * @param $model
     * @param array $where
     * @param string|null $field
     * @param string $order
     * @return mixed
     */
    public static function doExportSelect($model, array $where, ?string $field = null, string $order = 'desc')
    {
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                if ($value[0] === 'like' || $value[0] === 'not like') {
                    $model = $model->where($column, $value[0], "%$value[1]%");
                } elseif (in_array($value[0], ['>', '=', '<', '<>'])) {
                    $model = $model->where($column, $value[0], $value[1]);
                } elseif ($value[0] == 'in' && !empty($value[1])) {
                    $valArr = $value[1];
                    if (is_string($value[1])) {
                        $valArr = explode(",", trim($value[1]));
                    }
                    $model = $model->whereIn($column, $valArr);
                } elseif ($value[0] == 'not in' && !empty($value[1])) {
                    $valArr = $value[1];
                    if (is_string($value[1])) {
                        $valArr = explode(",", trim($value[1]));
                    }
                    $model = $model->whereNotIn($column, $valArr);
                } elseif ($value[0] == 'null') {
                    $model = $model->whereNull($column);
                } elseif ($value[0] == 'not null') {
                    $model = $model->whereNotNull($column);
                } elseif ($value[0] !== '' || $value[1] !== '') {
                    $model = $model->whereBetween($column, $value);
                }
            } else {
                $model = $model->where($column, $value);
            }
        }
        if ($field) {
            $model = $model->order($field, $order);
        }
        return $model;
    }
}
