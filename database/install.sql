DROP TABLE IF EXISTS `__prefix__action_log`;
CREATE TABLE `__prefix__action_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uname` varchar(50) NOT NULL DEFAULT '' COMMENT '操作人用户名',
  `module` varchar(2048) NOT NULL DEFAULT '' COMMENT '操作模块名称',
  `action` varchar(30) NOT NULL DEFAULT '' COMMENT '操作类型',
  `data_id` int(11) NOT NULL DEFAULT '0' COMMENT '操作数据ID',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '操作描述',
  `ip` varchar(45) NOT NULL DEFAULT '' COMMENT '操作IP地址(支持IPv6)',
  `os` varchar(50) NOT NULL DEFAULT '' COMMENT '客户端操作系统',
  `create_time` int(11) NOT NULL COMMENT '操作时间戳',
  `groupid` mediumint(9) unsigned NOT NULL DEFAULT '0' COMMENT '管理员用户组ID',
  `adminid` mediumint(9) unsigned NOT NULL DEFAULT '1' COMMENT '管理员用户ID',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_create_time` (`create_time`),
  KEY `idx_uname` (`uname`),
  KEY `idx_action` (`action`),
  KEY `idx_data_id` (`data_id`),
  KEY `idx_groupid` (`groupid`),
  KEY `idx_adminid` (`adminid`),
  KEY `idx_module_prefix` (`module`(100)) COMMENT '模块名前缀索引',
  KEY `idx_group_admin` (`groupid`,`adminid`),
  KEY `idx_module_action` (`module`(50),`action`),
  KEY `idx_ip_time` (`ip`,`create_time`),
  KEY `idx_os_time` (`os`,`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ROW_FORMAT=DYNAMIC COMMENT='系统操作日志表';

DROP TABLE IF EXISTS `__prefix__admin`;
CREATE TABLE `__prefix__admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
  `groupid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员所属组ID',
  `uname` varchar(128) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '管理员名称',
  `nickname` varchar(128) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '管理员昵称',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员密码',
  `thumb` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '管理员头像',
  `login_ip` varchar(45) DEFAULT '' COMMENT '登录IP',
  `session_id` varchar(128) DEFAULT '' COMMENT '会话ID',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1-启用 0-禁用',
  `login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  `last_active_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后活动时间',
  `is_lang` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '多语言支持：1-启用 0-关闭',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_uname` (`uname`),
  KEY `groupid` (`groupid`),
  KEY `idx_status` (`status`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员用户表';

-- 插入初始管理员记录（供安装时更新使用）
INSERT INTO `__prefix__admin` (`id`, `groupid`, `uname`, `nickname`, `password`, `thumb`, `status`, `create_time`) VALUES (1, 1, 'admin', '管理员', '', '', 1, UNIX_TIMESTAMP());

DROP TABLE IF EXISTS `__prefix__auth_group`;
CREATE TABLE `__prefix__auth_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '管理员组ID',
  `pid` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '上级管理员组ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员组名称',
  `title_auth` varchar(255) NOT NULL DEFAULT '' COMMENT '管理员组描述',
  `rules` text NOT NULL COMMENT '管理员组权限列表',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1-启用 0-禁用',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `sort` smallint(6) unsigned NOT NULL DEFAULT '50' COMMENT '排序：数值越小越靠前',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_pid` (`pid`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限组管理表';

INSERT INTO `__prefix__auth_group` VALUES ('1', '0', '系统管理员', '拥有所有权限', '1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,115,116,117,118,119,120,121,122,123,124,125,126,127,128,129,130,131,132,133,134,135,136,137,138,139,140,142', '1', '1656144972', '1773217994', '1');


