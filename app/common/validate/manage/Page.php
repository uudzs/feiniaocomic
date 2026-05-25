<?php

namespace app\common\validate\manage;

use think\Validate;

/**
 * 单页内容验证器
 */
class Page extends Validate
{
    protected $rule = [
        'title'       => 'require|max:100',
        'identifier'  => 'require|alphaDash|max:50|unique:page,identifier',
        'content'     => 'require',
        'status'      => 'in:0,1',
        'sort'        => 'number|between:0,9999',
    ];
    
    protected $message = [
        'title.require'      => '标题不能为空',
        'title.max'          => '标题不能超过100个字符',
        'identifier.require' => '标识不能为空',
        'identifier.alphaDash' => '标识只能包含字母、数字、下划线和破折号',
        'identifier.max'     => '标识不能超过50个字符',
        'identifier.unique'  => '标识已存在',
        'content.require'    => '内容不能为空',
        'status.in'          => '状态值不正确',
        'sort.number'        => '排序必须是数字',
        'sort.between'       => '排序值必须在0-9999之间',
    ];
    
    protected $scene = [
        'create' => ['title', 'identifier', 'content', 'status', 'sort'],
        'update' => ['title', 'content', 'status', 'sort'],
    ];
}
