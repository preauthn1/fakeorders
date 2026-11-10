<?php

namespace app\middleware;

use app\common\ExceptionCode;
use app\exception\MyBusinessException;
use app\services\UserServices;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 中间件-Token检测
 * Class TokenMiddleware
 * @package app\middleware
 */
class TokenMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $accessToken = $request->header('Authorization');
        if (empty($accessToken)) {
            $accessToken = $request->get('token');
        }
        if ($accessToken && !empty($accessToken)) {
            $out_data = UserServices::checkToken($accessToken);
            if ($out_data['code'] == 0) {
//                print_r($out_data['data']);
                $request->user_id = $out_data['data'];
                return $handler($request);
            } else {
                throw  new MyBusinessException($out_data['msg'], ExceptionCode::TOKEN_ERROR);
            }
        } else {
            throw  new MyBusinessException('请登录', ExceptionCode::TOKEN_ERROR);
        }
    }
}
