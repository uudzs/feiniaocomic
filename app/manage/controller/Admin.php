<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Db;
use think\facade\Log;
use think\facade\Session;
use think\facade\Request;
use app\common\model\manage\AuthGroup;
use app\common\model\manage\Admin as AdminModel;
use app\common\model\manage\ActionLog;
use app\common\validate\manage\Admin as AdminValidate;
use app\common\fields\manage\row\AdminList;
use Throwable;

// -----------------------------------------------------------------------------
// 用户管理控制器
// 负责用户的增删改查、权限控制及数据统计
// -----------------------------------------------------------------------------
class Admin extends Base
{
    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------
    protected $modelClass    = AdminModel::class;     // 数据模型类
    protected $validateClass = AdminValidate::class; // 数据验证类

    // -------------------------------------------------------------------------
    // 获取用户数据接口
    // -------------------------------------------------------------------------
    public function getUserData()
    {
        try {
            $sessionGid = Session::get('admin_gid');     // 获取当前用户组ID
            $currentUserId = Session::get('admin_id');   // 获取当前用户ID

            // 参数处理
            $searchParams = [
                'groupid' => Request::param('groupid/d', null) // 允许空值
            ];

            // 分页参数处理
            $pageParams = [
                'page'  => max(1, Request::param('page/d', 1)),  // 页码
                'limit' => min(100, max(1, Request::param('limit/d', 30))) // 每页数量
            ];

            // 构建基础查询
            $query = AdminModel::with('bltAuthGroup') // 关联权限组
                ->field('id,uname as title,thumb,nickname,groupid,status,create_time,login_time,login_ip');

            // 权限过滤逻辑
            $this->applyUserPermissionFilter($query, $sessionGid, $currentUserId);

            // 分组筛选处理
            $this->applyGroupFilter($query, $searchParams['groupid'], $sessionGid);

            // 分页查询
            $data = $query->order('create_time asc,id asc')
                ->paginate([
                    'list_rows' => $pageParams['limit'],
                    'page'      => $pageParams['page'],
                ]);

            // 处理数据，添加当前用户标识
            $items = $data->items();
            foreach ($items as &$item) {
                $item['is_current'] = ($item['id'] == $currentUserId) ? 1 : 0; // 标记当前用户
            }

            return json([
                'code'  => 0,
                'msg'   => 'success',
                'count' => $data->total(), // 总记录数
                'data'  => $items // 数据列表
            ]);
        } catch (Throwable $e) {
            Log::error('用户数据查询异常:' . $e->getMessage()); // 记录错误日志
            return json(['code' => 500, 'msg' => $e->getMessage()], 500); // 返回错误
        }
    }

    // -------------------------------------------------------------------------
    // 用户列表页面
    // -------------------------------------------------------------------------
    public function lst()
    {
        $config = AdminList::getListConfig(); // 获取列表配置
        return view('common/list', array_merge($config, $config['viewParams'])); // 渲染视图
    }

    // -------------------------------------------------------------------------
    // 用户详情页面
    // -------------------------------------------------------------------------
    public function detail(int $id)
    {
        $currentUserId = Session::get('admin_id');   // 当前用户ID
        $currentGroupId = Session::get('admin_gid'); // 当前用户组ID

        // 权限检查
        if ($currentGroupId > 2 && $currentUserId != $id) {
            return c_error(lang('no_permission_view_other_user')); // 无权限查看
        }

        $user = AdminModel::with(['bltAuthGroup'])->findOrFail($id); // 获取用户信息

        // 构建导航项
        $navItems = [
            [
                'url' => url('admin/detail', ['id' => $id]),
                'title' => lang('user_data'),
                'active' => 1
            ],
            [
                'url' => url('admin/lst'),
                'title' => lang('user_list'),
                'active' => 0
            ],
        ];

        // 获取统计数据
        $stats = $this->getUserStatistics($id);

        // 获取各类数据（带分页）
        $data = $this->getUserRelatedData($id);

        return view('admin/detail', [
            'user' => $user,        // 用户信息
            'navItems' => $navItems, // 导航项
            'stats' => $stats,      // 统计数据
            'data' => $data,        // 相关数据
            'userId' => $id         // 用户ID
        ]);
    }

    // -------------------------------------------------------------------------
    // 获取权限组树形数据
    // -------------------------------------------------------------------------
    public function getGroupTreeData()
    {
        return AuthGroup::getTreeData(
            withPrefix: true, // 添加前缀
            fields: ['id', 'pid', 'title', 'status', 'title_auth'], // 查询字段
            scope: 'filterSuperAdmin' // 过滤范围
        );
    }

