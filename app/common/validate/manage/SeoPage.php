<?php

namespace app\common\validate\manage;

use think\Validate;

class SeoPage extends Validate
{
    protected $rule = [
        'id'          => 'require|number',
        'module'      => 'require|max:32',
        'page_key'    => 'require|max:64|alphaDash',
        'title'       => 'max:200',
        'keywords'    => 'max:500',
        'description' => 'max:1000',
        'status'      => 'in:0,1',
    ];

    protected $message = [
        'id.require'          => 'ID不能为空',
        'id.number'           => 'ID必须为数字',
        'module.require'      => '所属模块不能为空',
        'module.max'         => '所属模块最多32个字符',
        'page_key.require'    => '页面标识不能为空',
        'page_key.max'       => '页面标识最多64个字符',
        'page_key.alphaDash' => '页面标识只能包含字母、数字、破折号和下划线',
        'title.max'          => '标题最多200个字符',
        'keywords.max'       => '关键词最多500个字符',
        'description.max'    => '描述最多1000个字符',
        'status.in'          => '状态值非法',
    ];

    protected $scene = [
        'add' => ['module', 'page_key', 'title', 'keywords', 'description'],
        'edit' => ['id', 'module', 'page_key', 'title', 'keywords', 'description', 'status'],
    ];
}
