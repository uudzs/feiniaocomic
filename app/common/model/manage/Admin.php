<?php

declare(strict_types=1);

namespace app\common\model\manage;

use think\Model;
use think\facade\Session;
use think\facade\Request;
use app\common\model\manage\Log as LogModel;

class Admin extends Model
{

    //—————————————————————————————————————————————————
    // 模型配置
    //————————————————————————————————————————————————— 

    protected $name = 'admin';

    // 密码加密盐值常量
    public const PASSWORD_SALT = 'feiniao_comic';

    //—————————————————————————————————————————————————
    // 获取器
    //—————————————————————————————————————————————————
    public function getLoginTimeAttr($value): string
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    //—————————————————————————————————————————————————
    // 模型关联
    //————————————————————————————————————————————————— 
    public function bltAuthGroup()
    {
        return $this->belongsTo(AuthGroup::class, 'groupid')
            ->bind(['group_title' => 'title']);
    }


    //—————————————————————————————————————————————————
    // 核心业务方法
    //—————————————————————————————————————————————————
    public function validateUser(array $data): int
    {
        $admin = $this->getAdminByUsername($data['username'] ?? '');

        if (!$admin) return 3;
        if (!$this->validatePassword($admin, $data['password'] ?? '')) return 2;
        if ($admin->status == 0) return 4;

        $this->handleSuccessfulLogin($admin);
        return 1;
    }

    //—————————————————————————————————————————————————
    // 私有辅助方法
    //—————————————————————————————————————————————————

    // 根据用户名获取管理员信息
    private function getAdminByUsername(string $username): ?self
    {
        return $this->where('uname', $username)->find();
    }

    // 验证密码有效性
    private function validatePassword(self $admin, string $password): bool
    {
        $encrypted = sha1(sha1(self::PASSWORD_SALT . $password));
        return $admin->password === $encrypted;
    }

    // 处理登录成功后的系列操作
    private function handleSuccessfulLogin(self $admin): void
    {
        $this->setSessionData($admin);
        $this->updateLoginInfo($admin);
        $this->createLoginLog($admin);
    }

    // 设置会话数据
    private function setSessionData(self $admin): void
    {
        Session::set('admin_name', $admin->uname);
        Session::set('admin_id', $admin->id);
        Session::set('admin_gid', $admin->groupid);
    }

    // 更新最后登录信息
    private function updateLoginInfo(self $admin): void
    {
        $admin->save([
            'login_time' => time(), //登陆时间
            'session_id' => Session::getId(), //session_id 
            'last_active_time' => time(), //最后活动时间
            'login_ip'   => Request::ip()
        ]);
    }

    // 创建登录日志记录
    private function createLoginLog(self $admin): void
    {
        LogModel::create([
            'adminid'    => $admin->id,
            'groupid'    => $admin->groupid,
            'uname'      => $admin->uname,
            'login_time' => time(),
            'login_ip'   => Request::ip(),
            'login_os'   => Request::server('HTTP_USER_AGENT')
        ]);
    }
}
