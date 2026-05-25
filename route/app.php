<?php

use think\facade\Route;

// // +----------------------------------------------------------------------
// // | 模块路由
// // +----------------------------------------------------------------------
$enabledModules = \app\module\model\Module::getEnabledModules();
if (!empty($enabledModules)) {
    $modulesPath = app()->getRootPath() . 'modules' . DIRECTORY_SEPARATOR;
    foreach ($enabledModules as $moduleName) {
        $moduleRouteFile = $modulesPath . $moduleName . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'frontend.php';
        if (file_exists($moduleRouteFile)) {
            include $moduleRouteFile;
        }
        // 加载API路由
        $apiRouteFile = $modulesPath . $moduleName . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'api.php';
        if (file_exists($apiRouteFile)) {
            include $apiRouteFile;
        }
    }
}

// 公共API路由组
Route::group('api', function () {
    // 系统API
    Route::group('system', function () {
        // 获取系统配置
        Route::get('appconfig', 'app\common\api\System@appConfig');
        // 发送验证码
        Route::post('sendcode', 'app\common\api\System@sendCode');
        // 验证验证码
        Route::post('verifycode', 'app\common\api\System@verifyCode');
        // 获取token
        Route::get('gettoken', 'app\common\api\System@getToken');
        // 获取单页内容
        Route::get('page', 'app\common\api\Page@get');
        // 获取单页内容多条
        Route::get('pages', 'app\common\api\Page@gets');
    });
});

Route::get('app', 'app\index\controller\Index@app');
Route::get('contactus', 'app\index\controller\Index@contactus');

// 单页内容
Route::rule('p/:id', 'app\index\controller\Index@single', 'GET')
    ->ext('html')
    ->pattern(['id' => '[^/]+'])
    ->name('single_page');
// 404 路由
Route::miss(function () {
    return app('app\index\controller\Error')->index();
});
