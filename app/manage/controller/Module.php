<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use app\module\library\ModuleManager;
use app\module\model\Module as ModuleModel;
use app\common\service\upgrade\UpgradeService;
use think\facade\View;
use think\facade\Request;
use think\facade\Log;

class Module extends Base
{
    protected $modelClass = ModuleModel::class;

    public function index()
    {
        $moduleManager = ModuleManager::instance();
        $modules = $moduleManager->getAllModules();
        $service = new UpgradeService();
        // 检查登录状态
        $isLogin = $service->isLoggedIn();
        return View::fetch('module/index', ['modules' => $modules, 'isLogin' => $isLogin]);
    }

    public function getModuleData()
    {
        try {
            $moduleManager = ModuleManager::instance();
            $modules = $moduleManager->getAllModules();
            $data = [];
            foreach ($modules as $module) {
                $data[] = [
                    'id' => $module['id'],
                    'name' => $module['name'],
                    'title' => $module['title'],
                    'description' => $module['description'],
                    'version' => $module['version'],
                    'author' => $module['author'],
                    'url' => $module['url'],
                    'status' => $module['status'],
                    'status_text' => $module['status_text'],
                    'create_time' => $module['create_time'],
                    'update_time' => $module['update_time'],
                ];
            }

            return json([
                'code' => 0,
                'count' => count($data),
                'message' => lang('render_success'),
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error("获取模块数据失败: {$e->getMessage()}");
            return json([
                'code' => 1,
                'message' => lang('get_failed') . $e->getMessage()
            ]);
        }
    }

    public function install()
    {
        if (Request::isPost()) {
            try {
                $name = Request::param('name');
                $version = Request::param('version');
                if (empty($name) || empty($version)) {
                    return json(['code' => 400, 'msg' => '参数错误']);
                }

                $localModules = ModuleModel::column('name', 'name');
                $service = new UpgradeService();
                $installedModules = $service->getInstalledModules();
                $installedModules = array_column($installedModules, 'version', 'name');

                // 标记安装状态
                if (isset($localModules[$name]) || isset($installedModules[$name])) {
                    return json(['code' => 400, 'msg' => '模块已存在']);
                }

                // 1. 下载升级包
                $download = $service->download('module', $name, $version);

                if (!$download['success']) {
                    return json(['code' => 400, 'msg' => '下载失败: ' . $download['message']]);
                }

                // 2. 文件拷贝
                $result = $service->execute($download['log_id']);
                if ($result['code'] !== 200) {
                    return json($result);
                }

                // 3. 执行安装
                $moduleManager = ModuleManager::instance();

                $result = $moduleManager->installModule($name, '');

                return json($result);
            } catch (\Throwable $e) {
                return json(['code' => 400, 'msg' => $e->getMessage() . '|' . $e->getLine()]);
            } catch (\Exception $e) {
                return json(['code' => 400, 'msg' => $e->getMessage() . '|' . $e->getLine()]);
            }
        }
        return json(['code' => 400, 'msg' => '请使用POST请求']);
    }

    public function update()
    {
        if (Request::isPost()) {
            try {
                $file = Request::file('file');
                if (!$file) {
                    return json(['code' => 400, 'msg' => '请选择模块文件']);
                }

                $id = Request::param('id');
                if (!$id) {
                    return json(['code' => 400, 'msg' => '请选择要更新的模块']);
                }

                $module = ModuleModel::find($id);
                if (!$module) {
                    return json(['code' => 400, 'msg' => '模块不存在']);
                }

                if ($module->isEnabled()) {
                    return json(['code' => 400, 'msg' => '请先禁用模块再更新']);
                }

                $ext = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
                if ($ext !== 'zip') {
                    return json(['code' => 400, 'msg' => '请上传 zip 格式的模块文件']);
                }

                $tempPath = app()->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR;
                if (!is_dir($tempPath)) {
                    mkdir($tempPath, 0755, true);
                }

                $fileName = $file->getOriginalName();
                $savedFile = $file->move($tempPath, $fileName);

                if (!$savedFile) {
                    return json(['code' => 500, 'msg' => '文件上传失败']);
                }

                $sourcePath = $savedFile->getPathname();

                $zip = new \ZipArchive();
                if ($zip->open($sourcePath) !== true) {
                    unlink($sourcePath);
                    return json(['code' => 400, 'msg' => '打开压缩包失败']);
                }

                $moduleName = '';
                $moduleJsonPath = '';

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $fileInfo = $zip->getNameIndex($i);
                    if (strpos($fileInfo, 'module.json') !== false && substr($fileInfo, -12) === 'module.json') {
                        $moduleJsonPath = $fileInfo;
                        break;
                    }
                }

                if (empty($moduleJsonPath)) {
                    $zip->close();
                    unlink($sourcePath);
                    return json(['code' => 400, 'msg' => '模块配置文件不存在']);
                }

                $moduleJsonContent = '';
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $fileInfo = $zip->getNameIndex($i);
                    if ($fileInfo === $moduleJsonPath) {
                        $moduleJsonContent = $zip->getFromIndex($i);
                        break;
                    }
                }

                $zip->close();

                if (empty($moduleJsonContent)) {
                    unlink($sourcePath);
                    return json(['code' => 400, 'msg' => '模块配置文件读取失败']);
                }

                $config = json_decode($moduleJsonContent, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    unlink($sourcePath);
                    return json(['code' => 400, 'msg' => '模块配置文件格式错误']);
                }

                $newModuleName = $config['name'] ?? '';
                if (empty($newModuleName)) {
                    unlink($sourcePath);
                    return json(['code' => 400, 'msg' => '模块配置文件中缺少 name 字段']);
                }

                if ($newModuleName !== $module->name) {
                    unlink($sourcePath);
                    return json(['code' => 400, 'msg' => '模块名称不匹配，不能更新为其他模块']);
                }

                $moduleManager = ModuleManager::instance();
                $result = $moduleManager->updateModule($module->name, $sourcePath);

                unlink($sourcePath);

                return json($result);
            } catch (\Throwable $e) {
                return json(['code' => 400, 'msg' => $e->getMessage() . '|' . $e->getLine()]);
            } catch (\Exception $e) {
                return json(['code' => 400, 'msg' => $e->getMessage() . '|' . $e->getLine()]);
            }
        }
        $moduleManager = ModuleManager::instance();
        $modules = $moduleManager->getAllModules();
        return View::fetch('module/update', ['modules' => $modules]);
    }

