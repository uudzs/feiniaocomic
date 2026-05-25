<?php

declare(strict_types=1);

namespace app\module\model;

use think\Model;
use think\facade\Db;

class Module extends Model
{
    protected $name = 'module';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = 0;
    protected $disuseTime = true;

    protected $type = [
        'config' => 'json',
        'routes' => 'json',
        'menus' => 'json',
        'dependencies' => 'json',
    ];

    const STATUS_INSTALLED = 'installed';
    const STATUS_ENABLED = 'enabled';
    const STATUS_DISABLED = 'disabled';
    const STATUS_UNINSTALLED = 'uninstalled';

    public static function getStatusList(): array
    {
        return [
            self::STATUS_INSTALLED => '已安装',
            self::STATUS_ENABLED => '已启用',
            self::STATUS_DISABLED => '已禁用',
            self::STATUS_UNINSTALLED => '未安装',
        ];
    }

    public function getStatusTextAttr($value, $data): string
    {
        return self::getStatusList()[$data['status']] ?? '未知';
    }

    public static function getByName(string $name): ?Module
    {
        return self::where('name', $name)->find();
    }

    public static function getEnabledModules(): array
    {
        return self::where('status', self::STATUS_ENABLED)->column('name');
    }

    public static function getInstalledModules(): array
    {
        return self::where('status', 'in', [self::STATUS_INSTALLED, self::STATUS_ENABLED])->column('name');
    }

    public function isInstalled(): bool
    {
        return in_array($this->status, [self::STATUS_INSTALLED, self::STATUS_ENABLED]);
    }

    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    public function markAsInstalled(): bool
    {
        return $this->save(['status' => self::STATUS_INSTALLED]);
    }

    public function markAsEnabled(): bool
    {
        return $this->save(['status' => self::STATUS_ENABLED]);
    }

    public function markAsDisabled(): bool
    {
        return $this->save(['status' => self::STATUS_DISABLED]);
    }

    public function markAsUninstalled(): bool
    {
        return $this->save(['status' => self::STATUS_UNINSTALLED]);
    }

    public function getModulePath(): string
    {
        return app()->getRootPath() . 'modules' . DIRECTORY_SEPARATOR . $this->name;
    }

    public function getModuleConfigPath(): string
    {
        return $this->getModulePath() . DIRECTORY_SEPARATOR . 'module.json';
    }

    public function getModuleInstallScriptPath(): string
    {
        return $this->getModulePath() . DIRECTORY_SEPARATOR . 'install.php';
    }

    public function getModuleUninstallScriptPath(): string
    {
        return $this->getModulePath() . DIRECTORY_SEPARATOR . 'uninstall.php';
    }

    public function getModuleRoutePath(): string
    {
        return $this->getModulePath() . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'app.php';
    }

    public function getModuleConfig(): ?array
    {
        $configPath = $this->getModuleConfigPath();
        if (!file_exists($configPath)) {
            return null;
        }

        $config = json_decode(file_get_contents($configPath), true);
        return json_last_error() === JSON_ERROR_NONE ? $config : null;
    }

    public function checkDependencies(): array
    {
        $errors = [];
        $dependencies = $this->dependencies ?? [];

        foreach ($dependencies as $dependency) {
            $module = self::getByName($dependency);
            if (!$module || !$module->isEnabled()) {
                $errors[] = "依赖模块 {$dependency} 未安装或未启用";
            }
        }

        return $errors;
    }

    public function checkConflicts(): array
    {
        $conflicts = [];
        $installedModules = self::getInstalledModules();

        foreach ($installedModules as $moduleName) {
            if ($moduleName === $this->name) {
                continue;
            }

            $module = self::getByName($moduleName);
            if ($module) {
                $moduleRoutes = $module->routes ?? [];
                $currentRoutes = $this->routes ?? [];

                foreach ($moduleRoutes as $route) {
                    foreach ($currentRoutes as $currentRoute) {
                        if (isset($route['path']) && isset($currentRoute['path']) && 
                            $route['path'] === $currentRoute['path']) {
                            $conflicts[] = "路由冲突: {$route['path']} 与模块 {$moduleName} 冲突";
                        }
                    }
                }
            }
        }

        return $conflicts;
    }

    public function getModuleInfo(): array
    {
        $config = $this->getModuleConfig();

        return [
            'id' => $this->id,
            'name' => $this->getData('name'),
            'title' => $config['title'] ?? $this->getData('title') ?? $this->getData('name'),
            'description' => $config['description'] ?? $this->getData('description') ?? '',
            'version' => $config['version'] ?? $this->getData('version') ?? '1.0.0',
            'author' => $config['author'] ?? $this->getData('author') ?? '',
            'url' => $config['url'] ?? $this->getData('url') ?? '',
            'status' => $this->status,
            'status_text' => $this->status_text,
            'dependencies' => $config['dependencies'] ?? $this->getData('dependencies') ?? [],
            'routes' => $config['routes'] ?? $this->getData('routes') ?? [],
            'menus' => $config['menus'] ?? $this->getData('menus') ?? [],
            'config' => $config['config'] ?? $this->getData('config') ?? [],
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
        ];
    }
}