# eno 的小黑屋：WordPress 个人博客

这是一个可以完整留在本地的 WordPress 博客仓库：前台主题、内容导入插件、文章源文件和 Docker 开发环境都在同一个项目里。视觉是深色 editorial console，当前内容以前端 React/Vue 和构建工具为主；后端、Shell/Linux、可观测性和数据类文章暂时收起，后续再补。

## 目录

- `wordpress-theme/eno-workbench/`：可安装的 WordPress 主题，首页、文章、搜索、分类和 404 都由 WordPress 数据驱动；前台不再展示年份归档入口。
- `content-importer/`：后台可上传的一次性幂等导入插件，内含 18 篇 HTML 正文。
- `articles/`：18 篇文章的 Markdown 源文件，方便继续编辑和复用。
- `infra/docker-compose.yml`：本地 WordPress + MariaDB 环境；`infra/.env.example` 只包含示例配置。
- `eno-workbench.zip`、`eno-workbench-content-importer.zip`：可从 WordPress 后台上传的安装包。

## 本地开发与验收

1. 复制 `infra/.env.example` 为 `infra/.env`，只在本机填写开发密码；该文件已被 Git 忽略。
2. 运行 `pnpm run dev`。脚本会启动本地 WordPress、自动完成初始安装、启用 Eno Workbench 主题并激活导入插件。
3. 打开 `http://localhost:8080/`，这里才是与线上 WordPress 结构一致的本地开发版；后台地址是 `http://localhost:8080/wp-admin/`。
4. 运行 `pnpm run local:check` 做基础路由检查，再人工验收桌面端、移动端、深浅色、搜索、分类、文章详情和后台管理链路。

`pnpm run prototype:dev` 只启动 Sites 兼容的视觉原型，不是博客的本地验收环境，也不参与线上部署。

## 发布与部署

本地验收通过并完成备份后，线上部署时在 WordPress 后台上传并启用 `eno-workbench.zip`，再上传并激活 `eno-workbench-content-importer.zip`。文章内容、分类、标签和发布时间都由插件写入 WordPress，后续可继续从后台编辑。不要把 `infra/.env`、密码或密钥提交到仓库。

主题的深浅模式会优先跟随系统设置；访客手动选择后写入浏览器 `localStorage`，刷新后保持选择。支持 View Transition API 的浏览器会从点击位置做圆形扩散，不支持或开启减少动态效果时会直接切换。