    // -------------------------------------------------------------------------
    // 新增用户操作
    // -------------------------------------------------------------------------
    public function add()
    {
        // 非AJAX请求渲染视图
        if (!Request::isAjax()) {
            return view('', [
                'GroupRes' => $this->getGroupTreeData(), // 权限组数据
                'groupid'  => Request::param('groupid/d', 0) // 默认组ID
            ]);
        }

        try {
            Db::startTrans(); // 开始事务
            $data = Request::param(); // 获取请求数据

            // 数据验证
            $validate = new AdminValidate();
            if (!$validate->scene('add')->check($data)) {
                return c_error($validate->getError()); // 验证失败
            }

            // 密码加密处理
            $data['password'] = $this->encryptPassword($data['password']);
            $data['login_ip'] = Request::ip(); // 登录IP
            $data['login_time'] = time(); // 登录时间
            $data['groupid'] = Request::param('groupid/d', 0); // 用户组ID

            // 创建用户
            $user = AdminModel::create($data);

            // 关联权限组
            Db::name('auth_group_access')->insert([
                'uid'      => $user->id, // 用户ID
                'group_id' => $data['groupid'] // 组ID
            ]);

            Db::commit(); // 提交事务

            // 记录操作日志
            $this->logUserAction('add_user', $data['uname'], $data['groupid'], $user->id);

            return c_success(lang('add_user_success'), [
                'url' => url('admin/lst', ['groupid' => $data['groupid']]) // 返回URL
            ]);
        } catch (Throwable $e) {
            Db::rollback(); // 回滚事务
            Log::error("用户添加失败: {$e->getMessage()} " . json_encode($data)); // 记录错误
            return c_error(lang('operation_failed') . $e->getMessage()); // 返回错误
        }
    }

    // -------------------------------------------------------------------------
    // 编辑用户操作
    // -------------------------------------------------------------------------
    public function edit(int $id)
    {
        // POST请求处理编辑逻辑
        if (Request::isPost()) {
            return $this->handleEdit($id);
        }

        // 获取用户数据
        $user = AdminModel::with(['bltAuthGroup'])->findOrFail($id);

        return view('', [
            'Admins'   => $user, // 用户数据
            'GroupRes' => $this->getGroupTreeData() // 权限组数据
        ]);
    }

