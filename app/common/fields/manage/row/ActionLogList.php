<?php
namespace app\common\fields\manage\row;

use think\facade\Session;

class ActionLogList
{

    public static function getListConfig(): array
    {
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
                'sort' => true,
                'align' => 'center'
            ],
            [
                'field' => 'uname',
                'title' => '操作用户',
                'width' => 200
            ],
            [
                'field' => 'module',
                'title' => '操作事项',
            ],
            [
                'field' => 'create_time',
                'title' => '操作时间',
                'width' => 180,
                'sort' => true,
                'align' => 'center'
            ],
            [
                'field' => 'ip',
                'title' => 'IP地址',
                'width' => 160,
                'align' => 'center'
            ],
            [
                'field' => 'os',
                'title' => '操作系统',
                'width' => 160,
                'align' => 'center'
            ]
        ];

        // ==================== 工具栏配置 ====================
        $toolbar = [];
        if (Session::get('admin_id') === 1) { 
            $toolbar = [
                [
                    'url'    => url('action/del'),
                    'params' => [],
                    'event'  => 'del',
                    'icon'   => 'layui-icon-delete',
                    'text'   => '批量删除',
                    'class'  => 'layui-btn-danger',
                ],
            ];
        }

        // ==================== 导航菜单配置 ====================
        $navItems = [
            [
                'url' => url('log/lst'),
                'title' => '登录日志',
                'active' => false
            ],
            [
                'url' => url('action/lst'),
                'title' => '操作日志',
                'active' => true
            ],
        ];

        // ==================== 搜索字段配置 ====================
        $searchFields = [
            [
                'type'        => 'text',
                'name'        => 'uname',
                'placeholder' => '请输入用户名称',
                'field'       => 'uname'  
            ],
        ];

        // ==================== 行操作配置 ====================
        $actions = [ ];

        // ==================== 返回配置数组 ====================
        return [
            'cols'         => json_encode([$cols], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'toolbar'      => $toolbar,
            'actions'      => $actions,
            'searchFields' => $searchFields,
            'dataUrl'      => url('action/getLogData'),
            'model'        => 'actionLog',
            'navItems'     => $navItems,
            'listTitle'    => '操作日志',
            'viewParams'   => [
                'moduleType' => 'actionLog'
            ]
        ];
    }
}