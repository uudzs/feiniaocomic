<?php

declare(strict_types=1);

namespace app\common\model\manage;

use think\Model;

class Conf extends Model
{
    // +----------------------------------------------------------------------
    // | 定义数据表名称
    // +----------------------------------------------------------------------

    protected $name = 'conf';

    // 模型中文名称
    public static $chineseName = '配置';

    public static $typeConfig = [
        0 => '--',
        1 => '单行文本',
        2 => '单选',
        3 => '复选',
        4 => '下拉',
        5 => '多行文本',
        6 => '附件',
        7 => '小数',
        8 => '整数',
        9 => '长文本',
    ];

    // +----------------------------------------------------------------------
    // | 类型获取器
    // +----------------------------------------------------------------------

    public function getTypeAttr($value)
    {
        return self::$typeConfig[$value] ?? '--';
    }

    public function getModelAttr($value)
    {
        $Category = ConfCategory::where('status', 1)->find($value);
        return $Category ? $Category['name'] : '--';
    }

}
