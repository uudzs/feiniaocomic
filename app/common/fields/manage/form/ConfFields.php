<?php

namespace app\common\fields\manage\form;

use app\common\model\manage\ConfCategory;
use app\common\model\manage\Conf as ConfModel;

class ConfFields
{
    // 解析选项
    private static function options(): array
    {
        $options = ConfCategory::getAllCategories(true);
        if (empty($options)) return [];
        $result = [];
        foreach ($options as $key => $value) {
            $result[] = [
                'id'    => $value['id'],
                'name'  => $value['ename'],
                'value' => $value['id'],
                'title' => $value['name']
            ];
        }
        return $result;
    }

    // 解析选项
    private static function getType(): array
    {
        $types = ConfModel::$typeConfig;
        if (empty($types)) return [];
        $result = [];
        foreach ($types as $key => $value) {
            if (!$key) continue;
            $result[] = [
                'value' => $key,
                'title' => $value
            ];
        }
        return $result;
    }

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
                        'title'       => '字段名称',
                        'required'    => true,
                        'maxlength'   => 120,
                        'verify'      => 'required',
                        'placeholder' => '最多120个字符',
                    ],
                    [
                        'name'        => 'ename',
                        'type'        => 'text',
                        'title'       => '字段标识',
                        'required'    => true,
                        'maxlength'   => 20,
                        'verify'      => 'required',
                        'placeholder' => '必须为10位以内小写英文字母',
                    ],
                    [
                        'name'        => 'model',
                        'type'        => 'select',
                        'title'       => '配置分组',
                        'required'    => true,
                        'verify'      => 'required',
                        'options'     => self::options(),
                    ],
                    [
                        'name'        => 'type',
                        'type'        => 'radio',
                        'title'       => '字段类型',
                        'required'    => true,
                        'verify'      => 'required',
                        'options'     => self::getType(),
                    ],
                    [
                        'name'      => 'values',
                        'type'      => 'textarea',
                        'title'     => '选项列表',
                        'maxlength' => 250,
                        'placeholder' => '单选/复选/下拉必填！多个选项用英文逗号分隔，举例：男,女,其他',
                    ],
                    [
                        'name'        => 'value',
                        'type'        => 'text',
                        'title'       => '默认内容',
                        'maxlength'   => 120,
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
                    ],
                    [
                        'name'        => 'is_os',
                        'type'        => 'radio',
                        'title'       => '系统字段',
                        'value' => '0',
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
