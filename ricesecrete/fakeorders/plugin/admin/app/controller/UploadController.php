<?php

namespace plugin\admin\app\controller;

use app\common\upload\S3;
use Exception;
use plugin\admin\app\model\Upload;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;

/**
 * 附件管理
 */
class UploadController extends Crud
{
    /**
     * 不需要权限的方法
     *
     * @var string[]
     */
    protected $noNeedAuth = ['presigned', 'file', 'avatar'];
    /**
     * @var Upload
     */
    protected $model = null;

    /**
     * 只返回当前管理员数据
     * @var string
     */
    protected $dataLimit = 'personal';

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Upload;
    }

    /**
     * 浏览
     * @return Response
     * @throws Throwable
     */
    public function index(): Response
    {
        return raw_view('upload/index');
    }

    /**
     * 浏览附件
     * @return Response
     * @throws Throwable
     */
    public function attachment(): Response
    {
        return raw_view('upload/attachment');
    }

    /**
     * 查询附件
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        if (!empty($where['ext']) && is_string($where['ext'])) {
            $where['ext'] = ['in', explode(',', $where['ext'])];
        }
        if (!empty($where['name']) && is_string($where['name'])) {
            $where['name'] = ['like', "%{$where['name']}%"];
        }
        $query = $this->doSelect($where, $field, $order);
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 更新附件
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'GET') {
            return raw_view('upload/update');
        }
        return parent::update($request);
    }

    /**
     * 添加附件
     * @param Request $request
     * @return Response
     * @throws Exception|Throwable
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'GET') {
            return raw_view('upload/insert');
        }
        $data_temp = S3::uploadFile($request);
        if ($data_temp['code'] != 0) {
            return $this->json(1, '上传失败');
        }
        $data = $data_temp['data'];
        $upload = new Upload;
        $upload->admin_id = admin_id();
        $upload->name = $data['name'];
        [
            $upload->url,
            $upload->name,
            $_,
            $upload->file_size,
            $upload->mime_type,
            $upload->image_width,
            $upload->image_height,
            $upload->ext
        ] = array_values($data);
        $upload->category = $request->post('category');
        $upload->save();
        return $this->json(0, '上传成功', [
            'url' => $data['url'],
            'name' => $data['name'],
            'size' => $data['size'],
        ]);
    }

    /**
     * 获取上传签名
     * @param Request $request
     * @return Response
     */
    public function presigned(Request $request)
    {
        $data = S3::getPresignedUrl($request);
        if ($data['code'] == 0) {
            return $this->json(0, '上传成功', $data['data']);
        }
        return $this->json(1);
    }

    /**
     * 上传文件
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function file(Request $request): Response
    {
        $data = S3::uploadFile($request);
        if ($data['code'] != 0) {
            return $this->json(1, '上传失败');
        }
        return $this->json(0, '上传成功', $data['data']);
    }

    /**
     * 上传头像
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function avatar(Request $request): Response
    {
        $file = current($request->file());
        if ($file && $file->isValid()) {
            $ext = strtolower($file->getUploadExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) {
                return json(['code' => 2, 'msg' => '仅支持 jpg jpeg gif png格式']);
            }
//            $image = Image::make($file);
//            $width = $image->width();
//            $height = $image->height();
//            $size = min($width, $height);
//            $relative_path = 'upload/avatar/' . date('Ym');
//            $real_path = base_path() . "/plugin/admin/public/$relative_path";
//            if (!is_dir($real_path)) {
//                mkdir($real_path, 0777, true);
//            }
//            $name = bin2hex(pack('Nn', time(), random_int(1, 65535)));
//            $ext = $file->getUploadExtension();
//
//            $image->crop($size, $size)->resize(300, 300);
//            $path = base_path() . "/plugin/admin/public/$relative_path/$name.lg.$ext";
//            $image->save($path);
//
//            $image->resize(120, 120);
//            $path = base_path() . "/plugin/admin/public/$relative_path/$name.md.$ext";
//            $image->save($path);
//
//            $image->resize(60, 60);
//            $path = base_path() . "/plugin/admin/public/$relative_path/$name.$ext";
//            $image->save($path);
//
//            $image->resize(30, 30);
//            $path = base_path() . "/plugin/admin/public/$relative_path/$name.sm.$ext";
//            $image->save($path);
            $data = S3::uploadFile($request);
            return json($data);
        }
        return json(['code' => 1, 'msg' => 'file not found']);
    }

    /**
     * 删除附件
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function delete(Request $request): Response
    {
        $ids = $this->deleteInput($request);
        $primary_key = $this->model->getPk();
        $files = $this->model->whereIn($primary_key, $ids)->select()->toArray();
        $file_list = array_map(function ($item) {
            $path = $item['url'];
            if (preg_match("#^/app/admin#", $path)) {
                $admin_public_path = config('plugin.admin.app.public_path') ?: base_path() . "/plugin/admin/public";
                return $admin_public_path . str_replace("/app/admin", "", $item['url']);
            }
            return null;
        }, $files);
        $file_list = array_filter($file_list, function ($item) {
            return !empty($item);
        });
        $result = parent::delete($request);
        if (($res = json_decode($result->rawBody())) && $res->code === 0) {
            foreach ($file_list as $file) {
                @unlink($file);
            }
        }
        return $result;
    }
}
