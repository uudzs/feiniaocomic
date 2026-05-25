# 飞鸟漫画（Feiniao Comic）

<div align="center">
  <br>
  <img src="https://www.paheng.com/static/image/feiniao/comic/banner.jpg" alt="飞鸟漫画">
  <br>
  <h3>模块化漫画在线阅读开源系统</h3>
  <p>基于 ThinkPHP 8.1.4 开发 · 前后端分离 · 多模块架构 · 全端覆盖</p>

  <p>
    <a href="https://feiniao.paheng.net"><strong>官网首页</strong></a> ·
    <a href="https://demo.feiniao.comic.paheng.net"><strong>演示站点</strong></a> ·
    <a href="#联系方式"><strong>加入QQ群</strong></a>
  </p>
</div>

---

## 📖 系统简介

飞鸟漫画是一套基于 **ThinkPHP 8.1.4** 开发的模块化漫画在线阅读开源系统。系统采用**前后端分离**架构，后端提供完整的 RESTful API，前端覆盖 **PC 端、H5 端（手机浏览器）、App 端及后台管理面板**，真正做到一套代码，全端覆盖。

系统内置 **漫画管理**、**用户系统**、**云存储** 三大核心模块，支持模块化安装/卸载/升级，灵活扩展，未来规划 **小说**、**短视频**、**直播** 功能模块，让内容更为丰富。

| 指标 | 数据 |
|------|------|
| 开发框架 | ThinkPHP 8.1.4 |
| PHP 版本 | >= 8.1 |
| 数据库 | MySQL 5.7+ |
| 架构模式 | 多模块 + 前后端分离 |
| 授权协议 | Apache-2.0 |

---

## 🚀 核心特性

### 漫画管理
- 🏠 **作品管理** — 漫画作品增删改查，支持封面、简介、状态（连载/完结）
- 📂 **分类体系** — 无限级分类，支持热门推荐
- 📚 **分卷/章节** — 支持分卷管理，章节批量上传，图片懒加载
- 🏷️ **标签系统** — 灵活标签体系，多维度作品筛选
- ✍️ **作者管理** — 作者主页、作品聚合、粉丝关注
- ⭐ **评分系统** — 用户评分与评分统计
- 🔍 **搜索引擎** — 搜索引擎优化（SEO），TKDS 模板变量渲染
- 📥 **采集入库** — 支持第三方漫画数据批量导入

### 用户系统
- 👤 注册/登录/第三方登录/手机号登录
- 💾 收藏、关注、阅读历史
- ⚙️ 个人中心（修改密码、头像、邮箱/手机绑定）

### 云存储
- ☁️ **本地存储** — 默认支持本地文件存储
- 🌐 **阿里云 OSS** — 阿里云对象存储
- 🌐 **腾讯云 COS** — 腾讯云对象存储
- 🌐 **华为云 OBS** — 华为云对象存储
- 🌐 **AWS S3** — 亚马逊云存储
- 🖼️ 图片处理（缩略图、水印、裁剪、旋转）
- 🔒 文件安全校验（类型检查、大小限制）

### 系统架构
- 🧩 **模块化设计** — 漫画/用户/存储核心模块独立，支持安装/卸载/升级
- 🎨 **主题切换** — 内置亮色/暗色双主题
- 🌍 **多语言** — 支持中文/英文切换
- 🔄 **在线升级** — 后台一键检测并升级模块
- 📱 **媲美原生APP的性能** — 基于uniapp x开发，支持多端的同时，拥有原生APP的性能

---

## 📸 界面截图

### 🖥️ PC 端

<div align="center">
  <img src="https://www.paheng.com/static/image/feiniao/comic/pc/home.jpeg" alt="PC首页" width="80%">
  <p><em>PC 端首页</em></p>
  <br>
  <img src="https://www.paheng.com/static/image/feiniao/comic/pc/comic_home.jpeg" alt="PC漫画库" width="80%">
  <p><em>PC 端漫画库</em></p>
  <br>
  <img src="https://www.paheng.com/static/image/feiniao/comic/pc/comic_detail.jpeg" alt="PC漫画详情" width="80%">
  <p><em>PC 端漫画详情</em></p>
</div>

### 📱 H5 端

<div align="center">
  <img src="https://www.paheng.com/static/image/feiniao/comic/mobile/home.jpeg" alt="H5首页" width="45%">
  <p><em>H5 端漫画库</em></p>
  <img src="https://www.paheng.com/static/image/feiniao/comic/mobile/comic_detail.jpeg" alt="H5详情" width="45%">
  <p><em>H5 端漫画详情</em></p>
</div>

### 📲 App 端

<div align="center">
  <img src="https://www.paheng.com/static/image/feiniao/comic/app/comic_app_preview_1.jpg" alt="App首页" width="25%">
  <img src="https://www.paheng.com/static/image/feiniao/comic/app/comic_app_preview_2.jpg" alt="漫画详情" width="25%">
  <img src="https://www.paheng.com/static/image/feiniao/comic/app/comic_app_preview_3.jpg" alt="漫画筛选" width="25%">
  <img src="https://www.paheng.com/static/image/feiniao/comic/app/comic_app_preview_4.jpg" alt="我的" width="25%">
  <p><em>App 端（uniapp X）</em></p>
  <br>
  <img src="https://www.paheng.com/static/image/feiniao/comic/feiniao_cimic_v1_0_0.apk.png" alt="App下载二维码" width="200">
  <p><em>📱 Android 版扫码下载体验</em></p>
</div>

### ⚙️ 后台管理

