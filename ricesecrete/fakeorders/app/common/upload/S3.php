<?php

namespace app\common\upload;

use app\common\ExceptionCode;
use app\exception\MyBusinessException;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use support\Request;

/**
 * AWS S3操作类
 * Class S3
 * @package app\libs
 */
class S3
{
    /**
     * 获取预签名上传URL
     * @param string $folder
     * @param string $filename
     * @return array
     */
    public static function getPresignedUrl($folder = '', $filename = '')
    {
        try {
            $config = config('upload.aws_s3');
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $config['region'],
                'credentials' => [
                    'key' => $config['credentials_key'],
                    'secret' => $config['credentials_secret'],
                ]
            ]);

            $key = $folder . '/' . date("Y-m-d") . "/" . sha1(date('YmdHis', time()) . uniqid()) . '.' . pathinfo(
                    $filename,
                    PATHINFO_EXTENSION
                );
            $cmd = $s3Client->getCommand('PutObject', [
                'Bucket' => $config['bucket'],
                'Key' => $key,
            ]);

            $presignedUrl = $s3Client->createPresignedRequest($cmd, '+1 hour')->getUri();

            return [
                'code' => 0,
                'data' => [
                    'url' => (string)$presignedUrl,
                    'key' => $key,
                    'base_url' => $config['url']
                ]
            ];
        } catch (AwsException $e) {
            return ['code' => ExceptionCode::S3_FILE_ERROR, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 上传文件 单文件
     * @param Request $request
     * @return array
     */
    public static function uploadFile(Request $request)
    {
        $relative_dir = $request->input('folder', '/upload/files');
        $uid = $request->input('uid', '');
        $relative_dir = trim($relative_dir, '\\/');
        $relative_dir = "hkxa/" . $relative_dir . "/";
        $file = current($request->file());
        if (!$file || !$file->isValid()) {
            throw new MyBusinessException('未找到上传文件', 400);
        }
        $admin_public_path = rtrim(config('plugin.admin.app.public_path', ''), '\\/');
        $base_dir = $admin_public_path ? $admin_public_path . DIRECTORY_SEPARATOR : base_path() . '/plugin/admin/public/';
        $full_dir = $base_dir . $relative_dir;
        if (!is_dir($full_dir)) {
            mkdir($full_dir, 0777, true);
        }

        $ext = $file->getUploadExtension() ?: null;
        $mime_type = $file->getUploadMimeType();
        $file_name = $file->getUploadName();
        $file_size = $file->getSize();
        if ($file_size > 1024 * 1024 * 10) {
            throw new MyBusinessException('文件过大', 400);
        }
        if (!$ext && $file_name === 'blob') {
            [$___image, $ext] = explode('/', $mime_type);
            unset($___image);
        }

        $ext = strtolower($ext);
        $ext_forbidden_map = ['php', 'php3', 'php5', 'css', 'js', 'html', 'htm', 'asp', 'jsp'];
        if (in_array($ext, $ext_forbidden_map)) {
            throw new MyBusinessException('不支持该格式的文件上传', 400);
        }
        $fileArr = [];
        try {
            $config = config('upload.aws_s3');
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $config['region'],
                'credentials' => [
                    'key' => $config['credentials_key'],
                    'secret' => $config['credentials_secret'],
                ]
            ]);
            $ext = $file->getUploadExtension() ?: null;
            $ext = strtolower($ext);
            $fileName = $relative_dir . date("Y-m-d") . "/" . sha1(date(
                        'YmdHis',
                        time()
                    ) . uniqid()) . '.' . $ext;

            $result = $s3Client->putObject([
                'Bucket' => $config['bucket'],
                'Key' => $fileName,
                'Body' => fopen($file->getRealPath(), 'rb'),
                '@http' => [
                    'proxy' => $config['proxy']
                ]
            ]);

            if ($result['@metadata']['statusCode'] === 200) {
                $fileArr = [
                    'name' => $fileName,
                    'url' => $result['ObjectURL'],
                    'size' => $file_size,
                    'uid' => $uid,
                    'type' => $ext
                ];
            }
        } catch (AwsException $e) {
            return ['code' => ExceptionCode::S3_FILE_ERROR, 'msg' => $e->getMessage()];
        }
        return ['code' => 0, 'data' => $fileArr];
    }

    /**
     * 上传文件 多文件
     * @param array $files
     * @param string $folder
     * @param string $uid
     * @return array
     */
    public static function uploadFiles($files, $folder = '', $uid = '')
    {
        if (empty($files)) {
            $files = request()->file();
        }
        if (!is_array($files)) {
            $files = [$files];
        }
        $fileArr = [];
        if (!empty($files) && count($files) > 0) {
            try {
                $config = config('upload.aws_s3');
                $s3Client = new S3Client([
                    'version' => 'latest',
                    'region' => $config['region'],
                    'credentials' => [
                        'key' => $config['credentials_key'],
                        'secret' => $config['credentials_secret'],
                    ]
                ]);

                foreach ($files as $file) {
                    $fileName = $folder . '/' . date("Y-m-d") . "/" . sha1(date(
                                'YmdHis',
                                time()
                            ) . uniqid()) . '.' . $file->extension();

                    $result = $s3Client->putObject([
                        'Bucket' => $config['bucket'],
                        'Key' => $fileName,
                        'Body' => fopen($file->getRealPath(), 'rb'),
                        '@http' => [
                            'proxy' => $config['proxy']
                        ]
                    ]);

                    if ($result['@metadata']['statusCode'] === 200) {
                        $fileArr[] = [
                            'name' => $fileName,
                            'url' => $result['ObjectURL'],
                            'uid' => $uid,
                            'type' => $file->extension()
                        ];
                    }
                }
            } catch (AwsException $e) {
                return ['code' => ExceptionCode::S3_FILE_ERROR, 'msg' => $e->getMessage()];
            }
        } else {
            return ['code' => ExceptionCode::S3_FILE_ERROR];
        }

        return ['code' => 0, 'data' => $fileArr];
    }

    /**
     * 删除文件
     * @param string $url
     * @return array
     */
    public static function delFile($url)
    {
        if (empty($url)) {
            return ['code' => ExceptionCode::S3_FILE_ERROR];
        }

        try {
            $config = config('upload.aws_s3');
            if (strpos($url, $config['url']) !== false) {
                $urlArr = explode($config['url'] . '/', $url);
                if (isset($urlArr[1])) {
                    $s3Client = new S3Client([
                        'version' => 'latest',
                        'region' => $config['region'],
                        'credentials' => [
                            'key' => $config['credentials_key'],
                            'secret' => $config['credentials_secret'],
                        ]
                    ]);

                    $s3Client->deleteObject([
                        'Bucket' => $config['bucket'],
                        'Key' => $urlArr[1]
                    ]);

                    return ['code' => 0];
                }
            }
            return ['code' => ExceptionCode::S3_FILE_ERROR];
        } catch (AwsException $e) {
            return ['code' => ExceptionCode::S3_FILE_ERROR, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 移动/复制文件
     * @param string $source
     * @param string $destination
     * @return array
     */
    public static function moveFile($source, $destination)
    {
        if (empty($source) || empty($destination)) {
            return ['code' => ExceptionCode::S3_FILE_ERROR, 'msg' => '源文件或目标路径不能为空'];
        }

        try {
            $config = config('upload.aws_s3');
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $config['region'],
                'credentials' => [
                    'key' => $config['credentials_key'],
                    'secret' => $config['credentials_secret'],
                ]
            ]);

            $s3Client->copyObject([
                'Bucket' => $config['bucket'],
                'Key' => $destination,
                'CopySource' => "{$config['bucket']}/{$source}",
            ]);

            return [
                'code' => 0,
                'url' => $config['url'] . '/' . $destination,
                'msg' => '移动成功'
            ];
        } catch (AwsException $e) {
            return ['code' => ExceptionCode::S3_FILE_ERROR, 'msg' => $e->getMessage()];
        }
    }

    /**
     * 通过文件名获取文件URL
     * @param string $filename
     * @param bool $pre_signed 是否返回预签名URL，默认false
     * @return array
     */
    public static function getFileUrl(string $filename, bool $pre_signed = false): array
    {
        if (empty($filename)) {
            return ['code' => ExceptionCode::S3_FILE_ERROR, 'msg' => '文件名不能为空'];
        }
        try {
            $config = config('upload.aws_s3');
            if (!$pre_signed) {
                // 返回直接的URL（需要bucket配置为公开访问）
                return [
                    'code' => 0,
                    'data' => [
                        'url' => $config['url'] . '/' . $filename
                    ]
                ];
            }

            // 如果需要预签名URL
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $config['region'],
                'credentials' => [
                    'key' => $config['credentials_key'],
                    'secret' => $config['credentials_secret'],
                ]
            ]);

            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => $config['bucket'],
                'Key' => $filename
            ]);

            $request = $s3Client->createPresignedRequest($cmd, '+1 hour');
            $presignedUrl = (string)$request->getUri();

            return [
                'code' => 0,
                'data' => [
                    'url' => $presignedUrl
                ]
            ];
        } catch (AwsException $e) {
            return ['code' => ExceptionCode::S3_FILE_ERROR, 'msg' => $e->getMessage()];
        }
    }
}
