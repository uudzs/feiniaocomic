<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use app\common\model\manage\ActionLog as ActionLogModel;
use app\common\fields\manage\row\ActionLogList;

class ActionLog extends Base
{

    // 获取日志数据
    protected $modelClass = ActionLogModel::class;

    // +----------------------------------------------------------------------
    // | 数据接口
    // +----------------------------------------------------------------------
    public function getLogData()
    {
        return $this->getCommonData([
            'searchFields' => [
                'uname' => 's', // 字段名 => 类型
            ],
            'field' => '*',            // 选择所有字段
            'order' => 'create_time DESC, id DESC',
            'condition' => function ($query) {
                // 添加权限过滤条件
                $userGroupId = session('admin_gid');

                if ($userGroupId == 1) {
                    // 管理员组，无需额外条件
                } elseif ($userGroupId > 1) {
                    // 仅获取groupid大于1的记录
                    $query->where('groupid', '>', 1);
                } else {
                    // 其他情况返回空数据集
                    $query->whereRaw('1=0');
                }
            },
            'model' => ActionLogModel::class // 指定模型类
        ]);
    }


    // 日志列表视图
    public function lst()
    {
        // 使用字段配置文件获取列表配置
        $config = ActionLogList::getListConfig();

        return view('common/list', $config);
    }
}
