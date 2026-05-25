<?php

declare(strict_types=1);

namespace app\common\validate\manage;

use think\Validate;

class ConfCategory extends Validate
{
    // -------------------------------
    // 基础验证规则
    // -------------------------------
    protected $rule = [
        'name' => 'max:100',        // 中文名称长度限制
        'ename' => 'max:100|alpha|unique:conf_category', // 分组标识格式及唯一性校验
    ];

    // -------------------------------
    // 验证失败提示信息（补全版）
    // -------------------------------
    protected $message = [
        'name.max'    => '分组名称不得超过100个字符！',
        'ename.max'    => '分组标识不得超过100个字符！',   // 补充长度限制提示
        'ename.alpha'  => '分组标识必须是字母！',
        'ename.unique' => '分组标识不得重复！',
        'ename.min'    => '分组标识需至少5个字母！',     // 补充编辑场景最小长度提示
    ];

    // -------------------------------
    // 验证场景定义
    // -------------------------------
    protected $scene = ['add', 'edit'];

    // -------------------------------
    // 编辑场景特殊规则
    // -------------------------------
    public function sceneEdit()
    {
        return $this->only(['name', 'ename'])
            ->append('ename', 'min:5')  // 编辑时追加最小长度要求
            ->remove('ename', 'unique');
    }
}
