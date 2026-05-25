<?php

namespace app\common\fields\manage\row;

use think\facade\Request;
use app\common\model\manage\ConfCategory;

class ConfList
{

    // 解析选项
    private static function options(): array
    {
        $modelType = Request::param('model', '0');
        $options = ConfCategory::getAllCategories(true);
        if (empty($options)) return [];
        $result = [];
        foreach ($options as $key => $value) {
            $result[] = [
                'url'  => url('conf/lst', ['model' => $value['id']]),
                'title' => $value['name'],
                'active' => $modelType == $value['id']
            ];
        }
        return $result;
    }

    public static function getListConfig(): array
    {
        $requestParams = Request::param();
        $modelType = Request::param('model', '0');

        // ==================== 左侧导航配置 ====================
        $navItems = self::options();

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
                'field' => 'sort',
                'title' => '排序',
                'width' => 60,
                'align' => 'center',
                'edit' => 'text',
                'templet' => '#sortTpl'
            ],
            [
                'field' => 'title',
                'title' => '字段标题',
                'sort' => true
            ],
            [
                'field' => 'model',
                'title' => '设置位置',
                'sort' => true,
                'width' => 150,
                'align' => 'center'
            ],
            [
                'field' => 'ename',
                'title' => '字段代码',
                'width' => 150,
            ],
            [
                'field' => 'type',
                'title' => '字段类型',
                'sort' => true,
                'width' => 150,
                'align' => 'center',
                'templet' => '#typeTpl'
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
                'url' => url('conf/form'),
                'params' => [
                    'width' => '800px',
                    'height' => '80%'
                ],
                'event' => 'openform',
                'icon' => 'layui-icon-addition',
                'text' => '添加字段'
            ],
            [
                'url' => url('conf/del'),
                'params' => [],
                'event' => 'del',
                'icon' => 'layui-icon-delete',
                'text' => '批量删除',
                'class' => 'layui-btn-danger'
            ],
            [
                'url' => url('conf/conf', $requestParams),
                'text' => '返回配置',
                'params' => [],
                'event' => '',
                'icon' => 'layui-icon-tabs',
                'class' => 'layui-btn-normal'
            ]
        ];

        // ==================== 行操作配置 ====================
        $actions = [
            [
                'url' => url('conf/form') . '?id=',
                'text' => '编辑',
                'event' => 'openform',
                'params' => [
                    'width' => '800px',
                    'height' => '80%'
                ]
            ]
        ];

        // ==================== 搜索字段配置 ====================
        $searchFields = [
            [
                'type' => 'text',
                'name' => 'title',
                'placeholder' => '请输入字段标题',
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
            'navItems' => $navItems,
            'dataUrl' => url('conf/getConfData', $requestParams),
            'model' => 'conf',
            'listTitle' => '配置字段列表',
            'viewParams' => [
                'modelType' => $modelType
            ]
        ];
    }
}
