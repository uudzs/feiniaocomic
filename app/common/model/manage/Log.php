<?php

declare(strict_types=1);

namespace app\common\model\manage;

use think\Model;

class Log extends Model
{
    // +----------------------------------------------------------------------
    // | 定义数据表名称
    // +----------------------------------------------------------------------

    protected $name = 'Log';

    // 模型中文名称
    public static $chineseName = '登陆日志';

    // 时间获取器
    public function getLoginTimeAttr($value)
    {
        return date('Y-m-d h:i:s', $value);
    }
}
