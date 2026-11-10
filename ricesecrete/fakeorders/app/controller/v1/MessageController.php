<?php

namespace app\controller\v1;

use app\common\BaseController;
use plugin\admin\app\services\MessageServices;
use support\Request;

class MessageController extends BaseController
{
    /**
     * 获取消息列表
     * @param Request $request
     * @return \support\Response
     */
    public function get_list(Request $request)
    {
        $message_type = $request->input('message_type', 0);
        $type = $request->input('type', 0);
        $limit = $request->input('limit', 10);
        $lang = locale();
        $data = MessageServices::getMessageList($this->user_id, $message_type, $type, $limit, $lang);
        $out_data = [
            'code' => 0,
            'data' => $data
        ];
        return self::returnMsg($out_data);
    }

    /**
     * 已读消息
     * @param Request $request
     * @return \support\Response
     */
    public function read(Request $request)
    {
        $id = $request->input('id');
        $message_type = $request->input('message_type', 0);
        MessageServices::readMessage($id, $message_type, $this->user_id);
        $out_data = [
            'code' => 0
        ];
        return self::returnMsg($out_data);
    }
}
