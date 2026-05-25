<?php
// 全局中间件定义文件
return [
    // 1. 先加载语言包
    \think\middleware\LoadLangPack::class,
    \app\common\middleware\SetLang::class,
    \think\middleware\AllowCrossDomain::class,
    // 2. Session初始化
    \think\middleware\SessionInit::class,
    // 3. 其他中间件
    \think\middleware\CheckRequestCache::class,
];
