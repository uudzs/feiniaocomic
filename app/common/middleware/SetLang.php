<?php

namespace app\common\middleware;

use think\facade\Cookie;
use think\facade\Lang;
use think\facade\Config;
use think\facade\Session;

class SetLang
{

    //-----------------------------------------------------------------
    // 语言设置中间件
    //-----------------------------------------------------------------

    public function handle($request, \Closure $next)
    {
        // 获取配置中的cookie变量名，默认使用'feiniao_lang'
        $cookieVar = Config::get('lang.cookie_var', 'feiniao_lang');

        // 获取默认语言设置，默认为'zh-cn'（中文）
        $defaultLang = Config::get('lang.default_lang', 'zh-cn');

        //-----------------------------------------------------------------
        // 语言检测优先级：Session > Cookie > 默认配置
        //-----------------------------------------------------------------

        // 1. 最高优先级：从Session中获取语言设置（使用统一前缀feiniao_lang）
        $lang = Session::get('feiniao_lang');

        // 2. 第二优先级：如果Session中没有，从Cookie中获取
        if (!$lang) {
            $lang = Cookie::get($cookieVar);
        }

        // 3. 最低优先级：如果以上都没有，使用默认语言
        if (!$lang) {
            $lang = $defaultLang;
        }

        //-----------------------------------------------------------------
        // 语言有效性验证
        //-----------------------------------------------------------------

        // 获取允许的语言列表，默认为['zh-cn', 'en-us']
        $allowLangs = Config::get('lang.allow_lang_list', ['zh-cn', 'en-us']);

        // 验证检测到的语言是否在允许列表中
        if (!in_array($lang, $allowLangs)) {
            // 如果语言不被支持，则回退到默认语言
            $lang = $defaultLang;
        }

        //-----------------------------------------------------------------
        // 应用语言设置
        //-----------------------------------------------------------------

        // 设置系统语言环境
        Lang::setLangSet($lang);

        //-----------------------------------------------------------------
        // 传递语言设置到其他组件
        //-----------------------------------------------------------------

        // 定义全局常量，供模型层使用（用于数据库表前缀等）
        define('CURRENT_LANG', $lang);

        // 将当前语言设置附加到请求对象，供控制器和视图使用
        $request->CurLang = $lang;

        // 继续执行后续中间件和请求处理
        return $next($request);
    }
}
