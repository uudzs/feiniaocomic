<?php

declare(strict_types=1);

namespace app\common\service\upgrade;

/**
 * 权限检查器
 * 用于升级前检查文件/目录的读写权限
 */
class PermissionChecker
{
    protected string $rootPath;
    protected bool $isWindows;

    public function __construct()
    {
        $this->rootPath = root_path();
        $this->isWindows = DIRECTORY_SEPARATOR === '\\';
    }

    /**
     * 检查升级所需的所有文件权限
     * @param array $addFiles 新增文件列表
     * @param array $updateFiles 更新文件列表
     * @param array $deleteFiles 删除文件列表
     * @return array ['success' => bool, 'errors' => [...], 'summary' => [...]]
     */
    public function checkAll(array $addFiles, array $updateFiles, array $deleteFiles): array
    {
        $result = [
            'success' => true,
            'errors' => [],
            'summary' => [
                'total' => count($addFiles) + count($updateFiles) + count($deleteFiles),
                'add' => count($addFiles),
                'update' => count($updateFiles),
                'delete' => count($deleteFiles),
                'passed' => 0,
                'failed' => 0,
            ],
        ];

        // 检查新增文件
        foreach ($addFiles as $file) {
            $check = $this->checkFile('add', $file);
            if ($check['ok']) {
                $result['summary']['passed']++;
            } else {
                $result['success'] = false;
                $result['errors'][] = array_merge($check, ['operation' => 'add', 'file' => $file]);
                $result['summary']['failed']++;
            }
        }

        // 检查更新文件
        foreach ($updateFiles as $file) {
            $check = $this->checkFile('update', $file);
            if ($check['ok']) {
                $result['summary']['passed']++;
            } else {
                $result['success'] = false;
                $result['errors'][] = array_merge($check, ['operation' => 'update', 'file' => $file]);
                $result['summary']['failed']++;
            }
        }

        // 检查删除文件
        foreach ($deleteFiles as $file) {
            $check = $this->checkFile('delete', $file);
            if ($check['ok']) {
                $result['summary']['passed']++;
            } else {
                $result['success'] = false;
                $result['errors'][] = array_merge($check, ['operation' => 'delete', 'file' => $file]);
                $result['summary']['failed']++;
            }
        }

        return $result;
    }

    /**
     * 检查单个文件的权限
     * @param string $type 操作类型：add/update/delete
     * @param string $file 相对路径
     * @return array ['ok' => bool, 'reason' => string, 'suggestion' => string, 'required' => string]
     */
    public function checkFile(string $type, string $file): array
    {
        $fullPath = $this->rootPath . $file;

        // 新增文件
        if ($type === 'add') {
            $parentDir = dirname($fullPath);

            // 检查父目录是否存在
            if (!is_dir($parentDir)) {
                // 父目录不存在，尝试创建
                if (@mkdir($parentDir, 0755, true)) {
                    // 创建成功，清理测试目录
                    @rmdir($parentDir);
                    return [
                        'ok' => true,
                        'reason' => '可以创建父目录',
                    ];
                }
                return [
                    'ok' => false,
                    'reason' => '父目录不存在且无法创建',
                    'suggestion' => $this->isWindows
                        ? "请在命令行执行: mkdir \"{$parentDir}\""
                        : "请在命令行执行: mkdir -p " . dirname($file),
                    'required' => '可创建父目录',
                ];
            }

            // 父目录存在，检查是否可写
            if (is_writable($parentDir)) {
                return ['ok' => true, 'reason' => '父目录可写'];
            }

            return [
                'ok' => false,
                'reason' => '父目录不可写',
                'suggestion' => $this->isWindows
                    ? "请在文件管理器中给目录 \"{$parentDir}\" 添加完全控制权限"
                    : "请在命令行执行: chmod 755 " . dirname($file),
                'required' => '755',
            ];
        }

        // 更新文件
        if ($type === 'update') {
            if (!file_exists($fullPath)) {
                // 文件不存在不算错误（升级包有但原文件没有视为新增）
                return [
                    'ok' => true,
                    'reason' => '文件不存在，将作为新增处理',
                ];
            }

            if (is_writable($fullPath)) {
                return ['ok' => true, 'reason' => '文件可写'];
            }

            return [
                'ok' => false,
                'reason' => '文件不可写',
                'suggestion' => $this->isWindows
                    ? "请在文件管理器中给文件 \"{$fullPath}\" 添加完全控制权限"
                    : "请在命令行执行: chmod 644 " . $file,
                'required' => '644',
            ];
        }

        // 删除文件
        if ($type === 'delete') {
            if (!file_exists($fullPath)) {
                // 文件不存在不算错误
                return [
                    'ok' => true,
                    'reason' => '文件不存在，无需删除',
                ];
            }

            if (is_writable($fullPath)) {
                return ['ok' => true, 'reason' => '文件可删除'];
            }

            return [
                'ok' => false,
                'reason' => '文件不可删除（无写权限）',
                'suggestion' => $this->isWindows
                    ? "请在文件管理器中给文件 \"{$fullPath}\" 添加完全控制权限"
                    : "请在命令行执行: chmod 644 " . $file,
                'required' => '644',
            ];
        }

        return ['ok' => true];
    }

    /**
     * 检查目录权限
     * @param string $dir 相对路径
     * @return bool
     */
    public function checkDir(string $dir): bool
    {
        $fullPath = $this->rootPath . $dir;

        if (!is_dir($fullPath)) {
            // 尝试创建
            return @mkdir($fullPath, 0755, true);
        }

        return is_writable($fullPath);
    }
    
    /**
     * 格式化错误信息为可读文本
     * @param array $errors 错误列表
     * @return string
     */
    public function formatErrors(array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        $lines = ["权限检查失败，请修复以下问题：\n"];

        foreach ($errors as $i => $error) {
            $num = $i + 1;
            $opText = $this->getOperationText($error['operation'] ?? '');
            $lines[] = "{$num}. [{$opText}] {$error['file']}";
            $lines[] = "   原因: {$error['reason']}";
            if (!empty($error['suggestion'])) {
                $lines[] = "   修复: {$error['suggestion']}";
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * 获取操作类型的中文描述
     */
    protected function getOperationText(string $operation): string
    {
        return match ($operation) {
            'add' => '新增',
            'update' => '更新',
            'delete' => '删除',
            default => '未知',
        };
    }
}
