<?php

declare(strict_types=1);

namespace app\common\model\manage;

use think\Model;
use think\facade\Session;
use think\Collection;
use app\common\model\manage\AuthRule;

class AuthGroup extends Model
{

    protected $name = 'auth_group';

    // +----------------------------------------------------------------------
    // | 模型配置
    // +----------------------------------------------------------------------

    protected $type = [
        'rules' => 'comma', // 定义自动类型转换
    ];

    // +----------------------------------------------------------------------
    // | 作用域
    // +----------------------------------------------------------------------

    // 过滤超级管理员可见性
    public function scopeFilterSuperAdmin($query)
    {
        $gid = Session::get('admin_id', 0);
        if ($gid != 1) {
            $query->where('id', '<>', 1);
        }
        return $query;
    }

    // +----------------------------------------------------------------------
    // | 权限树操作
    // +----------------------------------------------------------------------

    // 获取完整权限树（带缓存）
    public static function getPermissionTree(): array
    {
        static $cache = null;
        if ($cache === null) {
            $rules = AuthRule::scope('filterStatus')
                ->order('sort , id ASC')
                ->select()
                ->toArray();
            $cache = self::buildTree($rules);
        }
        return $cache;
    }

    // 构建树形结构
    private static function buildTree(array $items, int $pid = 0): array
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item['pid'] == $pid) {
                $children = self::buildTree($items, $item['id']);
                $item['children'] = $children ?: [];
                $tree[] = $item;
            }
        }
        return $tree;
    }

    // +----------------------------------------------------------------------
    // | 权限操作
    // +----------------------------------------------------------------------

    // 获取实际有效的权限ID集合
    public function getValidRulesAttribute(): array
    {
        $rules = $this->rules ? explode(',', $this->rules) : [];
        return array_unique(array_filter(array_map('intval', $rules)));
    }

    // 获取完整权限链（包含子权限）
    public function getFullRuleChain(): array
    {
        $selectedRules = $this->valid_rules;
        if (empty($selectedRules)) return [];

        $ruleIds = $selectedRules;
        $childIds = AuthRule::whereIn('pid', $selectedRules)
            ->column('id');

        // 递归获取子权限（最多3层）
        for ($i = 0; $i < 6; $i++) {
            if (empty($childIds)) break;
            $ruleIds = array_merge($ruleIds, $childIds);
            $childIds = AuthRule::whereIn('pid', $childIds)
                ->column('id');
        }

        return array_unique($ruleIds);
    }

    // +----------------------------------------------------------------------
    // | 树形结构操作
    // +----------------------------------------------------------------------

    // 获取所有子组ID（包含自身）
    public static function getAllChildIdsWithSelf($id): array
    {
        $groups = self::select();
        return array_unique(array_merge(
            [intval($id)],
            self::findChildIds($groups, $id)
        ));
    }

    // 递归查找子ID
    private static function findChildIds(Collection $groups, int $pid): array
    {
        $ids = [];
        foreach ($groups as $group) {
            if ($group->pid == $pid) {
                $ids[] = $group->id;
                $ids = array_merge(
                    $ids,
                    self::findChildIds($groups, $group->id)
                );
            }
        }
        return $ids;
    }

    // +----------------------------------------------------------------------
    // | 权限更新
    // +----------------------------------------------------------------------

    // 安全更新权限
    public function safeUpdateRules(array $ruleIds): bool
    {
        try {
            // 清理无效权限ID
            $validIds = AuthRule::whereIn('id', $ruleIds)
                ->column('id');

            // 更新规则字段
            $this->rules = implode(',', $validIds);
            return $this->save();
        } catch (\Throwable $e) {
            throw new \RuntimeException('权限更新失败：' . $e->getMessage());
        }
    }

    // +----------------------------------------------------------------------
    // | 类型转换
    // +----------------------------------------------------------------------

    // 自定义逗号分隔转换
    public function getCommaAttr($value, $data)
    {
        $field = $data['rules'] ?? '';
        return is_string($field) ? $field : '';
    }

    public function setCommaAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    // +----------------------------------------------------------------------
    // | 树形结构数据
    // +----------------------------------------------------------------------

    // 获取用户组树形数据
    public static function getTreeData($withPrefix = false, $fields = ['id', 'pid', 'title'], $scope = null): array
    {
        $query = self::field($fields);
        
        // 应用作用域
        if ($scope && method_exists(self::class, 'scope' . ucfirst($scope))) {
            $query->$scope();
        }
        
        $groups = $query->order('id ASC')->select()->toArray();
        $tree = self::buildTree($groups);
        
        if ($withPrefix) {
            $tree = self::addPrefixToTree($tree);
        }
        
        return $tree;
    }

    // 为树形结构添加前缀
    private static function addPrefixToTree(array $tree, $prefix = ''): array
    {
        foreach ($tree as &$node) {
            $node['title_auth'] = $prefix . $node['title'];
            if (!empty($node['children'])) {
                $node['children'] = self::addPrefixToTree($node['children'], $prefix . '├─ ');
            }
        }
        return $tree;
    }
}
