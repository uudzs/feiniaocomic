<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\Log;
use think\facade\Config;
use think\facade\Request;
use think\facade\View;
use think\exception\ValidateException;
use app\manage\middleware\Auth;
use app\common\model\manage\Admin;
use app\common\model\manage\AuthRule;
use app\common\model\manage\ActionLog;
use Throwable;
use think\facade\Session;

class Base extends BaseController
{
    // -------------------------------------------------------------------------
    // 中间件与属性
    // -------------------------------------------------------------------------
    protected $middleware = [Auth::class];
    protected $modelClass;
    protected $validateClass;

    // -------------------------------------------------------------------------
    // 初始化方法
    // -------------------------------------------------------------------------
    public function initialize()
    {
        // 加载插件菜单
        $this->initializeAuthData();
        $this->initializeUserData();
    }

    // -------------------------------------------------------------------------
    // 表单处理方法
    // -------------------------------------------------------------------------
    protected function handleFormSubmit(array $data, ?int $id = null)
    {
        // 依赖检查
        if (!$this->validateDependencies()) {
            return c_error(lang('system_config_error'));
        }

        // 模型初始化
        $model = $this->initializeModel($id);
        if ($id && !$model) {
            return c_error(lang('data_not_exists'));
        }

        // 数据验证
        $validateResult = $this->validateFormData($data, $id);

        if ($validateResult['code'] !== 200) {
            return $validateResult;
        }

        // 事务处理
        return $this->processFormTransaction($model, $data, $id);
    }

    // -------------------------------------------------------------------------
    // 表单视图渲染
    // -------------------------------------------------------------------------
    protected function renderFormView(array $formTabs, $currentData, string $pageTitle, int $curPid = 0)
    {
        $resourceFlags = $this->detectFormResources($formTabs);

        return view('common/form', array_merge([
            'SingData'  => $currentData,
            'pageTitle' => $pageTitle,
            'formTabs'  => $formTabs,
            'curPid'    => $curPid,
        ], $resourceFlags));
    }

    // -------------------------------------------------------------------------
    // 字段选项动态赋值
    // -------------------------------------------------------------------------
    protected function assignOptionsForFields(array &$formTabs, array $fieldsMap)
    {
        $fieldCache = $this->buildFieldCache($formTabs);

        foreach ($fieldsMap as $fieldName => $options) {
            if (isset($fieldCache[$fieldName])) {
                $fieldCache[$fieldName]['options'] = $options;
            } else {
                Log::warning("表单字段{$fieldName}不存在于表单配置中");
            }
        }
    }

    // -------------------------------------------------------------------------
    // 获取所有子栏目ID
    // -------------------------------------------------------------------------
    public function getAllChildIds(int $rootId, string $pidField = 'pid'): array
    {
        $modelClass = $this->modelClass;
        $deleteIds = [$rootId];
        $queue = [$rootId];

        while (!empty($queue)) {
            $currentId = array_shift($queue);
            $children = $modelClass::where($pidField, $currentId)->column('id');

            if (!empty($children)) {
                $deleteIds = array_merge($deleteIds, $children);
                $queue = array_merge($queue, $children);
            }
        }

        return array_unique($deleteIds);
    }

    // -------------------------------------------------------------------------
    // 删除操作
    // -------------------------------------------------------------------------
    public function baseDel()
    {
        try {
            $originalIds = $this->normalizeIds(Request::param('ids')); // 标准化ID参数
            if (empty($originalIds)) {
                return c_error(lang('invalid_data_selection'));
            }

            $modelClass = $this->modelClass;

            $model = new $modelClass;
            $pk = $model->getPk();

            if (!$pk) {
                return c_error(lang('model_pk_undefined'));
            }

            $modelChineseName = $modelClass::$chineseName ?? $model->getName();

            $deleteCount = $model::where($pk, 'in', $originalIds)->delete();

            $this->logFormAction(lang('delete_data_log', [$modelChineseName, $deleteCount]));

            return $deleteCount > 0 ? c_success(lang('delete_success_count', [$deleteCount, $modelChineseName])) : c_error(lang('no_data_operation'));
        } catch (Throwable $e) {
            Log::error("删除异常：{$e->getMessage()} IDS:" . implode(',', $originalIds ?? []));
            return c_error($e->getMessage() . '|' . $e->getLine() . '|' . $e->getFile());
        }
    }

