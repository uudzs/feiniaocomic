<?php

namespace app\common\fields\manage\row;

class PageList
{
    public static function getListConfig(): array
    {
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
                'title'  => '单页标题',
                'sort'   => true
            ],
            [
                'field'  => 'identifier',
                'title'  => '唯一标识',
                'sort'   => true
            ],
            [
                'field'  => 'created_at',
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
           
        ];

        // ==================== 工具栏配置 ====================
        $toolbar = [
            [
                'url'    => url('page/form'),
                'params' => [
                    'width' => '80%',
                    'height' => '80%'
                ],
                'event'  => 'openform',
                'icon'   => 'layui-icon-add-1',
                'text'   => '添加单页',
                'params' => [
                    'width' => '800px',
                    'height' => '800px'
                ]
            ],
            [
                'url'    => url('page/del'),
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
                'url'    => url('page/form') . '?id=',
                'text'   => '编辑',
                'event'  => 'openform',
                'params' => [
                    'width' => '80%',
                    'height' => '80%'
                ]
            ],
            [
                'url'    => url('page/del') . '?ids=',
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
                'placeholder' => '请输入单页标题',
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
            'dataUrl'      => url('page/getData'),
            'model'        => 'page',
            'listTitle'    => '单页管理',
            'viewParams'   => []
        ];
    }
}
