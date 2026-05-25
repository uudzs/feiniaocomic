<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\common\model\manage\Admin;
use think\facade\Session;
use think\facade\View;
use think\facade\Request;
use think\facade\Cache;

class Login
{
    // -------------------------------------------------------------------------
    // 常量配置
    // -------------------------------------------------------------------------
    private const SESSION_KEY = 'admin_id';

    // -------------------------------------------------------------------------
    // 主入口方法
    // -------------------------------------------------------------------------
    public function index()
    {
        //Cache::clear();
        return Session::has(self::SESSION_KEY)
            ? $this->redirectToHome()
            : View::fetch();
    }

    // -------------------------------------------------------------------------
    // 登录验证处理
    // -------------------------------------------------------------------------
    public function submit()
    {
        if (!Request::isAjax()) {
            return $this->jsonResponse(400, lang('illegal_request'));
        }

        $data = Request::param();

        // 验证码检查
        if (!$this->validateCaptcha($data['captcha'] ?? '')) {
            return $this->jsonResponse(400, lang('captcha_error'));
        }

        // 用户验证
        $status = (new Admin())->validateUser($data);
        return $this->handleLoginStatus($status);
    }

    // -------------------------------------------------------------------------
    // 用户退出处理
    // -------------------------------------------------------------------------
    public function logout()
    {
        Session::delete(self::SESSION_KEY);
        Session::clear();
        return $this->jsonResponse(200, lang('logout_success'), ['url' => url('login/index')]);
    }

    // -------------------------------------------------------------------------
    // 私有辅助方法
    // -------------------------------------------------------------------------

    /** 重定向到首页 */
    private function redirectToHome()
    {
        return c_alert(lang('already_logged_in'), url('index/dashboard')->build());
    }

    /** JSON响应封装 */
    private function jsonResponse(int $code, string $msg, array $data = [])
    {
        return json(compact('code', 'msg', 'data'));
    }

    /** 验证码验证 */
    private function validateCaptcha(?string $code): bool
    {
        return !empty($code) && captcha_check($code);
    }

    /** 处理登录状态 */
    private function handleLoginStatus(int $status)
    {
        return match ($status) {
            1 => $this->jsonResponse(200, lang('login_success'), ['url' => url('index/dashboard')]),
            3 => $this->jsonResponse(400, lang('account_not_exists')),
            4 => $this->jsonResponse(400, lang('account_disabled')),
            default => $this->jsonResponse(400, lang('login_failed'))
        };
    }
}
