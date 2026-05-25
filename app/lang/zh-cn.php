<?php
return [

    // =========================================================================
    // 系统通用
    // =========================================================================

    // 操作类型
    'add' => '添加',
    'edit' => '修改',
    'delete' => '删除',
    'restore' => '还原',
    'operation' => '操作',

    // 通用状态
    'total' => '总数',
    'review' => '审核',
    'status' => '状态',
    'sort' => '排序',
    'data' => '数据',
    'all_data' => '所有数据',

    // 通用提示
    'unknown' => '未知',
    'get_success' => '获取成功',
    'get_failed' => '获取失败',
    'add_success' => '%s添加成功！',
    'edit_success' => '%s修改成功！',
    'delete_success' => '删除成功',
    'delete_success_count' => '成功删除 %d 条%s数据',
    'operation_success' => '操作成功！！',
    'operation_failed' => '操作失败',
    'operation_failed_retry' => '操作失败，请稍后重试',
    'render_success' => '渲染成功',

    // 验证消息
    'illegal_request' => '非法请求',
    'illegal_request_operation' => '非法请求，请通过正常操作流程执行',
    'invalid_parameters' => '参数错误',
    'invalid_operation' => '非常操作！',
    'title_required' => '%s名称不能为空',
    'url_required' => '链接地址不能为空',
    'url_valid' => '请输入有效的链接地址',

    // 系统错误
    'server_error' => '服务器错误',
    'system_error' => '系统错误',
    'system_busy' => '系统繁忙',
    'system_busy_try_later' => '系统繁忙，请稍后再试',
    'system_config_error' => '系统配置异常，请联系管理员',
    'system_exception' => '系统异常：',
    'contact_admin' => '请联系管理员',
    'contact_technical' => '），请联系技术调整。',

    // =========================================================================
    // 登录认证
    // =========================================================================
    'login_success' => '登录成功！！！',
    'login_failed' => '用户名或密码错误',
    'logout' => '退出',
    'logout_success' => '退出成功！！！',
    'logout_alert' => '确定退出当前用户吗？',
    'already_logged_in' => '您已登录，请勿重复登录！',
    'captcha_error' => '验证码错误，请刷新后重试',
    'account_not_exists' => '账号不存在',
    'account_disabled' => '账号已被禁用',
    'session_expired' => '会话已过期，请重新登录',
    'access_denied' => '访问被拒绝',

    // =========================================================================
    // 权限管理
    // =========================================================================
    'no_permission_view_other_user' => '您无权查看其他用户的信息',
    'no_delete_permission' => '权限不足：仅系统管理员或超级管理员可操作',
    'operation_forbidden' => '禁止操作',
    'select_at_least_one_permission' => '请选择至少一个权限',

    // =========================================================================
    // 数据操作
    // =========================================================================
    'data_not_exists' => '要编辑的数据不存在',
    'model_pk_undefined' => '模型未定义主键字段',
    'invalid_data_selection' => '请选择有效数据',
    'no_data_operation' => '没有可操作的数据',
    'invalid_record_id' => '无效的记录ID',
    'record_not_exists' => '指定的记录不存在',
    'select_data_first' => '请选择要删除的数据',
    'max_delete_limit' => '单次最多删除50条数据',
    'no_data_found' => '未找到可删除的数据',
    'model_not_exists' => '请求的模型不存在',

    // =========================================================================
    // 文件上传
    // =========================================================================
    'upload_success' => '上传成功',
    'upload_failed' => '上传失败',
    'upload_server_error' => '服务器处理文件时发生错误',
    'upload_size_exceed' => '文件大小超过服务器限制（',
    'file_size_exceed' => '文件大小不能超过',
    'invalid_file_type' => '文件类型不允许',
    'file_not_exists' => '文件不存在',
    'file_too_large' => '文件大小超出限制',
    'file_extension_error' => '文件扩展名不允许',
    'file_type_error' => '文件类型错误',
    'watermark_failed' => '水印添加失败',
    'storage_failed' => '文件存储失败',
    'path_invalid' => '文件路径无效',

    // =========================================================================
    // 状态和排序
    // =========================================================================
    'status_update_success' => '状态更新成功',
    'sort_value_must_be_number' => '排序值必须是数字',
    'sort_value_range' => '排序值必须在0-10000之间',
    'sort_update_success' => '修改成功，请刷新查看最新排序。',

    // =========================================================================
    // 缓存管理
    // =========================================================================
    'cache_clear_success' => '缓存清理完成',
    'clear_cache' => '清除缓存',
    'clear_cache_confirm' => '清除缓存后将获得最新数据信息！！！',

    // =========================================================================
    // 备份管理
    // =========================================================================
    'backup_data' => '备份数据',
    'backup_confirm' => '确定备份数据吗？备份数据需要点时间，请稍候！！！',
    'backup_success' => '备份操作成功！',
    'backup_failed' => '备份操作失败',
    'invalid_action_type' => '无效操作类型',
    'backup_file_not_found' => '备份文件不存在',
    'backup_file_corrupted' => '备份文件已损坏',
    'backup_permission_denied' => '没有文件操作权限',

    // =========================================================================
    // 后台首页
    // =========================================================================

    // 页面标题
    'admin_home' => '后台首页',
    'index_home' => '前台首页',
    'website_data_overview' => '网站数据概览',
    'quick_operations' => '快捷操作',
    'system_information' => '系统信息',
    'website_expiry_countdown' => '网站到期倒计时',
    'notes' => '注意事项',
    'technical_support' => '技术支持',
    'support_us' => '支持我们',
    'admin_video' => '视频教程',
    'admin_doc' => '开发数据',
    'admin_banquan' => '版权 © Feiniao',

    // 时间单位
    'day' => '天',
    'hour' => '时',
    'minute' => '分',
    'second' => '秒',
    'website_expiry' => '网站到期',

    // 支持弹窗
    'thank_you_for_support' => '感谢您的支持！',
    'donation_help' => '您的捐助将帮助我们持续改进产品。',

    // 技术支持
    'wechat_consultation' => '微信咨询',
    'scan_qrcode_to_add_wechat' => '扫描二维码添加微信',
    'wechat_qrcode' => '微信二维码',
    'qq_consultation' => 'QQ咨询',
    'qq_number' => 'QQ号码: 2689543658',
    'consult_immediately' => '立即咨询',

    // 系统信息标签
    'system_version' => '系统信息',
    'php_version' => 'PHP版本',
    'server_software' => '服务器软件',
    'database' => '数据库',
    'login_ip' => '登录IP',
    'server_time' => '服务器时间',
    'uptime' => '运行时间',

    // 卡片标题
    'pending_comics' => '待审核漫画',
    'all_comics' => '所有漫画',
    'all_categories' => '所有栏目',
    'image_ads' => '图片广告',

    // 快捷方式
    'category_list' => '栏目列表',
    'comic_list' => '漫画列表',
    'website_settings' => '网站设置',
    'user_management' => '用户管理',
    'friend_links' => '友情链接',

    // 安全规则
    'data_protection' => '数据保护',
    'data_protection_content' => '定期备份数据，每周手动备份一次。导出用户数据时注意隐藏敏感信息，不要通过邮件发送后台密码。',
    'operation_specifications' => '操作规范',
    'operation_specifications_content' => '删除数据前确认提示，批量操作前先小范围测试。不要上传可疑文件，图片上传前确认内容合规。',
    'login_environment' => '登录环境',
    'login_environment_content' => '避免使用公共WiFi登录后台，离开电脑时务必退出账号或锁屏。',
    'account_protection' => '账号保护',
    'account_protection_content' => '不要将账号借给他人，离职人员账号需立即停用。',
    'password_management' => '密码管理',
    'password_management_content' => '使用复杂密码（字母+数字+符号），不要用生日、123456等简单密码。',

    // 动态信息
    'published_new_comic' => '发布了新漫画：%s',
    'added_new_category' => '新增了栏目：%s',

    // 运行时间
    'uptime_format' => '%d天%d小时%d分钟',

    // 按钮文本
    'donate' => '捐助',
    'technical' => '技术',

    // =========================================================================
    // 漫画管理
    // =========================================================================
    'comic' => '数据',
    'new_comic' => '新数据',
    'add_comic' => '添加数据',
    'edit_comic' => '编辑数据',
    'comic_success' => '数据操作成功！',
    'comic_failed' => '数据操作失败',
    'comic_title' => '数据标题',
    'comic_content' => '数据内容',
    'comic_cateid' => '所属栏目',
    'comic_thumb' => '缩略图',
    'comic_tag' => '标签',
    'cateid_required' => '请选择所属栏目',

    // 批量操作
    'select_comics_first' => '请选择要操作的漫画',
    'target_category_not_exists' => '目标分类不存在或已被删除',
    'batch_move_comics_log' => '批量移动%d条漫画',
    'batch_move_success' => '成功移动 %d 篇漫画',
    'no_comics_found' => '未找到可操作的漫画',
    'delete_comics_log' => '成功删除%d条数据',
    'delete_comics_success' => '成功删除%d条数据',
    'operation_failed_check_log' => '操作失败，请检查日志',

    // =========================================================================
    // 栏目管理
    // =========================================================================
    'category' => '栏目',
    'new_category' => '新栏目',
    'add_category' => '添加栏目',
    'edit_category' => '编辑栏目',
    'category_success' => '栏目操作成功！',
    'category_failed' => '栏目操作失败',
    'category_title' => '栏目名称',
    'category_ename' => '英文名称',
    'category_pid' => '父级栏目',
    'category_type' => '栏目类型',
    'ename_required' => '英文名称不能为空',

    // 删除相关
    'invalid_category_id' => '无效的栏目ID',
    'category_not_exists' => '栏目不存在或已被删除',
    'cannot_delete_category_with_data' => '<div class="alert alert-danger"><h4>无法删除栏目！</h4><p>该栏目及子栏目下存在 <b>%d</b> 篇漫画数据</p><p>请先删除相关数据后再操作</p></div>',
    'delete_category_log' => '删除栏目《%s》及%d个子栏目',
    'delete_category_success' => '成功删除栏目《%s》及%d个子栏目',

    // =========================================================================
    // 链接管理
    // =========================================================================
    'link' => '链接',
    'new_link' => '新链接',
    'add_link' => '添加链接',
    'edit_link' => '修改链接',
    'link_success' => '链接操作成功！',
    'link_failed' => '链接操作失败',
    'link_title' => '链接标题',
    'link_url' => '链接地址',
    'link_thumb' => '链接图标',
    'link_type' => '链接类型',

    // =========================================================================
    // 广告管理
    // =========================================================================
    'ad' => '广告',
    'new_ad' => '新广告',
    'add_ad' => '添加广告',
    'edit_ad' => '修改广告',
    'ad_success' => '广告操作成功！',
    'ad_failed' => '广告操作失败',
    'ad_title' => '广告标题',
    'ad_stitle' => '广告副标题',
    'ad_thumb' => '广告图片',
    'ad_url' => '广告链接',
    'ad_pos' => '广告位置',
    'ad_end_time' => '结束时间',
    'adposid_required' => '请选择广告位置',
    'thumb_required' => '请上传广告图片',

    // 广告位相关
    'adpos_not_exists' => '广告位不存在',
    'adpos_disabled' => '广告位已被禁用',

    // =========================================================================
    // 广告位管理
    // =========================================================================
    'adpos' => '广告位置',
    'new_adpos' => '新广告位',
    'add_adpos' => '添加广告位置',
    'edit_adpos' => '编辑广告位置',
    'adpos_success' => '广告位操作成功！',
    'adpos_failed' => '广告位操作失败',
    'adpos_title' => '广告位名称',
    'adpos_ename' => '英文标识',
    'adpos_width' => '宽度',
    'adpos_height' => '高度',
    'width_required' => '请设置宽度',
    'height_required' => '请设置高度',

    // 删除相关
    'invalid_delete_params' => '无效的删除参数',
    'adpos_not_exists' => '广告位不存在或已被删除',
    'cannot_delete_adpos_with_ads' => '【%s】下有 %d 个广告，请先删除！',
    'delete_adpos_failed' => '删除广告位【%s】失败',
    'delete_adpos_log' => '删除广告位【%s】',
    'delete_adpos_success' => '成功删除广告位【%s】',

    // =========================================================================
    // 用户管理
    // =========================================================================
    'user_data' => '用户数据',
    'user_list' => '用户列表',
    'add_user_success' => '添加用户成功！！！',
    'edit_user_success' => '修改用户成功！！！',
    'delete_user_success' => '成功删除 %d 条数据',
    'edit_failed' => '修改失败: ',
    'invalid_user_ids' => '无效的用户ID',
    'service_exception' => '服务异常',

    // 日志模块
    'add_user' => '新增用户',
    'edit_user' => '用户修改',
    'delete_user' => '删除用户',
    'user_data_modified' => '修改用户资料，用户ID:%d',

    // =========================================================================
    // 用户组管理
    // =========================================================================
    'add_user_group' => '添加用户组',
    'edit_user_group' => '编辑用户组',
    'delete_group_success' => '删除成功，共移除 %d 个用户组',
    'permission_update_success' => '权限更新成功',
    'permission_update_failed' => '权限更新失败',
    'permission_save_failed' => '权限保存失败：',
    'user_group_not_exists' => '用户组不存在',
    'user_group_not_exists_or_deleted' => '用户组不存在或已被删除',

    // 日志模块
    'user_group_management' => '用户组管理',
    'assign_permission' => '权限分配',
    'delete_group' => '删除用户组',
    'user_group_operation' => '用户组操作',

    // =========================================================================
    // 权限管理
    // =========================================================================
    'permission' => '权限',
    'new_permission' => '新权限',
    'add_permission' => '添加权限',
    'edit_permission' => '修改权限',
    'permission_success' => '权限操作成功！',
    'permission_failed' => '权限操作失败',
    'permission_title' => '权限名称',
    'permission_name' => '权限标识',
    'permission_pid' => '父级权限',
    'permission_show' => '是否显示',
    'permission_list' => '权限列表',
    'name_required' => '权限标识不能为空',

    // 日志模块
    'delete_permission' => '权限管理',
    'permission_operation' => '删除权限',

    // =========================================================================
    // 备份管理
    // =========================================================================
    'backup_added' => '添加备份',
    'backup_restored' => '还原备份',
    'backup_deleted' => '删除备份【%s】',
    'backup_management' => '备份管理',

    // =========================================================================
    // 配置管理
    // =========================================================================
    'config_field' => '配置字段',
    'new_field' => '新字段',
    'add_config_field' => '添加配置字段',
    'edit_config_field' => '修改配置字段',
    'config_field_success' => '配置字段操作成功！',
    'config_update_success' => '配置更新成功！',
    'field_name' => '字段名称',
    'field_ename' => '英文标识',
    'field_type' => '字段类型',
    'field_model' => '所属模型',
    'field_name_exists' => '字段名「%s」已存在，请使用其他名称',
    'invalid_category_param' => '非法分类参数',
    'invalid_option' => '%s 包含无效选项',
    'system_field_protected' => '系统字段「%s」不允许删除',
    'no_data_to_delete' => '没有可删除的数据',
    'delete_config_fields_log' => '删除配置字段（共 %d 条）',
    'delete_config_fields_success' => '成功删除 %d 条配置字段',
    'update_config_items_log' => '更新%d个配置项',
    'config_data_process_exception' => '配置数据处理异常',

    // =========================================================================
    // 日志相关
    // =========================================================================
    'delete_data_log' => '删除%s数据（共 %d 条）',
    'delete_failed_debug' => '删除失败：%d行 %s',
];
