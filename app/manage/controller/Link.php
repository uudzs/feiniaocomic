<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Request;
use app\common\model\manage\Link as LinkModel;
use app\common\validate\manage\Link as LinkValidate;
use app\common\fields\manage\form\LinkFields;
use app\common\fields\manage\row\LinkList;
use Throwable;

// -----------------------------------------------------------------------------
// 友情链接控制器
// 负责友情链接的增删改查及展示功能
// -----------------------------------------------------------------------------
class Link extends Base
{
    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------
    protected $modelClass    = LinkModel::class;
    protected $validateClass = LinkValidate::class;

    // -------------------------------------------------------------------------
    // 表单处理方法
    // -------------------------------------------------------------------------
    public function form(?int $id = null)
    {
        // 非AJAX请求渲染视图
        if (!Request::isAjax()) {
            $model = $id ? LinkModel::find($id) : null;
            $currentData = $model ? $model->getData() : [];
            $pageTitle = $id ? lang('edit_link') : lang('add_link');
            $currentParentId = Request::param('type/d', 0);
            $formTabs = LinkFields::getFormTabs();

            return parent::renderFormView($formTabs, $currentData, $pageTitle, $currentParentId);
        }

        // AJAX请求处理数据提交
        return $this->processFormSubmission($id);
    }

    // -------------------------------------------------------------------------
    // 获取链接数据
    // -------------------------------------------------------------------------
    public function getLinkData()
    {
        $type = Request::param('type/d', 0);

        $searchFields = [
            'title'  => 's',
            'status' => 'd',
            'type'   => 'd'
        ];

        // 类型为0时移除类型过滤
        if ($type === 0) {
            unset($searchFields['type']);
        }

        return $this->getCommonData([
            'searchFields' => $searchFields,
            'field'        => 'title,thumb,id,sort,url,create_time,type,status',
            'order'        => 'sort asc, create_time DESC'
        ]);
    }

    // -------------------------------------------------------------------------
    // 链接列表页面
    // -------------------------------------------------------------------------
    public function lst()
    {
        $config = LinkList::getListConfig();
        return view('common/list', array_merge($config, $config['viewParams']));
    }

    // -------------------------------------------------------------------------
    // 私有辅助方法
    // -------------------------------------------------------------------------

    /** 处理表单提交 */
    private function processFormSubmission(?int $id)
    {
        try {
            $data = Request::param();
            $result = parent::handleFormSubmit($data, $id);

            $adTitle = $data['title'] ?? ($id ? "ID:{$id}" : lang('new_link'));
            $actionType = $id ? lang('edit') : lang('add');

            $this->logFormAction("{$actionType}" . lang('link') . "《{$adTitle}》");

            return json([
                'code' => $result['code'] ?? 200,
                'msg'  => $result['msg'] ?? ("{$actionType}" . lang('link_success')),
                'url'  => url('link/lst', ['type' => $data['type'] ?? 0])->build(),
                'data' => $result['data'] ?? []
            ]);
        } catch (Throwable $e) {
            return json([
                'code' => $e->getCode() ?: 500,
                'msg'  => $e->getMessage()
            ]);
        }
    }
}
