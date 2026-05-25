<?php

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Db;
use think\facade\Log;
use app\common\model\manage\Page as PageModel;
use app\common\validate\manage\Page as PageValidate;
use app\common\fields\manage\form\PageFields;
use app\common\fields\manage\row\PageList;
use think\facade\Request;

/**
 * 单页内容控制器
 */
class Page extends Base
{

    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------
    protected $modelClass    = PageModel::class;
    protected $validateClass = PageValidate::class;

    // -------------------------------------------------------------------------
    // 获取链接数据
    // -------------------------------------------------------------------------
    public function getData()
    {
        $searchFields = [
            'title'  => 's',
        ];

        return $this->getCommonData([
            'searchFields' => $searchFields,
            'field'        => 'title,identifier,id,sort,created_at,status',
            'order'        => 'sort asc, created_at DESC'
        ]);
    }

    // -------------------------------------------------------------------------
    // 链接列表页面
    // -------------------------------------------------------------------------
    public function lst()
    {
        $config = PageList::getListConfig();
        return view('common/list', array_merge($config, $config['viewParams']));
    }

    // -------------------------------------------------------------------------
    // 表单处理方法
    // -------------------------------------------------------------------------
    public function form(?int $id = null)
    {
        // 非AJAX请求渲染视图
        if (!Request::isAjax()) {
            $currentData = $id ? PageModel::find($id)->getData() : []; // 当前数据
            $pageTitle = $id ? lang('edit') : lang('add'); // 页面标题
            $formTabs = PageFields::getFormTabs(); // 获取表单配置
            return parent::renderFormView( // 调用基类渲染方法
                $formTabs,
                $currentData,
                $pageTitle
            );
        }

        // AJAX请求处理数据提交
        try {
            $data = Request::param(); // 获取请求数据

            // 调用基类处理表单提交
            $result = parent::handleFormSubmit($data, $id);

            // 记录操作日志
            $this->logFormAction('单页管理', $data, 'title');

            // 返回操作结果
            return json([
                'code'                => $result['code'] ?? 200,
                'msg'                 => $result['msg'] ?? '操作成功',
                'data' => $result['data'] ?? [], // 返回数据
                'url' => url('page/lst')->build(), // 返回URL
            ]);
        } catch (\Throwable $e) {
            return json([
                'code' => $e->getCode() ?: 500, // 错误代码
                'msg'  => $e->getMessage(), // 错误消息
            ]);
        }
    }

    /**
     * 删除数据
     */
    public function del($id)
    {
        try {
            Db::startTrans(); // 开始事务

            $normalizedIds = $this->normalizeIds(Request::param('ids')); // 标准化ID参数

            if (empty($normalizedIds)) {
                return c_error(lang('invalid_delete_params')); // 无效删除参数
            }

            // 执行删除
            $deleteCount = PageModel::where('id', 'in', $normalizedIds)->delete();

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
