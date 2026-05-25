<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use app\common\model\manage\ActionLog;
use think\facade\View;
use Throwable;

// -----------------------------------------------------------------------------
// 后台首页控制器
// 负责仪表盘数据展示、系统信息和统计功能
// -----------------------------------------------------------------------------
class Index extends Base
{

    public function dashboard()
    {
        // 动态获取所有模块的仪表盘汇总数据
        $cardsConfig = $this->getModuleDashboardCards();

        // 动态生成最新动态信息
        $latestNews = $this->getLatestActionLog();

        // 获取系统环境信息
        $systemInfo = $this->getSystemInfo();

        // 页面标题和模块标题
        $pageTitles = [
            'page_title' => lang('admin_home'),
            'website_data_overview' => lang('website_data_overview'),
            'quick_operations' => lang('quick_operations'),
            'system_information' => lang('system_information'),
            'notes' => lang('notes'),
            'technical_support' => lang('technical_support'),
            'support_us' => lang('support_us')
        ];

        return View::fetch('', [
            // 核心统计模块数据
            'cardsConfig'   => $cardsConfig,

            // 最新数据和动态
            'latestNews'    => $latestNews,

            // 系统环境信息
            'system_info'   => $systemInfo,

            // 页面标题
            'pageTitles'    => $pageTitles,

            // JavaScript多语言文本
            'jsLang' => [
                'system_version' => lang('system_version'),
                'php_version' => lang('php_version'),
                'server_software' => lang('server_software'),
                'database' => lang('database'),
                'login_ip' => lang('login_ip'),
            ]
        ]);
    }

    // -------------------------------------------------------------------------
    // 动态获取所有模块的仪表盘卡片数据（按模块分组）
    // -------------------------------------------------------------------------
    private function getModuleDashboardCards(): array
    {
        $grouped = [];

        // 获取所有已安装/启用的模块
        $modules = \app\module\model\Module::where('status', 'in', ['installed', 'enabled'])
            ->select();

        foreach ($modules as $module) {
            $moduleName = $module->name;
            // 通过 helper 函数调用模块的 DashboardService
            $service = module_service($moduleName, 'DashboardService');
            if ($service && method_exists($service, 'getSummary')) {
                try {
                    $moduleCards = $service->getSummary();
                    if (!empty($moduleCards)) {
                        $grouped[] = [
                            'name'  => $moduleName,
                            'title' => $module->title ?: $moduleName,
                            'cards' => $moduleCards,
                        ];
                    }
                } catch (\Throwable $e) {
                    \think\facade\Log::error("Dashboard error [{$moduleName}]: " . $e->getMessage());
                }
            }
        }

        // 默认展开前3个模块，其余折叠
        foreach ($grouped as $i => $item) {
            $grouped[$i]['collapsed'] = ($i >= 3);
        }
        return $grouped;
    }

    // -------------------------------------------------------------------------
    // 生成最新动态信息
    // -------------------------------------------------------------------------
    private function getLatestActionLog(): array
    {
        $news = [];

        // 获取最新操作日志
        $list = ActionLog::order('id', 'desc')
            ->limit(10)
            ->select();

        foreach ($list as $item) {
            $news[] = [
                'time' => date('Y-m-d H:i:s', strtotime($item->create_time)),
                'content' => '用户[' . $item->uname . '] ' . $item->module,
                'icon' => 'layui-icon-component'
            ];
        }

        // 按时间倒序排序
        usort($news, function ($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        // 只返回最新的10条
        return array_slice($news, 0, 10);
    }

    // -------------------------------------------------------------------------
    // 获取系统环境信息
    // -------------------------------------------------------------------------
    private function getSystemInfo(): array
    {
        return [
            'user_agent'       => $_SERVER['HTTP_USER_AGENT'] ?? lang('unknown'),
            'php_version'      => 'PHP-' . PHP_VERSION,
            'server_software'  => $_SERVER['SERVER_SOFTWARE'] ?? lang('unknown'),
            'database'         => 'MySQL 5.7+',
            'remote_ip'        => $_SERVER['REMOTE_ADDR'] ?? lang('unknown'),
            'server_time'      => date('Y-m-d H:i:s'),
            'thinkphp_version' => app()->version(),
            'os'               => PHP_OS,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'memory_limit'     => ini_get('memory_limit')
        ];
    }
}
