<?php

declare(strict_types=1);

namespace app\common\paginator;

use think\paginator\driver\Bootstrap;

/**
 * 自定义分页驱动 - 支持路径风格分页
 * 第1页: /comic/index.html
 * 第2页: /comic/index/2.html
 */
class PagePath extends Bootstrap
{
    /**
     * 获取页码对应的链接
     *
     * @param int $page
     * @return string
     */
    protected function url(int $page): string
    {
        if ($page <= 0) {
            $page = 1;
        }

        // 如果 path 包含 [PAGE]，处理路径风格分页
        if (str_contains($this->options['path'], '[PAGE]')) {
            // 第1页不显示页码
            if ($page === 1) {
                // 处理多种格式的第一页路径：
                // /path/[PAGE].html -> /path.html
                // /path-xxx-[PAGE].html -> /path-xxx.html
                $path = preg_replace('/[-\/]\[PAGE\]\.html$/', '.html', $this->options['path']);
            } else {
                $path = str_replace('[PAGE]', (string) $page, $this->options['path']);
            }
            $parameters = [];
        } else {
            // 默认查询参数风格
            $parameters = [$this->options['var_page'] => $page];
            $path = $this->options['path'];
        }

        if (count($this->options['query']) > 0) {
            $parameters = array_merge($this->options['query'], $parameters);
        }

        $url = $path;
        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters, '', '&');
        }

        return $url . $this->buildFragment();
    }
}
