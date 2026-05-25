<?php

use think\facade\Route;

// // +----------------------------------------------------------------------
// // | 模块路由
// // +----------------------------------------------------------------------
$enabledModules = \app\module\model\Module::getEnabledModules();
if (!empty($enabledModules)) {
    $modulesPath = app()->getRootPath() . 'modules' . DIRECTORY_SEPARATOR;
    foreach ($enabledModules as $moduleName) {
        $moduleRouteFile = $modulesPath . $moduleName . DIRECTORY_SEPARATOR . 'route' . DIRECTORY_SEPARATOR . 'admin.php';
        if (file_exists($moduleRouteFile)) {
            include $moduleRouteFile;
        }
    }
}

// // +----------------------------------------------------------------------
// // | 模块管理路由
// // +----------------------------------------------------------------------
Route::group('module', function () {
    Route::rule('index', 'index');
    Route::rule('getModuleData', 'getModuleData');
    Route::rule('install', 'install');
    Route::rule('update', 'update');
    Route::rule('uninstall', 'uninstall');
    Route::rule('enable', 'enable');
    Route::rule('disable', 'disable');
    Route::rule('delete', 'delete');
    Route::rule('setting', 'setting');
    Route::rule('detail', 'detail');
    Route::rule('refresh', 'refresh');
})->prefix('module/');

// // +----------------------------------------------------------------------
// // | 单页管理路由
// // +----------------------------------------------------------------------
Route::group('page', function () {
    Route::rule('getData', 'getData'); // 数据
    Route::rule('lst', 'lst');                 // 列表
    Route::rule('form', 'form');               // 添加
    Route::rule('del', 'del');                 // 删除
})->prefix('page/');

// // +----------------------------------------------------------------------
// // | 管理员路由
// // +----------------------------------------------------------------------
Route::group('admin', function () {
    Route::rule('getUserData', 'getUserData'); // 数据
    Route::rule('lst', 'lst');                 // 列表
    Route::rule('add', 'add');                 // 添加
    Route::rule('edit', 'edit');               // 编辑
    Route::rule('del', 'del');                 // 删除
    Route::rule('detail', 'detail');                 // 当前用户
})->prefix('admin/');

// // +----------------------------------------------------------------------
// // | 登录日志
// // +----------------------------------------------------------------------
Route::group('log', function () {
    Route::rule('getLogData', 'getLogData'); // 数据
    Route::rule('lst', 'lst');               // 列表
    Route::rule('del', 'baseDel');           // 删除
})->prefix('Log/');


// // +----------------------------------------------------------------------
// // | 操作日志
// // +----------------------------------------------------------------------
Route::group('action', function () {
    Route::rule('getLogData', 'getLogData'); // 数据
    Route::rule('lst', 'lst');               // 列表
    Route::rule('del', 'baseDel');           // 删除
})->prefix('ActionLog/');

// // +----------------------------------------------------------------------
// // | 用户组路由
// // +----------------------------------------------------------------------
Route::group('group', function () {
    Route::rule('getGroupData', 'getGroupData'); // 数据
    Route::rule('lst', 'lst');                   // 列表
    Route::rule('form', 'form');                 // 添加
    Route::rule('power', 'power');               // 权限
    Route::rule('del', 'del');                   // 删除
})->prefix('AuthGroup/');

// // +----------------------------------------------------------------------
// // | 权限路由
// // +----------------------------------------------------------------------
Route::group('rule', function () {
    Route::rule('getRuleData', 'getRuleData'); // 数据
    Route::rule('lst', 'lst');                 // 列表
    Route::rule('form', 'form');               // 添加
    Route::rule('del', 'del');                 // 删除
})->prefix('AuthRule/');

// // +----------------------------------------------------------------------
// // | 公共路由
// // +----------------------------------------------------------------------
Route::group('common', function () {
    Route::rule('clearCache', 'clearCache');     // 清除缓存
    Route::rule('sort', 'BaseSort');             // 修改排序
    Route::rule('changeStatus', 'changeStatus'); // 修改状态
    Route::rule('changeModuleStatus', 'changeModuleStatus'); // 修改状态
})->prefix('Common/');

// // +----------------------------------------------------------------------
// // | 上传路由
// // +----------------------------------------------------------------------
Route::group('upload', function () {
    Route::rule('uploadimg', 'UploadImg');    // 上传图片
    Route::rule('uploadImages', 'uploadImages'); // 上传图集
    Route::rule('DeleteImg', 'DeleteImg');    // 删除图片
    Route::rule('uploadFile', 'uploadFile');  // 上传文件
})->prefix('Upload/');

// // +----------------------------------------------------------------------
// // | 配置路由
// // +----------------------------------------------------------------------
Route::group('conf', function () {
    Route::rule('conf', 'conf');               // 设置
    Route::rule('getConfData', 'getConfData'); // 数据
    Route::rule('lst', 'lst');                 // 列表
    Route::rule('form', 'form');               // 添加
    Route::rule('del', 'del');                 // 删除
})->prefix('Conf/');

// // +----------------------------------------------------------------------
// // | 配置分类路由
// // +----------------------------------------------------------------------
Route::group('confcategory', function () {
    Route::rule('getData', 'getData'); // 数据
    Route::rule('lst', 'lst');                 // 列表
    Route::rule('form', 'form');               // 添加
    Route::rule('del', 'del');                 // 删除
})->prefix('Confcategory/');

// // +----------------------------------------------------------------------
// // | 链接路由
// // +----------------------------------------------------------------------
Route::group('link', function () {
    Route::rule('getLinkData', 'getLinkData'); // 数据
    Route::rule('lst', 'lst');                 // 列表
    Route::rule('form', 'form');               // 添加
    Route::rule('del', 'baseDel');             // 删除
})->prefix('Link/');

// // +----------------------------------------------------------------------
// | 语言路由
// +----------------------------------------------------------------------
Route::group('language', function () {
    Route::rule('switch', 'switch');           // 语言切换
})->prefix('Language/');

// // +----------------------------------------------------------------------
// | 升级管理路由
// +----------------------------------------------------------------------
Route::group('upgrade', function () {
    Route::rule('index', 'index');                     // 首页
    Route::rule('system', 'system');                    // 检查系统更新
    Route::rule('systemUpgrade', 'systemUpgrade');      // 执行系统升级
    Route::rule('module', 'module');                    // 模块升级列表
    Route::rule('checkModuleUpdate', 'checkModuleUpdate'); // 检查模块更新
    Route::rule('moduleUpgrade', 'moduleUpgrade');      // 执行模块升级
    Route::rule('template', 'template');                // 模板升级列表
    Route::rule('checkTemplateUpdate', 'checkTemplateUpdate'); // 检查模板更新
    Route::rule('templateUpgrade', 'templateUpgrade');  // 执行模板升级
    Route::rule('logs', 'logs');                        // 升级记录
    Route::rule('logDetail', 'logDetail');              // 升级记录详情
    Route::rule('rollback', 'rollback');                // 回滚
})->prefix('Upgrade/');

// // +----------------------------------------------------------------------
// | 反馈管理路由
// +----------------------------------------------------------------------
Route::group('feedback', function () {
    Route::rule('index', 'index');                      // 反馈页面
    Route::rule('submit', 'submit');                    // 提交反馈
    Route::rule('getTypes', 'getTypes');               // 获取反馈类型
})->prefix('Feedback/');