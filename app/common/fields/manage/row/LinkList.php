<?php

namespace app\common\fields\manage\row;

use think\facade\Request;

class LinkList
{
    public static function getListConfig(): array
    {
        $type = Request::param('type/d', 0);

        // ==================== 表格列配置 ====================
        $cols = [
            [
                'type'   => 'checkbox',
                'fixed'  => 'left'
            ],
            [
                'field'  => 'id',
                'title'  => 'ID',
                'width'  => 70,
                'sort'   => true,
                'align'  => 'center'
            ],
            [
                'field'  => 'sort',
                'title'  => '排序',
                'width'  => 60,
                'align'  => 'center',
                'edit'   => 'text',
                'templet' => '#sortTpl'
            ],
            [
                'field'  => 'title',
                'title'  => '链接标题',
                'sort'   => true
            ],
            [
                'field'  => 'url',
                'title'  => '链接地址',
                'sort'   => true
            ],
            [
                'field'  => 'thumb',
                'title'  => '封面图',
                'width'  => 80,
                'align'  => 'center',
                'templet' => '#thumbTpl'
            ],
            [
                'field'  => 'type',
                'title'  => '链接类型',
                'width'  => 100,
                'align'  => 'center',
                'templet' => '#typeTpl'
            ],
            [
                'field'  => 'create_time',
                'title'  => '添加时间',
                'width'  => 120,
                'align'  => 'center',
                'sort'   => true
            ],
            [
                'field'  => 'status',
                'title'  => '显示',
                'width'  => 60,
                'templet' => '#statusTpl',
                'align'  => 'center'
            ],
            [
                'fixed'  => 'right',
                'title'  => '操作',
                'toolbar' => '#barEdit',
                'width'  => 100,
                'align'  => 'center'
            ]
        ];

        // ==================== 分类导航 ====================
        $navItems = [
            [
                'url'    => url('link/lst'),
                'title'  => '所有链接',
                'active' => $type == 0
            ],
            [
                'url'    => url('link/lst', ['type' => 1]),
                'title'  => '文字链接',
                'active' => $type == 1
            ],
            [
                'url'    => url('link/lst', ['type' => 2]),
                'title'  => '图片链接',
                'active' => $type == 2
            ]
        ];

        // ==================== 工具栏配置 ====================
        $toolbar = [
            [
                'url'    => url('link/form'),
                'params' => ['height' => '800px'],
                'event'  => 'openform',
                'icon'   => 'layui-icon-add-1',
                'text'   => '添加链接',
                'params' => [
                    'width' => '800px',
                    'height' => '800px'
                ]
            ],
            [
                'url'    => url('link/del'),
                'event'  => 'del',
                'icon'   => 'layui-icon-delete',
                'text'   => '批量删除',
                'params' => [],
                'class'  => 'layui-btn-danger'
            ]
        ];

        // ==================== 行操作配置 ====================
        $actions = [
            [
                'url'    => url('link/form') . '?id=',
                'text'   => '编辑',
                'event'  => 'openform',
                'params' => [
                    'width' => '800px',
                    'height' => '800px'
                ]
            ],
            [
                'url'    => url('link/del') . '?id=',
                'text'   => '删除',
                'event'  => 'del',
                'color'  => 'red',
                'event'  => 'del'
            ]
        ];

        // ==================== 搜索字段配置 ====================
        $searchFields = [
            [
                'type'        => 'text',
                'name'        => 'title',
                'placeholder' => '请输入链接标题',
                'width'       => '180px'
            ],

            [
                'type'        => 'select',
                'name'        => 'status',
                'placeholder' => '启用',
                'width'       => '80px',
                'options'     => [
                    ['value' => 1,  'name' => '启用'],
                    ['value' => 0,  'name' => '隐藏']
                ]
            ],

        ];

        // ==================== 返回配置集合 ====================
        return [
            'cols'         => json_encode([$cols], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'navItems'     => $navItems,
            'toolbar'      => $toolbar,
            'actions'      => $actions,
            'searchFields' => $searchFields,
            'dataUrl'      => url('link/getLinkData', ['type' => $type]),
            'model'        => 'link',
            'listTitle'    => '友情链接管理',
            'viewParams'   => [
                'type' => $type,
                'moduleType' => 'link'
            ]
        ];
    }
}
