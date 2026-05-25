<?php

namespace app\manage\controller;

use app\manage\controller\Base;
use app\common\validate\manage\Upload as UploadValidate;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\Filesystem;
use think\Image;
use think\exception\FileException;

class Upload extends Base
{
    // -------------------------------------------------------------------------
    // 上传配置常量定义
    // -------------------------------------------------------------------------
    const MAX_IMAGE_SIZE = 5 * 1024 * 1024;      // 5MB
    const MAX_FILE_SIZE  = 50 * 1024 * 1024;     // 50MB
    const ALLOW_IMAGE_EXT = 'jpg,png,jpeg,gif';  // 允许的图片格式
    const ALLOW_FILE_EXT  = 'zip,rar,7z,pdf,doc,docx,xls,xlsx,exe,mp3,mp4,m4v,jpg,png,jpeg,gif'; // 允许的文件格式

    /**
     * 使用存储模块上传文件（带降级处理）
     * @param mixed $file 文件对象
     * @param string $path 存储路径
     * @param string|null $filename 文件名
     * @return array ['success' => bool, 'url' => string|null, 'path' => string|null, 'file_id' => int|null, 'error' => string|null]
     */
    protected function uploadToStorage($file, string $path = '', string $filename = null): array
    {
        // 尝试使用存储模块
        if (module_installed('storage')) {
            try {
                $service = module_service('storage', 'StorageService', null, true);
                if ($service) {
                    $result = $service->upload($file, $path, $filename);
                    if ($result['success']) {
                        return $result;
                    }
                    // 上传失败，记录错误并降级到本地存储
                    Log::warning('存储模块上传失败: ' . ($result['error'] ?? '未知错误') . '，降级到本地存储');
                }
            } catch (\Exception $e) {
                Log::error('存储模块上传异常: ' . $e->getMessage() . '，降级到本地存储');
            }
        }

        // 降级到本地存储
        return $this->uploadToLocal($file, $path);
    }

