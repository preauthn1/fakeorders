<?php

namespace app\controller\v1;

use app\common\BaseController;
use app\services\VideoTaskServices;
use support\Request;

class VideoController extends BaseController
{

    /**
     * 获取视频列表
     * @param Request $request
     * @return \support\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_video_list(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $lang = locale();
        $out_data = VideoTaskServices::getVideoList($this->user_id, $page, $limit, $lang);
        return self::returnMsg($out_data);
    }

    /**
     * 获取视频信息
     * @param Request $request
     * @return \support\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_video(Request $request)
    {
        $video_id = $request->input('video_id', '');
        $out_data = VideoTaskServices::getVideo($this->user_id, $video_id);
        return self::returnMsg($out_data);
    }

    /**
     * 添加视频任务
     * @param Request $request
     * @return \support\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add_task(Request $request)
    {
        $video_id = $request->input('video_id', '');
        $out_data = VideoTaskServices::addTask($this->user_id, $video_id);
        return self::returnMsg($out_data);
    }

    /**
     * 更新任务图片
     * @param Request $request
     * @return \support\Response
     */
    public function update_img(Request $request)
    {
        $task_id = $request->input('task_id', '');
        $img = $request->input('img', '');
        $out_data = VideoTaskServices::updateImg($task_id, $img);
        return self::returnMsg($out_data);
    }

    /**
     * 获取任务列表
     * @param Request $request
     * @return \support\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_task_list(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $type = $request->input('type', 1);
        $out_data = VideoTaskServices::getTaskList($this->user_id, $type, $page, $limit);
        return self::returnMsg($out_data);
    }

}