<div align="center">
  <img src="https://www.paheng.com/static/image/feiniao/comic/backstage/home.png" alt="后台仪表盘" width="80%">
  <p><em>后台管理面板</em></p>
  <br>
  <img src="https://www.paheng.com/static/image/feiniao/comic/backstage/comic.png" alt="漫画管理" width="80%">
  <p><em>漫画管理界面</em></p>
  <br>
  <img src="https://www.paheng.com/static/image/feiniao/comic/backstage/module.png" alt="模块管理" width="80%">
  <p><em>模块管理界面</em></p>
</div>

---

## 📞 联系方式

| 渠道 | 信息 |
|------|------|
| 🏠 官网 | [https://feiniao.paheng.net](https://feiniao.paheng.net) |
| 🎮 演示站 | [https://demo.feiniao.comic.paheng.net](https://demo.feiniao.comic.paheng.net) |
| 💬 QQ 群 | **177260545**（点击[加入QQ群](https://qm.qq.com/q/wn50gvh5x6)） |

> 🎉 欢迎加入 QQ 群，获取最新版本更新、技术支持和交流讨论！

---

## 💰 赞助支持

如果这个项目对你有帮助，欢迎赞助支持我们持续开发和维护！

<div align="center">
  <table>
    <tr>
      <td align="center">
        <img src="https://www.paheng.com/static/image/zfb.png" alt="微信收款码1" width="280">
        <p><strong>微信支付1</strong></p>
      </td>
      <td align="center">
        <img src="https://www.paheng.com/static/image/wx.png" alt="微信收款码2" width="280">
        <p><strong>微信支付2</strong></p>
      </td>
    </tr>
  </table>
</div>

> 💡 赞助时请备注您的联系方式，我们会将您列入赞助名单以表感谢！

---

## ⚠️ 免责声明

1. 本系统仅供**学习交流**使用，请勿用于任何违法违规用途。
2. 使用本系统所产生的一切**内容、数据、版权纠纷及其他法律后果**，均由使用者自行承担，与系统开发者及版权方**无关**。
3. 本系统不提供任何漫画内容数据，所有漫画内容均由使用者自行上传或通过第三方接口获取。
4. 使用本系统前，请确认您所在地区的法律法规是否允许，如因违反当地法律产生的后果，开发者不承担任何责任。
5. 本系统为开源软件，使用者可以自由下载、安装、使用，但不允许将本系统源码二次转售。

---

## ©️ 版权声明

```
飞鸟漫画（Feiniao Comic） - 模块化漫画在线阅读开源系统
版权所有 © 2024-2026 FeiniaoComic 保留所有权利。

本项目基于 Apache License 2.0 开源协议发布，并免费提供使用。
更多细节请参阅 LICENSE.txt 文件。

FeiniaoComic® 为 feiniaocomic 团队所有。
```

---

## 🔗 相关源码

| 项目 | 地址 | 说明 |
|------|------|------|
| 飞鸟阅读 | [GitHub](https://github.com/uudzs/feiniao) | 后端服务 |
| 飞鸟阅读 | [gitee](https://gitee.com/paheng/feiniao) | 后端服务 |


> 更多相关项目请访问：[https://feiniao.paheng.net](https://feiniao.paheng.net)

---

## 🛠️ 环境要求

| 环境 | 版本 |
|------|------|
| PHP | >= 8.1 |
| MySQL | >= 5.7 |
| Nginx / Apache | 最新稳定版 |
| Composer | >= 2.0 |

### PHP 扩展要求

```
curl, openssl, fileinfo, gd, mbstring, pdo_mysql, redis, zip
```

---

## 📥 快速安装

### 第一步：克隆项目

```bash
git clone https://gitee.com/paheng/feiniaocomic.git
```

```bash
git clone https://github.com/uudzs/feiniaocomic.git
```

### 第二步：宝塔面板创建站点

1. 登录宝塔面板，点击 **网站 → 添加站点**
2. 填写域名，选择 **PHP 8.1+** 版本
3. 将网站根目录指向项目克隆目录

### 第三步：设置运行目录

1. 在宝塔面板站点列表中，点击刚创建的站点 **设置 → 网站目录**
2. **运行目录** 选择 `/public`，点击保存

### 第四步：配置伪静态

在站点 **设置 → 伪静态** 中，选择 `thinkphp` 规则，或填入以下配置：

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php?s=$1 last;
        break;
    }
}
```

### 第五步：开启安装

访问你的站点首页，系统会自动跳转到安装引导页面：

```
http://你的域名/
```

> 🎯 根据安装向导页面提示，填入数据库连接信息，点击安装即可完成部署。

---

## 📋 目录结构

```
project/
├── app/                    # 应用核心
│   ├── common/             # 公共服务（JWT、邮件、验证码等）
│   ├── index/              # 前端首页控制器
│   ├── manage/             # 后台管理控制器
│   └── module/             # 模块管理服务
├── config/                 # 配置文件
├── database/               # 数据库 SQL 文件
├── extend/                 # 扩展类库
├── modules/                # 业务模块
├── public/                 # Web 根目录
│   ├── static/             # 静态资源
│   └── storage/            # 上传文件
├── route/                  # 路由配置
├── template/               # 视图模板
│   ├── default/            # 前端主题
│   └── manage/             # 后台管理
└── vendor/                 # Composer 依赖
```

---

## 🤝 参与贡献

欢迎提交 Issue 和 Pull Request，一起完善飞鸟漫画系统！

---

<div align="center">
  <br>
  <p>Made with ❤️ by FeiniaoComic Team</p>
  <p>Copyright © 2026 FeiniaoComic · Apache-2.0 License</p>
</div>
