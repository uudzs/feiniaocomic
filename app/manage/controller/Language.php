<?php

declare(strict_types=1);

namespace app\manage\controller;

use app\manage\controller\Base;
use think\facade\Request;
use think\facade\Config;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Lang;

/**
 * 语言管理控制器
 */
class Language extends Base
{
    //-----------------------------------------------------------------
    // 语言切换方法
    //-----------------------------------------------------------------
    public function switch()
    {

        $lang = strtolower(Request::param('lang', 'zh-cn')); // 获取语言参数并转为小写，默认中文

        $supportedLangs = Config::get('lang.allow_lang_list', ['zh-cn', 'en-us']); // 从配置获取支持的语言列表

        if (!in_array($lang, $supportedLangs)) { // 验证语言是否在支持列表中
            return json([
                'code' => 0,
                'msg' => Lang::get('Unsupported language') // 返回不支持语言的错误提示
            ]);
        }

        try {
            //-----------------------------------------------------------------
            // 设置语言环境（Session + Cookie + 框架语言）
            //-----------------------------------------------------------------
            $cookieVar = Config::get('lang.cookie_var', 'think_lang'); // 从配置获取cookie变量名，默认think_lang

            Session::set('fn_lang', $lang); // 设置Session，使用统一前缀fn_lang

            Cookie::set($cookieVar, $lang, 30 * 86400); // 设置Cookie，有效期30天

            Lang::setLangSet($lang); // 设置框架当前语言环境

            return json([
                'code' => 200,
                'msg' => Lang::get('Language switched successfully'), // 返回语言切换成功提示
                'data' => [
                    'lang' => $lang // 返回设置的语言代码
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => Lang::get('Operation failed') // 返回操作失败提示
            ]);
        }
    }
}
