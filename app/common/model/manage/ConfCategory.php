<?php
declare(strict_types=1);

namespace app\common\model\manage;

use think\Model;

/**
 * 配置分类模型
 */
class ConfCategory extends Model
{
    // 表名
    protected $name = 'conf_category';
    
    // 主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 时间字段格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'status' => 'integer',
        'sort' => 'integer',
    ];
    
    /**
     * 获取所有分类
     * @param bool $onlyActive 是否只获取启用的分类
     * @return array
     */
    public static function getAllCategories(bool $onlyActive = true): array
    {
        $query = self::order('sort ASC');
        if ($onlyActive) {
            $query->where('status', 1);
        }
        return $query->select()->toArray();
    }
    
    /**
     * 根据标识获取分类
     * @param string $ename 分类标识
     * @return array|null
     */
    public static function getByEname(string $ename): ?array
    {
        $category = self::where('ename', $ename)->where('status', 1)->find();
        return $category ? $category->toArray() : null;
    }
    
    /**
     * 检查标识是否存在
     * @param string $ename 分类标识
     * @param int|null $excludeId 排除的ID
     * @return bool
     */
    public static function existsEname(string $ename, ?int $excludeId = null): bool
    {
        $query = self::where('ename', $ename);
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() > 0;
    }
}