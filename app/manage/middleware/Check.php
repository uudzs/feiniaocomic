<?php

declare(strict_types=1);

namespace app\manage\middleware;

use app\common\model\manage\Admin;
use think\facade\Session;

class Check
{

    //-------------------------------------------------------------------------
    // 登陆判断
    //-------------------------------------------------------------------------

    public function handle($request, \Closure $next)
    {
        $isLoginPage = preg_match('/login/', $request->pathinfo());

        // 未登录且不在登录页，跳转到登录
        if (empty(Session::get('admin_id')) && !$isLoginPage) {
            return $this->jumpToLogin('您还未登陆系统，请先登陆！！！');
        }

        // 已登录用户进行会话验证
        if (Session::has('admin_id')) {
            $userId = Session::get('admin_id');
            $user = Admin::find($userId);

            if (!$user) {
                Session::clear();
                return $this->jumpToLogin('用户不存在，请重新登录！');
            }
        }

        return $next($request);
    }


    //-------------------------------------------------------------------------
    // 跳转接口
    //-------------------------------------------------------------------------

    private function jumpToLogin(string $message)
    {
        return view('public/exception_jump', [
            'jump_msg' => $message,
            'jump_url' => url('login/index')
        ]);
    }
}
