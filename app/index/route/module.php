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
// 单页
Route::rule('p/:id', 'app\index\controller\Index@single', 'GET')
    ->ext('html')
    ->pattern(['id' => '[^/]+'])
    ->name('single_page');