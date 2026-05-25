<?php
// +----------------------------------------------------------------------
// | 多语言设置
// +----------------------------------------------------------------------

return [
    // 默认语言 - 从 .env 文件 LANG.default_lang 读取
    'default_lang'        => env('lang.default_lang', 'zh-cn'),
    
    // 是否开启语言切换 - 从 .env 文件 LANG.lang_switch_on 读取
    'lang_switch_on'       => env('lang.lang_switch_on', true),
    
    // 自动侦测浏览器语言
    'auto_detect_browser' => true,
    
    // 允许的语言列表 - 从 .env 文件 LANG.lang_allow_list 读取并转换为数组
    'allow_lang_list'     => array_filter(
        array_map('trim', explode(',', env('lang.lang_allow_list', 'zh-cn,en-us'))),
        function($item) {
            return !empty($item);
        }
    ),
    
    // 多语言自动侦测变量名
    'detect_var'          => 'lang',
    
    // 是否使用Cookie记录
    'use_cookie'          => true,
    
    // 多语言cookie变量
    'cookie_var'          => 'think_lang',
    
    // 多语言header变量
    'header_var'          => 'think-lang',
    
    // 扩展语言包
    'extend_list'         => [],
    
    // Accept-Language转义为对应语言包名称
    'accept_language'     => [
        'zh-hans-cn' => 'zh-cn',
    ],
    
    // 是否支持语言分组
    'allow_group'         => false,
];
