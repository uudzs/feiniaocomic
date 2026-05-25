<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use app\common\model\manage\AuthRule as AuthRuleModel;
use app\common\validate\manage\AuthRule as AuthRuleValidate;
use app\common\fields\manage\form\AuthRuleFields;
use app\common\fields\manage\row\RuleList;
use app\common\model\manage\ActionLog;
use Throwable;

// -----------------------------------------------------------------------------
// 权限规则管理控制器
// 负责权限规则的增删改查及树形结构管理
// -----------------------------------------------------------------------------
class AuthRule extends Base
{
    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------
    protected $modelClass    = AuthRuleModel::class;     // 数据模型类
    protected $validateClass = AuthRuleValidate::class; // 数据验证类

    // -------------------------------------------------------------------------
    // 获取权限规则数据接口
    // -------------------------------------------------------------------------
    public function getRuleData()
    {
        $treeData = AuthRuleModel::getTreeData(false); // 获取树形数据（不添加前缀）

        return json([
            'code' => 0,
            'msg'  => 'success',
            'data' => $treeData, // 返回数据
        ]);
    }

    // -------------------------------------------------------------------------
    // 权限规则列表页面
    // -------------------------------------------------------------------------
    public function lst()
    {
        $config = RuleList::getListConfig(); // 获取列表配置

        // 确保配置中有树形表格设置
        $config['isTreeTable'] = true;   // 标记为树形表格

        // 超级管理员导航项配置
        if (session('auser_id') == 1) {
            $config['navItems'][] = [
                'url'    => url('rule/lst'), // 权限列表URL
                'title'  => lang('permission_list'), // 权限列表标题
                'active' => true // 激活状态
            ];
        }

        return view('common/list', array_merge($config)); // 渲染视图
    }

    // -------------------------------------------------------------------------
    // 表单处理方法
    // -------------------------------------------------------------------------
    public function form(?int $id = null)
    {
        // 非AJAX请求渲染视图
        if (!Request::isAjax()) {
            $model = $id ? AuthRuleModel::find($id) : null; // 查找模型
            $currentData = $model ? $model->getData() : []; // 获取数据
            $pageTitle = $id ? lang('edit_permission') : lang('add_permission'); // 页面标题
            $currentParentId = Request::param('addpid/d', 0); // 当前父级ID

            // 字段选项配置
            $fieldsOptions = [
                'pid' => AuthRuleModel::getTreeData(), // 父级权限选项
            ];

            $formTabs = AuthRuleFields::getFormTabs(); // 获取表单配置
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

            $adTitle = $data['title'] ?? ($id ? "ID:{$id}" : lang('new_permission')); // 权限标题
            $actionType = $id ? lang('edit') : lang('add'); // 操作类型
            $this->logFormAction("{$actionType}" . lang('permission') . "《{$adTitle}》"); // 记录日志

            return json([
                'code' => $result['code'] ??  200,
                'msg' => $result['msg'] ??  ("{$actionType}" . lang('permission_success')), // 成功消息
                'url' => url('rule/lst')->build(), // 返回URL
                'data' => $result['data'] ?? [] // 返回数据
            ]);
        } catch (Throwable $e) {
            return json([
                'code' => $e->getCode() ?: 500, // 错误代码
                'msg'  => $e->getMessage(), // 错误消息
            ]);
        }
    }



    // -------------------------------------------------------------------------
    // 删除权限规则方法（删除节点及子节点）
    // -------------------------------------------------------------------------
    public function del()
    {
        try {
            Db::startTrans(); // 开始事务
            $ids = Request::param('ids', '');
            // 参数处理增强
            $normalizedIds = $this->normalizeIds($ids); // 标准化ID参数

            if (empty($normalizedIds)) {
                return c_error(lang('invalid_delete_params')); // 无效参数
            }

            // 执行删除并检查结果
            $result = $this->baseDel(); // 调用基类删除方法

            // 记录操作日志
            $this->logPermissionAction('delete_permission'); // 记录权限操作日志

            Db::commit(); // 提交事务
            return $result; // 返回结果

        } catch (Throwable $e) {
            Db::rollback(); // 回滚事务
            Log::error('删除失败：' . $e->getMessage()); // 记录错误
            return c_error(lang('operation_failed_check_log') . $e->getMessage() . '|' . $e->getFile() . '|' . $e->getLine()); // 返回错误
        }
    }

    // -------------------------------------------------------------------------
    // 私有辅助方法
    // -------------------------------------------------------------------------

    /** 记录权限操作日志 */
    private function logPermissionAction(string $module): void
    {
        ActionLog::create([
            'uname' => session('admin_name'), // 用户名
            'groupid' => session('admin_gid'), // 用户组ID
            'module' => lang($module), // 操作模块
            'action' => lang('delete'), // 操作类型
            'data_id' => session('admin_id'), // 数据ID
            'description' => lang('permission_operation'), // 操作描述
            'ip' => Request::ip(), // 操作IP
            'os' => $this->getOsInfo(), // 操作系统
        ]);
    }

    /** 获取操作系统信息 */
    private function getOsInfo(): string
    {
        return get_os_info() ?: 'Unknown'; // 获取OS信息
    }
}
