<?php

use think\facade\View;

if (!function_exists('furl')) {
    function furl(string $url, array $vars = [], bool $absolute = false): string
    {
        // 生成 URL（可选择相对或绝对）
        $fullUrl = $absolute ? url($url, $vars)->domain(true)->build() : url($url, $vars)->build();

        // 解析 URL
        $parts = parse_url($fullUrl);
        $path = $parts['path'] ?? '';

        // 去掉路径中第一级应用名（例如 /index/xxx 变为 /xxx）
        $appName = 'index';
        $search = '/' . $appName . '/';
        if (strpos($path, $search) === 0) {
            $newPath = substr($path, strlen($appName) + 1);
            // 如果去掉后为空，则设为 '/'
            if ($newPath === '') $newPath = '/';
        } else {
            $newPath = $path;
        }

        // 重新组合 URL
        $newUrl = '';
        if (isset($parts['scheme'])) $newUrl .= $parts['scheme'] . '://';
        if (isset($parts['host'])) $newUrl .= $parts['host'];
        if (isset($parts['port'])) $newUrl .= ':' . $parts['port'];
        $newUrl .= $newPath;
        if (isset($parts['query'])) $newUrl .= '?' . $parts['query'];
        if (isset($parts['fragment'])) $newUrl .= '#' . $parts['fragment'];

        return $newUrl;
    }
}

if (!function_exists('module_installed')) {
    /**
     * 检查模块是否已安装
     * 通过 module 表和 module.json 双重验证
     * @param string $module 模块名称
     * @param bool $checkEnabled 是否检查已启用（默认只检查已安装）
     * @return bool
     */
    function module_installed(string $module, bool $checkEnabled = false): bool
    {
        // 静态缓存，避免重复查询
        static $cache = [];

        $cacheKey = $module . '_' . ($checkEnabled ? 'enabled' : 'installed');
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        try {
            // 检查 module.json 是否存在
            $moduleJsonPath = app()->getBasePath() . '../modules/' . $module . '/module.json';
            if (!file_exists($moduleJsonPath)) {
                $cache[$cacheKey] = false;
                return false;
            }

            // 检查数据库记录
            $moduleModel = \app\module\model\Module::getByName($module);
            if (!$moduleModel) {
                $cache[$cacheKey] = false;
                return false;
            }

            // 检查状态
            $status = $checkEnabled ? \app\module\model\Module::STATUS_ENABLED
                : [\app\module\model\Module::STATUS_INSTALLED, \app\module\model\Module::STATUS_ENABLED];

            $result = in_array($moduleModel->status, (array)$status);
            $cache[$cacheKey] = $result;
            return $result;
        } catch (\Exception $e) {
            \think\facade\Log::error("检查模块 {$module} 安装状态失败: " . $e->getMessage());
            $cache[$cacheKey] = false;
            return false;
        }
    }
}

if (!function_exists('module_enabled')) {
    /**
     * 检查模块是否已启用
     * @param string $module 模块名称
     * @return bool
     */
    function module_enabled(string $module): bool
    {
        return module_installed($module, true);
    }
}

if (!function_exists('module_config')) {
    /**
     * 获取模块配置（从 module.json）
     * @param string $module 模块名称
     * @return array|null
     */
    function module_config(string $module): ?array
    {
        // 检查模块是否已安装
        if (!module_installed($module)) {
            return null;
        }

        $moduleJsonPath = app()->getBasePath() . '../modules/' . $module . '/module.json';
        if (!file_exists($moduleJsonPath)) {
            return null;
        }

        $config = json_decode(file_get_contents($moduleJsonPath), true);
        return json_last_error() === JSON_ERROR_NONE ? $config : null;
    }
}

