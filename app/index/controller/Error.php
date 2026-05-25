<?php

declare(strict_types=1);

namespace app\index\controller;

class Error
{
    public function index()
    {
        if ($this->isApiRequest()) {
            return json([
                'code' => 404,
                'message' => '页面不存在',
            ]);
        }
        return view('404');
    }

    /**
     * 判断是否为API请求
     * 兼容不带 X-Requested-With 头部的 AJAX 请求
     */
    protected function isApiRequest(): bool
    {
        $request = request();

        // 传统 AJAX 请求
        if ($request->isAjax()) {
            return true;
        }

        // API 路径
        if (strpos($request->pathinfo(), 'api/') === 0) {
            return true;
        }

        // 期望 JSON 响应
        if (strpos($request->header('accept', ''), 'application/json') !== false) {
            return true;
        }

        return false;
    }
}
