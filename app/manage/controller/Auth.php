<?php

namespace app\manage\controller;

use think\facade\Db;
use think\facade\Session;

/**
 * 权限认证类
 */
class Auth
{
    // -------------------------------------------------------------------------
    // 类常量与属性
    // -------------------------------------------------------------------------

    /** @var array 用户信息缓存 */
    protected static $userCache = [];
    protected static $_authList = [];

    // -------------------------------------------------------------------------
    // 初始化方法
    // -------------------------------------------------------------------------

    //-----------------------------------------------------------------
    // 构造方法
    //-----------------------------------------------------------------
    public function __construct() {}

    // -------------------------------------------------------------------------
    // 核心权限验证方法
    // -------------------------------------------------------------------------

    //-----------------------------------------------------------------
    // 权限验证
    //-----------------------------------------------------------------
    public function check($name, $uid, $type = 1, $mode = 'url', $relation = 'or')
    {
        $authList = $this->getAuthList($uid, $type);
        $nameList = $this->normalizeName($name);
        return $this->validate($authList, $nameList, $mode, $relation);
    }

    // -------------------------------------------------------------------------
    // 辅助方法
    // -------------------------------------------------------------------------

    //-----------------------------------------------------------------
    // 标准化规则名称
    //-----------------------------------------------------------------
    protected function normalizeName($name)
    {
        if (!is_array($name)) {
            $name = explode(',', strtolower(str_replace(' ', '', $name)));
        }
        return array_map('strtolower', $name);
    }

    //-----------------------------------------------------------------
    // 执行验证逻辑
    //-----------------------------------------------------------------
    protected function validate($authList, $nameList, $mode, $relation)
    {
        $matched = [];
        $request = array_change_key_case($_REQUEST, CASE_LOWER);

        foreach ($authList as $rule) {
            if ($mode === 'url' && strpos($rule, '?') !== false) {
                list($base, $query) = explode('?', $rule, 2);
                if ($this->matchUrlRule($base, $query, $nameList, $request)) {
                    $matched[] = $base;
                }
            } elseif (in_array($rule, $nameList)) {
                $matched[] = $rule;
            }
        }

        return $relation === 'or'
            ? !empty($matched)
            : empty(array_diff($nameList, $matched));
    }

    //-----------------------------------------------------------------
    // 匹配URL规则
    //-----------------------------------------------------------------
    protected function matchUrlRule($base, $query, $nameList, $request)
    {
        parse_str($query, $ruleParams);
        $intersect = array_intersect_assoc($request, $ruleParams);

        return in_array(strtolower($base), $nameList) && $intersect == $ruleParams;
    }

    // -------------------------------------------------------------------------
    // 用户组与权限获取
    // -------------------------------------------------------------------------

    //-----------------------------------------------------------------
    // 获取用户组
    //-----------------------------------------------------------------
    public function getGroups($uid)
    {
        static $groupCache = [];

        if (!isset($groupCache[$uid])) {
            $query = Db::name('auth_group_access')
                ->alias('a')
                ->join('auth_group g', 'a.group_id = g.id')
                ->where('a.uid', $uid)
                ->where('g.status', 1)
                ->field('uid,group_id,title,rules');

            $groupCache[$uid] = $query->select()->toArray() ?: [];
        }

        return $groupCache[$uid];
    }

    //-----------------------------------------------------------------
    // 获取权限列表
    //-----------------------------------------------------------------
    protected function getAuthList($uid, $type)
    {
        $cacheKey = $uid . '_' . $type;

        if (isset(self::$_authList[$cacheKey])) {
            return self::$_authList[$cacheKey];
        }

        if ($this->useSessionCache($cacheKey)) {
            return Session::get('_auth_lists_' . $cacheKey, []);
        }

        $ruleIds = $this->getUserRuleIds($uid);
        $authList = $this->fetchValidRules($ruleIds, $type, $uid);
        return $this->cacheAuthList($cacheKey, $authList);
    }

    // -------------------------------------------------------------------------
    // 数据库操作相关
    // -------------------------------------------------------------------------

    //-----------------------------------------------------------------
    // 获取用户规则ID集合
    //-----------------------------------------------------------------
    protected function getUserRuleIds($uid)
    {
        $groups = $this->getGroups($uid);
        $ruleIds = [];

        foreach ($groups as $group) {
            $ruleIds = array_merge($ruleIds, explode(',', trim($group['rules'], ',')));
        }

        return array_unique(array_filter($ruleIds));
    }

    //-----------------------------------------------------------------
    // 获取有效规则列表
    //-----------------------------------------------------------------
    protected function fetchValidRules($ruleIds, $type, $uid)
    {
        if (empty($ruleIds)) return [];

        $query = Db::name('auth_rule')
            ->where('id', 'in', $ruleIds)
            ->where('type', $type)
            ->where('status', 1)
            ->field('name');

        return $query->select()->map(function ($rule) use ($uid) {
            return strtolower($rule['name']);
        })->filter()->toArray();
    }

    // -------------------------------------------------------------------------
    // 缓存处理
    // -------------------------------------------------------------------------

    //-----------------------------------------------------------------
    // 检查是否使用会话缓存
    //-----------------------------------------------------------------
    protected function useSessionCache($key)
    {
        return Session::has('_auth_lists_' . $key);
    }

    //-----------------------------------------------------------------
    // 缓存权限列表
    //-----------------------------------------------------------------
    protected function cacheAuthList($key, $data)
    {
        $data = array_unique($data);
        $sessionKey = '_auth_lists_' . $key;

        // 写入 session 并保存
        Session::set($sessionKey, $data);
        Session::save();

        // 同步写入内存缓存
        self::$_authList[$key] = $data;
        return $data;
    }

    // -------------------------------------------------------------------------
    // 外部调用方法
    // -------------------------------------------------------------------------

    //-----------------------------------------------------------------
    // 刷新权限（清除缓存，下次访问时自动重新加载）
    //-----------------------------------------------------------------
    public static function refreshAuth($uid = null, $type = 1)
    {
        if ($uid === null) {
            $uid = session('admin_id');
        }

        if (!$uid) {
            return false;
        }

        // 支持刷新type=1和type=2两种权限
        $types = is_array($type) ? $type : [$type];

        foreach ($types as $t) {
            $cacheKey = $uid . '_' . $t;
            $sessionKey = '_auth_lists_' . $cacheKey;

            // 清除内存缓存
            unset(self::$_authList[$cacheKey]);

            // 清除session缓存
            Session::delete($sessionKey);
        }

        Session::save();

        return true;
    }

    //-----------------------------------------------------------------
    // 重新加载权限（清除旧缓存并重新获取）
    //-----------------------------------------------------------------
    public static function reloadAuth($uid = null, $type = 1)
    {
        if ($uid === null) {
            $uid = session('admin_id');
        }

        if (!$uid) {
            return false;
        }

        // 先清除缓存
        self::refreshAuth($uid, $type);

        // 重新获取权限
        $auth = new self();
        $types = is_array($type) ? $type : [$type];

        foreach ($types as $t) {
            $auth->getAuthList($uid, $t);
        }

        return true;
    }
}
