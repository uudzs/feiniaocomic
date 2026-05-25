<?php

declare(strict_types=1);

namespace app\manage\controller;

use think\facade\Log;
use think\facade\Cache;
use think\facade\Request;
use think\exception\ValidateException;
use Throwable;

// -----------------------------------------------------------------------------
// 通用功能控制器
// 提供状态切换、排序更新、缓存清理等通用功能
// -----------------------------------------------------------------------------
class Common
{
    // -------------------------------------------------------------------------
    // 核心配置属性
    // -------------------------------------------------------------------------
    protected $modelClass;    // 模型类名（必须）
    protected $validateClass; // 验证器类名（可选）

    // -------------------------------------------------------------------------
    // 默认配置（子类可覆盖）
    // -------------------------------------------------------------------------
    protected $primaryKey = 'id';         // 主键字段名
    protected $statusField = 'status';    // 状态字段名
    protected $sortField = 'sort';        // 排序字段名

    // -------------------------------------------------------------------------
    // 请求验证与安全方法
    // -------------------------------------------------------------------------

    /** 验证AJAX请求 */
    protected function validateAjaxRequest(): void
    {
        if (!Request::isAjax()) {
            throw new \RuntimeException(lang('illegal_request'), 403); // 非法请求
        }
    }

    /** 安全获取模型类 */
    protected function safeGetModelClass(string $modelSuffix): string
    {
        $modelName = ucfirst($modelSuffix); // 构建模型名
        $modelClass = "app\\common\\model\\manage\\{$modelName}"; // 完整类名

        if (!class_exists($modelClass)) {
            Log::error("尝试访问不存在的模型: {$modelClass}"); // 记录错误
            throw new \RuntimeException(lang('model_not_exists'), 404); // 模型不存在
        }

        return $modelClass; // 返回模型类
    }

    // -------------------------------------------------------------------------
    // 通用状态切换方法
    // -------------------------------------------------------------------------

    /** 状态切换方法 */
    public function changeStatus()
    {
        try {
            $this->validateAjaxRequest(); // 验证AJAX请求
            $data = Request::param(); // 获取请求参数

            // 模型类安全获取
            $modelClass = $this->safeGetModelClass($data['model'] ?? '');

            // 字段使用配置
            $statusField = $data['field'] ?? $this->statusField; // 状态字段
            $id = (int)($data['id'] ?? 0); // 记录ID

            if ($id <= 0) {
                throw new ValidateException(lang('invalid_record_id')); // 无效记录ID
            }

            // 获取记录
            $record = $modelClass::where($this->primaryKey, $id)
                ->field([$this->primaryKey, $statusField])
                ->find();

            if (!$record) {
                throw new \RuntimeException(lang('data_not_exists'), 404); // 数据不存在
            }

            // 状态切换（支持多值状态）
            $newStatus = $record[$statusField] == 1 ? 0 : 1; // 切换状态
            $modelClass::where($this->primaryKey, $id)
                ->update([$statusField => $newStatus]); // 更新状态

            return c_success(lang('status_update_success')); // 返回成功
        } catch (Throwable $e) {
            return $this->handleException($e); // 异常处理
        }
    }

    /** 安全获取模型类 */
    protected function safeGetModuleModelClass(string $modelSuffix): string
    {
        $modelClass = "module\\{$modelSuffix}"; // 完整类名

        if (!class_exists($modelClass)) {
            Log::error("尝试访问不存在的模型: {$modelClass}"); // 记录错误
            throw new \RuntimeException(lang('model_not_exists'), 404); // 模型不存在
        }

        return $modelClass; // 返回模型类
    }

    /** 状态切换方法 */
    public function changeModuleStatus()
    {
        try {
            $this->validateAjaxRequest(); // 验证AJAX请求
            $data = Request::param(); // 获取请求参数

            // 模型类安全获取
            $modelClass = $this->safeGetModuleModelClass($data['model'] ?? '');

            // 字段使用配置
            $statusField = $data['field'] ?? $this->statusField; // 状态字段
            $id = (int)($data['id'] ?? 0); // 记录ID

            if ($id <= 0) {
                throw new ValidateException(lang('invalid_record_id')); // 无效记录ID
            }

            // 获取记录
            $record = $modelClass::where($this->primaryKey, $id)
                ->field([$this->primaryKey, $statusField])
                ->find();

            if (!$record) {
                throw new \RuntimeException(lang('data_not_exists'), 404); // 数据不存在
            }

            // 状态切换（支持多值状态）
            $newStatus = $record[$statusField] == 1 ? 0 : 1; // 切换状态
            $modelClass::where($this->primaryKey, $id)
                ->update([$statusField => $newStatus]); // 更新状态

            return c_success(lang('status_update_success')); // 返回成功
        } catch (Throwable $e) {
            return $this->handleException($e); // 异常处理
        }
    }

