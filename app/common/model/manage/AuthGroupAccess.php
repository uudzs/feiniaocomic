<?php

declare(strict_types=1);

namespace app\common\model\manage;


use think\model\Pivot;

class AuthGroupAccess extends Pivot
{
    // +----------------------------------------------------------------------
    // | 定义数据表名称
    // +----------------------------------------------------------------------

    protected $name = 'auth_group_access';
    protected $pk = ['uid', 'group_id']; // 复合主键

}
