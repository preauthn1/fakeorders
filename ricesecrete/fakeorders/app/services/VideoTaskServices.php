<?php


namespace app\services;


use app\common\finance\HSI;
use app\common\Util;
use app\model\pledge\PledgeCurrentLogModel;
use app\model\pledge\PledgeCurrentModel;
use app\model\pledge\PledgeProfitModel;
use app\model\video\VideoModel;
use app\model\video\VideoTaskModel;
use think\facade\Db;

class VideoTaskServices
{
    //0未开始 1进行中 2审核中 3审核失败 4已完成
    const STATUS_ING = 1;//进行中
    const STATUS_AUDIT = 2;//审核中
    const STATUS_FAIL = 3;//审核失败
    const STATUS_SUCCESS = 4;//已完成

    /**
     * 添加任务
     * @param $user_id
     * @param $video_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function addTask($user_id, $video_id)
    {
        $map = [
            'user_id' => $user_id,
            'video_id' => $video_id,
        ];
        $task = VideoTaskModel::where($map)->find();
        if ($task) {
            return [
                'code' => 1,
                'msg' => '任务已存在'
            ];
        }
        $video_info = VideoModel::where('id', $video_id)->where('status', 1)->find();
        if (empty($video_info)) {
            return [
                'code' => 1,
                'msg' => '视频不存在'
            ];
        }
        //`id` int NOT NULL AUTO_INCREMENT COMMENT '主键',
        //  `video_id` int DEFAULT '0' COMMENT '视频id',
        //  `user_id` int DEFAULT '0' COMMENT '用户id',
        //  `profit` decimal(20,8) DEFAULT '0.00000000' COMMENT '任务金额',
        //  `img` text COLLATE utf8mb4_general_ci COMMENT '任务图片',
        //  `status` tinyint(1) DEFAULT '1' COMMENT '任务状态 0未开始 1进行中 2审核中 3审核失败 4已完成',
        //  `created_at` datetime DEFAULT NULL COMMENT '创建时间',
        //  `updated_at` datetime DEFAULT NULL COMMENT '更新时间',
        $in_data = [
            'user_id' => $user_id,
            'video_id' => $video_id,
            'profit' => $video_info['profit'],
            'status' => self::STATUS_ING,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $id = VideoTaskModel::insertGetId($in_data);
        if ($id) {
            return [
                'code' => 0,
                'msg' => '添加成功',
                'data' => $id
            ];
        }
        return [
            'code' => 1,
            'msg' => '添加失败'
        ];
    }

    /**
     * 判断任务是否存在
     * @param $user_id
     * @param $video_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function taskStatus($user_id, $video_id)
    {
        $map = [
            'user_id' => $user_id,
            'video_id' => $video_id,
        ];
        $task = VideoTaskModel::where($map)->find();
        if (empty($task)) {
            return 0;
        }
        return $task['status'];
    }

    /**
     * 更新任务图片
     * @param $task_id
     * @param $img
     * @return array
     */
    public static function updateImg($task_id, $img)
    {
        $map = [
            'id' => $task_id,
        ];
        $task = VideoTaskModel::where($map)->find();
        if (empty($task)) {
            return [
                'code' => 1,
                'msg' => '任务不存在'
            ];
        }
        //0未开始 1进行中 2审核中 3审核失败 4已完成
        if ($task['status'] == self::STATUS_ING || $task['status'] == self::STATUS_FAIL) {
            $ret = VideoTaskModel::where($map)->update([
                'img' => json_encode($img),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            if ($ret) {
                self::updateStatus($task_id, self::STATUS_AUDIT);
                return [
                    'code' => 0,
                    'msg' => '更新成功'
                ];
            } else {
                return [
                    'code' => 1,
                    'msg' => '更新失败'
                ];
            }
        }
        return [
            'code' => 1,
            'msg' => '更新失败'
        ];
    }

    /**
     * 更新任务状态
     * @param $task_id
     * @param $status
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function updateStatus($task_id, $status)
    {
        $map = [
            'id' => $task_id,
        ];
        $task_info = VideoTaskModel::where($map)->find();
        $ret = false;
        $user_id = 0;
        $amount = 0;
        if (!empty($task_info)) {
            $user_id = $task_info['user_id'];
            $amount = $task_info['profit'];
            $task_info['status'] = $status;
            $task_info['updated_at'] = date('Y-m-d H:i:s');
            $ret = $task_info->save();
        }
        if ($ret) {
            if ($status == self::STATUS_SUCCESS) {
                //审核成功 添加收益
                $title = '视频任务';
                $remarks = '视频任务奖励';
                $type = UserBillServices::TYPE_USER;
                $detail_type = 19;
                $data_bill = UserBillServices::addUserBill($user_id, $amount, UserBillServices::CHANGE_ADD, $title, $remarks, $type, $detail_type);
                if ($data_bill['code'] == 0) {
                    return true;
                }
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 获取任务列表
     * @param $user_id
     * @param $type
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getTaskList($user_id, $type, $page, $limit)
    {
        $map = [
            't.user_id' => $user_id,
        ];
        if ($type == 1) {
            $list = VideoTaskModel::alias('t')->leftJoin('ex_video', 't.video_id=ex_video.id')
                ->where($map)->field('t.id,t.video_id,t.profit,t.img,t.status,t.created_at,ex_video.title')
                ->whereIn('t.status', [self::STATUS_ING, self::STATUS_AUDIT, self::STATUS_FAIL])->page($page, $limit)->select();
        } else {
            $list = VideoTaskModel::alias('t')->leftJoin('ex_video', 't.video_id=ex_video.id')
                ->where($map)->field('t.id,t.video_id,t.profit,t.img,t.status,t.created_at,ex_video.title')
                ->whereIn('t.status', [self::STATUS_SUCCESS])->page($page, $limit)->select();
        }
        $list = $list->toArray();
        return [
            'code' => 0,
            'msg' => '获取成功',
            'data' => $list
        ];
    }

    /**
     * 获取视频列表
     * @param $user_id
     * @param $page
     * @param $limit
     * @param $lang
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getVideoList($user_id, $page, $limit, $lang = 'en')
    {
        $map = [
            'status' => 1,
            'lang' => $lang
        ];
        $list = VideoModel::where($map)->field('id,title,src,profit,status,lang,created_at,updated_at')->page($page, $limit)->select();
        $list = $list->toArray();
        foreach ($list as &$item) {
            $item['task_status'] = self::taskStatus($user_id, $item['id']);
        }
        return [
            'code' => 0,
            'msg' => '获取成功',
            'data' => $list
        ];
    }

    /**
     * 获取视频详情
     * @param $user_id
     * @param $video_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getVideo($user_id, $video_id)
    {
        $map = [
            'id' => $video_id,
            'status' => 1,
        ];
        $video = VideoModel::where($map)->find();
        if (empty($video)) {
            return [
                'code' => 1,
                'msg' => '视频不存在'
            ];
        }
        $task_status = self::taskStatus($user_id, $video_id);
        $video['task_status'] = $task_status;
        return [
            'code' => 0,
            'msg' => '获取成功',
            'data' => $video
        ];
    }
}