    public function uninstall()
    {
        $id = Request::param('id');
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $module = ModuleModel::find($id);
        if (!$module) {
            return json(['code' => 400, 'msg' => '模块不存在']);
        }

        $moduleManager = ModuleManager::instance();
        $result = $moduleManager->uninstallModule($module->name);

        return json($result);
    }

    public function enable()
    {
        $id = Request::param('id');
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $module = ModuleModel::find($id);
        if (!$module) {
            return json(['code' => 400, 'msg' => '模块不存在']);
        }

        $moduleManager = ModuleManager::instance();
        $result = $moduleManager->enableModule($module->name);

        return json($result);
    }

    public function disable()
    {
        $id = Request::param('id');
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $module = ModuleModel::find($id);
        if (!$module) {
            return json(['code' => 400, 'msg' => '模块不存在']);
        }

        $moduleManager = ModuleManager::instance();
        $result = $moduleManager->disableModule($module->name);

        return json($result);
    }

    public function delete()
    {
        $id = Request::param('id');
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $module = ModuleModel::find($id);
        if (!$module) {
            return json(['code' => 400, 'msg' => '模块不存在']);
        }

        $moduleManager = ModuleManager::instance();
        $result = $moduleManager->deleteModule($module->name);

        return json($result);
    }

    public function setting()
    {
        $id = Request::param('id');
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $module = ModuleModel::find($id);
        if (!$module) {
            return json(['code' => 400, 'msg' => '模块不存在']);
        }

        $moduleInfo = $module->getModuleInfo();

        if (Request::isPost()) {
            $config = Request::post('config', []);
            $module->config = $config;
            $module->save();

            return json(['code' => 200, 'msg' => '设置保存成功']);
        }

        return View::fetch('module/setting', [
            'module' => $moduleInfo
        ]);
    }

    public function detail()
    {
        $id = Request::param('id');
        if (!$id) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }

        $module = ModuleModel::find($id);
        if (!$module) {
            return json(['code' => 400, 'msg' => '模块不存在']);
        }

        $moduleInfo = $module->getModuleInfo();

