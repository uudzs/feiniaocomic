<?php

namespace app\common\fields\manage\form;

class ConfCategoryFields
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
                        'name'        => 'name',
                        'type'        => 'text',
                        'title'       => '分组名称',
                        'required'    => true,
                        'maxlength'   => 100,
                        'verify'      => 'required',
                        'placeholder' => '最多100个字符',
                    ],
                    [
                        'name'        => 'ename',
                        'type'        => 'text',
                        'title'       => '分组标识',
                        'required'    => true,
                        'maxlength'   => 100,
                        'verify'      => 'required',
                        'placeholder' => '必须为100位以内小写英文字母',
                    ],
                    [
                        'name'      => 'sort',
                        'type'      => 'text',
                        'title'     => '排序',
                        'value' => '50',
                        'placeholder' => '如：50',
                    ],
                    [
                        'name'        => 'status',
                        'type'        => 'radio',
                        'title'       => '是否启用',
                        'value' => '1',
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
                    ]
                ],
            ],

        ];
    }
}
