<?php


namespace app\common;


class BaseController
{
    //用户id
    protected $user_id = 0;

    public function __construct()
    {
        $lang = request()->input('lang', '');
        if (empty($lang)) {
            //Accept-Language
            $lang_str = request()->header('Accept-Language');
            if (!empty($lang_str)) {
                $lang_arr = explode(',', $lang_str);
                $lang = isset($lang_arr[0]) ? $lang_arr[0] : 'en';
            } else {
                $lang = 'en';
            }
        }
        locale($lang);
        $this->user_id = request()->user_id;
    }

    /**
     * 返回格式化数据
     * @param string $code
     * @param array $data
     * @param string $msg
     */
    public static function returnMsg($code = 0, $data = [], $msg = '')
    {
        if (is_array($code)) {
            $obj = $code;
            $code = isset($obj['code']) ? $obj['code'] : 500;
            $data = isset($obj['data']) ? $obj['data'] : [];
            $msg = isset($obj['msg']) ? $obj['msg'] : '';
        }
        $return_data = [
            'code' => '500',
            'msg' => 'undefined message',
            'data' => $code == 0 ? $data : [],
            'timestamp' => time()
        ];

        if (isset($code)) {
            $return_data['code'] = $code;
            if (!empty($msg)) {
                $return_data['msg'] = $msg;
            } else if (isset(ExceptionCode::$code[$code])) {
                $return_data['msg'] = ExceptionCode::$code[$code];
            }
            if (empty($data)) {
                unset($return_data['data']);
            }
        }
        if (isset($return_data['msg'])) {
            $return_data['msg'] = lang($return_data['msg']);
        }
        return json($return_data);
    }
}