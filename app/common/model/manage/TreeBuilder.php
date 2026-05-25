<?php

namespace app\common\model\manage;

trait TreeBuilder
{
    // -------------------------------------------------------------------------
    // 获取树形结构数据
    // -------------------------------------------------------------------------
    public static function getTreeData(
        bool $withPrefix = true,
        array $fields = ['*'],
        ?string $scope = null,
        array $append = [],
        array|int|null $type = null // 允许数组或单个值
    ): array {
        $query = static::field($fields)->order('sort asc,id asc');

        // 类型判断:处理 type 条件
        if ($type !== null) {
            if (is_array($type)) {
                if (!empty($type)) {
                    $query->whereIn('type', $type);
                }
            } else {
                $query->where('type', $type);
            }
        }

        if ($scope && method_exists(static::class, 'scope' . ucfirst($scope))) {
            $query->{$scope}();
        }

        $dataList = $query->select();

        if (!empty($append)) {
            $dataList->append($append);
        }

        return static::buildTreeData($dataList->toArray(), $withPrefix);
    }

    // -------------------------------------------------------------------------
    // 构建树形数据
    // -------------------------------------------------------------------------
    private static function buildTreeData(array $data, bool $withPrefix): array
    {
        $grouped = [];
        foreach ($data as $item) {
            $grouped[$item['pid']][] = $item;
        }

        $tree = [];
        self::buildTreeNodes(
            data: $grouped,
            pid: 0,
            result: $tree,
            level: 0,
            withPrefix: $withPrefix
        );
        return $tree;
    }

    // -------------------------------------------------------------------------
    // 递归构建树节点
    // -------------------------------------------------------------------------
    private static function buildTreeNodes(
        array &$data,
        int $pid,
        array &$result,
        int $level,
        bool $withPrefix
    ): void {
        if (!isset($data[$pid])) return;

        foreach ($data[$pid] as $node) {
            $item = $node;
            if ($withPrefix) {
                $item['title'] = str_repeat('------', $level) . '' . $item['title'];
            }
            $item['level'] = $level;
            $item['is_leaf'] = empty($data[$item['id']]);

            $result[] = $item;
            self::buildTreeNodes($data, $item['id'], $result, $level + 1, $withPrefix);
        }
    }
}