    /**
     * 本地存储（降级方案）
     * @param mixed $file 文件对象
     * @param string $path 存储路径
     * @return array ['success' => bool, 'url' => string|null, 'path' => string|null, 'file_id' => int|null, 'error' => string|null]
     */
    protected function uploadToLocal($file, string $path = ''): array
    {
        try {
            $savePath = $path ? rtrim($path, '/') : 'uploads';
            $savename = Filesystem::disk('public')->putFile($savePath, $file, 'md5');
            $fullPath = 'storage/' . str_replace('\\', '/', $savename);

            return [
                'success' => true,
                'url' => '/' . $fullPath,
                'path' => '/' . $fullPath,
                'error' => null
            ];
        } catch (\Exception $e) {
            Log::error('本地存储上传失败: ' . $e->getMessage());
            return [
                'success' => false,
                'url' => null,
                'path' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 删除文件（支持存储模块和本地存储）
     * @param int $fileId 文件ID（用于存储模块）
     * @param string $localPath 本地文件路径
     * @return array ['success' => bool, 'error' => string|null]
     */
    protected function deleteFile(?string $path = null): array
    {
        // 尝试使用存储模块删除（支持fileId或URL）
        if (module_installed('storage') && $path) {
            try {
                // StorageService会自动获取默认配置
                $service = module_service('storage', 'StorageService', null, true);
                if ($service) {
                    $result = $service->delete($path);
                    if ($result['success']) {
                        return $result;
                    }
                    Log::warning('存储模块删除失败: ' . ($result['error'] ?? '未知错误'));
                }
            } catch (\Exception $e) {
                Log::error('存储模块删除异常: ' . $e->getMessage());
            }
        }

        return ['success' => false, 'error' => '存储模块删除失败'];
    }

    // -------------------------------------------------------------------------
    // 统一图片上传方法
    // -------------------------------------------------------------------------
    public function UploadImg()
    {
        // 接收文件对象
        $file = Request::file('file');

        // 使用统一验证器
        $validate = new UploadValidate();
        if (! $validate->scene('image')->check(['image' => $file])) {
            return c_error($validate->getError());
        }

        try {
            // 批量获取配置参数（减少数据库查询）
            $configs = Db::name('conf')
                ->whereIn('ename', ['water', 'waterimg', 'water_position', 'water_opacity'])
                ->column('value', 'ename');

            // 使用存储模块上传（自动降级到本地存储）
            $result = $this->uploadToStorage($file, 'images');

            if (!$result['success']) {
                Log::error('图片上传失败：' . $result['error']);
                return json([
                    'code' => 500,
                    'msg'  => lang('upload_server_error')
                ]);
            }

            // 水印处理（仅本地存储时）
            // 检查模块是否安装
            if (!module_installed('storage')) {
                Log::info('存储模块未安装，使用本地存储');
                return null;
            }

            $service = module_service('storage', 'StorageService', null, true);
            if (!$service || !$service->getDriver()) {
                Log::warning('存储服务不可用，使用本地存储');
                return null;
            }

            if (!$service) {
                // 使用本地存储，处理水印
                $fullPath = app()->getRootPath() . 'public/' . ltrim($result['path'], '/');
                $this->processWatermark($fullPath, $configs);
            }

            // 返回标准化路径
            return json([
                'code' => 200,
                'msg'  => lang('upload_success'),
                'path' => $result['path'],
                'url' => $result['url'],
                'file_id' => $result['file_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('图片上传失败：' . $e->getMessage());
            return json([
                'code' => 500,
                'msg'  => lang('upload_server_error')
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 删除图片方法
    // -------------------------------------------------------------------------
    public function DeleteImg()
    {
        try {
            $path = Request::post('path');

            // 参数验证
            if (empty($path)) {
                return json([
                    'code' => 400,
                    'msg'  => lang('invalid_parameters')
                ]);
            }

            // 尝试通过存储模块删除（支持fileId或URL）
            if (module_installed('storage')) {
                $result = $this->deleteFile($path);
                if ($result['success']) {
                    return json([
                        'code' => 200,
                        'msg'  => lang('delete_success')
                    ]);
                }
                // 存储模块删除失败，尝试本地删除
                Log::warning('存储模块删除失败: ' . $result['error']);
            }

            // 降级到本地删除（path）
            if (!$this->validatePath($path)) {
                return json([
                    'code' => 400,
                    'msg'  => lang('invalid_parameters')
                ]);
            }

            // 构建完整路径
            $fullPath = $this->getFullPath($path);

            // 安全检查（仅本地路径）
            if (!$this->isSafeUploadPath($fullPath)) {
                return json([
                    'code' => 403,
                    'msg'  => lang('operation_forbidden')
                ]);
            }

            // 文件存在性检查
            if (!file_exists($fullPath)) {
                return json([
                    'code' => 404,
                    'msg'  => lang('file_not_exists')
                ]);
            }

            // 执行删除
            if (@unlink($fullPath)) {
                return json([
                    'code' => 200,
                    'msg'  => lang('delete_success')
                ]);
            }

            return json([
                'code' => 500,
                'msg'  => lang('delete_failed')
            ]);
        } catch (\Exception $e) {
            Log::error('图片删除失败: ' . $e->getMessage());
            return json([
                'code' => 500,
                'msg'  => lang('server_error')
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 文件上传方法
    // -------------------------------------------------------------------------
    public function uploadFile()
    {
        try {
            // 文件存在性检查
            $file = Request::file('file');
            if (!$file) {
                return $this->getUploadLimitError();
            }

            // 文件大小验证
            if (!$this->validateFileSize($file->getSize(), self::MAX_FILE_SIZE)) {
                return json([
                    'code' => 500,
                    'msg'  => lang('file_size_exceed') . '50MB'
                ]);
            }

            // 文件类型安全验证
            if (!$this->validateFileExtension($file, self::ALLOW_FILE_EXT)) {
                return json([
                    'code' => 500,
                    'msg'  => lang('invalid_file_type')
                ]);
            }

            // 使用存储模块上传（自动降级到本地存储）
            $result = $this->uploadToStorage($file, 'files');

            if (!$result['success']) {
                return json([
                    'code' => 500,
                    'msg'  => $result['error'] ?: lang('upload_failed')
                ]);
            }

            return json([
                'code' => 200,
                'msg'  => lang('upload_success'),
                'path' => $result['path']
            ]);
        } catch (FileException $e) {
            return json([
                'code' => 500,
                'msg'  => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'msg'  => lang('upload_failed')
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 商品图片上传方法
    // -------------------------------------------------------------------------
    public function uploadImages()
    {
        $file = Request::file('file');

        try {
            // 验证规则
            $this->validateImageFile($file);

            // 使用存储模块上传（自动降级到本地存储）
            $result = $this->uploadToStorage($file, 'images/pictures');

            if (!$result['success']) {
                return c_error($result['error'] ?: lang('upload_failed'));
            }

            // 返回格式
            return json([
                'code' => 0,
                'data' => [
                    'url'  => $result['url'] ?: (Request::domain() . $result['path']),
                    'path' => $result['path'],
                    'file_id' => $result['file_id'] ?? null,
                ]
            ]);
        } catch (\Exception $e) {
            return c_error(lang('invalid_operation'));
        }
    }

    // -------------------------------------------------------------------------
    // 私有辅助方法
    // -------------------------------------------------------------------------

    /** 处理图片水印 */
    private function processWatermark($imagePath, $configs)
    {
        if ($configs['water'] === 1 && !empty($configs['waterimg'])) {
            $watermark = app()->getRootPath() . 'public/' . ltrim($configs['waterimg'], '/');

            if (file_exists($watermark)) {
                // 获取水印位置
                $position = $configs['water_position'] ?? 4; // 默认右下角
                $positionMap = [
                    1 => Image::WATER_NORTHWEST, // 左上角
                    2 => Image::WATER_NORTHEAST, // 右上角
                    3 => Image::WATER_SOUTHWEST, // 左下角
                    4 => Image::WATER_SOUTHEAST, // 右下角
                    5 => Image::WATER_CENTER,    // 居中
                ];
                $waterPosition = $positionMap[$position] ?? Image::WATER_SOUTHEAST;

                // 获取水印透明度
                $opacity = $configs['water_opacity'] ?? 80;
                $opacity = max(0, min(100, intval($opacity))); // 限制在 0-100 之间

                Image::open($imagePath)
                    ->water($watermark, $waterPosition, $opacity)
                    ->save($imagePath);
            }
        }
    }

    /** 验证文件路径安全性 */
    private function validatePath($path)
    {
        return !empty($path) && is_string($path) &&
            strpos($path, '..') === false &&
            strlen($path) < 500;
    }

    /** 获取完整文件路径 */
    private function getFullPath($path)
    {
        return app()->getRootPath() . 'public/' . ltrim($path, '/');
    }

    /** 检查是否为安全的上传路径 */
    private function isSafeUploadPath($fullPath)
    {
        $uploadDirs = ['storage/images/', 'storage/files/', 'storage/images/pictures/'];

        foreach ($uploadDirs as $dir) {
            if (strpos($fullPath, $dir) !== false) {
                return true;
            }
        }
        return false;
    }

    /** 获取上传限制错误信息 */
    private function getUploadLimitError()
    {
        $maxSize = min(
            $this->convertToBytes(ini_get('upload_max_filesize')),
            $this->convertToBytes(ini_get('post_max_size'))
        );
        $maxSizeReadable = round($maxSize / (1024 * 1024), 2) . 'MB';

        return json([
            'code' => 500,
            'msg'  => lang('upload_size_exceed') . $maxSizeReadable . lang('contact_technical')
        ]);
    }

    /** 转换大小单位为字节 */
    private function convertToBytes($size)
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return round($size);
    }

    /** 验证文件大小 */
    private function validateFileSize($fileSize, $maxSize)
    {
        return $fileSize <= $maxSize;
    }

    /** 验证文件扩展名 */
    private function validateFileExtension($file, $allowedExtensions)
    {
        $extension = strtolower($file->extension());
        $allowed = explode(',', $allowedExtensions);

        return in_array($extension, $allowed);
    }

    /** 验证图片文件 */
    private function validateImageFile($file)
    {
        validate(['file' => [
            'fileSize' => self::MAX_IMAGE_SIZE,
            'fileExt'  => self::ALLOW_IMAGE_EXT
        ]])->check(['file' => $file]);
    }
}
