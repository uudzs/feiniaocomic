<?php

namespace app\common\fields\manage\row;

use think\facade\Session;
use app\common\model\manage\AuthGroup;

class AdminList
{

    public static function getListConfig(): array
    {
        $currentUserId = Session::get('admin_id');
        $currentGroupId = Session::get('admin_gid');

        // ==================== 表格列配置 ====================
        $cols = [
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 70,
                'sort' => true,
                'align' => 'center'
            ],
            [
                'field' => 'group_title',
                'title' => '所属用户组',
                'width' => 120,
                'align' => 'center'
            ],
            [
                'field' => 'title',
                'title' => '用户名称',
                'sort' => true
            ],
            [
                'field' => 'is_current',
                'title' => '是否当前登录用户',
                'width' => 200,
                'align' => 'center',
                'templet' => '#currentUserTpl'
            ],
            [
                'field' => 'status',
                'title' => '显示',
                'width' => 60,
                'templet' => '#statusTpl',
                'align' => 'center'
            ],
            [
                'fixed' => 'right',
                'title' => '操作',
                'toolbar' => '#barEdit',
                'width' => 150,
                'align' => 'center'
            ]
        ];

        // ==================== 导航菜单配置 ====================
        $navItems = [];
        if ($currentGroupId <= 2) {
            $navItems = [
                [
                    'url' => url('admin/lst'),
                    'title' => '用户列表',
                    'active' => 1
                ],
                [
                    'url' => url('group/lst'),
                    'title' => '用户组表',
                    'active' => 0
                ]
            ];

            // 仅超级管理员显示权限列表
            if ($currentUserId == 1) {
                $navItems[] = [
                    'url'    => url('rule/lst'),
                    'title'  => '权限列表',
                    'active' => 0
                ];
            }
        }

        // ==================== 工具栏配置 ====================
        $toolbar = [];
        if ($currentGroupId <= 2) {
            $toolbar = [
                [
                    'url' => url('admin/add'),
                    'params' => [
                        'width' => '50%',
                        'height' => '50%'
                    ],
                    'event' => 'openform',
                    'icon' => 'layui-icon-addition',
                    'text' => '添加用户'
                ],
                [
                    'url' => url('log/lst'),
                    'text' => '登录日志',
                    'params' => [],
                    'event' => '',
                    'icon' => 'layui-icon-tabs',
                    'class' => 'layui-btn-normal'
                ],
                [
                    'url' => url('action/lst'),
                    'text' => '操作日志',
                    'params' => [],
                    'event' => '',
                    'icon' => 'layui-icon-link',
                    'class' => 'layui-btn-warm'
                ]
            ];
        }

        // ==================== 行操作配置 ====================
        $actions = [
            [
                'url' => url('admin/edit') . '?id=',
                'text' => '编辑',
                'event' => 'openform',
                'params' => [
                    'width' => '600px',
                    'height' => '400px'
                ]
            ],
        ];

        // 仅超级管理员显示删除按钮
        if ($currentUserId == 1) {
            $actions[] = [
                'url' => url('admin/del') . '?id=',
                'text' => '删除',
                'color' => 'red',
                'params' => [],
                'event' => 'del'
            ];
        }

        // ==================== 搜索字段配置 ====================
        $searchFields = [
            // 用户列表暂时不需要搜索字段，可根据需要添加
            // [
            //     'type'        => 'text',
            //     'name'        => 'title',
            //     'placeholder' => '请输入用户名称',
            // ],
        ];

        // ==================== 返回配置数组 ====================
        return [
            'cols'         => json_encode([$cols], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'toolbar'      => $toolbar,
            'actions'      => $actions,
            'searchFields' => $searchFields,
            'dataUrl'      => url('admin/getUserData'),
            'model'        => 'Admin',
            'navItems'     => $navItems,
            'listTitle'    => '用户列表',
            'viewParams'   => [
                'moduleType' => 'admin'
            ]
        ];
    }

    /**
     * 获取树形结构数据（供表单使用）
     */
    public static function getGroupTreeData(): array
    {
        return AuthGroup::getTreeData(
            withPrefix: true,
            fields: ['id', 'pid', 'title', 'status', 'title_auth'],
            scope: 'filterSuperAdmin'
        );
    }
}