    // -------------------------------------------------------------------------
    // 统一数据获取方法
    // -------------------------------------------------------------------------
    protected function getCommonData(array $options = [])
    {
        $config = array_merge([
            'searchFields' => [],
            'field' => '*',
            'order' => 'id DESC',
            'condition' => null,
            'model' => $this->modelClass
        ], $options);

        // 分页参数
        $limit = intval(Request::param('limit', 10));
        $page = intval(Request::param('page', 1));

        // 创建查询
        $modelClass = $config['model'];
        $query = $modelClass::field($config['field']);

        // 应用条件
        if (is_callable($config['condition'])) {
            $config['condition']($query);
        }

        // 处理搜索
        $this->applySearchConditions($query, $config['searchFields']);

        // 获取数据
        $total = $query->count();
        $data = $query->order($config['order'])
            ->limit($limit)
            ->page($page)
            ->select()
            ->toArray();

        return json([
            'code' => 0,
            'count' => $total,
            'message' => lang('render_success'),
            'data' => $data
        ]);
    }

    // -------------------------------------------------------------------------
    // 操作日志记录
    // -------------------------------------------------------------------------
    public function logFormAction(string $module): void
    {
        ActionLog::create([
            'uname' => Session::get('admin_name') ?? lang('unknown'),
            'groupid' => Session::get('admin_gid'),
            'module' => $module,
            'ip' => Request::ip(),
            'os' => $this->getOsInfo()
        ]);
    }

    // -------------------------------------------------------------------------
    // 私有辅助方法
    // -------------------------------------------------------------------------

    /** 初始化权限数据 */
    private function initializeAuthData(): void
    {
        $authCon = Request::controller() . '/' . Request::action();
        $curAuthCon = AuthRule::where('name', $authCon)->find();

        View::assign([
            'AuthCon' => $authCon,
            'CurAuthCon' => $curAuthCon,
        ]);
    }

    /** 初始化用户数据 */
    private function initializeUserData(): void
    {
        $curUserInfo = Admin::find(Session::get('admin_id'));
        $superAdmin = Admin::find(1);
        $curGroups = $curUserInfo->bltAuthGroup->toArray();

        $allMenus = AuthRule::order('sort')
            ->where('show', 1)
            ->where('id', 'in', $curGroups['rules'])
            ->select()
            ->toArray();

        $authMenu = [];
        $currentMenuPid = 0;
        $currentMenuId = 0;

        foreach ($allMenus as $menu) {
            if ($menu['pid'] == 0 && !isset($authMenu[$menu['id']])) {
                $menu['children'] = [];
                $menu['expanded'] = false;
                $menu['active'] = false;
                $authMenu[$menu['id']] = $menu;
            } else {
                if (isset($authMenu[$menu['pid']])) {
                    $menu['active'] = false;
                    $authMenu[$menu['pid']]['children'][] = $menu;
                } else {
                    foreach ($allMenus as $item) {
                        if ($item['id'] == $menu['pid']) {
                            $item['children'] = [];
                            $item['expanded'] = false;
                            $item['active'] = false;
                            $authMenu[$item['id']] = $item;
                            break;
                        }
                    }
                    if (isset($authMenu[$menu['pid']])) {
                        $menu['active'] = false;
                        $authMenu[$menu['pid']]['children'][] = $menu;
                    }
                }
            }
        }

        $authMenu = array_values($authMenu);
        $currentAuthCon = AuthRule::where('name',  Request::controller() . '/' . Request::action())->find();
        if ($currentAuthCon) {
            $currentMenuId = $currentAuthCon['id'];
            if ($currentAuthCon['pid'] > 0) {
                $currentMenuPid = $currentAuthCon['pid'];
                foreach ($authMenu as &$menu) {
                    if ($menu['id'] == $currentMenuPid) {
                        $menu['expanded'] = true;
                        $menu['active'] = true;
                        foreach ($menu['children'] as &$child) {
                            if ($child['id'] == $currentMenuId) {
                                $child['active'] = true;
                                break;
                            }
                        }
                        break;
                    }
                }
            } else {
                foreach ($authMenu as &$menu) {
                    if ($menu['id'] == $currentMenuId) {
                        $menu['active'] = true;
                        break;
                    }
                }
            }
        }

        View::assign([
            'CurUserInfo' => $curUserInfo,
            'superAdimin' => $superAdmin,
            'AuthMenu' => $authMenu,
            'multiLangEnabled' => Config::get('lang.use_cookie', true)
        ]);
    }

