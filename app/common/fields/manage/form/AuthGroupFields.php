<?php

namespace app\common\fields\manage\form;

class AuthGroupFields
{
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
                        'title'       => '所属用户组',
                        'required'    => true,
                        'verify'      => 'required',
                        'placeholder' => '请选择位置',
                        'options'     => [], // 留空，动态填充
                    ],
                    [
                        'name'        => 'title',
                        'type'        => 'text',
                        'title'       => '用户组名称',
                        'required'    => true,
                        'maxlength'   => 120,
                        'verify'      => 'required',
                        'placeholder' => '标题最多60个字符',
                    ],
                    [
                        'name'        => 'title_auth',
                        'type'        => 'text',
                        'title'       => '用户组描述',
                        'maxlength'   => 120,
                        'placeholder' => '最多60个字符',
                    ],
                    [
                        'name'        => 'status',
                        'type'        => 'radio',
                        'title'       => '是否启用',
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
                            ],

                        ],
                    ],
                ]
            ]

        ];
    }
}
