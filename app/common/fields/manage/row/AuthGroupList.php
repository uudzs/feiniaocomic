<?php

namespace app\common\fields\manage\row;

class AuthGroupList
{
    public static function getListConfig(): array
    {
        // ==================== 表格列配置 ====================
        $cols = [
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 70,
                'sort'  => true,
                'align' => 'center',
            ],
            [
                'field'   => 'title',
                'title'   => '用户组名称',
                'sort'    => true,
                'templet' => '#treeTitleTpl',
            ],
            [
                'field' => 'title_auth',
                'title' => '权限说明',
                'width' => 320,
            ],
            [
                'field'   => 'status',
                'title'   => '启用',
                'width'   => 60,
                'templet' => '#statusTpl',
                'align'   => 'center',
            ],
            [
                'fixed'   => 'right',
                'title'   => '操作',
                'toolbar' => '#barEdit',
                'width'   => 180,
                'align'   => 'center',
            ],
        ];

        // ==================== 工具栏配置 ====================
        $toolbar = [
            [
                'url'    => url('group/form'),
                'params' => [
                    'width'  => '600px',
                    'height' => '390px',
                ],
                'event'  => 'openform',
                'icon'   => 'layui-icon-addition',
                'text'   => '添加用户组',
            ],
            [
                'url'    => '',
                'params' => [],
                'text'   => '全部展开',
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
                'url'    => url('group/power') . '?id=',
                'text'   => '权限分配',
                'event'  => 'openform',
                'color'  => 'warm',
                'params' => [
                    'width'  => '600px',
                    'height' => '94%',
                ],
            ],
            [
                'url'    => url('group/form') . '?id=',
                'text'   => '编辑',
                'event'  => 'openform',
                'params' => [
                    'width'  => '600px',
                    'height' => '390px',
                ],
            ],
            [
                'url'   => url('group/del') . '?id=',
                'text'  => '删除',
                'color' => 'red',
                'event' => 'del',
            ],
        ];

        // ==================== 左侧导航配置 ====================
        $navItems = [
            [
                'url'    => url('admin/lst'),
                'title'  => '用户列表',
                'active' => '',
            ],
            [
                'url'    => url('group/lst'),
                'title'  => '用户组表',
                'active' => 1,
            ]
        ];

        // 仅超级管理员(id=1)显示权限列表导航项
        if (session('admin_id') == 1) {
            $navItems[] = [
                'url'    => url('rule/lst'),
                'title'  => '权限列表',
                'active' => ''
            ];
        }

        // ==================== 返回配置数组 ====================
        return [
            'cols'         => json_encode([$cols], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'toolbar'      => $toolbar,
            'actions'      => $actions,
            'navItems'     => $navItems,
            'dataUrl'      => url('group/getGroupData'),
            'model'        => 'group',
            'listTitle'    => '用户组表',
            'isTreeTable'  => true,
            'viewParams'   => [
                'moduleType' => 'group'
            ]
        ];
    }
}
