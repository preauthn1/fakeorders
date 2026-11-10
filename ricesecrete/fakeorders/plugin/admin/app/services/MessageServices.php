<?php


namespace plugin\admin\app\services;


use app\common\push\PushApi;
use app\services\UserServices;
use plugin\admin\app\model\Content;
use plugin\admin\app\model\ContentRead;
use plugin\admin\app\model\Dict;
use plugin\admin\app\model\Message;

class MessageServices
{
    /**
     * 添加消息
     * @param $user_id
     * @param $admin_id
     * @param $title
     * @param $content
     * @param int $is_pop
     * @param int $type
     * @return bool
     */
    public static function addMessage($user_id, $admin_id, $title, $content, $is_pop = 1, $type = 0)
    {
        $in_data = [];
        if (is_array($user_id)) {
            foreach ($user_id as $id) {
                $in_data[] = [
                    'user_id' => $id,
                    'admin_id' => $admin_id,
                    'title' => $title,
                    'content' => $content,
                    'type' => $type,
                    'is_pop' => $is_pop,
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                PushApi::sendPrivateMessage($id, [
                    'title' => $title,
                    'content' => $content,
                    'type' => $type,
                    'is_pop' => $is_pop
                ]);
            }
        } else {
            $in_data[] = [
                'user_id' => $user_id,
                'admin_id' => $admin_id,
                'title' => $title,
                'content' => $content,
                'type' => $type,
                'is_pop' => $is_pop,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            PushApi::sendPrivateMessage($user_id, [
                'title' => $title,
                'content' => $content,
                'type' => $type,
                'is_pop' => $is_pop
            ]);
        }
        $messageModel = new Message();
        $messageModel->insertAll($in_data);
        return true;
    }

    /**
     * 读取消息
     * @param $id
     * @param int $message_type
     * @param int $user_id
     * @return bool|int
     */
    public static function readMessage($id, $message_type = 0, $user_id = 0)
    {
        if ($message_type == 0) {
            $contentReadModel = new ContentRead();
            $map = [
                'user_id' => $user_id,
                'content_id' => $id
            ];
            $temp = $contentReadModel->where($map)->find();
            if ($temp) {
                return true;
            }
            $contentReadModel->insert([
                'user_id' => $user_id,
                'content_id' => $id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } else {
            $messageModel = new Message();
            $ret = $messageModel->where('id', $id)->update(['is_read' => 1, 'read_at' => date('Y-m-d H:i:s')]);
            if ($ret) {
                return true;
            }
            return false;
        }
    }

    /**
     * 获取消息列表
     * @param $user_id
     * @param $type
     * @param $message_type
     * @param $limit
     * @param $lang
     * @return array
     */
    public static function getMessageList($user_id, $message_type, $type, $limit, $lang = 'en')
    {
        if ($message_type == 0) {
            $contentModel = new Content();
            $query = $contentModel
                ->where('type', 1)
                ->where('lang', $lang)
                ->order('id', 'desc');
            $paginator = $query->paginate($limit);
            $total = $paginator->total();
            $items = $paginator->items();
            $readContentIds = ContentRead::where('user_id', $user_id)->field('content_id')
                ->column('content_id');
            $unread_count = Content::where('type', 1)
                ->where('lang', $lang)
                ->whereNotIn('id', $readContentIds)
                ->count();
        } else {
            $messageModel = new Message();
            $query = $messageModel->where('user_id', $user_id)
                ->where('type', $type)
                ->order('id', 'desc');
            $paginator = $query->paginate($limit);
            $total = $paginator->total();
            $items = $paginator->items();
            $unread_count = $messageModel->where('user_id', $user_id)->where('is_read', 0)->count();
        }
        $message_item = [];
        foreach ($items as &$item) {
            $item = $item->toArray();
            $temp_item['id'] = $item['id'];
            $temp_item['title'] = $item['title'];
            $temp_item['content'] = $item['content'];
            $temp_item['message_type'] = $message_type;
            $temp_item['type'] = $item['type'];
            if ($message_type == 0) {
                $read = ContentRead::where('user_id', $user_id)->where('content_id', $item['id'])->find();
                $temp_item['is_read'] = 0;
                $temp_item['is_pop'] = 1;
                if ($read) {
                    $read = $read->toArray();
                    $temp_item['read_at'] = $read['created_at'];
                    $temp_item['is_read'] = 1;
                }
            } else {
                $temp_item['is_read'] = $item['is_read'];
                $temp_item['read_at'] = empty($item['read_at']) ? "" : $item['read_at'];
                $temp_item['is_pop'] = $item['is_pop'];
            }
            $temp_item['created_at'] = $item['created_at'];
            $temp_item['updated_at'] = $item['updated_at'];
            $message_item[] = $temp_item;
        }
        return [
            'total' => $total,
            'unread_count' => $unread_count,
            'items' => $message_item
        ];
    }

    /**
     * 获取未读消息数
     * @param $user_id
     * @return int
     */
    public static function getUnreadCount($user_id)
    {
        //wa_content
        //wa_content_read
        $readContentIds = ContentRead::where('user_id', $user_id)->field('content_id')
            ->column('content_id');

        $systemUnread = Content::where('type', 1)
            ->whereNotIn('id', $readContentIds)
            ->count();
        $personalUnread = Message::where('user_id', $user_id)
            ->where('is_read', 0)
            ->count();
        return $systemUnread + $personalUnread;
    }

    /**
     * 导出文件数据
     * @param $map
     * @param $page
     * @param int $limit
     * @return array
     */
    public static function exportFile($map, $page, $limit = 1000)
    {
        $name = '消息列表';
        $field = [
            'user_id' => '用户ID',
            'user_name' => '用户名',
            'admin_id' => '管理员ID',
            'title' => '标题',
            'content' => '内容',
            'type' => '类型',
            'is_pop' => '是否弹窗',
            'is_read' => '是否已读',
            'updated_at' => '更新时间',
        ];
        if (empty($map)) {
            $list = Message::limit(($page - 1) * $limit, $limit)->select();
        } else {
            $list = Message::where($map)->limit(($page - 1) * $limit, $limit)->select();
        }
        $data = [];
        //自定义: 0 充值到账: 1 提现到账: 2
        $messagetype = Dict::get('messagetype');
        $message_arr = [];
        foreach ($messagetype as $item) {
            $message_arr[(int)$item['value']] = $item['name'];
        }
        foreach ($list as $item) {
            //0禁用 1启用
            $data[] = [
                'user_id' => $item->user_id,
                'user_name' => UserServices::getFieldValue($item->user_id, 'username'),
                'admin_id' => $item->admin_id,
                'title' => $item->title,
                'content' => $item->content,
                'type' => $message_arr[$item->type],
                'is_pop' => $item->is_pop == 0 ? '禁用' : '启用',
                'is_read' => $item->is_read == 0 ? '未读' : '已读',
                'updated_at' => $item->updated_at,
            ];
        }
        return [
            'name' => $name,
            'field' => $field,
            'data' => $data,
        ];
    }
}