<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Request;
use app\common\model\manage\Conf;
use app\common\model\manage\ConfCategory;
use app\common\library\Mailer;

/**
 * Debug 模式管理控制器
 */
class Debug extends Base
{
    /**
     * Debug 管理页面
     */
    public function index()
    {
        // 获取当前 Debug 状态
        $debugStatus = $this->getDebugStatus();

        return view('debug/index', [
            'debugStatus' => $debugStatus,
            'email' => ConfCategory::where('ename', 'email')->find()->toArray()
        ]);
    }

    /**
     * 切换 Debug 状态
     */
    public function toggle()
    {
        if (!Request::isPost()) {
            return json(['code' => 400, 'msg' => '请求方式错误']);
        }

        try {
            $status = Request::post('status/d', 0);
            $result = $this->setDebugStatus($status);

            if ($result) {
                $this->logFormAction('切换 Debug 模式为：' . ($status ? '开启' : '关闭'));
                return json([
                    'code' => 200,
                    'msg' => 'Debug 模式已' . ($status ? '开启' : '关闭'),
                    'data' => ['status' => $status]
                ]);
            } else {
                return json(['code' => 500, 'msg' => '修改失败，请检查 .env 文件权限']);
            }
        } catch (\Throwable $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 获取当前 Debug 状态
     */
    private function getDebugStatus(): int
    {
        $envPath = $this->getEnvPath();

        if (!file_exists($envPath)) {
            return 0;
        }

        $content = file_get_contents($envPath);

        // 匹配 APP_DEBUG = true/false
        if (preg_match('/APP_DEBUG\s*=\s*(true|false)/i', $content, $matches)) {
            return strtolower($matches[1]) === 'true' ? 1 : 0;
        }

        return 0;
    }

    /**
     * 设置 Debug 状态
     */
    private function setDebugStatus(int $status): bool
    {
        $envPath = $this->getEnvPath();

        if (!file_exists($envPath)) {
            throw new \Exception('.env 文件不存在');
        }

        // 检查文件是否可写
        if (!is_writable($envPath)) {
            throw new \Exception('.env 文件没有写入权限');
        }

        $content = file_get_contents($envPath);
        $newValue = $status ? 'true' : 'false';

        // 替换 APP_DEBUG 的值
        $newContent = preg_replace(
            '/(APP_DEBUG\s*=\s*)(true|false)/i',
            '${1}' . $newValue,
            $content
        );

        // 如果没有匹配到，则在文件末尾添加
        if ($newContent === $content && !preg_match('/APP_DEBUG/i', $content)) {
            $newContent .= PHP_EOL . "APP_DEBUG = " . $newValue . PHP_EOL;
        }

        // 写入文件
        $result = file_put_contents($envPath, $newContent, LOCK_EX);

        return $result !== false;
    }

    /**
     * 获取 .env 文件路径
     */
    private function getEnvPath(): string
    {
        return root_path() . '.env';
    }

    /**
     * 获取邮箱配置
     */
    public function getEmailConfig()
    {
        try {
            // 获取邮箱配置项
            $category = ConfCategory::where('ename', 'email')->find()->toArray();
            if (empty($category)) {
                return json(['code' => 400, 'msg' => '配置不存在']);
            }
            $emailConfig = [];
            $configs = Conf::where('model', $category['id'])->where('status', 1)->select();

            foreach ($configs as $config) {
                $emailConfig[$config['ename']] = $config['value'];
            }
            return json([
                'code' => 200,
                'msg' => '获取邮箱配置成功',
                'data' => $emailConfig
            ]);
        } catch (\Throwable $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 发送测试邮件
     */
    public function sendTestEmail()
    {
        if (!Request::isPost()) {
            return json(['code' => 400, 'msg' => '请求方式错误']);
        }

        try {
            $to = Request::post('to', '');
            $subject = Request::post('subject', '测试邮件');
            $content = Request::post('content', '这是一封测试邮件，用于验证邮箱配置是否正确。');

            if (empty($to)) {
                return json(['code' => 400, 'msg' => '请输入收件人邮箱']);
            }

            // 验证邮箱格式
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return json(['code' => 400, 'msg' => '邮箱格式不正确']);
            }

            // 获取邮箱配置
            $category = ConfCategory::where('ename', 'email')->find()->toArray();
            if (empty($category)) {
                return json(['code' => 400, 'msg' => '配置不存在']);
            }
            $configs = Conf::where('model', $category['id'])->where('status', 1)->select();
            $emailConfig = [];
            foreach ($configs as $config) {
                $emailConfig[$config['ename']] = $config['value'];
            }

            // 检查必要配置
            $requiredConfig = ['emailaddress', 'smtpserver', 'smtpport', 'smtpusername', 'smtppassword'];
            foreach ($requiredConfig as $key) {
                if (empty($emailConfig[$key])) {
                    return json(['code' => 400, 'msg' => '邮箱配置不完整，请先配置邮箱设置']);
                }
            }
            try {
                $result = Mailer::create($emailConfig)
                    ->to($to)
                    ->subject($subject)
                    ->content($content, false)
                    ->send();
                if ($result) {
                    $this->logFormAction('发送测试邮件到：' . $to);
                    return json([
                        'code' => 200,
                        'msg' => '邮件发送成功',
                        'data' => ['to' => $to, 'subject' => $subject]
                    ]);
                } else {
                    return json(['code' => 500, 'msg' => '邮件发送失败，请检查配置']);
                }
            } catch (\Exception $e) {
                return json(['code' => 500, 'msg' => $e->getMessage()]);
            }            
        } catch (\Throwable $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }
}
