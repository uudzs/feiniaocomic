<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Db;
use think\facade\Log;
use think\facade\Session;
use think\facade\Request;
use app\common\model\manage\Admin as AdminModel;
use app\common\model\manage\AuthGroupAccess as AuthGroupAccessModel;
use app\common\model\manage\AuthGroup as AuthGroupModel;
use app\common\model\manage\ActionLog;
use app\common\validate\manage\AuthGroup as AuthGroupValidate;
use app\common\fields\manage\form\AuthGroupFields;
use app\common\fields\manage\row\AuthGroupList;
use Throwable;

// -----------------------------------------------------------------------------
// 用户组管理控制器
// 负责用户组的增删改查、权限分配及树形结构管理
// -----------------------------------------------------------------------------
class AuthGroup extends Base
{
    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------
    protected $modelClass    = AuthGroupModel::class;     // 数据模型类
    protected $validateClass = AuthGroupValidate::class; // 数据验证类

    // -------------------------------------------------------------------------
    // 获取用户组数据接口
    // -------------------------------------------------------------------------
    public function getGroupData()
    {
        $fields = ['id', 'pid', 'title', 'status', 'title_auth']; // 查询字段
        $withPrefix = false; // 不添加前缀
        $useScope = 'filterSuperAdmin'; // 应用作用域过滤

        $data = AuthGroupModel::getTreeData(
            withPrefix: $withPrefix,
            fields: $fields,
            scope: $useScope,
        );

        return json([
            'code' => 0,
            'msg'  => 'success',
            'data' => $data, // 返回数据
        ]);
    }

    // -------------------------------------------------------------------------
    // 用户组列表页面
    // -------------------------------------------------------------------------
    public function lst()
    {
        $config = AuthGroupList::getListConfig(); // 获取列表配置
        return view('common/list', array_merge($config)); // 渲染视图
    }