if (!function_exists('module_service')) {
    /**
     * 获取模块服务
     * @param string $module 模块名称
     * @param string $service 服务类名
     * @param mixed ...$args 构造函数参数
     * @return object|null
     */
    function module_service(string $module, string $service, ...$args): ?object
    {
        // 检查模块是否已安装
        if (!module_installed($module)) {
            \think\facade\Log::warning("模块 {$module} 未安装，无法获取服务 {$service}");
            return null;
        }

        $className = "\\module\\{$module}\\service\\{$service}";
        if (!class_exists($className)) {
            \think\facade\Log::warning("服务类 {$className} 不存在");
            return null;
        }

        try {
            if (empty($args)) {
                return app($className);
            }
            // 如果有参数，直接实例化
            $reflection = new \ReflectionClass($className);
            return $reflection->newInstanceArgs($args);
        } catch (\Exception $e) {
            \think\facade\Log::error("获取模块服务失败: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('module_call')) {
    /**
     * 动态调用模块服务方法
     * @param string $module 模块名称
     * @param string $service 服务类名
     * @param string $method 方法名
     * @param mixed ...$args 参数
     * @return mixed
     */
    function module_call(string $module, string $service, string $method, ...$args): mixed
    {
        // 检查模块是否已安装
        if (!module_installed($module)) {
            \think\facade\Log::warning("模块 {$module} 未安装，无法调用方法 {$method}");
            return null;
        }

        $serviceObj = module_service($module, $service);
        if ($serviceObj && method_exists($serviceObj, $method)) {
            try {
                return $serviceObj->$method(...$args);
            } catch (\Exception $e) {
                \think\facade\Log::error("调用模块方法失败: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }
}

if (!function_exists('module_alias')) {
    /**
     * 通过别名获取模块服务实例
     * 从 module.json 读取服务别名
     * @param string $module 模块名称
     * @param string $alias 服务别名
     * @return object|null
     */
    function module_alias(string $module, string $alias): ?object
    {
        // 检查模块是否已安装
        if (!module_installed($module)) {
            \think\facade\Log::warning("模块 {$module} 未安装，无法通过别名 {$alias} 获取服务");
            return null;
        }

        $config = module_config($module);
        if (!$config || empty($config['services'])) {
            return null;
        }

        foreach ($config['services'] as $serviceName => $serviceConfig) {
            if (isset($serviceConfig['alias']) && $serviceConfig['alias'] === $alias) {
                return module_service($module, $serviceName);
            }
        }

        return null;
    }
}

if (!function_exists('module_invoke')) {
    /**
     * 通过别名动态调用模块服务方法
     * @param string $module 模块名称
     * @param string $alias 服务别名
     * @param string $method 方法名
     * @param mixed ...$args 参数
     * @return mixed
     */
    function module_invoke(string $module, string $alias, string $method, ...$args): mixed
    {
        // 检查模块是否已安装
        if (!module_installed($module)) {
            \think\facade\Log::warning("模块 {$module} 未安装，无法通过别名 {$alias} 调用方法 {$method}");
            return null;
        }

        $service = module_alias($module, $alias);
        if ($service && method_exists($service, $method)) {
            try {
                return $service->$method(...$args);
            } catch (\Exception $e) {
                \think\facade\Log::error("通过别名调用模块方法失败: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }
}

if (!function_exists('module_event')) {
    /**
     * 触发模块事件
     * @param string $event 事件名称
     * @param mixed $data 事件数据
     * @return mixed
     */
    function module_event(string $event, $data = null): mixed
    {
        return \think\facade\Event::trigger($event, $data);
    }
}

if (!function_exists('module_listen')) {
    /**
     * 监听模块事件
     * @param string $event 事件名称
     * @param callable $listener 监听器
     * @return void
     */
    function module_listen(string $event, callable $listener): void
    {
        \think\facade\Event::listen($event, $listener);
    }
}

if (!function_exists('__')) {
    /**
     * 魔术方法动态调用模块服务
     * 使用 __('storage') 获取服务，然后链式调用方法
     * 或直接 __('storage.upload', $file, $path) 调用方法
     * @param string $expression 模块.服务[.方法] 或 模块.别名[.方法]
     * @return mixed
     */
    function __(string $expression): mixed
    {
        $parts = explode('.', $expression);

        if (count($parts) >= 1) {
            $module = $parts[0];
            // 检查模块是否已安装
            if (!module_installed($module)) {
                \think\facade\Log::warning("模块 {$module} 未安装，无法调用服务");
                return null;
            }
        }

        if (count($parts) === 2) {
            // __('storage') - 返回 StorageService
            // __('module.service') - 返回服务实例
            list($module, $serviceOrAlias) = $parts;
            return module_alias($module, $serviceOrAlias) ?? module_service($module, $serviceOrAlias);
        }

        if (count($parts) >= 3) {
            // __('storage.upload', $file, $path) - 动态调用方法
            list($module, $serviceOrAlias, $method) = $parts;
            $args = array_slice(func_get_args(), 1);
            return module_invoke($module, $serviceOrAlias, $method, ...$args);
        }

        return null;
    }
}

if (!function_exists('setViewPath')) {
    function setViewPath(): void
    {
        $theme    = config('site.theme', 'default');
        $viewPath = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR;

        // 确保视图目录存在
        if (!is_dir($viewPath)) {
            // 如果主题目录不存在，使用 default 主题
            $theme    = 'default';
            $viewPath = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR;
        }

        View::config(['view_path' => $viewPath]);

        // 从数据库读取配置
        $dbConfig = [];
        $config = app\common\model\manage\Conf::cache(3600)->withAttr(['model' => function ($value) {
            return $value;
        }])->where('status', 1)->order('sort asc')->select()->toArray();
        if ($config) {
            $dbConfig =  array_column($config, 'value', 'ename');
        }

        // 合并 config/site.php 中的主题配置（文件配置优先，避免覆盖数据库配置）
        $siteConfig = config('site');
        $siteConfig = array_merge($siteConfig, $dbConfig);

        View::assign([
            'globalConfig' => $config,
            'ConfRes' => $siteConfig,
        ]);
    }
}

if (!function_exists('module_view')) {
    /**
     * 多主题视图回退查找
     * 优先级：template/{theme}/模块名/ > template/{theme}/common/ > modules/模块/view/
     *
     * @param string $template   模板文件名（不含后缀）
     * @param array  $data       模板变量
     * @param string $moduleName 模块名（不传则自动从当前控制器推断）
     * @return string
     * @throws \think\Exception
     */
    function module_view(string $template, array $data = [], string $moduleName = ''): string
    {
        if ($moduleName === '') {
            // 从当前控制器名推断模块名（如 module\comic\frontend\controller\Index -> comic）
            $class = request()->controller(true);
            // 尝试从已注册的模块中查找
            $moduleName = $class;
        }

        $theme  = config('site.theme', 'default');
        $root   = app()->getRootPath();

        // 按优先级查找视图文件
        $paths = [
            $root . "template/{$theme}/{$moduleName}/{$template}.html",
            $root . "modules/{$moduleName}/frontend/view/{$template}.html",
            $root . "template/{$theme}/common/{$template}.html",
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return View::fetch($path, $data);
            }
        }

        throw new \think\Exception("模板文件不存在：{$template}");
    }
}

if (!function_exists('theme_asset')) {
    /**
     * 生成多主题下的静态资源 URL
     * 优先级：template/{theme}/static/ > modules/{module}/frontend/static/
     *
     * @param string $path       资源相对路径（如 css/style.css）
     * @param string $moduleName 模块名（可选，传入时额外查找模块静态资源）
     * @return string
     */
    function theme_asset(string $path, string $moduleName = ''): string
    {
        $theme = config('site.theme', 'default');

        // 1. 模块静态资源
        if ($moduleName !== '') {
            $publicModuleAsset = "static" . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . $path;
            if (file_exists(app()->getRootPath() . "public" . DIRECTORY_SEPARATOR  . $publicModuleAsset)) {
                return DIRECTORY_SEPARATOR . $publicModuleAsset;
            }
        }

        // 2. 当前主题下静态资源        
        $themeAsset = "static" . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . $path;
        if (file_exists(app()->getRootPath() . "public" . DIRECTORY_SEPARATOR . $themeAsset)) {
            return DIRECTORY_SEPARATOR . $themeAsset;
        }

        // 2. 返回原路径
        return DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('theme_styles')) {
    /**
     * 生成主题 CSS 变量（在模板 <head> 中调用）
     * 浅色模式变量定义在 :root 中，深色模式变量定义在 .dark-theme 中
     *
     * @return string <style> 标签内容
     */
    function theme_styles(): string
    {
        $siteConfig = config('site');
        $lightVars = [];
        $darkVars  = [];
        $primaryColor = null;

        foreach ($siteConfig as $key => $value) {
            if (!is_string($value) && !is_numeric($value)) {
                continue;
            }

            // 浅色模式颜色
            if (strpos($key, 'color_') === 0 && strpos($key, 'dark_color_') !== 0) {
                $cssVar = '--' . str_replace('_', '-', $key);
                $lightVars[] = "    {$cssVar}: {$value};";
                // 记录主色值用于生成 RGB 版本
                if ($key === 'color_primary') {
                    $primaryColor = $value;
                }
            }
            // 深色模式颜色
            elseif (strpos($key, 'dark_color_') === 0) {
                $cssVar = '--color-' . str_replace('_', '-', substr($key, 11));
                $darkVars[] = "    {$cssVar}: {$value};";
            }
            // 布局和排版（深浅模式共用）
            elseif (strpos($key, 'layout_') === 0 || strpos($key, 'typo_') === 0) {
                $cssVar = '--' . str_replace('_', '-', $key);
                $lightVars[] = "    {$cssVar}: {$value};";
                $darkVars[]  = "    {$cssVar}: {$value};";
            }
            // 效果配置（深浅模式共用）
            elseif (strpos($key, 'effect_') === 0) {
                $cssVar = '--' . str_replace('_', '-', $key);
                $lightVars[] = "    {$cssVar}: {$value};";
                $darkVars[]  = "    {$cssVar}: {$value};";
            }
        }

        // 添加颜色的 RGB 版本（用于半透明效果）
        $rgbColors = [
            'color_primary' => 'color-primary-rgb',
            'color_success' => 'color-success-rgb',
            'color_warning' => 'color-warning-rgb',
            'color_danger'  => 'color-danger-rgb',
            'color_info'    => 'color-info-rgb',
        ];

        foreach ($rgbColors as $configKey => $cssVarName) {
            if (!empty($siteConfig[$configKey])) {
                $rgb = hexToRgb($siteConfig[$configKey]);
                if ($rgb) {
                    $lightVars[] = "    --{$cssVarName}: {$rgb};";
                    $darkVars[]  = "    --{$cssVarName}: {$rgb};";
                }
            }
        }

        $css = '<style>' . PHP_EOL;
        if (!empty($lightVars)) {
            $css .= ':root {' . PHP_EOL . implode(PHP_EOL, $lightVars) . PHP_EOL . '}' . PHP_EOL;
        }
        if (!empty($darkVars)) {
            $css .= '.dark-theme {' . PHP_EOL . implode(PHP_EOL, $darkVars) . PHP_EOL . '}' . PHP_EOL;
        }
        $css .= '</style>';

        return $css;
    }
}

if (!function_exists('hexToRgb')) {
    /**
     * 将十六进制颜色转换为 RGB 格式
     * @param string $hex 十六进制颜色值（如 #0078d4 或 #fff）
     * @return string|null RGB 值（如 "0, 120, 212"）
     */
    function hexToRgb(string $hex): ?string
    {
        // 移除 # 前缀
        $hex = ltrim($hex, '#');

        // 处理简写格式（如 #fff）
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        // 验证格式
        if (!preg_match('/^[a-fA-F0-9]{6}$/', $hex)) {
            return null;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }
}

if (!function_exists('get_seo')) {
    /**
     * 获取SEO渲染结果
     * 用于控制器和模板中获取SEO信息
     *
     * @param string $module 模块名称（如 'comic', 'user'）
     * @param string $pageKey 页面标识（如 'comic_index', 'comic_detail'）
     * @param array $data 变量数据（如 ['comic_title' => 'xxx', 'comic_author' => 'xxx']）
     * @param string $type 返回类型：'array'返回数组，'string'返回meta标签字符串
     * @return array|string
     *
     * 使用示例：
     *   // 模板中使用
     *   {get_seo('comic', 'comic_index')}
     *   {get_seo('comic', 'comic_detail', ['comic_title' => '航海王', 'comic_author' => '尾田荣一郎'])}
     *
     *   // 控制器中使用
     *   $seo = get_seo('comic', 'comic_detail', $comicData);
     *   $this->assign('title', $seo['title']);
     */
    function get_seo(string $module, string $pageKey, array $data = [], string $type = 'array'): array|string
    {
        // 检查模块是否已安装
        if (!module_installed($module)) {
            return $type === 'string' ? '' : ['title' => '', 'keywords' => '', 'description' => ''];
        }

        // 尝试加载模块的 SeoService
        $serviceClass = "\\module\\{$module}\\seo\\SeoService";

        if (!class_exists($serviceClass)) {
            // 如果没有 SeoService，尝试通用处理
            return get_seo_fallback($module, $pageKey, $data, $type);
        }

        try {
            // 调用 SeoService 渲染
            $result = $serviceClass::render($pageKey, $data);

            if ($type === 'string') {
                return seo_to_meta_string($result);
            }

            return $result;
        } catch (\Throwable $e) {
            \think\facade\Log::error("SEO渲染失败 [{$module}][{$pageKey}]: " . $e->getMessage());
            return $type === 'string' ? '' : ['title' => '', 'keywords' => '', 'description' => ''];
        }
    }
}

if (!function_exists('get_seo_fallback')) {
    /**
     * SEO通用回退处理（当模块没有 SeoService 时使用）
     * @param string $module
     * @param string $pageKey
     * @param array $data
     * @param string $type
     * @return array|string
     */
    function get_seo_fallback(string $module, string $pageKey, array $data = [], string $type = 'array'): array|string
    {
        // 尝试从数据库直接读取模板
        try {
            $templates = \app\common\model\manage\SeoPage::where('module', $module)
                ->where('page_key', $pageKey)
                ->where('status', 1)
                ->find();

            if (!$templates) {
                return $type === 'string' ? '' : ['title' => '', 'keywords' => '', 'description' => ''];
            }

            // 合并公共变量
            $siteName = \app\common\model\manage\Conf::cache(3600)
                ->where('status', 1)
                ->where('ename', 'sitename')
                ->value('value') ?? '网站';

            $commonVars = [
                'site_name'    => $siteName,
                'site_domain'  => \think\facade\Request::domain(),
                'current_year' => date('Y'),
            ];

            $data = array_merge($commonVars, $data);

            // 替换变量
            $result = [
                'title'       => preg_replace_callback('/\{(\w+)\}/', fn($m) => $data[$m[1]] ?? '', $templates->title ?? ''),
                'keywords'    => preg_replace_callback('/\{(\w+)\}/', fn($m) => $data[$m[1]] ?? '', $templates->keywords ?? ''),
                'description' => preg_replace_callback('/\{(\w+)\}/', fn($m) => $data[$m[1]] ?? '', $templates->description ?? ''),
            ];

            if ($type === 'string') {
                return seo_to_meta_string($result);
            }

            return $result;
        } catch (\Throwable $e) {
            return $type === 'string' ? '' : ['title' => '', 'keywords' => '', 'description' => ''];
        }
    }
}

if (!function_exists('seo_to_meta_string')) {
    /**
     * 将SEO数组转换为 meta 标签字符串
     * @param array $seo SEO数组 ['title' => '', 'keywords' => '', 'description' => '']
     * @return string
     */
    function seo_to_meta_string(array $seo): string
    {
        $html = '';

        if (!empty($seo['title'])) {
            $title = htmlspecialchars($seo['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $html .= "<title>{$title}</title>\n";
        }

        if (!empty($seo['keywords'])) {
            $keywords = htmlspecialchars($seo['keywords'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $html .= "<meta name=\"keywords\" content=\"{$keywords}\">\n";
        }

        if (!empty($seo['description'])) {
            $description = htmlspecialchars($seo['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $html .= "<meta name=\"description\" content=\"{$description}\">\n";
        }

        return $html;
    }
}

if (!function_exists('assign_seo')) {
    /**
     * 直接将SEO结果赋值给视图（控制器中使用）
     * 自动设置 title, keywords, description 到视图中
     *
     * @param string $module 模块名称
     * @param string $pageKey 页面标识
     * @param array $data 变量数据
     * @return void
     *
     * 使用示例：
     *   assign_seo('comic', 'comic_detail', ['comic_title' => '航海王', 'comic_author' => '尾田荣一郎']);
     */
    function assign_seo(string $module, string $pageKey, array $data = []): void
    {
        $seo = get_seo($module, $pageKey, $data);

        \think\facade\View::assign([
            'metatitle'       => $seo['title'] ?? '',
            'metakeywords'    => $seo['keywords'] ?? '',
            'metadescription' => $seo['description'] ?? '',
        ]);
    }
}
