<?php

namespace app\common\fields\manage\row;

class ConfCategoryList
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
                'align' => 'center'
            ],
            [
                'field' => 'sort',
                'title' => '排序',
                'width' => 100,
                'align' => 'center',
                'edit' => 'text',
                'templet' => '#sortTpl'
            ],
            [
                'field' => 'name',
                'title' => '分组标题',
            ],
            [
                'field' => 'ename',
                'title' => '分组标识',
                'width' => 150,
            ],
            [
                'field' => 'status',
                'title' => '启用',
                'width' => 60,
                'templet' => '#statusTpl',
                'align' => 'center'
            ],
            [
                'fixed' => 'right',
                'title' => '操作',
                'toolbar' => '#barEdit',
                'width' => 100,
                'align' => 'center'
            ]
        ];

        // ==================== 工具栏配置 ====================
        $toolbar = [
            [
                'url' => url('confcategory/form'),
                'params' => [
                    'width' => '60%',
                    'height' => '50%'
                ],
                'event' => 'openform',
                'icon' => 'layui-icon-addition',
                'text' => '添加分组'
            ],
            [
                'url' => url('confcategory/del'),
                'params' => [],
                'event' => 'del',
                'icon' => 'layui-icon-delete',
                'text' => '批量删除',
                'class' => 'layui-btn-danger'
            ]
        ];

        // ==================== 行操作配置 ====================
        $actions = [
            [
                'url' => url('confcategory/form') . '?id=',
                'text' => '编辑',
                'event' => 'openform',
                'params' => [
                    'width' => '60%',
                    'height' => '50%'
                ]
            ]
        ];

        // ==================== 搜索分组配置 ====================
        $searchFields = [
            [
                'type' => 'text',
                'name' => 'name',
                'placeholder' => '请输入分组标题',
            ],
            [
                'type' => 'select',
                'name' => 'status',
                'placeholder' => '状态',
                'width'       => '80px',
                'options' => [
                    ['value' => 1, 'name' => '启用'],
                    ['value' => 0, 'name' => '隐藏']
                ]
            ]
        ];

        // ==================== 返回配置数组 ====================
        return [
            'cols' => json_encode([$cols], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE),
            'toolbar' => $toolbar,
            'actions' => $actions,
            'searchFields' => $searchFields,
            'dataUrl' => url('confcategory/getData'),
            'model' => 'confCategory',
            'listTitle' => '配置分组列表'
        ];
    }
}
