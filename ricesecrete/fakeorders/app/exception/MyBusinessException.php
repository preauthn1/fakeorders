<?php


namespace app\exception;


use app\common\BaseController;
use support\exception\BusinessException;
use Webman\Http\Request;
use Webman\Http\Response;

class MyBusinessException extends BusinessException
{
    public function render(Request $request): ?Response
    {
        $data = [
            'code' => $this->getCode(),
            'msg' => $this->getMessage()
        ];
        return BaseController::returnMsg($data);
    }
}