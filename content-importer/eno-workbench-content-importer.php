<?php
/**
 * Plugin Name: Eno Workbench Content Importer
 * Description: 一次性、幂等导入 Eno Workbench 的四篇正式文章；不创建账号，不提供前台逻辑。
 * Version: 1.0.3
 * Author: eno
 */
if (!defined('ABSPATH')) { exit; }

function eno_workbench_import_articles() {
    $specs = array(
        array('title' => '深入 Vue 3 响应式系统：依赖追踪、调度器与性能边界', 'slug' => 'vue3-reactivity-in-depth', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('Vue','响应式','运行时','性能优化'), 'excerpt' => '从 targetMap 的依赖图开始，沿着 track、trigger、scheduler、computed 与 watch 拆开一次更新，并讨论分支清理、深层代理和外部状态集成的性能边界。', 'date' => '2020-06-18', 'file' => '01-vue3-reactivity-in-depth.html'),
        array('title' => 'React 渲染的两阶段模型：Fiber、协调、提交与并发更新', 'slug' => 'react-rendering-fiber-concurrency', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('React','Fiber','并发渲染','性能优化'), 'excerpt' => '从 render 与 commit 两阶段出发，解释 Fiber 如何保存可中断的工作单元、协调如何复用节点，以及并发更新为什么必须区分优先级。', 'date' => '2021-02-11', 'file' => '02-react-rendering-fiber-concurrency.html'),
        array('title' => 'Webpack 编译流程全景：模块图、Loader、Plugin、Chunk 与持久化缓存', 'slug' => 'webpack-compilation-pipeline', 'category' => '工具与效率', 'category_slug' => 'tooling', 'tags' => array('Webpack','构建系统','Loader','Plugin'), 'excerpt' => '沿着一次 webpack 编译，拆开入口解析、模块图构建、Loader 转换、Plugin 生命周期、Chunk 生成与持久化缓存。', 'date' => '2022-04-09', 'file' => '03-webpack-compilation-pipeline.html'),
        array('title' => 'Vite 为什么快：原生 ESM、依赖预构建、按需转换与 HMR 边界', 'slug' => 'vite-dev-server-hmr', 'category' => '工具与效率', 'category_slug' => 'tooling', 'tags' => array('Vite','ESM','HMR','构建工具'), 'excerpt' => 'Vite 的速度来自开发阶段延迟构建的取舍。本文从浏览器原生 ESM、依赖预构建、按需转换与 HMR 边界解释这套模型。', 'date' => '2023-09-15', 'file' => '04-vite-dev-server-hmr.html'),
        array('title' => '一次线上 Node 服务内存上涨，我是怎么把问题缩小的', 'slug' => 'node-memory-growth-debugging', 'category' => '后端服务', 'category_slug' => 'backend', 'tags' => array('Node.js','内存','排障'), 'excerpt' => '从 RSS、heap snapshot 到一处被缓存住的闭包，记录一次没有靠重启解决的 Node 内存问题。', 'date' => '2020-06-18', 'file' => 'node-memory-growth-debugging.html'),
        array('title' => '给一个 HTTP 服务加超时：比设置一个数字麻烦得多', 'slug' => 'http-timeout-boundaries', 'category' => '后端服务', 'category_slug' => 'backend', 'tags' => array('HTTP','Node.js','可靠性'), 'excerpt' => '连接、读取、业务和下游请求的超时不是同一个概念，边界不清就会把慢请求变成雪崩。', 'date' => '2021-02-11', 'file' => 'http-timeout-boundaries.html'),
        array('title' => 'MySQL 索引不是越多越好：一次慢查询的回放', 'slug' => 'mysql-index-slow-query-replay', 'category' => '数据与缓存', 'category_slug' => 'data-cache', 'tags' => array('MySQL','索引','SQL'), 'excerpt' => '用 EXPLAIN、数据分布和写入成本回放一次索引选择，理解联合索引为什么没有生效。', 'date' => '2022-04-09', 'file' => 'mysql-index-slow-query-replay.html'),
        array('title' => '缓存击穿不是一句加锁就结束：我把保护放在哪一层', 'slug' => 'cache-breakdown-singleflight', 'category' => '数据与缓存', 'category_slug' => 'data-cache', 'tags' => array('缓存','Redis','并发'), 'excerpt' => '热点 key 失效时，大量请求同时回源。记录 singleflight、TTL 抖动和失败回退如何配合。', 'date' => '2023-01-27', 'file' => 'cache-breakdown-singleflight.html'),
        array('title' => 'Shell 脚本跑在 CI 里以后，错误处理才真正开始', 'slug' => 'shell-ci-failure-modes', 'category' => 'Shell 与 Linux', 'category_slug' => 'shell-linux', 'tags' => array('Shell','CI','自动化'), 'excerpt' => 'set -e 并不能替你定义失败语义，管道、临时文件、信号和重试都需要显式处理。', 'date' => '2023-09-15', 'file' => 'shell-ci-failure-modes.html'),
        array('title' => 'Linux 上的文件描述符：服务为什么突然打不开新连接', 'slug' => 'linux-file-descriptors-practical', 'category' => 'Shell 与 Linux', 'category_slug' => 'shell-linux', 'tags' => array('Linux','文件描述符','服务运维'), 'excerpt' => '从 ulimit、进程级限制到连接泄漏，解释 too many open files 该怎么定位和修复。', 'date' => '2024-03-06', 'file' => 'linux-file-descriptors-practical.html'),
        array('title' => 'Rust 所有权让我少写了一个锁：从缓存边界开始', 'slug' => 'rust-ownership-cache-boundary', 'category' => 'Rust', 'category_slug' => 'rust', 'tags' => array('Rust','所有权','并发'), 'excerpt' => '用所有权和借用重新画缓存 API 的边界，避免共享可变状态把并发代码变成锁的集合。', 'date' => '2024-08-22', 'file' => 'rust-ownership-cache-boundary.html'),
        array('title' => '把 Rust async 任务关干净：取消、JoinHandle 与退出顺序', 'slug' => 'rust-async-shutdown-order', 'category' => 'Rust', 'category_slug' => 'rust', 'tags' => array('Rust','Tokio','异步'), 'excerpt' => '服务收到 SIGTERM 后如何让任务停止、连接关闭、日志刷完，避免优雅退出只是口号。', 'date' => '2025-01-18', 'file' => 'rust-async-shutdown-order.html'),
        array('title' => '从日志到可行动的指标：我给一次发布补了三条线', 'slug' => 'observability-three-signals', 'category' => '可观测性', 'category_slug' => 'observability', 'tags' => array('可观测性','日志','指标'), 'excerpt' => '日志很多不等于可观测。以一次发布为例，把请求、错误和资源消耗连成能驱动行动的信号。', 'date' => '2025-05-30', 'file' => 'observability-three-signals.html'),
        array('title' => 'Vite 迁移后的第一周：快了，但测试和依赖暴露了问题', 'slug' => 'vite-migration-first-week', 'category' => '工具与效率', 'category_slug' => 'tooling', 'tags' => array('Vite','迁移','工程效率'), 'excerpt' => '从 webpack 迁到 Vite 并不只是换启动命令，测试环境、别名、动态导入和依赖预构建都要重新确认。', 'date' => '2025-10-12', 'file' => 'vite-migration-first-week.html'),
        array('title' => 'React 列表卡顿的真正原因：不是把 key 换成 index', 'slug' => 'react-list-performance-review', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('React','性能','列表'), 'excerpt' => '一次长列表评审里，真正的问题是无效重渲染和过大的上下文，而不是表面上的 key 警告。', 'date' => '2026-02-07', 'file' => 'react-list-performance-review.html'),
        array('title' => '部署脚本的最后一公里：回滚不是复制旧目录', 'slug' => 'deployment-rollback-last-mile', 'category' => '部署与工程', 'category_slug' => 'deployment', 'tags' => array('部署','回滚','发布'), 'excerpt' => '把版本、数据库变更、健康检查和回滚动作放进同一个发布协议，避免出问题时只能手工补救。', 'date' => '2026-08-01', 'file' => 'deployment-rollback-last-mile.html'),
    );
    $ids = array();
    foreach ($specs as $spec) {
        $term = get_term_by('slug', $spec['category_slug'], 'category');
        if (!$term) { $created = wp_insert_term($spec['category'], 'category', array('slug' => $spec['category_slug'])); $term = is_wp_error($created) ? null : get_term($created['term_id'], 'category'); }
        $path = plugin_dir_path(__FILE__) . 'content/' . $spec['file'];
        $content = is_readable($path) ? file_get_contents($path) : '';
        if (!$term || $content === '') { continue; }
        $existing = get_page_by_path($spec['slug'], OBJECT, 'post');
        $local_date = $spec['date'] . ' 09:00:00';
        $post = array('post_title'=>$spec['title'],'post_name'=>$spec['slug'],'post_content'=>$content,'post_excerpt'=>$spec['excerpt'],'post_status'=>'publish','post_type'=>'post','comment_status'=>'open','post_date'=>$local_date,'post_date_gmt'=>get_gmt_from_date($local_date));
        if ($existing) { $post['ID'] = (int) $existing->ID; }
        $id = wp_insert_post($post, true);
        if (is_wp_error($id)) { continue; }
        wp_set_post_terms($id, array((int)$term->term_id), 'category', false);
        wp_set_post_terms($id, $spec['tags'], 'post_tag', false);
        $ids[] = (int) $id;
    }
    update_option('eno_workbench_import_last_ids', $ids, false);
    update_option('eno_workbench_import_last_at', current_time('mysql'), false);
    eno_workbench_draft_default_hello_world();
}
register_activation_hook(__FILE__, 'eno_workbench_import_articles');

