# Eno Workbench Content Importer

在 WordPress 后台上传并安装 `eno-workbench-content-importer.zip`，激活后会幂等创建或更新 18 篇正式文章、真实分类和对应标签，并按站点时区写入 2019—2026 的发布时间。再次激活只会按固定 slug 更新，并把已移除的 Rust、部署、后端、Shell、可观测性和数据类文章改为草稿，不会删除其他文章或创建账号。文章正文由 `articles/` Markdown 源文件生成到 `content/` HTML；运行 `pnpm run content:sync` 可重新同步正文。激活成功后后台顶部会显示 18 篇文章链接；默认 Hello World 仅在标题和正文都严格匹配时改为草稿。