    // -------------------------------------------------------------------------
    // 通用排序更新方法
    // -------------------------------------------------------------------------

    /** 排序更新方法 */
    public function baseSort()
    {
        try {
            $this->validateAjaxRequest(); // 验证AJAX请求
            $data = Request::post(); // 获取POST数据

            // 验证排序值
            if (!is_numeric($data['value'])) {
                throw new ValidateException(lang('sort_value_must_be_number')); // 排序值必须是数字
            }

            $value = (int)$data['value']; // 转换为整数
            if ($value < 0 || $value > 10000) {
                throw new ValidateException(lang('sort_value_range')); // 排序值范围错误
            }

            if (!isset($data['model']) || empty($data['model'])) {
                throw new ValidateException('模型错误'); // 模型错误
            }

            $models = explode('\\', $data['model']);
            if (count($models) > 1) {
                $modelClass = $this->safeGetModuleModelClass(implode('\\', $models), $data['model']);
            } else {
                $modelClass = $this->safeGetModelClass($data['model'] ?? '');
            }
            
            $id = (int)($data['id'] ?? 0); // 记录ID

            if ($id <= 0) {
                throw new ValidateException(lang('invalid_record_id')); // 无效记录ID
            }

            // 查找记录
            $record = $modelClass::where($this->primaryKey, $id)->find();
            if (!$record) {
                throw new \RuntimeException(lang('record_not_exists'), 404); // 记录不存在
            }

            // 更新排序
            $record->{$this->sortField} = $value; // 设置排序值
            $record->save(); // 保存记录

            return c_success(lang('sort_update_success')); // 返回成功
        } catch (Throwable $e) {
            return $this->handleException($e); // 异常处理
        }
    }

    // -------------------------------------------------------------------------
    // 缓存清理方法
    // -------------------------------------------------------------------------

    /** 清除缓存方法 */
    public function clearCache()
    {
        try {
            Cache::clear(); // 清理常规缓存

            // 清理模板缓存
            $this->clearTemplateCache('index/temp');   // 前台模板缓存
            $this->clearTemplateCache('admin/temp'); // 后台模板缓存

            return c_success(lang('cache_clear_success')); // 返回成功
        } catch (Throwable $e) {
            return $this->handleException($e); // 异常处理
        }
    }

    // -------------------------------------------------------------------------
    // 辅助方法
    // -------------------------------------------------------------------------

    /** 高效清理模板缓存 */
    protected function clearTemplateCache(string $relativePath): void
    {
        $dir = root_path('runtime') . $relativePath; // 构建完整路径

        if (!is_dir($dir)) {
            return; // 目录不存在则返回
        }

        try {
            $this->deleteDirectoryContents($dir); // 递归删除目录内容

            // 重建目录（确保目录存在）
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true); // 创建目录
            }
        } catch (Throwable $e) {
            Log::error("清理模板缓存失败: {$e->getMessage()}"); // 记录错误
        }
    }

    /** 递归删除目录内容（保留目录结构） */
    protected function deleteDirectoryContents(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']); // 获取文件列表（排除.和..）
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file; // 构建完整路径
            if (is_dir($path)) {
                $this->deleteDirectoryContents($path); // 递归处理子目录
                @rmdir($path); // 尝试删除空目录
            } else {
                @unlink($path); // 尝试删除文件
            }
        }
    }

    /** 异常统一处理（适配Layui弹窗） */
    protected function handleException(Throwable $e)
    {
        $code = $e->getCode() ?: 500; // 错误代码
        $msg = $e->getMessage(); // 错误消息

        // 特殊处理403错误（非法请求）
        if ($code === 403) {
            $msg = lang('illegal_request_operation'); // 非法请求提示
        }

        // 生产环境隐藏详细错误信息
        if (!config('app.app_debug')) {
            $msg = lang('operation_failed_retry'); // 操作失败提示
        }

        Log::error(sprintf( // 记录错误日志
            '%s: %s in %s:%d',
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        ));

        return json([
            'code' => $code, // 错误代码
            'msg'  => $msg,  // 错误消息
            'data' => []     // 空数据
        ]);
    }
}
