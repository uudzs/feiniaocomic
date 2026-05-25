<?php

namespace app\common\fields\manage\form;

class AuthRuleFields
{
    // 权限控制字段配置
    public static function getFormTabs(): array
    {
        return [
            [
                'title'  => '基本内容',
                'name'   => 'base',
                'icon'   => '',
                'fields' => [
                    [
                        'name'        => 'pid',
                        'type'        => 'select',
                        'title'       => '上级权限',
                        'required'    => true,
                        'verify'      => 'required',
                        'placeholder' => '顶级权限',
                        'use_parent_id' => true,
                        'options'     => [],
                    ],
                    [
                        'name'        => 'title',
                        'type'        => 'text',
                        'title'       => '权限名称',
                        'maxlength'   => 120,
                        'required'    => true,
                        'verify'      => 'required',
                        'placeholder' => '标题最多120个字符',
                    ],
                    [
                        'name'        => 'name',
                        'type'        => 'text',
                        'title'       => '权限代码',
                        'required'    => true,
                        'verify'      => 'required',
                        'maxlength'   => 120,
                        'placeholder' => '必须为英文，例：cate_edit',
                    ],
                    [
                        'name'        => 'rule',
                        'type'        => 'text',
                        'title'       => '权限路由',
                        'maxlength'   => 120,
                        'placeholder' => '必须为英文，例：cate_edit',
                    ],
                    [
                        'name'        => 'icon',
                        'type'        => 'text',
                        'title'       => '权限图标',
                        'maxlength'   => 120,
                        'placeholder' => '例：layui-icon-picture',
                    ],
                    [
                        'name'      => 'show',
                        'type'      => 'radio',
                        'title'     => '左侧导航',
                        'options'     => [

                            [
                                'name' => '否',
                                'value' => '0',
                                'title' => '否',
                            ],
                            [
                                'name' => '是',
                                'value' => '1',
                                'title' => '是',
                            ]
                        ],
                    ],
                    [
                        'name'      => 'status',
                        'type'      => 'radio',
                        'title'     => '启用状态',
                        'options'     => [
                            [
                                'name' => '是',
                                'value' => '1',
                                'title' => '是',
                            ],
                            [
                                'name' => '否',
                                'value' => '0',
                                'title' => '否',
                            ]
                        ],
                    ]
                ],
            ],

        ];
    }
}
