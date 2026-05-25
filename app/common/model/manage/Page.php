<?php

namespace app\common\model\manage;

use think\Model;

/**
 * 单页内容模型
 */
class Page extends Model
{

    protected $name = 'page';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $dateFormat = 'Y-m-d H:i:s';

    // 模型中文名称
    public static $chineseName = '单页';

    /**
     * 根据标识获取单页内容
     * @param string $identifier 标识
     * @return mixed
     */
    public function getByIdentifier(string $identifier)
    {
        return $this->where('status', 1)->cache(3600)->where('identifier', $identifier)->find();
    }

    /**
     * 检查标识是否已存在
     * @param string $identifier 标识
     * @param int $id 排除的ID
     * @return bool
     */
    public function isIdentifierExists(string $identifier, int $id = 0)
    {
        $query = $this->where('identifier', $identifier);
        if ($id > 0) {
            $query->where('id', '<>', $id);
        }
        return $query->count() > 0;
    }
}
