# eno 的小黑屋：WordPress 个人博客

这是一个可以完整留在本地的 WordPress 博客仓库：前台主题、内容导入插件、文章源文件和 Docker 开发环境都在同一个项目里。视觉是深色 editorial console，内容范围覆盖前端、后端、Shell/Linux、Rust、部署和工程实践。

## 目录

- `wordpress-theme/eno-workbench/`：可安装的 WordPress 主题，首页、文章、搜索、分类、日期归档和 404 都由 WordPress 数据驱动。
- `content-importer/`：后台可上传的一次性幂等导入插件，内含 16 篇 HTML 正文。
- `articles/`：16 篇文章的 Markdown 源文件，方便继续编辑和复用。
- `infra/docker-compose.yml`：本地 WordPress + MariaDB 环境；`infra/.env.example` 只包含示例配置。
- `eno-workbench.zip`、`eno-workbench-content-importer.zip`：可从 WordPress 后台上传的安装包。

## 本地启动

1. 复制 `infra/.env.example` 为 `infra/.env`，只在本机填写密码。
2. 在 `infra/` 目录运行 `docker compose up -d`。
3. 打开 `http://localhost:8080/` 完成 WordPress 初始安装。
4. 在后台启用 Eno Workbench 主题，再激活 Eno Workbench Content Importer 插件。插件会按固定 slug 幂等导入 16 篇文章，重复激活不会创建副本。

## 发布与部署

线上部署时，在 WordPress 后台上传并启用 `eno-workbench.zip`，再上传并激活 `eno-workbench-content-importer.zip`。文章内容、分类、标签和发布时间都由插件写入 WordPress，后续可继续从后台编辑。不要把 `infra/.env`、密码或密钥提交到仓库。

主题的深浅模式会优先跟随系统设置；访客手动选择后写入浏览器 `localStorage`，刷新后保持选择。支持 View Transition API 的浏览器会从点击位置做圆形扩散，不支持或开启减少动态效果时会直接切换。
