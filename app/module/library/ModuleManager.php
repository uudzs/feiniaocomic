<?php

declare(strict_types=1);

namespace app\module\library;

use app\module\model\Module as ModuleModel;
use think\facade\Db;
use think\facade\Event;
use think\facade\Log;
use think\facade\Config;
use think\facade\Session;
use app\manage\controller\Auth;
use Exception;

class ModuleManager
{
    protected static ?ModuleManager $instance = null;

    protected string $modulesPath;

    protected function __construct()
    {
        $this->modulesPath = app()->getRootPath() . 'modules' . DIRECTORY_SEPARATOR;
    }

    public static function instance(): ModuleManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getModulesPath(): string
    {
        return $this->modulesPath;
    }

    public function scanModules(): array
    {
        $modules = [];
        if (!is_dir($this->modulesPath)) {
            return $modules;
        }

        $dirs = scandir($this->modulesPath);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..') {
                continue;
            }

            $modulePath = $this->modulesPath . $dir;
            if (is_dir($modulePath)) {
                $configPath = $modulePath . DIRECTORY_SEPARATOR . 'module.json';
                if (file_exists($configPath)) {
                    $config = json_decode(file_get_contents($configPath), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $modules[$dir] = $config;
                    }
                }
            }
        }

        return $modules;
    }

    public function installModule(string $moduleName, string $sourcePath = ''): array
    {
        try {
            // if (empty($sourcePath)) {
            //     $modulePath = $this->modulesPath . $moduleName;
            //     if (!is_dir($modulePath)) {
            //         return ['code' => 400, 'msg' => '模块目录不存在'];
            //     }
            // } else {
            //     $result = $this->extractModule($sourcePath, $moduleName);
            //     if ($result['code'] !== 200) {
            //         return $result;
            //     }
            // }

            $moduleConfigPath = $this->modulesPath . $moduleName . DIRECTORY_SEPARATOR . 'module.json';
            if (!file_exists($moduleConfigPath)) {
                return ['code' => 400, 'msg' => '模块配置文件不存在'];
            }

            $config = json_decode(file_get_contents($moduleConfigPath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['code' => 400, 'msg' => '模块配置文件格式错误'];
            }

            // 获取最终要使用的模块名
            $finalModuleName = $config['name'] ?? $moduleName;

            // 检查最终模块名的唯一性
            $existingModule = ModuleModel::getByName($finalModuleName);
            if ($existingModule) {
                return ['code' => 400, 'msg' => '模块已存在'];
            }

            $module = new ModuleModel();
            $module->name = $finalModuleName;
            $module->title = $config['title'] ?? $finalModuleName;
            $module->description = $config['description'] ?? '';
            $module->version = $config['version'] ?? '1.0.0';
            $module->author = $config['author'] ?? '';
            $module->rule = $config['rule'] ?? '';
            $module->dependencies = $config['dependencies'] ?? [];
            $module->routes = $config['routes'] ?? [];
            $module->menus = $config['menus'] ?? [];
            $module->config = $config['config'] ?? [];
            $module->status = ModuleModel::STATUS_ENABLED;
            $module->save();

            $this->runInstallScript($finalModuleName);

            MenuRegistrar::instance()->registerModuleMenus($finalModuleName);

            // 如果是超级管理员（admin_id=1）安装，自动给其用户组添加所有权限
            $this->autoGrantPermissionsToSuperAdmin($finalModuleName);
            $this->clearAuthCache();

            Event::trigger('module_installed', $finalModuleName);
            Log::info("模块 {$finalModuleName} 安装成功");
            return ['code' => 200, 'msg' => '模块安装成功', 'data' => $module->toArray()];
        } catch (Exception $e) {
            Log::error("模块安装失败: {$e->getMessage()}");
            return ['code' => 500, 'msg' => '模块安装失败: ' . $e->getMessage()];
        }
    }

    public function reinstallModule(string $moduleName, string $sourcePath = ''): array
    {
        try {
            if (empty($sourcePath)) {
                $modulePath = $this->modulesPath . $moduleName;
                if (!is_dir($modulePath)) {
                    return ['code' => 400, 'msg' => '模块目录不存在'];
                }
            } else {
                $result = $this->extractModule($sourcePath, $moduleName);
                if ($result['code'] !== 200) {
                    return $result;
                }
            }

            $moduleConfigPath = $this->modulesPath . $moduleName . DIRECTORY_SEPARATOR . 'module.json';
            if (!file_exists($moduleConfigPath)) {
                return ['code' => 400, 'msg' => '模块配置文件不存在'];
            }

            $config = json_decode(file_get_contents($moduleConfigPath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['code' => 400, 'msg' => '模块配置文件格式错误'];
            }

            $finalModuleName = $config['name'] ?? $moduleName;

            $module = ModuleModel::getByName($finalModuleName);
            if (!$module) {
                return ['code' => 400, 'msg' => '模块不存在'];
            }

            if ($module->status !== ModuleModel::STATUS_UNINSTALLED) {
                return ['code' => 400, 'msg' => '只能重新安装已卸载的模块'];
            }

            $module->title = $config['title'] ?? $finalModuleName;
            $module->description = $config['description'] ?? '';
            $module->version = $config['version'] ?? '1.0.0';
            $module->author = $config['author'] ?? '';
            $module->rule = $config['rule'] ?? '';
            $module->dependencies = $config['dependencies'] ?? [];
            $module->routes = $config['routes'] ?? [];
            $module->menus = $config['menus'] ?? [];
            $module->config = $config['config'] ?? [];
            $module->status = ModuleModel::STATUS_ENABLED;
            $module->save();

            $this->runInstallScript($finalModuleName);

            MenuRegistrar::instance()->registerModuleMenus($finalModuleName);

            $this->clearAuthCache();

            // 如果是超级管理员（admin_id=1）安装，自动给其用户组添加所有权限
            $this->autoGrantPermissionsToSuperAdmin($finalModuleName);

            Event::trigger('module_reinstalled', $finalModuleName);

            Log::info("模块 {$finalModuleName} 重新安装成功");
            return ['code' => 200, 'msg' => '模块重新安装成功', 'data' => $module->toArray()];
        } catch (Exception $e) {
            Log::error("模块重新安装失败: {$e->getMessage()}");
            return ['code' => 500, 'msg' => '模块重新安装失败: ' . $e->getMessage()];
        }
    }

    public function updateModule(string $moduleName, string $sourcePath): array
    {
        try {
            $module = ModuleModel::getByName($moduleName);
            if (!$module) {
                return ['code' => 400, 'msg' => '模块不存在'];
            }

            $wasEnabled = $module->isEnabled();

            if ($wasEnabled) {
                MenuRegistrar::instance()->unregisterModuleMenus($moduleName);
            }

            $result = $this->extractModule($sourcePath, $moduleName . '_temp');
            if ($result['code'] !== 200) {
                return $result;
            }

            $tempModulePath = $this->modulesPath . $moduleName . '_temp';
            $moduleConfigPath = $tempModulePath . DIRECTORY_SEPARATOR . 'module.json';
            if (!file_exists($moduleConfigPath)) {
                $this->deleteDirectory($tempModulePath);
                return ['code' => 400, 'msg' => '模块配置文件不存在'];
            }

            $config = json_decode(file_get_contents($moduleConfigPath), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->deleteDirectory($tempModulePath);
                return ['code' => 400, 'msg' => '模块配置文件格式错误'];
            }

            $oldModulePath = $this->modulesPath . $moduleName;
            if (is_dir($oldModulePath)) {
                $backupPath = $this->modulesPath . $moduleName . '_backup_' . date('YmdHis');
                rename($oldModulePath, $backupPath);
                Log::info("模块 {$moduleName} 备份到: {$backupPath}");
            }

            rename($tempModulePath, $oldModulePath);

            $module->title = $config['title'] ?? $moduleName;
            $module->description = $config['description'] ?? '';
            $module->version = $config['version'] ?? '1.0.0';
            $module->author = $config['author'] ?? '';
            $module->rule = $config['rule'] ?? '';
            $module->dependencies = $config['dependencies'] ?? [];
            $module->routes = $config['routes'] ?? [];
            $module->menus = $config['menus'] ?? [];
            $module->config = $config['config'] ?? [];
            $module->save();

            if ($wasEnabled) {
                MenuRegistrar::instance()->registerModuleMenus($moduleName);
                Log::info("模块 {$moduleName} 重新启用成功");
            }

            $this->clearAuthCache();

            Event::trigger('module_updated', $moduleName);

            Log::info("模块 {$moduleName} 更新成功");
            return ['code' => 200, 'msg' => '模块更新成功', 'data' => $module->toArray()];
        } catch (Exception $e) {
            Log::error("模块更新失败: {$e->getMessage()}");
            return ['code' => 500, 'msg' => '模块更新失败: ' . $e->getMessage()];
        }
    }

    protected function extractModule(string $sourcePath, string $moduleName): array
    {
        $targetPath = $this->modulesPath . $moduleName;
        if (is_dir($targetPath)) {
            return ['code' => 400, 'msg' => '模块目录已存在'];
        }

        if (!mkdir($targetPath, 0755, true)) {
            return ['code' => 500, 'msg' => '创建模块目录失败'];
        }

        if (is_file($sourcePath)) {
            $zip = new \ZipArchive();
            if ($zip->open($sourcePath) !== true) {
                return ['code' => 400, 'msg' => '打开压缩包失败'];
            }

            $zip->extractTo($targetPath);
            $zip->close();
        } elseif (is_dir($sourcePath)) {
            $this->copyDirectory($sourcePath, $targetPath);
        } else {
            return ['code' => 400, 'msg' => '无效的模块源'];
        }

        return ['code' => 200, 'msg' => '模块解压成功'];
    }

    protected function copyDirectory(string $source, string $target): void
    {
        if (!is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $targetPath = $target . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $targetPath);
            } else {
                copy($sourcePath, $targetPath);
            }
        }
    }

    protected function runInstallScript(string $moduleName): void
    {
        $installScript = $this->modulesPath . $moduleName . DIRECTORY_SEPARATOR . 'install.php';
        if (file_exists($installScript)) {
            include $installScript;
        }
    }

    public function uninstallModule(string $moduleName): array
    {
        try {
            $module = ModuleModel::getByName($moduleName);
            if (!$module) {
                return ['code' => 400, 'msg' => '模块不存在'];
            }

            if ($module->isEnabled()) {
                return ['code' => 400, 'msg' => '请先禁用模块'];
            }

            $this->runUninstallScript($moduleName);

            $module->markAsUninstalled();

            MenuRegistrar::instance()->unregisterModuleMenus($moduleName);

            $this->clearAuthCache();

            Event::trigger('module_uninstalled', $moduleName);

            Log::info("模块 {$moduleName} 卸载成功");
            return ['code' => 200, 'msg' => '模块卸载成功'];
        } catch (Exception $e) {
            Log::error("模块卸载失败: {$e->getMessage()}");
            return ['code' => 500, 'msg' => '模块卸载失败: ' . $e->getMessage()];
        }
    }

    protected function runUninstallScript(string $moduleName): void
    {
        $uninstallScript = $this->modulesPath . $moduleName . DIRECTORY_SEPARATOR . 'uninstall.php';
        if (file_exists($uninstallScript)) {
            include $uninstallScript;
        }
    }

    public function enableModule(string $moduleName): array
    {
        try {
            $module = ModuleModel::getByName($moduleName);
            if (!$module) {
                return ['code' => 400, 'msg' => '模块不存在'];
            }

            if ($module->isEnabled()) {
                return ['code' => 400, 'msg' => '模块已启用'];
            }

            $dependencyErrors = $module->checkDependencies();
            if (!empty($dependencyErrors)) {
                return ['code' => 400, 'msg' => '依赖检查失败: ' . implode(', ', $dependencyErrors)];
            }

            $conflictErrors = $module->checkConflicts();
            if (!empty($conflictErrors)) {
                return ['code' => 400, 'msg' => '冲突检查失败: ' . implode(', ', $conflictErrors)];
            }

            $module->markAsEnabled();

            $this->clearAuthCache();

            Event::trigger('module_enabled', $moduleName);

            Log::info("模块 {$moduleName} 启用成功");
            return ['code' => 200, 'msg' => '模块启用成功'];
        } catch (Exception $e) {
            Log::error("模块启用失败: {$e->getMessage()}");
            return ['code' => 500, 'msg' => '模块启用失败: ' . $e->getMessage()];
        }
    }

    public function disableModule(string $moduleName): array
    {
        try {
            $module = ModuleModel::getByName($moduleName);
            if (!$module) {
                return ['code' => 400, 'msg' => '模块不存在'];
            }

            if (!$module->isEnabled()) {
                return ['code' => 400, 'msg' => '模块未启用'];
            }

            $module->markAsDisabled();

            $this->clearAuthCache();

            Event::trigger('module_disabled', $moduleName);

            Log::info("模块 {$moduleName} 禁用成功");
            return ['code' => 200, 'msg' => '模块禁用成功'];
        } catch (Exception $e) {
            Log::error("模块禁用失败: {$e->getMessage()}");
            return ['code' => 500, 'msg' => '模块禁用失败: ' . $e->getMessage()];
        }
    }

    public function deleteModule(string $moduleName): array
    {
        try {
            $module = ModuleModel::getByName($moduleName);
            if (!$module) {
                return ['code' => 400, 'msg' => '模块不存在'];
            }

            if ($module->isInstalled()) {
                return ['code' => 400, 'msg' => '请先卸载模块'];
            }

            $modulePath = $this->modulesPath . $moduleName;
            if (is_dir($modulePath)) {
                $this->deleteDirectory($modulePath);
            }

            $module->delete();

            Event::trigger('module_deleted', $moduleName);

            Log::info("模块 {$moduleName} 删除成功");
            return ['code' => 200, 'msg' => '模块删除成功'];
        } catch (Exception $e) {
            Log::error("模块删除失败: {$e->getMessage()}");
            return ['code' => 500, 'msg' => '模块删除失败: ' . $e->getMessage()];
        }
    }

    protected function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    protected function clearAuthCache(): void
    {
        try {
            Auth::reloadAuth();
            Log::info("权限已更新");
        } catch (\Exception $e) {
            Log::error("权限更新失败: {$e->getMessage()}");
        }
    }

    /**
     * 自动给超级管理员授权新安装模块的权限
     * @param string $moduleName 模块名称
     * @return void
     */
    protected function autoGrantPermissionsToSuperAdmin(string $moduleName): void
    {
        try {
            // 获取当前登录的管理员ID
            $adminId = Session::get('admin_id', 0);

            // 只处理超级管理员（ID=1）
            if ($adminId != 1) {
                Log::info("当前管理员非超级管理员，跳过自动授权");
                return;
            }

            // 获取超级管理员的用户组ID
            $adminGid = Session::get('admin_gid', 0);
            if (empty($adminGid)) {
                Log::warning("超级管理员未设置用户组，无法自动授权");
                return;
            }

            // 查找超级管理员用户组
            $authGroupModel = new \app\common\model\manage\AuthGroup();
            $authGroup = $authGroupModel->where('id', $adminGid)->find();

            if (!$authGroup) {
                Log::warning("用户组不存在: {$adminGid}");
                return;
            }

            // 获取该模块的所有权限规则ID
            $authRuleModel = new \app\common\model\manage\AuthRule();
            $moduleRules = $authRuleModel->where('module', $moduleName)
                ->where('status', 1)
                ->column('id');

            if (empty($moduleRules)) {
                Log::info("模块 {$moduleName} 没有权限规则");
                return;
            }

            // 获取用户组当前的所有权限
            $currentRules = $authGroup->rules ? explode(',', trim($authGroup->rules, ',')) : [];
            $currentRules = array_filter(array_map('intval', $currentRules));

            // 合并新旧权限ID
            $newRules = array_unique(array_merge($currentRules, $moduleRules));

            // 排序并更新权限
            sort($newRules);
            $authGroup->rules = implode(',', $newRules);
            $authGroup->save();

            Log::info("已自动给超级管理员用户组 {$adminGid} 授权模块 {$moduleName} 的权限", [
                'module' => $moduleName,
                'group_id' => $adminGid,
                'new_rules_count' => count($newRules),
                'added_rules' => $moduleRules
            ]);
        } catch (\Exception $e) {
            Log::error("自动授权失败: {$e->getMessage()}");
        }
    }

    public function getModuleInfo(string $moduleName): ?array
    {
        $module = ModuleModel::getByName($moduleName);
        if (!$module) {
            return null;
        }

        return $module->getModuleInfo();
    }

    public function getAllModules(): array
    {
        $modules = ModuleModel::select()->toArray();
        $result = [];

        foreach ($modules as $module) {
            $moduleModel = ModuleModel::find($module['id']);
            if ($moduleModel) {
                $result[] = $moduleModel->getModuleInfo();
            }
        }

        return $result;
    }

    public function getEnabledModules(): array
    {
        $moduleNames = ModuleModel::getEnabledModules();
        $result = [];

        foreach ($moduleNames as $moduleName) {
            $info = $this->getModuleInfo($moduleName);
            if ($info) {
                $result[] = $info;
            }
        }

        return $result;
    }

    public function getInstalledModules(): array
    {
        $moduleNames = ModuleModel::getInstalledModules();
        $result = [];

        foreach ($moduleNames as $moduleName) {
            $info = $this->getModuleInfo($moduleName);
            if ($info) {
                $result[] = $info;
            }
        }

        return $result;
    }
}
