<?php

namespace app\common\fields\manage\form;

class LinkFields
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
                        'name'        => 'type',
                        'type'        => 'radio',
                        'title'       => '链接类型',
                        'required'    => true,
                        'verify'      => 'required',
                        'options'     => [
                            [
                                'title'  => '文字链接',
                                'value' => '1',
                            ],
                            [
                                'title'  => '图片链接',
                                'value' => '2',
                            ]
                        ],

                    ],
                    [
                        'name'        => 'title',
                        'type'        => 'text',
                        'title'       => '链接标题',
                        'required'    => true,
                        'maxlength'   => 120,
                        'verify'      => 'required',
                        'placeholder' => '标题最多120个字符',
                    ],
                    [
                        'name'    => 'thumb',
                        'type'    => 'image',
                        'title'   => '链接图片',
                        'placeholder'   => '请上传小于600kb、jpg、jpeg、png、gif格式图片。',
                    ],
                    [
                        'name'        => 'url',
                        'type'        => 'text',
                        'title'       => '链接地址',
                        'placeholder' => 'https://',
                        'maxlength'   => 120,
                    ],
                    [
                        'name'      => 'desc',
                        'type'      => 'textarea',
                        'title'     => '链接描述',
                        'maxlength' => 250,
                    ],
                ],
            ],

        ];
    }
}