    // -------------------------------------------------------------------------
    // 删除用户操作
    // -------------------------------------------------------------------------
    public function del()
    {
        try {
            // 权限校验
            if (!$this->hasDeletePermission()) {
                return json([
                    'code' => 403,
                    'msg' => lang('no_delete_permission')
                ], 403);
            }

            // 获取并验证删除IDs
            $ids = Request::param('ids', '');
            $idArr = $this->normalizeUserIds($ids);

            if (empty($idArr)) {
                return json(['code' => 400, 'msg' => lang('invalid_user_ids')]);
            }

            Db::startTrans();
            try {
                // 删除关联权限
                $accessDelete = Db::name('auth_group_access')
                    ->whereIn('uid', $idArr)
                    ->delete();

                // 删除用户数据
                $userDelete = AdminModel::destroy($idArr);

                // 记录操作日志
                $this->logUserAction('delete_user', Session::get('admin_name'), Session::get('admin_gid'), Session::get('admin_id'));

                Db::commit(); // 提交事务

                Log::info("用户删除成功", [
                    'operator' => Session::get('admin_id'),
                    'deleted_uids' => $idArr,
                    'affected_rows' => $userDelete
                ]);

                return json([
                    'code'  => 200,
                    'msg'   => lang('delete_user_success', [$userDelete]),
                    'count' => $userDelete,
                    'data'  => [
                        'deleted_uids' => $idArr,
                        'related_cleaned' => $accessDelete
                    ]
                ]);
            } catch (Throwable $e) {
                Db::rollback(); // 回滚事务
                Log::error("用户删除失败", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTrace()
                ]);
                return json([
                    'code' => 500,
                    'msg'  => lang('delete_failed') . (app()->isDebug() ? $e->getMessage() : lang('system_busy'))
                ]);
            }
        } catch (Throwable $e) {
            return json([
                'code' => 500,
                'msg'  => lang('service_exception') . (app()->isDebug() ? $e->getMessage() : lang('contact_admin'))
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // 私有辅助方法
    // -------------------------------------------------------------------------

    /** 应用用户权限过滤 */
    private function applyUserPermissionFilter($query, int $sessionGid, int $currentUserId): void
    {
        if ($sessionGid == 1) {
            // 超级管理员无限制
        } elseif ($sessionGid == 2) {
            // 管理员只能查看 groupid≥2 的用户
            $query->where('groupid', '>=', 2);
        } else {
            // 其他用户只能查看自己
            $query->where('id', $currentUserId);
        }
    }

    /** 应用分组筛选 */
    private function applyGroupFilter($query, $groupid, int $sessionGid): void
    {
        if ($groupid !== null) {
            $isAllowed = true;
            if ($sessionGid == 2 && $groupid < 2) {
                $isAllowed = false; // 管理员不能筛选更低权限组
            } elseif ($sessionGid > 2) {
                $isAllowed = false; // 普通用户无权筛选分组
            }

            if ($isAllowed) {
                $query->where('groupid', $groupid);
            }
        }
    }

    /** 获取用户统计数据 */
    private function getUserStatistics(int $userId): array
    {
        return [
            'action' => Db::name('action_log')->where('adminid', $userId)->count(), // 操作日志数
            'log' => Db::name('log')->where('adminid', $userId)->count(), // 登录日志数
        ];
    }

    /** 获取用户相关数据 */
    private function getUserRelatedData(int $userId): array
    {
        $pageSize = 20; // 每页数量
        return [
            'action' => Db::name('action_log')
                ->where('adminid', $userId)
                ->order('create_time', 'desc')
                ->paginate($pageSize), // 操作日志
            'log' => Db::name('log')
                ->where('adminid', $userId)
                ->order('login_time', 'desc')
                ->paginate($pageSize), // 登录日志
        ];
    }

    /** 处理编辑逻辑 */
    private function handleEdit(int $id)
    {
        $data = Request::param(); // 获取请求数据

        try {
            Db::startTrans(); // 开始事务

            // 数据验证
            validate($this->validateClass)->scene('edit')->check($data);

            // 更新密码
            if (!empty($data['newpassword'])) {
                $data['password'] = $this->encryptPassword($data['newpassword']);
            }

            // 更新用户主表
            AdminModel::update($data, ['id' => $id]);

            // 更新用户组关联
            if (isset($data['groupid'])) {
                Db::name('auth_group_access')
                    ->where('uid', $id)
                    ->update(['group_id' => $data['groupid']]);
            }

            Db::commit(); // 提交事务

            // 获取最新的groupid
            $groupid = $data['groupid'] ?? AdminModel::find($id)->groupid;

            // 记录操作日志
            $this->logUserAction('edit_user', $data['uname'], intval($data['groupid']), $id);

            return json([
                'code' => 200,
                'msg'  => lang('edit_user_success'),
                'url'  => url('admin/lst')->build(),
            ]);
        } catch (Throwable $e) {
            Db::rollback(); // 回滚事务
            Log::error("用户编辑失败: {$e->getMessage()}"); // 记录错误
            return json([
                'code' => 500,
                'msg'  => lang('edit_failed') . $e->getMessage()
            ]);
        }
    }

    /** 密码加密处理 */
    private function encryptPassword(string $password): string
    {
        return sha1(sha1(AdminModel::PASSWORD_SALT . $password)); // 双重SHA1加密
    }

    /** 检查删除权限 */
    private function hasDeletePermission(): bool
    {
        $currentUserId = Session::get('admin_id');
        $currentGroupId = Session::get('admin_gid');
        return $currentUserId == 1 || $currentGroupId == 2; // 系统管理员或超级管理员
    }

    /** 标准化用户ID参数 */
    private function normalizeUserIds($ids): array
    {
        $idArr = is_array($ids) ? $ids : explode(',', $ids); // 转换为数组
        $idArr = array_map('intval', $idArr);     // 转为整型
        $idArr = array_filter($idArr);           // 过滤空值
        return array_unique($idArr);            // 去重
    }

    /** 记录用户操作日志 */
    private function logUserAction(string $module, string $uname, int $groupid, int $dataId): void
    {
        ActionLog::create([
            'uname' => $uname,
            'groupid' => $groupid,
            'module' => lang($module),
            'action' => lang('operation'),
            'data_id' => $dataId,
            'description' => lang('user_data_modified', [$dataId]),
            'ip' => Request::ip(),
            'os' => $this->getOsInfo(),
        ]);
    }

    /** 获取操作系统信息 */
    private function getOsInfo(): string
    {
        return get_os_info() ?: 'Unknown'; // 获取OS信息
    }
}
