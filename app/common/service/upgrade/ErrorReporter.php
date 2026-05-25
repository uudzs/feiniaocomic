<?php

declare(strict_types=1);

namespace app\common\service\upgrade;

/**
 * 错误上报服务
 * 将系统致命错误上报到官方服务器
 */
class ErrorReporter
{
    // 是否启用
    protected static bool $enabled = true;

    // 站点密钥
    protected static string $siteKey = 'feiniaocomic';

    // 上报级别
    protected static array $reportLevels = [
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_COMPILE_ERROR,
        E_USER_ERROR,
        E_RECOVERABLE_ERROR,
    ];

    // 忽略的异常类型
    protected static array $ignoreTypes = [
        'think\exception\HttpResponseException',
        'think\exception\HttpException',
        'think\exception\ValidateException',
        'think\db\exception\DataNotFoundException',
        'think\db\exception\ModelNotFoundException',
    ];


    /**
     * 检查是否启用
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * 上报错误
     * @param \Throwable $e 异常对象
     * @return bool
     */
    public static function report(\Throwable $e): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        if (!self::shouldReport($e)) {
            return false;
        }

        $data = self::buildErrorData($e);

        return self::sendAsync($data);
    }

    /**
     * 判断是否应该上报
     */
    protected static function shouldReport(\Throwable $e): bool
    {
        // 忽略指定的异常类型
        foreach (self::$ignoreTypes as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        // 检查错误级别
        if (method_exists($e, 'getSeverity')) {
            $severity = $e->getSeverity();
            if (!in_array($severity, self::$reportLevels)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 构建错误数据
     */
    protected static function buildErrorData(\Throwable $e): array
    {
        // 获取已安装模块信息
        $modules = self::getInstalledModules();
        $templates = self::getInstalledTemplates();

        return [
            'site_url' => request()->domain() ?? 'cli',
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString(),
            'environment' => [
                'php_version' => PHP_VERSION,
                'system_version' => UpgradeService::getThisSystemVersion() ?? 'unknown',
                'modules' => $modules,
                'templates' => $templates,
                'request_uri' => request()->url(true) ?? '',
                'request_method' => request()->method() ?? '',
                'user_agent' => request()->header('user-agent', ''),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
            ],
            'occurred_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * 获取已安装模块信息
     */
    protected static function getInstalledModules(): array
    {
        $modules = [];
        $modulePath = root_path() . 'modules' . DIRECTORY_SEPARATOR;

        if (!is_dir($modulePath)) {
            return $modules;
        }

        $dirs = glob($modulePath . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $name = basename($dir);
            $moduleJson = $dir . DIRECTORY_SEPARATOR . 'module.json';

            if (file_exists($moduleJson)) {
                $info = json_decode(file_get_contents($moduleJson), true);
                $modules[$name] = $info['version'] ?? 'unknown';
            }
        }

        return $modules;
    }

    /**
     * 获取已安装模板信息
     */
    protected static function getInstalledTemplates(): array
    {
        // 获取已安装模板
        $templates = [];
        $templatePath = root_path() . 'template' . DIRECTORY_SEPARATOR;

        if (is_dir($templatePath)) {
            $dirs = glob($templatePath . '*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $name = basename($dir);
                if ($dir === '.' || $dir === '..' || $dir === 'manage') {
                    continue;
                }
                $themeJson = $dir . DIRECTORY_SEPARATOR . 'theme.json';
                if (file_exists($themeJson)) {
                    $info = json_decode(file_get_contents($themeJson), true);
                    $templates[$name] = $info['version'] ?? 'unknown';                   
                }
            }
        }
        return $templates;
    }

    /**
     * 异步发送错误报告
     */
    protected static function sendAsync(array $data): bool
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $signature = base64_encode(hash_hmac('sha256', $json, self::$siteKey, true));

        // 使用 curl 异步发送
        try {
            $url = UpgradeService::getApiUrl() . '/feedback/error';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Signature: ' . $signature,
                    'X-Site-Key: ' . self::$siteKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);

            curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 注册错误处理器
     */
    public static function register(): void
    {
        if (!self::isEnabled()) {
            return;
        }

        // 注册错误处理
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        // 注册异常处理
        set_exception_handler(function (\Throwable $e) {
            self::report($e);
            // 继续抛出异常，让系统处理
            throw $e;
        });
    }
}
