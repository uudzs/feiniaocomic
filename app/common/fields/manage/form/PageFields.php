<?php

namespace app\common\fields\manage\form;

class PageFields
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
                        'name'        => 'title',
                        'type'        => 'text',
                        'title'       => '标题',
                        'required'    => true,
                        'maxlength'   => 100,
                        'verify'      => 'required',
                        'placeholder' => '标题最多100个字符',
                    ],
                    [
                        'name'    => 'identifier',
                        'type'    => 'text',
                        'title'   => '唯一标识',
                        'required'    => true,
                        'maxlength'   => 50,
                        'verify'      => 'required',
                        'placeholder'   => '标题最多50个字符，由英文、数字构成，不要有中文或特殊符号！',
                    ],
                    [
                        'name'      => 'sort',
                        'type'      => 'text',
                        'title'     => '排序',
                        'placeholder' => '如：50',
                    ],
                    [
                        'name'        => 'content',
                        'type'        => 'editor',
                        'title'       => '内容',
                        'editor'      => 'content',
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
                ],
            ],

        ];
    }
}
