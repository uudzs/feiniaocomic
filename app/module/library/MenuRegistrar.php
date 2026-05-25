<?php

declare(strict_types=1);

namespace app\module\library;

use think\facade\Db;
use think\facade\Log;
use app\module\model\Module as ModuleModel;
use app\common\model\manage\AuthRule;

class MenuRegistrar
{
    protected static ?MenuRegistrar $instance = null;

    protected array $registeredMenus = [];

    protected function __construct() {}

    public static function instance(): MenuRegistrar
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function registerModuleMenus(string $moduleName): bool
    {
        try {
            $module = ModuleModel::getByName($moduleName);
            if (!$module) {
                Log::error("模块不存在: {$moduleName}");
                return false;
            }

            $menus = $module->menus ?? [];
            if (empty($menus)) {
                Log::info("模块 {$moduleName} 没有定义菜单");
                return true;
            }

            foreach ($menus as $menuType => $menuList) {
                foreach ($menuList as $menu) {
                    $this->registerMenu($moduleName, $menuType, $menu);
                }
            }

            Log::info("模块 {$moduleName} 菜单注册成功");
            return true;
        } catch (\Exception $e) {
            Log::error("模块 {$moduleName} 菜单注册失败: {$e->getMessage()}");
            return false;
        }
    }

    protected function registerMenu(string $moduleName, string $menuType, array $menu): void
    {
        $title = $menu['title'] ?? '';
        $icon = $menu['icon'] ?? '';
        $rule = $menu['rule'] ?? '';
        $permission = $menu['permission'] ?? '';
        $sort = $menu['sort'] ?? 0;
        $show = $menu['show'] ?? 1;
        $children = $menu['children'] ?? [];

        if (empty($title)) {
            Log::warning("模块 {$moduleName} 菜单标题为空");
            return;
        }

        $menuName = $rule ?: $title;

        $existingMenu = AuthRule::where('name', $menuName)->find();

        if ($existingMenu) {
            $existingMenu->title = $title;
            $existingMenu->icon = $icon;
            $existingMenu->rule = $rule;
            $existingMenu->sort = $sort;
            $existingMenu->show = $show;
            $existingMenu->module = $moduleName;
            $existingMenu->type = empty($rule) ? 0 : 1;
            $existingMenu->status = 1;
            $existingMenu->save();
        } else {
            $newMenu = new AuthRule();
            $newMenu->name = $menuName;
            $newMenu->title = $title;
            $newMenu->icon = $icon;
            $newMenu->rule = $rule;
            $newMenu->sort = $sort;
            $newMenu->show = $show;
            $newMenu->module = $moduleName;
            $newMenu->pid = 0;
            $newMenu->type = empty($rule) ? 0 : 1;
            $newMenu->status = 1;
            $newMenu->save();
        }

        $this->registeredMenus[$menuName] = [
            'module' => $moduleName,
            'type' => $menuType,
            'title' => $title,
            'rule' => $rule,
            'permission' => $permission,
        ];

        if (!empty($children)) {
            $parentMenu = AuthRule::where('name', $menuName)->find();
            if ($parentMenu) {
                foreach ($children as $child) {
                    if (!empty($child['rule'])) {
                        $this->registerChildMenu($moduleName, $menuType, $child, $parentMenu->id);
                    }
                }
            }
        }
    }

    protected function registerChildMenu(string $moduleName, string $menuType, array $menu, int $pid): void
    {
        $title = $menu['title'] ?? '';
        $icon = $menu['icon'] ?? '';
        $rule = $menu['rule'] ?? '';
        $sort = $menu['sort'] ?? 0;
        $show = $menu['show'] ?? 1;
        $children = $menu['children'] ?? [];

        if (empty($title)) {
            Log::warning("模块 {$moduleName} 子菜单标题为空");
            return;
        }

        $menuName = $rule ?: $title;

        $existingMenu = AuthRule::where('name', $menuName)->find();

        if ($existingMenu) {
            $existingMenu->title = $title;
            $existingMenu->icon = $icon;
            $existingMenu->rule = $rule;
            $existingMenu->sort = $sort;
            $existingMenu->show = $show;
            $existingMenu->module = $moduleName;
            $existingMenu->pid = $pid;
            $existingMenu->type = empty($rule) ? 0 : 1;
            $existingMenu->status = 1;
            $existingMenu->save();
            $currentMenuId = $existingMenu->id;
        } else {
            $newMenu = new AuthRule();
            $newMenu->name = $menuName;
            $newMenu->title = $title;
            $newMenu->icon = $icon;
            $newMenu->rule = $rule;
            $newMenu->sort = $sort;
            $newMenu->show = $show;
            $newMenu->module = $moduleName;
            $newMenu->pid = $pid;
            $newMenu->type = empty($rule) ? 0 : 1;
            $newMenu->status = 1;
            $newMenu->save();
            $currentMenuId = $newMenu->id;
        }

        $this->registeredMenus[$menuName] = [
            'module' => $moduleName,
            'type' => $menuType,
            'title' => $title,
            'rule' => $rule,
            'pid' => $pid,
        ];

        // 递归处理子菜单
        if (!empty($children)) {
            foreach ($children as $child) {
                if (!empty($child['rule'])) {
                    $this->registerChildMenu($moduleName, $menuType, $child, $currentMenuId);
                }
            }
        }
    }

    public function unregisterModuleMenus(string $moduleName): bool
    {
        try {
            AuthRule::where('module', $moduleName)->delete();
            Log::info("模块 {$moduleName} 菜单注销成功");
            return true;
        } catch (\Exception $e) {
            Log::error("模块 {$moduleName} 菜单注销失败: {$e->getMessage()}");
            return false;
        }
    }

    public function getRegisteredMenus(): array
    {
        return $this->registeredMenus;
    }

    public function getModuleMenus(string $moduleName): array
    {
        $moduleMenus = [];

        foreach ($this->registeredMenus as $menuName => $menuInfo) {
            if ($menuInfo['module'] === $moduleName) {
                $moduleMenus[$menuName] = $menuInfo;
            }
        }

        return $moduleMenus;
    }

    public function getMenuByPermission(string $permission): ?array
    {
        foreach ($this->registeredMenus as $menuInfo) {
            if ($menuInfo['permission'] === $permission) {
                return $menuInfo;
            }
        }

        return null;
    }

    public function registerAllModuleMenus(): void
    {
        $enabledModules = ModuleModel::getEnabledModules();

        foreach ($enabledModules as $moduleName) {
            $this->registerModuleMenus($moduleName);
        }

        Log::info('所有模块菜单注册完成', ['modules' => $enabledModules]);
    }

    public function unregisterAllModuleMenus(): void
    {
        AuthRule::where('module', '<>', '')->delete();
        $this->registeredMenus = [];
        Log::info('所有模块菜单注销完成');
    }

    public function getMenusByType(string $menuType): array
    {
        $menus = [];

        foreach ($this->registeredMenus as $menuName => $menuInfo) {
            if ($menuInfo['type'] === $menuType) {
                $menus[$menuName] = $menuInfo;
            }
        }

        return $menus;
    }

    public function getAdminMenus(): array
    {
        return $this->getMenusByType('admin');
    }

    public function getFrontendMenus(): array
    {
        return $this->getMenusByType('frontend');
    }
}