        return View::fetch('module/detail', [
            'module' => $moduleInfo
        ]);
    }

    public function refresh()
    {
        $moduleManager = ModuleManager::instance();
        $modules = $moduleManager->scanModules();

        foreach ($modules as $moduleName => $config) {
            $existingModule = ModuleModel::getByName($moduleName);
            if (!$existingModule) {
                $module = new ModuleModel();
                $module->name = $config['name'] ?? $moduleName;
                $module->title = $config['title'] ?? $moduleName;
                $module->description = $config['description'] ?? '';
                $module->version = $config['version'] ?? '1.0.0';
                $module->author = $config['author'] ?? '';
                $module->url = $config['url'] ?? '';
                $module->dependencies = $config['dependencies'] ?? [];
                $module->routes = $config['routes'] ?? [];
                $module->menus = $config['menus'] ?? [];
                $module->permissions = $config['permissions'] ?? [];
                $module->config = $config['config'] ?? [];
                $module->status = ModuleModel::STATUS_UNINSTALLED;
                $module->save();
            }
        }

        return json(['code' => 200, 'msg' => '模块刷新成功']);
    }

    // 模块市场
    public function market()
    {
        // 首次加载：获取模块列表并标记安装状态
        $modules = $this->getMarketModules();

        $moduleList = $modules['data'] ?? [];

        // 获取本地已安装的模块
        $localModules = ModuleModel::column('name', 'name');
        $service = new UpgradeService();
        $installedModules = $service->getInstalledModules();
        $installedModules = array_column($installedModules, 'version', 'name');
        // 标记安装状态
        foreach ($moduleList as &$module) {
            $moduleName = $module['name'] ?? '';
            if (isset($localModules[$moduleName]) && isset($installedModules[$moduleName])) {
                $localModule = ModuleModel::where('name', $moduleName)->find();
                $module['is_installed'] = true;
                $module['local_version'] = $localModule['version'] ?? '';
                $module['local_status'] = $localModule['status'] ?? '';
                // 检查是否有新版本
                $module['has_update'] = $this->checkModuleUpdate($moduleName, $module['version'] ?? '', $localModule['version'] ?? '');
            } else {
                $module['is_installed'] = false;
                $module['local_version'] = '';
                $module['local_status'] = '';
                $module['has_update'] = false;
            }
        }
        unset($module);

        return View::fetch('module/market', [
            'modules' => json_encode($moduleList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'moduleCount' => count($moduleList)
        ]);
    }

    /**
     * 获取市场模块列表
     * @param string|null $keyword 搜索关键词
     * @return array
     */
    protected function getMarketModules(?string $keyword = null): array
    {
        try {
            if ($keyword === null) {
                $keyword = Request::param('keyword/s', '');
            }

            $service = new UpgradeService();
            $result = $service->getModuleList(1, 100); // 不分页，最多获取100个

            $modules = [];
            if (!empty($result['data'])) {
                foreach ($result['data'] as $item) {
                    // 关键词过滤
                    if ($keyword) {
                        $name = strtolower($item['name'] ?? '');
                        $title = strtolower($item['title'] ?? '');
                        $search = strtolower($keyword);
                        if (strpos($name, $search) === false && strpos($title, $search) === false) {
                            continue;
                        }
                    }

                    $modules[] = [
                        'id' => $item['id'] ?? 0,
                        'name' => $item['name'] ?? '',
                        'title' => $item['title'] ?? $item['name'] ?? '',
                        'description' => $item['intro'] ?? '',
                        'version' => $item['version'] ?? '1.0.0',
                        'author' => $item['author'] ?? '',
                        'cover' => $item['image'] ?? '',
                        'price' => $item['price'] ?? 0,
                        'is_free' => $item['is_free'] ?? 0,
                        'platform' => $item['platform'] ?? 0,
                    ];
                }
            }

            return [
                'code' => 0,
                'count' => count($modules),
                'data' => $modules
            ];
        } catch (\Exception $e) {
            return [
                'code' => 1,
                'count' => 0,
                'data' => [],
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * 检查模块是否有更新
     */
    protected function checkModuleUpdate(string $moduleName, string $remoteVersion, string $localVersion): bool
    {
        if (empty($localVersion) || empty($remoteVersion)) {
            return false;
        }
        return version_compare($localVersion, $remoteVersion, '<');
    }
}
