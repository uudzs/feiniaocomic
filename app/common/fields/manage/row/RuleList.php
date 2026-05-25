<?php

namespace app\common\fields\manage\row;

class RuleList
{
    public static function getListConfig(): array
    {
        // ==================== 表格列配置 ====================
        $cols = [

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
                'title'  => '权限标题',
                'sort'   => true,
                'templet' => '#treeTitleTpl'
            ],
            [
                'field'  => 'name',
                'title'  => '权限代码',
                'width'  => 200
            ],
            [
                'field'  => 'show',
                'title'  => '左侧菜单',
                'width'  => 80,
                'templet' => '#showTpl',
                'align'  => 'center'
            ],
            [
                'field'  => 'status',
                'title'  => '启用',
                'width'  => 60,
                'templet' => '#statusTpl',
                'align'  => 'center'
            ],
            [
                'fixed'  => 'right',
                'title'  => '操作',
                'toolbar' => '#barEdit',
                'width'  => 200,
                'align'  => 'center'
            ]
        ];

        // ==================== 左侧导航配置 ====================
        $navItems = [
            [
                'url'    => url('admin/lst'),
                'title'  => '用户列表',
                'active' => false
            ],
            [
                'url'    => url('group/lst'),
                'title'  => '用户组表',
                'active' => false
            ]
        ];

        // ==================== 工具栏配置 ====================
        $toolbar = [
            [
                'url'    => url('rule/form'),
                'params' => [
                    'width'  => '800px',
                    'height' => '600px'
                ],
                'event'  => 'openform',
                'icon'   => 'layui-icon-addition',
                'text'   => '添加权限'
            ],
            [
                'url'    => '',
                'params' => [],
                'text'   => '全部展开',
                'id'     => 'btn-expand',
                'event'  => 'expandAll',
                'icon'   => 'layui-icon-down',
                'class'  => 'layui-btn-primary'
            ],
            [
                'url'    => '',
                'params' => [],
                'text'   => '全部折叠',
                'event'  => 'collapseAll',
                'icon'   => 'layui-icon-up',
                'class'  => 'layui-btn-primary'
            ]
        ];

        // ==================== 行操作配置 ====================
        $actions = [
            [
                'url'    => url('rule/form') . '?addpid=',
                'text'   => '添加子权限',
                'event'  => 'openform',
                'color'  => 'green',
                'params' => [
                    'width'  => '800px',
                    'height' => '600px'
                ]
            ],
            [
                'url'    => url('rule/form') . '?id=',
                'text'   => '编辑',
                'event'  => 'openform',
                'params' => [
                    'width'  => '800px',
                    'height' => '600px'
                ]
            ],
            [
                'url'    => url('rule/del') . '?id=',
                'text'   => '删除',
                'color'  => 'red',
                'event'  => 'del'
            ]
        ];

        // ==================== 返回配置集合 ====================
        return [
            'cols'         => json_encode([$cols], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'navItems'     => $navItems,
            'toolbar'      => $toolbar,
            'actions'      => $actions,
            'dataUrl'      => url('rule/getRuleData'),
            'model'        => 'authRule',
            'listTitle'    => '权限列表',
            'viewParams'   => [
                'isTreeTable' => true
            ]
        ];
    }
}
