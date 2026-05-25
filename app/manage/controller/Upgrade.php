<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\common\service\upgrade\UpgradeService;
use think\Request;
use app\common\model\manage\SystemUpgradeLog;

/**
 * 在线升级管理
 */
class Upgrade extends Base
{
    protected $modelClass = null; // 本控制器不使用模型类
    protected $validateClass = null;

    /**
     * 首页 - 升级管理
     */
    public function index()
    {
        $service = new UpgradeService();

        // 获取当前系统版本
        $systemVersion = SystemUpgradeLog::where('type', 'system')->where('status', 6)->order('id desc')->value('to_version');

        // 获取API地址
        $apiUrl = UpgradeService::getApiUrl();

        // 获取升级记录
        $logs = $service->getLogList(null, 1, 10);

        return view('upgrade/index', [
            'systemVersion' => $systemVersion,
            'apiUrl' => $apiUrl,
            'logs' => $logs,
        ]);
    }

    /**
     * 登录页面
     */
    public function login(Request $request)
    {
        $service = new UpgradeService();
        if (!$request->isPost()) {
            // 检查登录状态
            $isLogin = $service->isLoggedIn();
            $registerUrl = UpgradeService::getRegisterUrl();
            return view('upgrade/login', ['isLogin' => $isLogin, 'registerUrl' => $registerUrl]);
        }

        $account = $request->param('account', '');
        $password = $request->param('password', '');
        if (empty($account) || empty($password)) {
            return json(['code' => 400, 'msg' => '请输入账号和密码']);
        }

        // 登录
        $result = $service->login($account, $password);
        if ($result['success']) {
            return json(['code' => 200, 'msg' => $result['message'], 'data' => $result['data']]);
        } else {
            return json(['code' => 400, 'msg' => $result['message']]);
        }
    }

    /**
     * 登出
     */
    public function logout()
    {
        $service = new UpgradeService();
        $result = $service->logout();
        if ($result['success']) {
            return json(['code' => 200, 'msg' => $result['message']]);
        } else {
            return json(['code' => 400, 'msg' => $result['message']]);
        }
    }

    /**
     * 检查登录状态
     */
    public function checkLogin()
    {
        $service = new UpgradeService();
        $result = $service->checkLogin();
        return json([
            'code' => 200,
            'data' => $result['data'] ?? ['is_login' => false],
        ]);
    }

    /**
     * 系统升级
     */
    public function system()
    {
        $service = new UpgradeService();

        // 检查更新
        $result = $service->checkSystemUpdate();

        return json([
            'code' => 200,
            'data' => $result,
        ]);
    }

    /**
     * 开始系统升级
     */
    public function systemUpgrade(Request $request)
    {
        $version = $request->param('version', '');

        if (empty($version)) {
            return json(['code' => 400, 'msg' => '请选择要升级的版本']);
        }

        $service = new UpgradeService();

        // 1. 下载升级包
        $download = $service->download('system', 'system', $version);

        if (!$download['success']) {
            return json(['code' => 400, 'msg' => '下载失败: ' . $download['message']]);
        }

        // 2. 执行升级
        $result = $service->execute($download['log_id']);

        return json($result);
    }

    /**
     * 模块升级列表
     */
    public function module()
    {
        // 获取已安装模块
        $modules = [];
        $modulePath = root_path() . 'modules' . DIRECTORY_SEPARATOR;

        if (is_dir($modulePath)) {
            $dirs = glob($modulePath . '*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $name = basename($dir);
                $moduleJson = $dir . DIRECTORY_SEPARATOR . 'module.json';

                if (file_exists($moduleJson)) {
                    $info = json_decode(file_get_contents($moduleJson), true);
                    $modules[] = [
                        'name' => $name,
                        'title' => $info['title'] ?? $name,
                        'version' => $info['version'] ?? '1.0.0',
                        'description' => $info['description'] ?? '',
                    ];
                }
            }
        }

        return view('upgrade/module', [
            'modules' => $modules,
        ]);
    }

    /**
     * 检查模块更新
     */
    public function checkModuleUpdate(Request $request)
    {
        $moduleName = $request->param('name', '');

        if (empty($moduleName)) {
            return json(['code' => 400, 'msg' => '请指定模块名称']);
        }

        $service = new UpgradeService();
        $result = $service->checkModuleUpdate($moduleName);

        return json([
            'code' => 0,
            'data' => $result,
        ]);
    }

    /**
     * 模块升级
     */
    public function moduleUpgrade(Request $request)
    {
        $moduleName = $request->param('name', '');
        $version = $request->param('version', '');

        if (empty($moduleName) || empty($version)) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $service = new UpgradeService();

        // 1. 下载升级包
        $download = $service->download('module', $moduleName, $version);

        if (!$download['success']) {
            return json(['code' => 400, 'msg' => '下载失败: ' . $download['message']]);
        }

        // 2. 执行升级
        $result = $service->execute($download['log_id']);

        return json($result);
    }

    /**
     * 模板升级列表
     */
    public function template()
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
                    $templates[] = [
                        'name' => $name,
                        'title' => $info['title'] ?? $name,
                        'version' => $info['version'] ?? '1.0.0',
                        'author' => $info['author'] ?? '',
                    ];
                }
            }
        }
        return json([
            'code' => 0,
            'count' => count($templates),
            'message' => lang('render_success'),
            'data' => $templates
        ]);
    }

    /**
     * 检查模板更新
     */
    public function checkTemplateUpdate(Request $request)
    {
        $templateName = $request->param('name', '');

        if (empty($templateName)) {
            return json(['code' => 400, 'msg' => '请指定模板名称']);
        }

        $service = new UpgradeService();
        $result = $service->checkTemplateUpdate($templateName);

        return json([
            'code' => 0,
            'data' => $result,
        ]);
    }

    /**
     * 模板升级
     */
    public function templateUpgrade(Request $request)
    {
        $templateName = $request->param('name', '');
        $version = $request->param('version', '');

        if (empty($templateName) || empty($version)) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $service = new UpgradeService();

        // 1. 下载升级包
        $download = $service->download('template', $templateName, $version);

        if (!$download['success']) {
            return json(['code' => 400, 'msg' => '下载失败: ' . $download['message']]);
        }

        // 2. 执行升级
        $result = $service->execute($download['log_id']);

        return json($result);
    }

    /**
     * 升级记录
     */
    public function logs()
    {
        $page = request()->param('page', 1);
        $limit = request()->param('limit', 20);
        $type = request()->param('type', '');

        $service = new UpgradeService();
        $result = $service->getLogList($type ?: null, (int) $page, (int) $limit);

        return json([
            'code' => 0,
            'data' => $result['list'],
            'count' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
        ]);
    }

    /**
     * 升级记录详情
     */
    public function logDetail(Request $request)
    {
        $id = $request->param('id', 0);

        $service = new UpgradeService();
        $detail = $service->getLogDetail((int) $id);

        if (empty($detail)) {
            return json(['code' => 404, 'msg' => '记录不存在']);
        }

        return json([
            'code' => 0,
            'data' => $detail,
        ]);
    }
}