    // -------------------------------------------------------------------------
    // 表单处理方法
    // -------------------------------------------------------------------------
    public function form(?int $id = null)
    {
        // 非AJAX请求渲染视图
        if (!Request::isAjax()) {
            $currentData = $id ? AuthGroupModel::find($id)->getData() : []; // 当前数据
            $pageTitle = $id ? lang('edit_user_group') : lang('add_user_group'); // 页面标题
            $currentParentId = 0; // 当前父级ID

            // 字段选项配置
            $fieldsOptions = [
                'pid' => $this->getGroupTreeData(), // 父级组选项
            ];

            $formTabs = AuthGroupFields::getFormTabs(); // 获取表单配置
            $this->assignOptionsForFields($formTabs, $fieldsOptions); // 动态赋值选项

            return parent::renderFormView( // 调用基类渲染方法
                $formTabs,
                $currentData,
                $pageTitle,
                $currentParentId
            );
        }

        // AJAX请求处理数据提交
        try {
            $data = Request::param(); // 获取请求数据

            // 调用基类处理表单提交
            $result = parent::handleFormSubmit($data, $id);

            // 记录操作日志
            $this->logFormAction(lang('user_group_management'), $data, 'title');

            // 返回操作结果
            return json([
                'code'                => $result['code'] ?? 200,
                'msg'                 => $result['msg'] ?? lang('operation_success'),
                'require_full_reload' => 'require_full_reload', // 需要完全刷新
            ]);
        } catch (Throwable $e) {
            return json([
                'code' => $e->getCode() ?: 500, // 错误代码
                'msg'  => $e->getMessage(), // 错误消息
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 获取权限组树形数据
    // -------------------------------------------------------------------------
    public function getGroupTreeData()
    {
        return AuthGroupModel::getTreeData(
            withPrefix: true, // 添加前缀
            fields: ['id', 'pid', 'title', 'status', 'title_auth'], // 查询字段
            scope: 'filterSuperAdmin' // 过滤范围
        );
    }

    // -------------------------------------------------------------------------
    // 权限分配管理
    // -------------------------------------------------------------------------
    public function power($id = 0)
    {
        // 非AJAX请求显示权限分配页面
        if (!Request::isAjax()) {
            return $this->showPowerPage($id);
        }

        // AJAX请求处理权限保存
        return $this->handlePowerSave($id);
    }

    // -------------------------------------------------------------------------
    // 删除用户组及关联数据
    // -------------------------------------------------------------------------
    public function del($ids)
    {
        try {
            Db::startTrans(); // 开始事务

            // 获取所有关联组ID（处理多ID情况）
            $groupIds = [];
            foreach ((array) $ids as $id) {
                $groupIds = array_merge(
                    $groupIds,
                    AuthGroupModel::getAllChildIdsWithSelf((int) $id) // 获取所有子组ID
                );
            }
            $groupIds = array_unique($groupIds); // 去重

            // 删除关联用户数据
            AdminModel::whereIn('groupid', $groupIds)
                ->chunk(500, function ($users) {
                    $userIds = $users->column('id'); // 获取用户ID
                    AuthGroupAccessModel::whereIn('uid', $userIds)->delete(); // 删除用户组关联
                    AdminModel::destroy($userIds); // 删除用户
                });

            // 删除组权限关联
            AuthGroupAccessModel::whereIn('group_id', $groupIds)->delete();

            // 删除组数据
            AuthGroupModel::destroy($groupIds);

            // 记录操作日志
            $this->logGroupAction('delete_group', Session::get('admin_name'), Session::get('admin_gid'), Session::get('admin_id'));

            Db::commit(); // 提交事务
            return c_success(lang('delete_group_success', [count($groupIds)])); // 返回成功
        } catch (Throwable $e) {
            Db::rollback(); // 回滚事务
            Log::error('用户组删除失败：' . $e->getMessage()); // 记录错误
            return c_error(lang('delete_failed') . $e->getMessage()); // 返回错误
        }
    }

    // -------------------------------------------------------------------------
    // 私有辅助方法
    // -------------------------------------------------------------------------

    /** 显示权限分配页面 */
    private function showPowerPage($id)
    {
        $group = AuthGroupModel::find($id); // 查找用户组
        if (!$group) {
            return c_error(lang('user_group_not_exists')); // 用户组不存在
        }

        $checkedIds = $group->rules ? explode(',', $group->rules) : []; // 已选权限ID
        $allpermissionTree = AuthGroupModel::getPermissionTree(); // 所有权限树

        // 根据用户身份过滤权限树
        if (session('admin_id') === 1) {
            $permissionTree = $allpermissionTree; // 超级管理员拥有所有权限
        } else {
            $currentGroup = AuthGroupModel::find(session('admin_gid')); // 当前用户组
            if (!$currentGroup) {
                return c_error(lang('user_group_not_exists')); // 用户组不存在
            }
            $allowedRuleIds = explode(',', $currentGroup->rules); // 允许的规则ID
            $permissionTree = $this->filterPermissionTree($allpermissionTree, $allowedRuleIds); // 过滤权限树
        }

        $this->markSelectedNodes($permissionTree, $checkedIds); // 标记已选节点

        return view('/power', [
            'treeData' => json_encode($permissionTree, JSON_UNESCAPED_UNICODE), // 树形数据
            'group'    => $group, // 用户组信息
        ]);
    }

    /** 处理权限保存请求 */
    private function handlePowerSave($id)
    {
        try {
            $ruleIds = array_filter(explode(',', Request::post('ruleIds') ?? '')); // 清洗权限ID
            $ruleIds = array_map('intval', $ruleIds); // 强制转为整数

            if (empty($ruleIds)) {
                return c_error(lang('select_at_least_one_permission')); // 至少选择一个权限
            }

            $group = AuthGroupModel::find($id); // 查找用户组
            if (!$group) {
                return c_error(lang('user_group_not_exists_or_deleted')); // 用户组不存在
            }

            Db::startTrans(); // 开始事务
            try {
                if ($group->safeUpdateRules($ruleIds)) { // 安全更新规则
                    $this->logGroupAction('assign_permission', session('admin_name'), session('admin_gid'), session('admin_id')); // 记录日志
                    Db::commit(); // 提交事务
                    Auth::reloadAuth();
                    return c_success(lang('permission_update_success')); // 返回成功
                }
                return c_error(lang('permission_update_failed')); // 更新失败
            } catch (Throwable $e) {
                Db::rollback(); // 回滚事务
                Log::error("权限保存失败 GroupID:{$id} " . $e->getMessage()); // 记录错误
                return c_error(lang('permission_save_failed') . $e->getMessage()); // 返回错误
            }
        } catch (Throwable $e) {
            Log::error("权限分配异常：{$id} " . $e->getMessage()); // 记录错误
            return c_error(lang('system_busy_try_later')); // 系统繁忙
        }
    }

    /** 标记已选中的节点 */
    private function markSelectedNodes(&$tree, $checkedIds): void
    {
        foreach ($tree as &$node) {
            $node['checked'] = in_array($node['id'], $checkedIds); // 标记选中状态
            if (!empty($node['children'])) {
                $this->markSelectedNodes($node['children'], $checkedIds); // 递归处理子节点
            }
        }
    }

    /** 根据允许的规则ID过滤权限树 */
    protected function filterPermissionTree(array $nodes, array $allowedRuleIds): array
    {
        $filtered = [];
        foreach ($nodes as $node) {
            if (in_array($node['id'], $allowedRuleIds)) { // 检查是否在允许列表中
                $node['children'] = isset($node['children'])
                    ? $this->filterPermissionTree($node['children'], $allowedRuleIds) // 递归过滤子节点
                    : [];
                $filtered[] = $node; // 添加到过滤结果
            }
        }
        return $filtered;
    }

    /** 记录用户组操作日志 */
    private function logGroupAction(string $module, string $uname, int $groupid, int $dataId): void
    {
        ActionLog::create([
            'uname' => $uname,
            'groupid' => $groupid,
            'module' => lang($module),
            'action' => lang('operation'),
            'data_id' => $dataId,
            'description' => lang('user_group_operation'),
            'ip' => Request::ip(),
            'os' => $this->getOsInfo(),
        ]);
    }

    /** 获取操作系统信息 */
    private function getOsInfo(): string
    {
        return get_os_info() ?: 'Unknown'; // 获取OS信息
    }
}
