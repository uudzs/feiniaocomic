<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\exception\ValidateException;
use app\common\model\manage\Conf as ConfModel;
use app\common\model\manage\ConfCategory;
use app\common\validate\manage\Conf as ConfValidate;
use app\common\fields\manage\form\ConfFields;
use app\common\fields\manage\row\ConfList;
use Throwable;

// -----------------------------------------------------------------------------
// 配置管理控制器
// 负责系统配置字段的增删改查及配置值管理
// -----------------------------------------------------------------------------
class Conf extends Base
{
    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------
    protected $modelClass    = ConfModel::class;     // 数据模型类
    protected $validateClass = ConfValidate::class; // 数据验证类

    // -------------------------------------------------------------------------
    // 表单处理方法
    // -------------------------------------------------------------------------
    public function form(?int $id = null)
    {
        // 非AJAX请求渲染视图
        if (!Request::isAjax()) {
            $model = $id ? ConfModel::find($id) : null;
            $currentData = $model ? $model->getData() : [];
            $pageTitle = $id ? lang('edit_config_field') : lang('add_config_field');
            $currentParentId = Request::param('model/d', 0);

            $formTabs = ConfFields::getFormTabs();

            return parent::renderFormView($formTabs, $currentData, $pageTitle, $currentParentId);
        }

        // AJAX请求处理数据提交
        try {
            $data = Request::param();

            if (empty($id) && isset($data['ename'])) {
                $this->validateFieldNameUnique($data['ename']);
            }

            $result = parent::handleFormSubmit($data, $id);

            $fieldTitle = $data['title'] ?? ($id ? "ID:{$id}" : lang('new_field'));
            $actionType = $id ? lang('edit') : lang('add');
            $this->logFormAction("{$actionType}" . lang('config_field') . "《{$fieldTitle}》");

            return json([
                'code' => $result['code'] ?? 200,
                'msg'  => $result['msg'] ?? ("{$actionType}" . lang('config_field_success')),
                'url'  => url('conf/lst', ['model' => $data['model'] ?? 0])->build(),
                'data' => $result['data'] ?? [],
            ]);
        } catch (Throwable $e) {
            return json([
                'code' => $e->getCode() ?: 500,
                'msg'  => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 获取配置数据接口
    // -------------------------------------------------------------------------
    public function getConfData()
    {
        try {
            return $this->getCommonData([
                'searchFields' => [
                    'title' => 's',
                    'status' => 'd',
                    'model' => 'd',
                ],
                'field' => 'id,title,ename,type,model,is_os,status,sort',
                'order' => 'sort asc',
            ]);
        } catch (\Throwable $e) {
            return json([
                'code' => $e->getCode() ?: 500,
                'msg'  => $e->getMessage() . '|' . $e->getLine() . '|' . $e->getFile(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 配置管理核心方法
    // -------------------------------------------------------------------------
    public function conf()
    {
        // POST请求处理配置更新
        if (Request::isPost()) {
            $model = Request::param('model/d', 0);
            if ($model == 2) {
                // 保存主题配置
                return $this->handleThemeConfigUpdate();
            }
            return $this->handleConfigUpdate();
        }

        $themeList   = [];
        $currentTheme = config('site.theme', 'default');
        $themeConfig = [];
        $confCate = [];

        // model=2 时，扫描 template/ 下的主题目录
        $model = Request::param('model/d', null);
        if ($model == 2) {
            $themeList = $this->scanThemes();
            // 初始化/加载主题配置
            $themeConfig = $this->initThemeConfig($currentTheme);
        }

        // 获取所有分类（非 theme 模式也需要）
        $categories = ConfCategory::getAllCategories() ?? [];
        if (empty($model)) {
            if ($categories && isset($categories[0]) && isset($categories[0]['id'])) {
                $model = $categories[0]['id'];
            }
        }

        // 构建基础查询
        $query = ConfModel::withAttr(['type' => function ($value) {
            return $value;
        }])->where('status', 1);

        if (!empty($model)) {
            $query->where('model', $model);
            $confCate = ConfCategory::find($model);
        }
        $configs = $query->order('sort ASC')->select()->toArray();

        $viewData = [
            'Confs'        => $configs,
            'confCate'        => $confCate,
            'model'        => $model,
            'categories'   => $categories,
            'theme_list'   => $themeList,
            'current_theme' => $currentTheme,
        ];

        // 附加主题配置（转换为嵌套数组供模板使用）
        if ($model == 2) {
            $viewData['theme_config'] = $this->convertToNested($themeConfig);
        }

        return view('conf', $viewData);
    }

    // -------------------------------------------------------------------------
    // 扫描 template/ 下的主题目录
    // -------------------------------------------------------------------------
    private function scanThemes(): array
    {
        $themeDir = app()->getRootPath() . 'template';
        $themes   = [];

        if (!is_dir($themeDir)) {
            return $themes;
        }

        $dirs = scandir($themeDir);
        foreach ($dirs as $dir) {
            if ($dir === '.' || $dir === '..' || $dir === 'manage') {
                continue;
            }
            if (is_dir($themeDir . DIRECTORY_SEPARATOR . $dir)) {
                $infoFile = $themeDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . 'theme.json';
                $title = $dir;
                if (file_exists($infoFile)) {
                    $info  = json_decode(file_get_contents($infoFile), true);
                    $title = $info['title'] ?? $dir;
                }
                $themes[] = [
                    'dir'   => $dir,
                    'title' => $title,
                ];
            }
        }

        return $themes;
    }

    // -------------------------------------------------------------------------
    // 初始化主题配置（从 theme.json 导入到 config/site.php）
    // -------------------------------------------------------------------------
    private function initThemeConfig(string $theme): array
    {
        $rootPath   = app()->getRootPath();
        $configFile = $rootPath . 'config' . DIRECTORY_SEPARATOR . 'site.php';
        $themeJsonFile = $rootPath . 'template' . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'theme.json';

        // 读取现有配置
        $config = [];
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (!is_array($config)) {
                $config = [];
            }
        }

        // 从 theme.json 读取默认配置
        $themeConfig = [];
        if (file_exists($themeJsonFile)) {
            $themeJson = json_decode(file_get_contents($themeJsonFile), true);
            if (is_array($themeJson)) {
                $themeConfig = $this->extractThemeConfig($themeJson);
            }
        }

        // 合并配置（现有配置优先）
        $config = array_merge($themeConfig, $config);
        $config['theme'] = $theme;

        // 保存回文件
        $this->saveSiteConfig($configFile, $config);

        return $config;
    }

    // -------------------------------------------------------------------------
    // 将扁平化配置转换为嵌套数组（供模板使用）
    // -------------------------------------------------------------------------
    private function convertToNested(array $config): array
    {
        $nested = [
            'colors'      => [],
            'dark_colors' => [],
            'layout'      => [],
            'features'    => [],
            'typography'  => [],
            'effects'     => [],
        ];

        foreach ($config as $key => $value) {
            if (strpos($key, 'color_') === 0 && strpos($key, 'dark_color_') !== 0) {
                $nested['colors'][substr($key, 6)] = $value;
            } elseif (strpos($key, 'dark_color_') === 0) {
                $nested['dark_colors'][substr($key, 11)] = $value;
            } elseif (strpos($key, 'layout_') === 0) {
                $nested['layout'][substr($key, 7)] = $value;
            } elseif (strpos($key, 'feature_') === 0) {
                $nested['features'][substr($key, 8)] = $value;
            } elseif (strpos($key, 'typo_') === 0) {
                $nested['typography'][substr($key, 5)] = $value;
            } elseif (strpos($key, 'effect_') === 0) {
                $nested['effects'][substr($key, 7)] = $value;
            }
        }

        return $nested;
    }

    // -------------------------------------------------------------------------
    // 从 theme.json 提取可自定义的配置项
    // -------------------------------------------------------------------------
    private function extractThemeConfig(array $themeJson): array
    {
        $config = [];

        // 颜色配置（浅色模式）
        if (isset($themeJson['colors']) && is_array($themeJson['colors'])) {
            foreach ($themeJson['colors'] as $key => $value) {
                $config['color_' . $key] = $value;
            }
        }

        // 颜色配置（深色模式）
        if (isset($themeJson['dark_colors']) && is_array($themeJson['dark_colors'])) {
            foreach ($themeJson['dark_colors'] as $key => $value) {
                $config['dark_color_' . $key] = $value;
            }
        }

        // 布局配置
        if (isset($themeJson['layout']) && is_array($themeJson['layout'])) {
            foreach ($themeJson['layout'] as $key => $value) {
                $config['layout_' . $key] = $value;
            }
        }

        // 功能开关
        if (isset($themeJson['features']) && is_array($themeJson['features'])) {
            foreach ($themeJson['features'] as $key => $value) {
                $config['feature_' . $key] = $value;
            }
        }

        // 效果配置
        if (isset($themeJson['effects']) && is_array($themeJson['effects'])) {
            foreach ($themeJson['effects'] as $key => $value) {
                $config['effect_' . $key] = $value;
            }
        }

        return $config;
    }

    // -------------------------------------------------------------------------
    // 保存主题配置（处理 model=2 的 POST 请求）
    // -------------------------------------------------------------------------
    private function handleThemeConfigUpdate()
    {
        $data = Request::param();

        $rootPath   = app()->getRootPath();
        $configFile = $rootPath . 'config' . DIRECTORY_SEPARATOR . 'site.php';

        // 读取现有配置
        $config = [];
        if (file_exists($configFile)) {
            $config = include $configFile;
            if (!is_array($config)) {
                $config = [];
            }
        }

        // 更新主题配置字段（所有以 color_、dark_color_、layout_、typo_、home_、feature_、effect_ 开头的字段）
        $prefixes = ['color_', 'dark_color_', 'layout_', 'typo_', 'home_', 'feature_', 'effect_'];

        foreach ($data as $key => $value) {
            foreach ($prefixes as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $config[$key] = $value;
                    break;
                }
            }
        }

        // 保存回文件
        $this->saveSiteConfig($configFile, $config);

        $this->logFormAction('更新主题配置');

        return json([
            'code' => 200,
            'msg'  => '主题配置保存成功',
            'require_full_reload' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // 保存 site.php 配置文件
    // -------------------------------------------------------------------------
    private function saveSiteConfig(string $configFile, array $config): void
    {
        $export  = var_export($config, true);
        $content = "<?php\n// +----------------------------------------------------------------------\n// | 前端主题配置\n// +----------------------------------------------------------------------\n\nreturn " . $export . ";\n";
        file_put_contents($configFile, $content);
    }

    // -------------------------------------------------------------------------
    // 切换主题（AJAX）
    // -------------------------------------------------------------------------
    public function switchTheme()
    {
        if (!Request::isPost()) {
            return json(['code' => 400, 'msg' => '非法请求']);
        }

        $theme = Request::param('theme', '');
        if (!$theme) {
            return json(['code' => 400, 'msg' => '请选择主题']);
        }

        $themeDir = app()->getRootPath() . 'template' . DIRECTORY_SEPARATOR . $theme;
        if (!is_dir($themeDir)) {
            return json(['code' => 400, 'msg' => '主题目录不存在：' . $theme]);
        }

        // 更新 config/site.php
        $configFile = app()->getRootPath() . 'config' . DIRECTORY_SEPARATOR . 'site.php';
        if (!file_exists($configFile)) {
            return json(['code' => 500, 'msg' => '配置文件不存在']);
        }

        $config = include $configFile;
        if (!is_array($config)) {
            $config = [];
        }
        $config['theme'] = $theme;

        $export = var_export($config, true);
        $content = "<?php\n// +----------------------------------------------------------------------\n// | 前端主题配置\n// +----------------------------------------------------------------------\n\nreturn " . $export . ";\n";
        file_put_contents($configFile, $content);

        $this->logFormAction('切换主题为：' . $theme);

        return json([
            'code'             => 200,
            'msg'              => '主题切换成功',
            'require_full_reload' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // 配置字段列表页面
    // -------------------------------------------------------------------------
    public function lst()
    {
        $config = ConfList::getListConfig();

        if (session('admin_id') == 1) {
            $config['actions'][] = [
                'url'   => url('conf/del') . '?ids=',
                'text'  => lang('delete'),
                'color' => 'red',
                'params' => [],
                'event' => 'del',
            ];
        }

        return view('common/list', $config);
    }

    // -------------------------------------------------------------------------
    // 删除配置字段方法
    // -------------------------------------------------------------------------
    public function del()
    {
        try {
            Db::startTrans();

            $normalizedIds = $this->normalizeIds(Request::param('ids'));

            if (empty($normalizedIds)) {
                return c_error(lang('invalid_delete_params'));
            }

            $confs = ConfModel::where('id', 'in', $normalizedIds)->select();

            $protectedFields = [];
            foreach ($confs as $conf) {
                if ($conf->is_os == 1) {
                    $protectedFields[] = $conf->ename;
                }
            }

            if (!empty($protectedFields)) {
                Db::rollback();
                return c_error(lang('system_field_protected', [implode(',', $protectedFields)]));
            }

            $deleteCount = ConfModel::where('id', 'in', $normalizedIds)->delete();

            if ($deleteCount === 0) {
                Db::rollback();
                return c_error(lang('no_data_to_delete'));
            }

            $this->logFormAction(lang('delete_config_fields_log', [$deleteCount]));

            Db::commit();
            return c_success(lang('delete_config_fields_success', [$deleteCount]));
        } catch (Throwable $e) {
            Db::rollback();
            Log::error('删除失败：' . $e->getMessage());
            return c_error(lang('delete_failed') . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // 私有辅助方法
    // -------------------------------------------------------------------------

    /** 处理配置更新 */
    private function handleConfigUpdate()
    {
        $data       = Request::param();
        $updateData = $this->prepareConfigData($data);

        $updateCount = 0;
        foreach ($updateData as $item) {
            $result = ConfModel::update(['value' => $item['value']], ['id' => $item['id']]);
            if ($result) $updateCount++;
        }

        $this->logFormAction(lang('update_config_items_log', [$updateCount]));

        return json([
            'code'             => 200,
            'msg'              => lang('config_update_success'),
            'require_full_reload' => true,
        ]);
    }

    /** 准备配置数据 */
    private function prepareConfigData(array $formData): array
    {
        $processedData = [];
        try {
            $configItems = ConfModel::column('id,ename,type,values', 'ename');

            foreach ($formData as $field => $rawValue) {
                if (!isset($configItems[$field])) continue;

                $config       = $configItems[$field] ?? [];
                $processedValue = '';

                switch ($config['type']) {
                    case 2: // 单选
                    case 4: // 下拉
                        $validOptions = explode("\n", $config['values']);
                        $legal = [];
                        foreach ($validOptions as $value) {
                            $item = explode('=>', $value);
                            if ($item && isset($item[0])) $legal[] = $item[0];
                        }
                        if ($legal && $rawValue && !in_array($rawValue, $legal)) {
                            throw new \RuntimeException(lang('invalid_option', [$field]));
                        }
                        $processedValue = htmlspecialchars((string) $rawValue);
                        break;

                    case 3: // 复选框
                        $validOptions = explode("\n", $config['values']);
                        $legal = [];
                        foreach ($validOptions as $value) {
                            $item = explode('=>', $value);
                            if ($item && isset($item[0])) $legal[] = $item[0];
                        }
                        $values = is_array($rawValue) ? $rawValue : explode(',', $rawValue);
                        $filtered = array_intersect($values, $legal);
                        $processedValue = implode(',', array_map('htmlspecialchars', $filtered));
                        break;

                    default: // 文本类型
                        $processedValue = htmlspecialchars((string) $rawValue);
                }

                $processedData[] = [
                    'id'    => $config['id'],
                    'value' => $processedValue,
                ];
            }
        } catch (Throwable $e) {
            Log::error("配置处理失败: {$e->getMessage()}");
            throw new \RuntimeException(lang('config_data_process_exception'));
        }

        return $processedData;
    }

    /** 验证字段名唯一性 */
    private function validateFieldNameUnique(string $fieldName): void
    {
        $exists = ConfModel::where('ename', $fieldName)->count();
        if ($exists > 0) {
            throw new ValidateException(lang('field_name_exists', [$fieldName]));
        }
    }
}
