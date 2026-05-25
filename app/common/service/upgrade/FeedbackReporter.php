<?php

declare(strict_types=1);

namespace app\common\service\upgrade;

/**
 * 反馈上报服务
 * 将用户意见、建议、二开需求上报到官方服务器
 */
class FeedbackReporter
{
    // 是否启用
    protected static bool $enabled = true;

    // 站点密钥
    protected static string $siteKey = 'feiniaocomic';

    // 反馈类型
    const TYPE_IDEA = 'idea';           // 意见
    const TYPE_SUGGEST = 'suggest';      // 建议
    const TYPE_CUSTOM = 'custom';       // 二开需求

    /**
     * 检查是否启用
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * 提交反馈
     * @param array $data 反馈数据
     * @return bool
     */
    public static function submit(array $data): bool
    {
        if (!self::isEnabled()) {
            return false;
        }

        $feedbackData = self::buildFeedbackData($data);

        return self::sendAsync($feedbackData);
    }

    /**
     * 构建反馈数据
     */
    protected static function buildFeedbackData(array $userData): array
    {
        // 获取已安装模块信息
        $modules = self::getInstalledModules();
        $templates = self::getInstalledTemplates();

        return [
            'site_url' => request()->domain() ?? 'unknown',
            'feedback_type' => $userData['type'] ?? self::TYPE_IDEA,
            'content' => $userData['content'] ?? '',
            'contact' => $userData['contact'] ?? '',
            'environment' => [
                'php_version' => PHP_VERSION,
                'system_version' => UpgradeService::getThisSystemVersion() ?? 'unknown',
                'modules' => $modules,
                'templates' => $templates,
                'request_uri' => request()->url(true) ?? '',
                'request_method' => request()->method() ?? '',
                'user_agent' => request()->header('user-agent', ''),
                'admin_user' => session('admin_name') ?? 'unknown',
            ],
            'created_at' => date('Y-m-d H:i:s'),
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
        $templates = [];
        $templatePath = root_path() . 'template' . DIRECTORY_SEPARATOR;

        if (is_dir($templatePath)) {
            $dirs = glob($templatePath . '*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $name = basename($dir);
                if ($name === 'manage') {
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
     * 异步发送反馈报告
     */
    protected static function sendAsync(array $data): bool
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $signature = base64_encode(hash_hmac('sha256', $json, self::$siteKey, true));

        try {
            $url = UpgradeService::getApiUrl() . '/feedback/receive';
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
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($error) {
                return false;
            }

            // 如果返回JSON，尝试解析
            if ($response) {
                $result = json_decode($response, true);
                // 可以根据返回结果判断是否成功
                return isset($result['code']) && $result['code'] === 0;
            }

            return $httpCode === 200;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 获取反馈类型选项
     */
    public static function getTypeOptions(): array
    {
        return [
            ['value' => self::TYPE_IDEA, 'label' => '意见反馈'],
            ['value' => self::TYPE_SUGGEST, 'label' => '功能建议'],
            ['value' => self::TYPE_CUSTOM, 'label' => '二开需求'],
        ];
    }
}
