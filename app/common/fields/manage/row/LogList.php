<?php

namespace app\common\fields\manage\row;

use think\facade\Session;

class LogList
{

    public static function getListConfig(): array
    {
        $currentUserId = Session::get('admin_id');

        // ==================== 表格列配置 ====================
        $cols = [
            [
                'type' => 'checkbox',
                'fixed' => 'left'
            ],
            [
                'field' => 'id',
                'title' => 'ID',
                'width' => 70,
                'sort'  => true,
                'align' => 'center'
            ],
            [
                'field' => 'uname',
                'title' => '管理员名称',
                'width' => 180,
            ],
            [
                'field' => 'login_time',
                'title' => '登陆时间',
                'width' => 180,
                'sort'  => true,
                'align' => 'center'
            ],
            [
                'field' => 'login_ip',
                'title' => '登陆IP',
                'width' => 180,
                'align' => 'center'
            ],
            [
                'field' => 'login_os',
                'title' => '登陆操作系统',
            ],
        ];

        // ==================== 导航菜单配置 ====================
        $navItems = [
            [
                'url'    => url('log/lst'),
                'title'  => '登录日志',
                'active' => 1,
            ],
            [
                'url'    => url('action/lst'),
                'title'  => '操作日志',
                'active' => 0,
            ]
        ];

        // ==================== 工具栏配置 ====================
        $toolbar = [];
        if ($currentUserId === 1) {
            $toolbar = [
                [
                    'url'    => url('log/del'),
                    'params' => [],
                    'event'  => 'del',
                    'icon'   => 'layui-icon-delete',
                    'text'   => '批量删除',
                    'class'  => 'layui-btn-danger',
                ],
            ];
        }

        // ==================== 行操作配置 ====================
        $actions = [
            // 登录日志通常不需要行操作，可根据需要添加
        ];

        // ==================== 搜索字段配置 ====================
        $searchFields = [
            // 可根据需要添加搜索字段
            // [
            //     'type'        => 'text',
            //     'name'        => 'uname',
            //     'placeholder' => '请输入管理员名称',
            // ],
            // [
            //     'type'        => 'text',
            //     'name'        => 'login_ip',
            //     'placeholder' => '请输入登录IP',
            // ],
        ];

        // ==================== 返回配置数组 ====================
        return [
            'cols'         => json_encode([$cols], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'toolbar'      => $toolbar,
            'actions'      => $actions,
            'searchFields' => $searchFields,
            'dataUrl'      => url('log/getLogData'),
            'model'        => 'log',
            'navItems'     => $navItems,
            'listTitle'    => '登陆日志',
            'viewParams'   => [
                'moduleType' => 'log'
            ]
        ];
    }
}
