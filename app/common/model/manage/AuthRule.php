<?php

declare(strict_types=1);

namespace app\common\model\manage;

use think\Model;

class AuthRule extends Model
{
    // +----------------------------------------------------------------------
    // | 定义数据表名称
    // +----------------------------------------------------------------------
    protected $name = 'auth_rule';

    // 中文名
    public static $chineseName = '权限';

    use TreeBuilder;
}
