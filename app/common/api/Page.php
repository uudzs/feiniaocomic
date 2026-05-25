<?php

namespace app\common\api;

use app\BaseController;
use app\common\model\manage\Page as PageModel;

/**
 * 单页内容API
 */
class Page extends BaseController
{
    /**
     * 根据标识获取单页内容
     */
    public function get()
    {
        $identifier = input('identifier/s', '');

        if (empty($identifier)) {
            return $this->error('标识不能为空');
        }

        $pageModel = new PageModel();
        $page = $pageModel->getByIdentifier($identifier);

        if (!$page) {
            return $this->error('标识不能为空', 404);
        }

        // 只返回状态为显示的内容
        if ($page->status != 1) {
            return $this->error('单页内容不存在', 404);
        }

        return $this->success([
            'id' => $page->id,
            'title' => $page->title,
            'identifier' => $page->identifier,
            'content' => $page->content,
            'created_at' => $page->created_at,
            'updated_at' => $page->updated_at,
        ]);
    }

    /**
     * 获取多个单页内容
     */
    public function gets()
    {
        $identifiers = input('identifiers/s', '');

        if (empty($identifiers)) {
            return $this->error('标识不能为空');
        }

        $identifierList = explode(',', $identifiers);
        $pageModel = new PageModel();
        $pages = $pageModel->where('identifier', 'in', $identifierList)->where('status', 1)->select();

        $data = [];
        foreach ($pages as $page) {
            $data[$page->identifier] = [
                'id' => $page->id,
                'title' => $page->title,
                'identifier' => $page->identifier,
                'content' => $page->content,
                'created_at' => $page->created_at,
                'updated_at' => $page->updated_at,
            ];
        }

        return $this->success($data);
    }
}
