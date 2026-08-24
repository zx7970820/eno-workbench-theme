# Eno Workbench WordPress Theme

面向个人系统与编程博客的深色工程台主题。内容架构同时容纳前端、后端、Shell、Rust、Linux、系统设计与工程实践。

## 安装

1. 将 `eno-workbench` 目录压缩为 ZIP。
2. WordPress 后台进入“外观 → 主题 → 安装主题 → 上传主题”。
3. 安装后启用，并在“设置 → 常规”中把站点副标题改为“系统、编程与长期实践”。
4. 建议创建分类：`system-design`、`backend`、`rust`、`linux`、`frontend`、`tooling`。
5. 在“外观 → 菜单”中配置页脚菜单。

## 设计与技术

- 视觉源：Product Design 方案 2，经用户反馈调整为通用编程定位。
- 字体：Noto Sans SC + IBM Plex Mono。
- 图标：Tabler Icons Webfont。
- 图片：项目内生成的作者头像和系统拓扑图。
- 兼容：WordPress 6.5+、PHP 8.1+。

## 目录

- `front-page.php`：首页工程台布局。
- `index.php`：文章列表、分类、归档与搜索结果。
- `single.php`：长文阅读页。
- `assets/js/theme.js`：移动导航和搜索快捷键。
