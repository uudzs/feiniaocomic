<?php

declare(strict_types=1);

namespace app\common\validate\manage;

use think\Validate;

class Comic extends Validate
{

    //------------------------------
    // 验证规则 (多语言字段校验)
    //------------------------------
    protected $rule = [
        'cateid' => 'require|checkCateid',
        'title'  => 'require|max:250',
        'desc'   => 'max:250',
        'thumb'  => 'regex:/^[\w\/\.-]+$/|max:250', // 允许英文/数字/下划线/斜线/点/横线 
    ];

    //------------------------------
    // 多语言错误提示（中英对照）
    //------------------------------
    protected $message = [
        'title.require' => '漫画标题必填',
        'title.max'     => '漫画标题超过250字限制',
        'desc.max'      => '漫画简介超过250字限制',
        'thumb.regex' => '漫画封面路径只允许英文、数字、_、/、. 和 - 字符',
        'thumb.max'   => '漫画封面路径超过250字符限制',
    ];
    //------------------------------
    // 验证栏目
    //------------------------------
    protected function checkCateid($value, $rule, $data)
    {
        if ($value == 0) {
            return '必须选择有效栏目';
        }
        return true;
    }
    //------------------------------
    // 场景配置（保持操作一致性）
    //------------------------------
    protected $scene = [
        'add'  => [
            'title',
            'desc',
            'thumb',
            'cateid'
        ],
        'edit' => [
            'title',
            'desc',
            'thumb',
            'cateid'
        ]
    ];
}
