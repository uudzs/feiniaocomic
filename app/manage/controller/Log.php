<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use app\common\model\manage\Log as LogModel;
use app\common\fields\manage\row\LogList as LogListfields;

class Log extends Base
{
    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------

    protected $modelClass = LogModel::class;

    //-----------------------------------------------------------------
    // 获取日志数据
    //-----------------------------------------------------------------
    public function getLogData()
    {
        return $this->getCommonData([
            'field' => '*',
            'order' => 'login_time DESC, id DESC',
            'condition' => function ($query) {
                $userGroupId = session('admin_gid');

                if ($userGroupId == 1) {
                    // 管理员组，无需额外条件
                } elseif ($userGroupId > 1) {
                    $query->where('groupid', '>', 1);
                } else {
                    $query->whereRaw('1=0');
                }
            }
        ]);
    }

    //-----------------------------------------------------------------
    // 日志列表
    //-----------------------------------------------------------------
    public function lst()
    {
        $config = LogListfields::getListConfig();
        return view('common/list', $config);
    }
}