    /** 验证依赖 */
    private function validateDependencies(): bool
    {
        return class_exists($this->modelClass) && class_exists($this->validateClass);
    }

    /** 初始化模型 */
    private function initializeModel(?int $id)
    {
        return $id ? $this->modelClass::find($id) : new $this->modelClass;
    }

    /** 验证表单数据 */
    private function validateFormData(array $data, ?int $id): array
    {
        try {
            $scene = $id ? 'edit' : 'add';
            validate($this->validateClass)->scene($scene)->check($data);
            return ['code' => 200];
        } catch (ValidateException $e) {
            return ['code' => 400, 'msg' => $e->getError()];
        }
    }

    /** 处理表单事务 */
    private function processFormTransaction($model, array $data, ?int $id): array
    {
        Db::startTrans();
        try {
            $model->save($data);
            Db::commit();

            $modelClass = $this->modelClass;
            $modelName = $modelClass::$chineseName ?? lang('data');
            $successMsg = $id ? lang('edit_success', [$modelName]) : lang('add_success', [$modelName]);

            return [
                'code' => 200,
                'msg' => $successMsg,
                'data' => $model->toArray()
            ];
        } catch (Throwable $e) {
            Db::rollback();
            Log::error("表单提交失败 | {$e->getMessage()} | 数据：" . json_encode($data));
            return ['code' => 500, 'msg' => $e->getMessage()];
        }
    }

    /** 检测表单资源 */
    private function detectFormResources(array $formTabs): array
    {
        $flags = ['hasEditor' => false, 'hasImage' => false, 'hasImages' => false];
 
        foreach ($formTabs as $tab) {
            foreach ($tab['fields'] as $field) {
                switch ($field['type']) {
                    case 'editor':
                        $flags['hasEditor'] = true;
                        break;
                    case 'image':
                        $flags['hasImage'] = true;
                        break;
                    case 'images':
                        $flags['hasImages'] = true;
                        break;
                }
                if (array_sum($flags) === 3) break 2;
            }
        }

        return $flags;
    }

    /** 构建字段缓存 */
    private function buildFieldCache(array &$formTabs): array
    {
        $fieldCache = [];
        foreach ($formTabs as $tabIndex => &$tab) {
            foreach ($tab['fields'] as $fieldIndex => &$field) {
                $fieldCache[$field['name']] = &$field;
            }
        }
        return $fieldCache;
    }
    /** 标准化ID参数 */
    protected function normalizeIds($ids): array
    {
        $normalized = [];

        // 处理数组格式
        if (is_array($ids)) {
            $normalized = $ids;
        }
        // 处理数字格式
        elseif (is_numeric($ids)) {
            $normalized = [(int)$ids];
        }
        // 处理字符串格式
        elseif (is_string($ids)) {
            // 移除所有空格和分号，然后按逗号分割
            $cleaned = str_replace([' ', ';', '，'], ['', '', ','], $ids);
            $normalized = explode(',', $cleaned);
        }

        // 过滤和转换：移除空值，转换为整数，过滤非正整数
        return array_unique(array_filter(array_map(function ($id) {
            // 先转换为字符串再trim，确保处理数字和字符串
            $id = trim((string)$id);
            return $id !== '' ? intval($id) : null;
        }, $normalized), function ($id) {
            return $id !== null && $id > 0;
        }));
    }
    /** 应用搜索条件 */
    private function applySearchConditions($query, array $searchFields): void
    {
        foreach ($searchFields as $field => $type) {
            $value = Request::param($field);
            if ($value === null || $value === '') continue;

            switch ($type) {
                case 's':
                    $query->where($field, 'like', "%{$value}%");
                    break;
                case 'd':
                    $query->where($field, intval($value));
                    break;
                case 'range':
                    if (is_array($value) && count($value) === 2) {
                        $query->whereBetween($field, $value);
                    }
                    break;
                case 'date':
                    $query->whereDate($field, $value);
                    break;
                default:
                    $query->where($field, $value);
            }
        }
    }

    /** 获取操作系统信息 */
    private function getOsInfo(): string
    {
        return get_os_info() ?: 'Unknown';
    }
}
