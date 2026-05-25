<?php

declare(strict_types=1);

namespace app\common\model\manage;

use think\model;

/**
 * 系统升级记录模型
 */
class SystemUpgradeLog extends model
{
    protected $name = 'system_upgrade_log';

    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = false;

    // 时间字段格式化
    protected $dateFormat = 'Y-m-d H:i:s';

    // 状态常量
    const STATUS_PENDING = 0;      // 待处理
    const STATUS_DOWNLOADING = 1; // 下载中
    const STATUS_EXTRACTING = 2;  // 解压中
    const STATUS_BACKUPING = 3;   // 备份中
    const STATUS_PERM_CHECK = 4;  // 权限检查
    const STATUS_UPGRADING = 5;   // 升级中
    const STATUS_COMPLETE = 6;    // 已完成
    const STATUS_FAILED = 7;       // 失败
    const STATUS_ROLLBACK = 8;     // 回滚中

    // 类型常量
    const TYPE_SYSTEM = 'system';
    const TYPE_MODULE = 'module';
    const TYPE_TEMPLATE = 'template';

    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => '待处理',
            self::STATUS_DOWNLOADING => '下载中',
            self::STATUS_EXTRACTING => '解压中',
            self::STATUS_BACKUPING => '备份中',
            self::STATUS_PERM_CHECK => '权限检查',
            self::STATUS_UPGRADING => '升级中',
            self::STATUS_COMPLETE => '已完成',
            self::STATUS_FAILED => '失败',
            self::STATUS_ROLLBACK => '回滚中',
            default => '未知',
        };
    }

    /**
     * 获取类型文本
     * @param string $type
     * @return string
     */
    public static function getTypeText(string $type): string
    {
        return match ($type) {
            self::TYPE_SYSTEM => '系统',
            self::TYPE_MODULE => '模块',
            self::TYPE_TEMPLATE => '模板',
            default => '未知',
        };
    }

    /**
     * 获取状态选项
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            ['id' => self::STATUS_PENDING, 'name' => '待处理'],
            ['id' => self::STATUS_DOWNLOADING, 'name' => '下载中'],
            ['id' => self::STATUS_EXTRACTING, 'name' => '解压中'],
            ['id' => self::STATUS_BACKUPING, 'name' => '备份中'],
            ['id' => self::STATUS_PERM_CHECK, 'name' => '权限检查'],
            ['id' => self::STATUS_UPGRADING, 'name' => '升级中'],
            ['id' => self::STATUS_COMPLETE, 'name' => '已完成'],
            ['id' => self::STATUS_FAILED, 'name' => '失败'],
            ['id' => self::STATUS_ROLLBACK, 'name' => '回滚中'],
        ];
    }

    /**
     * 获取类型选项
     * @return array
     */
    public static function getTypeOptions(): array
    {
        return [
            ['id' => self::TYPE_SYSTEM, 'name' => '系统'],
            ['id' => self::TYPE_MODULE, 'name' => '模块'],
            ['id' => self::TYPE_TEMPLATE, 'name' => '模板'],
        ];
    }
}