DROP TABLE IF EXISTS `__prefix__auth_group_access`;
CREATE TABLE `__prefix__auth_group_access` (
  `uid` int(10) unsigned NOT NULL COMMENT '管理员ID',
  `group_id` int(10) unsigned NOT NULL COMMENT '管理员组ID',
  UNIQUE KEY `uid_group_id` (`uid`,`group_id`),
  KEY `uid` (`uid`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员与权限组关联表';

INSERT INTO `__prefix__auth_group_access` VALUES ('1', '1');

DROP TABLE IF EXISTS `__prefix__auth_rule`;
CREATE TABLE `__prefix__auth_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '权限规则ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '权限标识',
  `module` varchar(100) DEFAULT NULL COMMENT '所属模块',
  `rule` varchar(255) NOT NULL DEFAULT '' COMMENT '权限路由',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '权限名称',
  `pid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级权限ID',
  `icon` varchar(128) NOT NULL DEFAULT '' COMMENT '菜单图标',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '权限类型：1-菜单 2-操作',
  `show` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '是否显示：1-显示 0-隐藏',
  `sort` int(10) unsigned NOT NULL DEFAULT '50' COMMENT '排序：数值越小越靠前',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1-启用 0-禁用',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `name` (`name`) USING BTREE,
  KEY `idx_pid` (`pid`),
  KEY `idx_type` (`type`),
  KEY `idx_show` (`show`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限规则表';

INSERT INTO `__prefix__auth_rule` VALUES ('1', 'Base/clearCache', null, '', '清除缓存', '0', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('2', 'Base/BaseSort', null, '', '修改排序', '0', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('3', 'Base/changeStatus', null, '', '状态更新', '0', '', '1', '0', '4', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('4', 'Base/changeModuleStatus', null, '', '状态模块更新', '0', '', '1', '0', '5', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('5', 'Upload/UploadImg', null, 'upload/uploadImg', '图片上传', '0', '', '1', '0', '10', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('6', 'Upload/DeleteImg', null, 'upload/deleteImg', '图片删除', '0', '', '1', '0', '11', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('7', 'Upload/uploadFile', null, 'upload/uploadfile', '上传附件', '0', '', '1', '0', '12', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('8', 'Upload/uploadImages', null, 'upload/uploadImages', '图集上传', '0', '', '1', '0', '13', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('9', 'Login/index', null, '', '后台登录', '0', '', '1', '0', '20', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('10', 'Login/submit', null, '', '登录验证', '0', '', '1', '0', '21', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('11', 'Login/logout', null, '', '退出登录', '0', '', '1', '0', '22', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('12', 'config', null, '', '网站设置', '0', 'layui-icon-set', '1', '1', '30', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('13', 'conf/lst', null, 'conf/lst', '字段列表', '12', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('14', 'conf/form', null, '', '字段操作', '12', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('15', 'conf/getConfData', null, '', '数据接口', '12', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('16', 'conf/del', null, '', '字段删除', '12', '', '1', '0', '4', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('17', 'conf/conf', null, 'conf/conf', '配置列表', '12', '', '1', '1', '5', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('18', 'confcategory/lst', null, 'confcategory/lst', '分组列表', '12', 'layui-icon-group', '1', '1', '6', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('19', 'confcategory/getData', null, 'confcategory/getData', '配置分组数据', '18', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('20', 'confcategory/form', null, 'confcategory/form', '配置分组操作', '18', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('21', 'confcategory/del', null, 'confcategory/del', '配置分组删除', '18', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('22', 'language/switch', null, 'language/switch', '语言切换', '12', '', '1', '0', '50', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('23', 'debug/index', null, 'debug/index', '测试管理', '126', 'layui-icon-console', '1', '1', '31', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('24', 'debug/toggle', null, 'debug/toggle', 'debug操作', '23', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('25', 'debug/getEmailConfig', null, 'debug/getEmailConfig', '获取邮箱配置', '23', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('26', 'debug/sendTestEmail', null, 'debug/sendTestEmail', '发送测试邮件', '23', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('27', 'Admin/lst', null, 'admin/lst', '权限管理', '126', 'layui-icon-friends', '1', '1', '40', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('28', 'Admin/add', null, '', '用户添加', '27', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('29', 'Admin/edit', null, '', '用户修改', '27', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('30', 'Admin/del', null, '', '用户删除', '27', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('31', 'Admin/getUserData', null, '', '用户数据', '27', '', '1', '0', '4', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('32', 'admin/detail', null, 'admin/detail', '用户详情', '27', '', '1', '0', '5', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('33', 'AuthGroup/lst', null, 'group/lst', '用户组', '27', 'layui-icon-user', '1', '0', '10', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('34', 'AuthGroup/form', null, '', '用户组操作', '33', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('35', 'AuthGroup/power', null, '', '权限分配', '33', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('36', 'AuthGroup/getGroupData', null, '', '用户组接口', '33', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('37', 'AuthGroup/del', null, '', '用户组删除', '33', '', '1', '0', '4', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('38', 'AuthRule/lst', null, 'rule/lst', '权限列表', '27', 'layui-icon-password', '1', '0', '20', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('39', 'AuthRule/form', null, '', '权限操作', '38', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('40', 'AuthRule/getRuleData', null, '', '权限数据', '38', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('41', 'AuthRule/del', null, '', '权限删除', '38', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('42', 'Log/lst', null, 'log/lst', '日志记录', '126', 'layui-icon-log', '1', '1', '50', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('43', 'Log/getLogData', null, '', '登陆日志', '42', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('44', 'Log/baseDel', null, '', '日志删除', '42', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('45', 'ActionLog/lst', null, '', '操作日志', '42', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('46', 'ActionLog/getLogData', null, '', '日志数据', '45', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('47', 'ActionLog/baseDel', null, '', '日志删除', '45', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('48', 'Link/lst', null, 'link/lst', '友情链接', '12', 'layui-icon-link', '1', '1', '60', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('49', 'link/getLinkData', null, 'link/getLinkData', '链接数据', '48', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('50', 'link/form', null, 'link/form', '链接操作', '48', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('51', 'page/lst', null, 'page/lst', '单页管理', '12', 'layui-icon-list', '1', '1', '70', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('52', 'page/getData', null, 'page/getData', '单页数据', '51', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('53', 'page/form', null, 'page/form', '单页表单', '51', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('54', 'page/del', null, 'page/del', '单页删除', '51', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('115', 'Module/index', null, 'module/index', '模块管理', '126', 'layui-icon-app', '1', '1', '50', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('116', 'Module/refresh', null, 'module/refresh', '模块刷新', '115', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('117', 'Module/disable', null, 'module/disable', '模块禁用', '115', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('118', 'Module/uninstall', null, 'module/uninstall', '模块卸载', '115', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('119', 'Module/setting', null, 'module/setting', '模块设置', '115', '', '1', '0', '4', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('120', 'Module/delete', null, 'module/delete', '模块删除', '115', '', '1', '0', '5', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('121', 'Module/enable', null, 'module/enable', '模块启用', '115', '', '1', '0', '6', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('122', 'Module/getModuleData', null, 'module/getModuleData', '模块列表', '115', '', '1', '0', '7', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('123', 'Module/install', null, 'module/install', '模块安装', '115', '', '1', '0', '8', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('124', 'Module/detail', null, 'module/detail', '模块详情', '115', '', '1', '0', '9', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('125', 'Module/update', null, 'module/update', '更新模块', '115', '', '1', '0', '10', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('126', 'Upgrade/system', null, 'upgrade/system', '系统管理', '0', 'layui-icon-refresh', '1', '1', '110', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('127', 'Upgrade/index', null, 'upgrade/index', '升级管理', '126', '', '1', '1', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('128', 'Upgrade/systemUpgrade', null, 'upgrade/systemUpgrade', '执行系统升级', '127', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('129', 'Upgrade/module', null, 'upgrade/module', '模块升级', '126', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('130', 'Upgrade/checkModuleUpdate', null, 'upgrade/checkModuleUpdate', '检查模块更新', '129', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('131', 'Upgrade/moduleUpgrade', null, 'upgrade/moduleUpgrade', '执行模块升级', '129', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('132', 'Upgrade/template', null, 'upgrade/template', '模板升级', '126', '', '1', '0', '3', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('133', 'Upgrade/checkTemplateUpdate', null, 'upgrade/checkTemplateUpdate', '检查模板更新', '132', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('134', 'Upgrade/templateUpgrade', null, 'upgrade/templateUpgrade', '执行模板升级', '132', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('135', 'Upgrade/logs', null, 'upgrade/logs', '升级记录', '126', '', '1', '0', '4', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('136', 'Upgrade/logDetail', null, 'upgrade/logDetail', '升级记录详情', '135', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('137', 'Upgrade/rollback', null, 'upgrade/rollback', '回滚升级', '135', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('138', 'Feedback/index', null, 'feedback/index', '意见反馈', '126', 'layui-icon-survey', '1', '1', '5', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('139', 'Feedback/submit', null, 'feedback/submit', '提交反馈', '138', '', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('140', 'Feedback/getTypes', null, 'feedback/getTypes', '获取反馈类型', '138', '', '1', '0', '2', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('142', 'Index/dashboard', null, '', '公共权限', '0', 'layui-icon-home', '1', '0', '1', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('283', 'module/market', null, 'module/market', '模块市场', '115', '', '1', '0', '50', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('284', 'upgrade/login', null, 'upgrade/login', '联盟登录', '126', '', '1', '0', '50', '1');
INSERT INTO `__prefix__auth_rule` VALUES ('285', 'upgrade/logout', null, 'upgrade/logout', '退出联盟登录', '126', '', '1', '0', '50', '1');

DROP TABLE IF EXISTS `__prefix__conf`;
CREATE TABLE `__prefix__conf` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '配置名称',
  `ename` varchar(255) NOT NULL DEFAULT '' COMMENT '配置标识',
  `value` text NOT NULL COMMENT '配置值',
  `values` varchar(1000) NOT NULL DEFAULT '' COMMENT '可选值列表',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '1:单行文本,2:单选,3:复选,4:下拉,5:多行文本,6:附件,7:小数,8:整数,9:长文本',
  `model` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '配置分类：1-基本设置 2-SEO设置 3-联系方式 4-图片水印 5-其他',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1-启用 0-禁用',
  `is_os` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '系统字段：1-是 0-否',
  `sort` int(10) unsigned NOT NULL DEFAULT '50' COMMENT '排序：数值越小越靠前',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `uk_ename` (`ename`),
  KEY `idx_model` (`model`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  KEY `idx_is_os` (`is_os`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8mb4 COMMENT='系统配置表';

INSERT INTO `__prefix__conf` VALUES ('1', '站点名称', 'sitename', '飞鸟漫画', '', '1', '1', '1', '0', '1');
INSERT INTO `__prefix__conf` VALUES ('2', '关键词语', 'keywords', '', '', '1', '1', '1', '0', '2');
INSERT INTO `__prefix__conf` VALUES ('3', '网站描述', 'description', '', '', '5', '1', '1', '0', '3');
INSERT INTO `__prefix__conf` VALUES ('4', '网站备案', 'beian', '', '', '1', '1', '1', '0', '4');
INSERT INTO `__prefix__conf` VALUES ('5', '网站版权', 'banquan', '© 2026 飞鸟漫画 版权所有', '', '1', '1', '1', '0', '11');
INSERT INTO `__prefix__conf` VALUES ('6', '网站域名', 'www', 'https://feiniao.paheng.net', '', '1', '3', '1', '0', '8');
INSERT INTO `__prefix__conf` VALUES ('7', '公司电话', 'tel', '', '', '1', '3', '1', '0', '5');
INSERT INTO `__prefix__conf` VALUES ('8', '公司传真', 'fax', '', '', '1', '3', '1', '0', '13');
INSERT INTO `__prefix__conf` VALUES ('9', '公司邮箱', 'email', '', '', '1', '3', '1', '0', '9');
INSERT INTO `__prefix__conf` VALUES ('10', '公司地址', 'add', '', '', '1', '3', '1', '0', '6');
INSERT INTO `__prefix__conf` VALUES ('11', '网站logo', 'logo', 'http://feiniao.paheng.net/storage/picture/20250212/231008a6d3a63d80412bc5d4de305989.png', '', '6', '2', '1', '0', '14');
INSERT INTO `__prefix__conf` VALUES ('12', '公司名称', 'gsmc', '飞鸟漫画', '', '1', '3', '1', '0', '1');
INSERT INTO `__prefix__conf` VALUES ('13', '联系人', 'lxr', '飞鸟漫画', '', '1', '3', '1', '0', '10');
INSERT INTO `__prefix__conf` VALUES ('14', '网安备案', 'wangan', '', '', '1', '1', '1', '0', '7');
INSERT INTO `__prefix__conf` VALUES ('15', '站点开启', 'siteon', '1', '1=>开启\n0=>关闭', '2', '1', '1', '1', '50');
INSERT INTO `__prefix__conf` VALUES ('16', '技术支持邮箱', 'tech_email', '', '', '1', '3', '1', '0', '12');
INSERT INTO `__prefix__conf` VALUES ('17', '销售咨询邮箱', 'sales_email', '', '', '1', '3', '1', '0', '14');
INSERT INTO `__prefix__conf` VALUES ('18', '合作洽谈邮箱', 'cooperation_email', '', '', '1', '3', '1', '0', '15');
INSERT INTO `__prefix__conf` VALUES ('19', '开启水印', 'water', '0', '1=>开启\r\n0=>关闭', '2', '4', '1', '1', '19');
INSERT INTO `__prefix__conf` VALUES ('20', '水印图片', 'waterimg', '/static/images/watermark.png', '', '6', '4', '1', '1', '18');
INSERT INTO `__prefix__conf` VALUES ('21', '水印位置', 'water_position', '4', '1=>左上角\r\n2=>右上角\r\n3=>左下角\r\n4=>右下角\r\n5=>居中', '2', '4', '1', '1', '17');
INSERT INTO `__prefix__conf` VALUES ('22', '水印透明度', 'water_opacity', '80', '', '8', '4', '1', '1', '16');
INSERT INTO `__prefix__conf` VALUES ('23', '微博链接', 'weibo', '', '', '1', '5', '1', '0', '20');
INSERT INTO `__prefix__conf` VALUES ('24', 'favicon图标', 'favicon', '', '', '6', '2', '1', '0', '16');
INSERT INTO `__prefix__conf` VALUES ('25', '底部logo', 'footerlogo', '', '', '6', '2', '1', '0', '17');
INSERT INTO `__prefix__conf` VALUES ('26', '技术支持电话', 'tech_tel', '', '', '1', '5', '1', '0', '31');
INSERT INTO `__prefix__conf` VALUES ('27', '销售咨询电话', 'sales_tel', '', '', '1', '5', '1', '0', '32');
INSERT INTO `__prefix__conf` VALUES ('28', '合作洽谈电话', 'cooperation_tel', '', '', '1', '5', '1', '0', '33');
INSERT INTO `__prefix__conf` VALUES ('29', '服务时间', 'service_time', '周一至周五 9:00-18:00', '', '1', '5', '1', '0', '34');
INSERT INTO `__prefix__conf` VALUES ('30', '在线咨询QQ', 'consult_qq', '', '', '1', '5', '1', '0', '39');
INSERT INTO `__prefix__conf` VALUES ('31', '在线咨询微信', 'consult_wechat', '', '', '1', '5', '1', '0', '40');
INSERT INTO `__prefix__conf` VALUES ('32', '咨询工作时间', 'consult_time', '周一至周日 9:00-21:00', '', '1', '5', '1', '0', '41');
INSERT INTO `__prefix__conf` VALUES ('33', '紧急技术支持', 'emergency_support', '', '', '1', '5', '1', '0', '42');
INSERT INTO `__prefix__conf` VALUES ('34', '微信公众号', 'wechat', '', '', '6', '2', '1', '0', '7');
INSERT INTO `__prefix__conf` VALUES ('35', '微信咨询', 'wechatt', '', '', '6', '2', '1', '0', '22');
INSERT INTO `__prefix__conf` VALUES ('36', '抖音二维码', 'douyin', '', '', '6', '2', '1', '0', '15');
INSERT INTO `__prefix__conf` VALUES ('37', '工作时间', 'worktime', '周一至周五: 9:00-18:00', '', '1', '1', '1', '0', '50');
INSERT INTO `__prefix__conf` VALUES ('38', '邮箱地址', 'emailaddress', '', '', '1', '6', '1', '0', '1');
INSERT INTO `__prefix__conf` VALUES ('39', 'SMTP服务器', 'smtpserver', 'smtp.qq.com', '', '1', '6', '1', '0', '2');
INSERT INTO `__prefix__conf` VALUES ('40', 'SMTP端口', 'smtpport', '465', '', '8', '6', '1', '0', '3');
INSERT INTO `__prefix__conf` VALUES ('41', 'SMTP用户名', 'smtpusername', '', '', '1', '6', '1', '0', '4');
INSERT INTO `__prefix__conf` VALUES ('42', 'SMTP密码', 'smtppassword', '', '', '1', '6', '1', '0', '5');
INSERT INTO `__prefix__conf` VALUES ('43', '是否使用SSL', 'smtpssl', '1', '1=>是\n0=>否', '2', '6', '1', '0', '6');
INSERT INTO `__prefix__conf` VALUES ('44', '发件人名称', 'sendername', '飞鸟漫画', '', '1', '6', '1', '0', '7');

DROP TABLE IF EXISTS `__prefix__conf_category`;
CREATE TABLE `__prefix__conf_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分类ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `ename` varchar(100) NOT NULL DEFAULT '' COMMENT '分类标识',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1-启用 0-禁用',
  `sort` int(10) unsigned NOT NULL DEFAULT '50' COMMENT '排序：数值越小越靠前',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ename` (`ename`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COMMENT='配置分类表';

INSERT INTO `__prefix__conf_category` VALUES ('1', '基本设置', 'basic', '1', '1', '2026-03-07 22:01:17', '2026-03-07 22:01:17');
INSERT INTO `__prefix__conf_category` VALUES ('2', '外观设置', 'exterior', '1', '2', '2026-03-07 22:01:17', '2026-03-12 18:19:42');
INSERT INTO `__prefix__conf_category` VALUES ('3', '联系方式', 'contact', '1', '3', '2026-03-07 22:01:17', '2026-03-07 22:01:17');
INSERT INTO `__prefix__conf_category` VALUES ('4', '图片水印', 'watermark', '1', '4', '2026-03-07 22:01:17', '2026-03-07 22:01:17');
INSERT INTO `__prefix__conf_category` VALUES ('5', '其他设置', 'other', '1', '5', '2026-03-07 22:01:17', '2026-03-07 22:01:17');
INSERT INTO `__prefix__conf_category` VALUES ('6', '邮箱配置', 'email', '1', '6', '2026-03-10 11:11:51', '2026-03-10 11:11:51');

DROP TABLE IF EXISTS `__prefix__link`;
CREATE TABLE `__prefix__link` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '链接ID',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '链接名称',
  `url` varchar(500) NOT NULL DEFAULT '' COMMENT '链接地址',
  `thumb` varchar(500) DEFAULT '' COMMENT '链接图片',
  `desc` varchar(500) DEFAULT '' COMMENT '链接描述',
  `type` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '链接类型：1-文字链接 2-图片链接',
  `sort` int(10) unsigned NOT NULL DEFAULT '50' COMMENT '排序：数值越小越靠前',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态：1-启用 0-禁用',
  `adminid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) NOT NULL DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_sort` (`sort`),
  KEY `idx_adminid` (`adminid`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COMMENT='友情链接表';

INSERT INTO `__prefix__link` VALUES ('1', '飞鸟漫画', 'https://feiniao.paheng.net', '', '', '1', '50', '1', '0', '1764578298', '1772990553', '0');

DROP TABLE IF EXISTS `__prefix__log`;
CREATE TABLE `__prefix__log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '日志ID',
  `adminid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理员ID',
  `groupid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '管理组ID',
  `uname` varchar(128) NOT NULL DEFAULT '' COMMENT '管理员用户名',
  `login_ip` varchar(45) NOT NULL DEFAULT '' COMMENT '登录IP',
  `login_time` int(11) NOT NULL DEFAULT '0' COMMENT '登录时间',
  `login_os` varchar(500) DEFAULT '' COMMENT '登录系统信息',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `idx_adminid` (`adminid`),
  KEY `idx_groupid` (`groupid`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_uname` (`uname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='登录日志表';

DROP TABLE IF EXISTS `__prefix__module`;
CREATE TABLE `__prefix__module` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `name` varchar(100) NOT NULL COMMENT '模块名称',
  `title` varchar(255) NOT NULL COMMENT '模块标题',
  `description` text COMMENT '模块描述',
  `version` varchar(50) NOT NULL DEFAULT '1.0.0' COMMENT '模块版本',
  `author` varchar(100) DEFAULT NULL COMMENT '模块作者',
  `url` varchar(255) DEFAULT NULL COMMENT '模块网址',
  `dependencies` json DEFAULT NULL COMMENT '依赖模块',
  `routes` json DEFAULT NULL COMMENT '路由配置',
  `menus` json DEFAULT NULL COMMENT '菜单配置',
  `config` json DEFAULT NULL COMMENT '模块配置',
  `status` varchar(20) NOT NULL DEFAULT 'uninstalled' COMMENT '模块状态',
  `create_time` int(11) unsigned DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) unsigned DEFAULT NULL COMMENT '更新时间',
  `delete_time` int(11) unsigned DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模块管理表';

DROP TABLE IF EXISTS `__prefix__page`;
CREATE TABLE `__prefix__page` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `identifier` varchar(50) NOT NULL COMMENT '标识',
  `content` text NOT NULL COMMENT '内容',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1=显示，0=隐藏',
  `sort` int(4) NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` datetime NOT NULL COMMENT '创建时间',
  `updated_at` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_identifier` (`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COMMENT='单页内容表';

INSERT INTO `__prefix__page` VALUES ('1', '隐私政策', 'privacy', '<p>&nbsp;一、引言</p><p>飞鸟漫画（以下简称&quot;我们&quot;）致力于保护用户的隐私和个人信息。本隐私政策旨在向您说明我们如何收集、使用、存储和保护您的个人信息，以及您享有的权利。</p><p><br/></p><p>二、我们收集的信息&nbsp;</p><p>1. 注册信息</p><p>- 您在注册账号时提供的信息，如用户名、密码、邮箱、手机号等</p><p>- 第三方登录信息（如微信、QQ等）&nbsp;</p><p>2. 使用信息</p><p>- 您的阅读历史、书架内容、搜索记录</p><p>- 设备信息（设备型号、操作系统版本、设备标识符）</p><p>- IP地址、浏览器类型、访问时间</p><p>- 应用使用数据（如打开频率、使用时长）&nbsp;</p><p>3. 内容信息</p><p>- 您上传的头像、昵称等个人资料</p><p>- 您发布的评论、反馈等用户生成内容</p><p><br/></p><p>三、信息使用方式&nbsp;</p><p>1. 提供服务</p><p>- 提供账号登录和管理功能</p><p>- 提供漫画阅读、书架管理等核心服务</p><p>- 提供个性化推荐和搜索功能&nbsp;</p><p>2. 改进服务</p><p>- 分析用户行为，优化产品功能</p><p>- 修复bug和提升用户体验&nbsp;</p><p>3. 安全保障</p><p>- 验证用户身份，防止账号被盗用</p><p>- 检测和防止欺诈行为&nbsp;</p><p>4. 合规要求</p><p>- 遵守法律法规和监管要求</p><p>- 响应法律程序和政府要求</p><p><br/></p><p>四、信息存储与保护&nbsp;</p><p>1. 存储方式</p><p>- 我们将您的个人信息存储在安全的服务器上</p><p>- 存储期限根据法律法规要求和业务需要确定&nbsp;</p><p>2. 保护措施</p><p>- 采用加密技术保护数据传输</p><p>- 实施访问控制和身份验证机制</p><p>- 定期进行安全审计和漏洞扫描</p><p><br/></p><p>五、用户权利&nbsp;</p><p>1. 访问与修改</p><p>- 您可以访问和修改您的个人资料</p><p>- 您可以查看您的阅读历史和书架内容&nbsp;</p><p>2. 注销账号</p><p>- 您可以申请注销账号，我们将在合理时间内处理&nbsp;</p><p>3. 数据导出</p><p>- 您可以请求导出您的个人数据</p><p><br/></p><p>六、第三方服务</p><p>- 我们可能使用第三方服务提供商（如支付、推送通知、数据分析）</p><p>- 这些第三方服务可能会收集您的某些信息</p><p>- 我们会要求第三方遵守相关隐私保护规定</p><p><br/></p><p>七、政策更新</p><p>- 我们可能会根据法律法规变化或业务发展更新本隐私政策</p><p>- 更新后的政策将在应用内通知您</p><p><br/></p><p>八、联系方式</p><p>- 邮箱： privacy@feiniaomanhua.com</p><p>- 电话：400-123-4567</p>', '1', '1', '2026-03-11 18:06:03', '2026-03-11 18:06:03');
INSERT INTO `__prefix__page` VALUES ('2', '用户协议', 'agreement', '<p>一、协议接受</p><p>- 您在使用飞鸟漫画服务前，应仔细阅读本协议</p><p>- 您的注册和使用行为视为接受本协议的约束</p><p><br/></p><p>二、用户资格</p><p>- 您必须年满13周岁才能使用本服务</p><p>- 未满18周岁的用户应在监护人指导下使用</p><p><br/></p><p>三、账号管理&nbsp;</p><p>1. 账号注册</p><p>- 您应提供真实、准确的注册信息</p><p>- 您应妥善保管账号和密码</p><p>- 您对账号下的所有行为负责&nbsp;</p><p>2. 账号使用</p><p>- 不得将账号转让、出租或出借他人</p><p>- 不得使用他人账号登录</p><p><br/></p><p>四、内容规范&nbsp;</p><p>1. 漫画内容</p><p>- 我们提供的漫画内容仅供个人学习和娱乐</p><p>- 部分内容可能包含成人元素，仅适合18周岁以上用户&nbsp;</p><p>2. 用户生成内容</p><p>- 您发布的评论、反馈等内容应符合法律法规</p><p>- 不得发布违法、违规、侵权或不良内容</p><p><br/></p><p>五、知识产权&nbsp;</p><p>1. 平台权利</p><p>- 飞鸟漫画拥有平台相关的知识产权</p><p>- 未经授权，不得复制、修改、传播平台内容&nbsp;</p><p>2. 用户权利</p><p>- 用户对自己发布的内容享有相应权利</p><p>- 您同意授予飞鸟漫画非独家、可转让的使用权</p><p><br/></p><p>六、用户行为规范</p><p>- 不得利用平台从事违法活动</p><p>- 不得干扰平台正常运营</p><p>- 不得侵犯他人合法权益</p><p>- 不得使用自动化工具批量操作</p><p><br/></p><p>七、付费服务</p><p>- 平台提供部分付费内容和服务</p><p>- 付费内容一经购买，不予退款（法律法规另有规定除外）</p><p>- 虚拟货币不得兑换为法定货币</p><p><br/></p><p>八、免责声明</p><p>- 平台不对用户生成内容的真实性负责</p><p>- 平台不保证服务的不间断性和无错误</p><p>- 平台不对因网络故障、系统故障等原因造成的损失负责</p><p><br/></p><p>九、协议更新</p><p>- 我们可能会更新本协议</p><p>- 更新后的协议将在应用内通知您</p><p>- 继续使用服务视为接受更新后的协议</p><p>### 十、法律适用</p><p>- 本协议适用中华人民共和国法律</p><p>- 如发生争议，应通过友好协商解决</p><p>- 协商不成的，可向有管辖权的人民法院提起诉讼</p>', '1', '2', '2026-03-11 18:08:10', '2026-03-11 18:08:10');
INSERT INTO `__prefix__page` VALUES ('3', '关于我们', 'about', '<h2>飞鸟漫画 - 您的专属漫画阅读平台</h2><p>飞鸟漫画成立于2026年，是一家专注于漫画内容分发与创作的数字阅读平台。我们致力于为用户提供高质量、多样化的漫画内容，打造沉浸式的阅读体验。</p><h3>我们的使命</h3><p>通过数字化技术，让优质漫画内容触达更多读者，为创作者提供展示才华的平台，推动漫画产业的健康发展。</p><h3>核心特色</h3><ul class=\" list-paddingleft-2\"><li><p>海量正版漫画资源，涵盖热血、冒险、恋爱、悬疑等多种题材</p></li><li><p>高清画质，流畅阅读体验，支持多种阅读模式</p></li><li><p>智能推荐系统，根据您的阅读偏好推荐个性化内容</p></li><li><p>活跃的社区互动，与其他漫迷交流分享</p></li><li><p>支持离线阅读，随时随地畅享漫画</p></li></ul><h3>我们的团队</h3><p>我们拥有一支充满激情与创意的团队，包括漫画编辑、技术开发、运营推广等多个专业领域的人才。团队成员均来自行业内知名企业，拥有丰富的经验和专业技能。</p><h3>联系方式</h3><p><strong>官方网站：</strong>www.feiniaomanhua.com</p><p><strong>客服邮箱：</strong>service@feiniaomanhua.com</p><p><strong>客服电话：</strong>400-123-4567</p><p><strong>工作时间：</strong>周一至周五 9:00-18:00</p><h3>加入我们</h3><p>如果您热爱漫画，有才华和激情，欢迎加入我们的团队，共同创造精彩的漫画世界！</p>', '1', '3', '2026-03-11 18:17:12', '2026-03-11 18:30:41');
INSERT INTO `__prefix__page` VALUES ('4', '常见问题', 'faqs', '<h2>常见问题解答</h2><p>欢迎阅读我们的常见问题解答，以下为您整理了用户在使用过程中经常遇到的问题，如有其他疑问，欢迎联系客服。</p><h3>一、账号相关</h3><h4>1. 如何注册账号？</h4><p>点击页面右上角的「注册」按钮，填写用户名、邮箱地址和密码即可完成注册。注册成功后可享受更多会员专属服务。</p><h4>2. 忘记密码怎么办？</h4><p>在登录页面点击「忘记密码」，输入您注册的邮箱地址，系统将发送重置链接到您的邮箱，点击链接即可设置新密码。</p><h4>3. 如何修改个人资料？</h4><p>登录后进入「个人中心」，点击「资料设置」，您可以修改头像、昵称、简介等信息，修改完成后点击保存即可生效。</p><h4>4. 账号被封禁怎么办？</h4><p>如果您的账号因违规操作被封禁，可以联系客服邮箱 <a href=\"mailto:service@domain.com\">service@domain.com</a> 进行申诉，我们将在 1-3 个工作日内处理。</p><h3>二、阅读相关</h3><h4>1. 如何收藏喜欢的作品？</h4><p>在作品详情页或阅读页面，点击收藏按钮（爱心图标）即可将作品添加至书架，方便下次继续阅读。</p><h4>2. 阅读记录如何查看？</h4><p>登录后进入「阅读历史」，可以查看您的阅读记录，包括最近阅读的作品、阅读进度等信息。</p><h4>3. 如何切换阅读主题？</h4><p>在阅读页面右上角，点击「主题」按钮，可选择浅色、深色或护眼模式，满足不同场景的阅读需求。</p><h4>4. 如何反馈内容问题？</h4><p>如发现内容错误、缺失章节等问题，可在作品详情页点击「反馈」按钮提交，我们会及时处理。</p><h3>三、会员与付费</h3><h4>1. 会员有哪些权益？</h4><ul class=\" list-paddingleft-2\"><li><p>去广告纯净阅读体验</p></li><li><p>优先阅读最新章节</p></li><li><p>专属会员标识</p></li><li><p>每月赠送阅读券</p></li><li><p>专属客服通道</p></li></ul><h4>2. 如何开通会员？</h4><p>进入「会员中心」，选择您需要的会员套餐，支持微信、支付宝、银行卡等多种支付方式，支付成功后立即生效。</p><h4>3. 会员到期后会怎样？</h4><p>会员到期后，您将恢复普通用户权限，已收藏的内容仍可查看，但无法享受会员专属权益。</p><h3>四、技术支持</h3><h4>1. 页面加载缓慢怎么办？</h4><p>建议您尝试以下方法：清除浏览器缓存、更换网络环境、更新至最新版本浏览器，或联系技术支持。</p><h4>2. 遇到技术问题如何反馈？</h4><p>您可以通过以下方式联系我们：</p><ul class=\" list-paddingleft-2\"><li><p>客服热线：400-123-4567（工作日 09:00-18:00）</p></li><li><p>技术支持邮箱：<a href=\"mailto:service@domain.com\">service@domain.com</a></p></li><li><p>在线客服：点击右下角「在线咨询」按钮</p></li></ul><h4>3. 如何清除缓存？</h4><p>在浏览器设置中找到「清除浏览数据」，勾选「缓存图片和文件」选项，点击清除即可。</p><h3>五、其他问题</h3><h4>1. 如何成为合作作者？</h4><p>请将您的作品简介发送至合作邮箱 <a href=\"mailto:service@domain.com\">service@domain.com</a>，我们的编辑团队会在一周内与您联系。</p><h4>2. 内容侵权投诉渠道</h4><p>如发现平台上存在侵权内容，请发送邮件至 <a href=\"mailto:service@domain.com\">service@domain.com</a>，提供相关证明材料，我们会第一时间处理。</p><h4>3. 如何关闭账号？</h4><p>进入「个人中心」→「账号设置」→「账号注销」，按提示完成注销流程。注销后，所有数据将无法恢复。</p><hr/><p style=\"text-align:center;color:#999;\">如有更多问题，欢迎联系我们的客服团队，我们竭诚为您服务！</p>', '1', '0', '2026-05-13 18:56:11', '2026-05-14 16:14:51');

DROP TABLE IF EXISTS `__prefix__verify_code`;
CREATE TABLE `__prefix__verify_code` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `target` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '目标地址（手机号或邮箱）',
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '验证码',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型：1短信，2邮件',
  `scene` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '场景',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0未使用，1已使用，2已过期',
  `expire_time` datetime NOT NULL COMMENT '过期时间',
  `use_time` datetime DEFAULT NULL COMMENT '使用时间',
  `ip` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '发送IP',
  `create_time` datetime NOT NULL COMMENT '创建时间',
  `update_time` datetime NOT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_target_type_scene_status` (`target`,`type`,`scene`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='验证码表';

DROP TABLE IF EXISTS `__prefix__seo_page`;
CREATE TABLE `__prefix__seo_page` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `page_key` varchar(64) NOT NULL DEFAULT '' COMMENT '页面标识',
  `module` varchar(32) NOT NULL DEFAULT '' COMMENT '所属模块',
  `title` text NOT NULL COMMENT '标题模板',
  `keywords` text NOT NULL COMMENT '关键词模板',
  `description` text NOT NULL COMMENT '描述模板',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_page_key` (`module`,`page_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SEO设置表';

INSERT INTO `__prefix__seo_page` VALUES ('1', 'comic_index', 'comic', '{site_name} - 精彩漫画在线阅读，收录{comic_count}部优质作品，每日更新', '{site_name}，漫画网站，热门漫画，最新漫画，在线阅读，{current_year}年漫画', '{site_name}是专业的在线漫画阅读平台，收录海量优质漫画{comic_count}部，汇聚优秀作者{comic_author_count}位，提供漫画章节{chapter_count}话以上，每日更新不停歇！', '1', '1778826399', '1778913510');
INSERT INTO `__prefix__seo_page` VALUES ('2', 'comic_list', 'comic', '{comic_category}{comic_tag}漫画列表 - 第{comic_page}页 - {site_name}', '{comic_category}漫画，{comic_tag}，{comic_sort}漫画推荐，免费在线阅读', '第{comic_page}页{comic_category}漫画列表，{site_name}为您推荐热门{comic_tag}漫画作品，包含{comic_status}漫画类型，支持多种排序方式{comic_sort}，找到您喜欢的漫画作品。', '1', '1778826399', '1778913510');
INSERT INTO `__prefix__seo_page` VALUES ('3', 'comic_detail', 'comic', '{comic_title}{comic_subtitle} - {comic_author}著 - {site_name}', '{comic_title}，{comic_author}，{comic_category}，{comic_tags}，{comic_status}，免费漫画', '{comic_title}是作者{comic_author}创作的优质{comic_category}漫画，作品评分{comic_rating}分，已连载{comic_chapter_count}话，已获{comic_favorite_count}人收藏。简介：{comic_description}。就在{site_name}免费在线阅读！', '1', '1778826399', '1778913510');
INSERT INTO `__prefix__seo_page` VALUES ('4', 'comic_read', 'comic', '{comic_title} - {chapter_title} - 第{comic_page}页 - {site_name}', '{comic_title}，{chapter_title}，{comic_author}，在线漫画，漫画阅读', '阅读漫画《{comic_title}》第{chapter_title}，作者{comic_author}，章节更新于{chapter_update_time}。{site_name}提供高清流畅的漫画阅读体验，支持多种阅读模式。', '1', '1778826399', '1778913510');
INSERT INTO `__prefix__seo_page` VALUES ('5', 'comic_search', 'comic', '搜索\"{comic_search_key}\"结果{comic_search_result_count}部 - 第{comic_page}页 - {site_name}', '{comic_search_key}，漫画搜索，{site_name}搜索结果，相关漫画推荐', '{site_name}为您找到与\"{comic_search_key}\"相关的漫画作品{comic_search_result_count}部的第{comic_page}页，包含热门连载、经典完结等多种类型漫画，欢迎在线阅读。', '1', '1778826399', '1778913510');
INSERT INTO `__prefix__seo_page` VALUES ('6', 'comic_author', 'comic', '{comic_author}作者主页 - 作品{comic_author_works}部 - {site_name}', '{comic_author}，{comic_author}的漫画，作者主页，人气作者', '{site_name}作者页面，作者{comic_author}共创作漫画作品{comic_author_works}部，其中{comic_author_works_ongoing}部连载中，{comic_author_works_completed}部已完结，收获粉丝{comic_author_fans}位，作者简介：{comic_author_description}。', '1', '1778826399', '1778913510');
INSERT INTO `__prefix__seo_page` VALUES ('7', 'single_page', 'comic', '{single_page_title} - {site_name}', '{site_name}，{single_page_title}，{current_year}年', '{single_page_content}。更多精彩内容尽在{site_name}，更新时间{single_page_publish_time}。', '1', '1778826399', '1778913510');

DROP TABLE IF EXISTS `__prefix__system_upgrade_log`;
CREATE TABLE `__prefix__system_upgrade_log` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` varchar(20) NOT NULL DEFAULT 'system' COMMENT '类型：system/module/template',
    `name` varchar(100) NOT NULL DEFAULT 'system' COMMENT '标识：system/模块名/模板名',
    `from_version` varchar(50) NOT NULL DEFAULT '' COMMENT '起始版本',
    `to_version` varchar(50) NOT NULL DEFAULT '' COMMENT '目标版本',
    `package_path` varchar(500) NOT NULL DEFAULT '' COMMENT '升级包路径',
    `sql_content` text COMMENT '待执行的SQL内容',
    `delete_files` text COMMENT '待删除的文件列表',
    `backup_path` varchar(500) NOT NULL DEFAULT '' COMMENT '备份文件路径',
    `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '状态：0=待处理 1=下载中 2=解压中 3=备份中 4=权限检查 5=升级中 6=完成 7=失败 8=回滚中',
    `error_msg` text COMMENT '错误信息',
    `execute_log` text COMMENT '执行日志（JSON格式）',
    `created_at` int(11) UNSIGNED DEFAULT 0,
    `updated_at` int(11) UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_type_name` (`type`, `name`),
    KEY `idx_status` (`status`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='升级记录表';

INSERT INTO `__prefix__system_upgrade_log` VALUES ('1', 'system', 'system', '1.0.0', '1.0.0', '', null, null, '', '6', null, null, '1778992238', '0');