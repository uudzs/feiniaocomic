<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\exception\ValidateException;
use app\common\fields\manage\form\ConfCategoryFields;
use app\common\fields\manage\row\ConfCategoryList;
use app\common\model\manage\ConfCategory as ConfCategoryModel;
use app\common\validate\manage\ConfCategory as ConfCategoryValidate;

/**
 * 配置分类管理控制器
 */
class ConfCategory extends Base
{

    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------
    protected $modelClass    = ConfCategoryModel::class;     // 数据模型类
    protected $validateClass = ConfCategoryValidate::class; // 数据验证类

    // -------------------------------------------------------------------------
    // 表单处理方法
    // -------------------------------------------------------------------------
    public function form(?int $id = null)
    {
        // 非AJAX请求渲染视图
        if (!Request::isAjax()) {
            $model = $id ? ConfCategoryModel::find($id) : null; // 查找模型
            $currentData = $model ? $model->getData() : []; // 获取数据
            $pageTitle = $id ? lang('edit_config_field') : lang('add_config_field'); // 页面标题
            $currentParentId = Request::param('model/d', 0); // 当前模型ID

            $formTabs = ConfCategoryFields::getFormTabs(); // 获取表单配置

            return parent::renderFormView($formTabs, $currentData, $pageTitle, $currentParentId); // 调用基类渲染
        }

        // AJAX请求处理数据提交
        try {
            $data = Request::param(); // 获取请求数据

            // 特殊处理：确保字段名唯一性
            if (empty($id) && isset($data['ename'])) {
                $this->validateFieldNameUnique($data['ename']); // 验证字段名唯一
            }

            // 调用基类处理表单提交
            $result = parent::handleFormSubmit($data, $id);

            // 记录操作日志
            $fieldTitle = $data['name'];
            $actionType = $id ? lang('edit') : lang('add');
            $this->logFormAction("{$actionType}" . "《{$fieldTitle}》");

            return json([
                'code' => $result['code'] ?? 200,
                'msg' => $result['msg'] ?? ("{$actionType}" . lang('config_field_success')), // 成功消息
                'url' => url('confcategory/lst')->build(), // 返回URL
                'data' => $result['data'] ?? [] // 返回数据
            ]);
        } catch (\Throwable $e) {
            return json([
                'code' => $e->getCode() ?: 500, // 错误代码
                'msg'  => $e->getMessage() // 错误消息
            ]);
        }
    }

    /** 验证字段名唯一性 */
    private function validateFieldNameUnique(string $fieldName): void
    {
        $exists = ConfCategoryModel::where('ename', $fieldName)->count(); // 检查字段名是否存在
        if ($exists > 0) {
            throw new ValidateException(lang('field_name_exists', [$fieldName])); // 字段名已存在
        }
    }

    // -------------------------------------------------------------------------
    // 配置字段列表页面
    // -------------------------------------------------------------------------
    public function lst()
    {
        $config = ConfCategoryList::getListConfig(); // 获取列表配置

        // 动态添加行操作（根据权限）
        if (session('admin_id') == 1) {
            $config['actions'][] = [
                'url' => url('confcategory/del') . '?id=', // 删除URL
                'text' => lang('delete'), // 删除文本
                'color' => 'red', // 颜色
                'params' => [], // 参数
                'event' => 'del' // 事件
            ];
        }

        return view('common/list', $config); // 渲染视图
    }

    // -------------------------------------------------------------------------
    // 获取配置数据接口
    // -------------------------------------------------------------------------
    public function getData()
    {
        try {
            return $this->getCommonData([
                'searchFields' => [
                    'name' => 's',  // 标题搜索（字符串匹配）
                    'status' => 'd', // 状态搜索（数字匹配）
                ],
                'field' => 'id,name,name as title,ename,status,sort', // 查询字段
                'order' => 'sort asc', // 排序规则
            ]);
        } catch (\Throwable $e) {
            return json([
                'code' => $e->getCode() ?: 500, // 错误代码
                'msg'  => $e->getMessage() . '|' . $e->getLine() . '|' . $e->getFile() // 错误消息
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 删除配置字段方法
    // -------------------------------------------------------------------------
    public function del()
    {
        try {
            Db::startTrans(); // 开始事务

            $normalizedIds = $this->normalizeIds(Request::param('ids')); // 标准化ID参数

            if (empty($normalizedIds)) {
                return c_error(lang('invalid_delete_params')); // 无效删除参数
            }

            // 查询当前数据
            $confs = ConfCategoryModel::where('id', 'in', $normalizedIds)->select();

            // 系统字段保护
            $protectedFields = [];
            foreach ($confs as $conf) {
                if ($conf->is_os == 1) {
                    $protectedFields[] = $conf->ename; // 收集受保护字段
                }
            }

            if (!empty($protectedFields)) {
                Db::rollback(); // 回滚事务
                return c_error(lang('system_field_protected', [implode(',', $protectedFields)])); // 系统字段保护
            }

            // 执行删除
            $deleteCount = ConfCategoryModel::where('id', 'in', $normalizedIds)->delete();

            if ($deleteCount === 0) {
                Db::rollback(); // 回滚事务
                return c_error(lang('no_data_to_delete')); // 没有可删除数据
            }

            // 记录操作日志
            $this->logFormAction(lang('delete_config_fields_log', [$deleteCount]));

            Db::commit(); // 提交事务
            return c_success(lang('delete_success', [$deleteCount])); // 删除成功

        } catch (\Throwable $e) {
            Db::rollback(); // 回滚事务
            Log::error('删除失败：' . $e->getMessage()); // 记录错误
            return c_error(lang('delete_failed') . $e->getMessage()); // 删除失败
        }
    }
}
