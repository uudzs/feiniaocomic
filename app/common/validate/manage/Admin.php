<?php

declare(strict_types=1);

namespace app\common\validate\manage;

use think\Validate;

class Admin extends Validate
{
    //—————————————————————————————————————————————————
    // 通用验证规则
    //—————————————————————————————————————————————————
    private const COMMON_PWD_RULE = 'regex:/^[\w!@#$%^&*()\-+=.]+$/|min:6|max:128';
    private const COMMON_NAME_RULE = 'alphaDash|max:60';

    //—————————————————————————————————————————————————
    // 验证规则配置
    //—————————————————————————————————————————————————
    protected $rule = [
        'uname'      => 'require|' . self::COMMON_NAME_RULE . '|unique:admin',
        'nickname'   => 'require|max:120',
        'password'   => 'require|' . self::COMMON_PWD_RULE,
        'repassword' => 'confirm:password',
        'newpassword'    => self::COMMON_PWD_RULE,
        'newrepassword'  => 'requireWith:newpassword|confirm:newpassword|' . self::COMMON_PWD_RULE
    ];

    //—————————————————————————————————————————————————
    // 自然语言提示信息
    //—————————————————————————————————————————————————
    protected $message = [
        // 账号相关
        'uname.require'      => '请填写登录账号',
        'uname.alphaDash'    => '账号格式不正确，只能包含字母、数字和下划线',
        'uname.max'          => '账号长度不能超过60个字符',
        'uname.unique'       => '该账号已被注册，请换一个',

        // 昵称相关
        'nickname.require'   => '请填写用户昵称',
        'nickname.max'       => '昵称长度不能超过120个字符',

        // 密码相关通用提示
        'password.require'   => '请设置登录密码',
        'password.min'       => '密码安全性较低，至少需要6个字符',
        'password.max'       => '密码长度超出限制，最多128个字符',
        'password.regex'     => '密码只能包含字母、数字、下划线及!@#$%^&*()-+=.等符号',

        // 确认密码
        'repassword.confirm' => '两次输入的密码不一致，请重新输入',

        // 修改密码场景
        'newpassword.regex'  => '新密码只能包含字母、数字、下划线及!@#$%^&*()-+=.等符号',
        'newpassword.min'    => '新密码至少需要6个字符',
        'newpassword.max'    => '新密码长度不能超过128个字符',

        'newrepassword.requireWith' => '请确认新密码',
        'newrepassword.confirm'     => '新密码与确认密码不一致',
        'newrepassword.regex'       => '确认密码包含非法字符',
        'newrepassword.min'         => '确认密码至少需要6个字符',
        'newrepassword.max'         => '确认密码长度不能超过128个字符'
    ];

    //—————————————————————————————————————————————————
    // 验证场景配置
    //—————————————————————————————————————————————————
    protected $scene = [
        'add'  => ['uname', 'nickname', 'password', 'repassword'],
        'edit' => ['nickname', 'newpassword', 'newrepassword'],
    ];

    //—————————————————————————————————————————————————
    // 字段别名
    //—————————————————————————————————————————————————
    protected $field = [
        'uname'       => '登录账号',
        'nickname'    => '用户昵称',
        'password'    => '登录密码',
        'repassword'  => '确认密码',
        'newpassword' => '新密码',
        'newrepassword' => '确认新密码'
    ];
}
