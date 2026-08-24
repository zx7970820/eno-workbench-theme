# eno的小黑屋 · Workbench Theme

这是“eno 的小黑屋”个人博客的本地设计与 WordPress 主题仓库。视觉基于 Product Design 方案 2，并按反馈改成通用的系统与编程博客，而非前端专站。

## 交付内容

- `src/`：可交互的本地高保真原型。
- `wordpress-theme/eno-workbench/`：可安装的 WordPress 主题源码。
- `eno-workbench.zip`：WordPress 后台可直接上传的主题包。
- `articles/`：Vue、React、Webpack、Vite 四篇深度长文草稿。
- `design-reference.png`：选定的视觉方案。
- `implementation-v2.png`：最终桌面实现。
- `mobile-menu-v2.png`：移动端导航状态。
- `design-qa.md`：通过的视觉与交互校验记录。

## 本地原型

依赖已经安装。项目使用 Vite，可运行 `pnpm dev` 预览，并可使用 `pnpm build` 生成生产文件。

## WordPress 部署

主题包上传并启用后，会接管首页、文章列表、搜索/归档和文章详情页。正式改动线上站点前需要确认，因为启用主题和发布文章会立刻改变公开页面。
