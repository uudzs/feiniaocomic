<?php

declare(strict_types=1);

namespace app\manage\middleware;

use app\manage\controller\Auth as AuthMiddleware;

class Auth
{

	public function handle($request, \Closure $next)
	{
		//实例权限
		$auth = new AuthMiddleware();  //实例权限

		// 获取规则	
		$con    = $request->controller();  // 获取当前控制器名称
		$action = $request->action();      // 获取当前方法名称
		$name   = $con . '/' . $action;    // 拼接规则name 

		// 权限判断	
		if (!$auth->check($name, session('admin_id'))) {
			// 判断是否为AJAX请求
			if ($request->isAjax()) {
				return json([
					'code' => 403,
					'msg'  => '很抱歉，您没有该操作权限！',
				]);
			}
			
			return view('public/exception_jump', [
				'jump_msg' => '很抱歉，您没有该操作权限，即将返回后台首页！！！',
				'jump_url' => url('index/dashboard'),

			]);
		}

		return $next($request);
	}
}