function eno_workbench_draft_default_hello_world() {
    $post = get_page_by_path('hello-world', OBJECT, 'post');
    if (!$post) { return; }
    $title = trim(wp_strip_all_tags($post->post_title));
    $body = preg_replace('/\\s+/', ' ', trim(wp_strip_all_tags($post->post_content)));
    $known_titles = array('世界，您好！', '世界，您好!', 'Hello world', 'Hello World');
    $known_bodies = array('欢迎来到 WordPress。这是您的第一篇文章。编辑或删除它，然后开始写作！', '欢迎使用 WordPress。这是您的第一篇文章。编辑或删除它，然后开始写作吧！', 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!');
    $title_matches = in_array($title, $known_titles, true);
    $body_matches = false;
    foreach ($known_bodies as $known_body) { if (strpos($body, $known_body) !== false) { $body_matches = true; break; } }
    if ($title_matches && $body_matches && $post->post_status === 'publish') { wp_update_post(array('ID' => (int) $post->ID, 'post_status' => 'draft')); }
}

function eno_workbench_import_admin_notice() {
    if (!current_user_can('manage_options')) { return; }
    $ids = get_option('eno_workbench_import_last_ids', array());
    if (!$ids) { return; }
    echo '<div class="notice notice-success is-dismissible"><p><strong>Eno Workbench：</strong>已幂等导入/更新 ' . esc_html(count($ids)) . ' 篇文章。</p><ul>'; 
    foreach ($ids as $id) { echo '<li><a href="' . esc_url(get_permalink($id)) . '" target="_blank" rel="noopener">' . esc_html(get_the_title($id)) . '</a></li>'; }
    echo '</ul></div>';
}
add_action('admin_notices', 'eno_workbench_import_admin_notice');
