<?php
/**
 * Plugin Name: Eno Workbench Content Importer
 * Description: 一次性、幂等导入 Eno Workbench 的 20 篇正式文章；不创建账号，不提供前台逻辑。
 * Version: 1.1.0
 * Author: eno
 */
if (!defined('ABSPATH')) { exit; }

function eno_workbench_import_articles() {
    $specs = array(
        array('title' => 'Vue Next beta 的响应式：顺着 effect、computed 和 scheduler 追一次更新', 'slug' => 'vue3-reactivity-in-depth', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('Vue','Vue 3','响应式','运行时'), 'excerpt' => '2020 年 6 月，Vue 3 还在 beta。我从一个只有 count 和 computed 的小例子开始，顺着 vue-next 的 effect、computed 和 scheduler 看一次更新到底经过哪些函数。', 'date' => '2020-06-18', 'file' => '01-vue3-reactivity-in-depth.html'),
        array('title' => 'React class 组件里的性能优化：shouldComponentUpdate 什么时候值得写', 'slug' => 'react-class-lifecycle-scu', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('React','生命周期','shouldComponentUpdate','性能优化'), 'excerpt' => '项目里的列表开始卡之后，我沿着 class 生命周期和 shouldComponentUpdate 查了一遍，最后发现比较本身也有成本。', 'date' => '2019-01-15', 'file' => 'react-class-lifecycle-scu.html'),
        array('title' => 'React 渲染的两阶段模型：Fiber 如何把工作变成可中断的任务', 'slug' => 'react-rendering-fiber-concurrency', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('React','Fiber','并发渲染','性能优化'), 'excerpt' => '从触发、Render、Commit 到浏览器绘制，沿着 Fiber 节点、workLoop 和 shouldYield 看 React 如何暂停低优先级工作。', 'date' => '2021-02-11', 'file' => '02-react-rendering-fiber-concurrency.html'),
        array('title' => 'React Hooks 从 useState 到自定义 Hook：先把闭包和规则弄明白', 'slug' => 'react-hooks-from-state-to-custom', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('React','Hooks','useState','自定义 Hook'), 'excerpt' => '从 useState 的更新队列讲到 useRef、useReducer 和自定义 Hook，顺便解释闭包快照与 Hooks 规则为什么不能随便破坏。', 'date' => '2021-10-20', 'file' => 'react-hooks-from-state-to-custom.html'),
        array('title' => 'Vue 2 响应式从 Observer 到 Watcher：一条属性是怎么通知到视图的', 'slug' => 'vue2-observer-dep-watcher', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('Vue','Vue 2','Observer','Watcher'), 'excerpt' => '沿着 Vue 2 的 Observer、Dep 和 Watcher 拆开一次属性读取与修改，理解 defineReactive 和异步更新队列。', 'date' => '2021-06-09', 'file' => 'vue2-observer-dep-watcher.html'),
        array('title' => 'Vue 2 computed 和 watch 的分界：dirty 标记如何避免重复计算', 'slug' => 'vue2-computed-watch-dirty', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('Vue','Vue 2','computed','watch'), 'excerpt' => '从 lazy watcher 和 dirty 字段开始，比较 computed 与 watch 的触发方式、缓存边界和各自适合解决的问题。', 'date' => '2022-07-18', 'file' => 'vue2-computed-watch-dirty.html'),
        array('title' => 'Vue 2 的三类 Watcher：渲染、computed 和用户 watch 分别在等什么', 'slug' => 'vue2-watcher-types', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('Vue','Vue 2','Watcher','源码'), 'excerpt' => '用源码里的 lazy、user、deep 和 render 标记区分三类 watcher，说明它们的创建时机、触发方式与实际场景。', 'date' => '2023-02-14', 'file' => 'vue2-watcher-types.html'),
        array('title' => '从 VNode 到 patch：Vue 2 虚拟 DOM 的一次更新是怎么落到真实节点的', 'slug' => 'vue2-virtual-dom-diff', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('Vue','Vue 2','虚拟 DOM','diff'), 'excerpt' => '沿着 createElm、sameVnode、patchVnode 和 updateChildren 看 Vue 2 的虚拟 DOM 更新，重点解释 key 到底参与了什么。', 'date' => '2023-11-08', 'file' => 'vue2-virtual-dom-diff.html'),
        array('title' => 'React Hooks 怎么分工：从 state、effect 到稳定引用', 'slug' => 'react-effect-memo-callback', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('React','Hooks','useEffect','性能优化'), 'excerpt' => '不把 Hook 当生命周期清单，从一个搜索页面的更新链开始，分清 state、ref、effect、memo、transition 和自定义 Hook 各自该管什么。', 'date' => '2024-02-26', 'file' => 'react-effect-memo-callback.html'),
        array('title' => 'Webpack 编译流程全景：模块图、Loader、Plugin、Chunk 与持久化缓存', 'slug' => 'webpack-compilation-pipeline', 'category' => '工具与效率', 'category_slug' => 'tooling', 'tags' => array('Webpack','构建系统','Loader','Plugin'), 'excerpt' => '沿着一次 webpack 编译，拆开入口解析、模块图构建、Loader 转换、Plugin 生命周期、Chunk 生成与持久化缓存。', 'date' => '2022-04-09', 'file' => '03-webpack-compilation-pipeline.html'),
        array('title' => 'Vite 为什么快：原生 ESM、依赖预构建、按需转换与 HMR 边界', 'slug' => 'vite-dev-server-hmr', 'category' => '工具与效率', 'category_slug' => 'tooling', 'tags' => array('Vite','ESM','HMR','构建工具'), 'excerpt' => 'Vite 的速度来自开发阶段延迟构建的取舍。本文从浏览器原生 ESM、依赖预构建、按需转换与 HMR 边界解释这套模型。', 'date' => '2023-09-15', 'file' => '04-vite-dev-server-hmr.html'),
        array('title' => 'Vite 迁移后的第一周：快了，但测试和依赖暴露了问题', 'slug' => 'vite-migration-first-week', 'category' => '工具与效率', 'category_slug' => 'tooling', 'tags' => array('Vite','迁移','工程效率'), 'excerpt' => '从 webpack 迁到 Vite 并不只是换启动命令，测试环境、别名、动态导入和依赖预构建都要重新确认。', 'date' => '2025-10-12', 'file' => 'vite-migration-first-week.html'),
        array('title' => 'React 列表卡顿的真正原因：不是把 key 换成 index', 'slug' => 'react-list-performance-review', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('React','性能','列表'), 'excerpt' => '一次长列表评审里，真正的问题是无效重渲染和过大的上下文，而不是表面上的 key 警告。', 'date' => '2026-02-07', 'file' => 'react-list-performance-review.html'),
        array('title' => '我把 React 仓库拉下来，翻了一遍它的 AI 协作文件', 'slug' => 'react-repo-agent-workflow', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('React','AI 协作','工程流程','开源协作'), 'excerpt' => '随手翻一遍 React 官方仓库里的 CLAUDE.md、SKILL.md 和 CI 配置，看看成熟项目怎样把 AI 协作放进日常开发，而不是停在口号上。', 'date' => '2026-08-18', 'file' => 'react-repo-agent-workflow.html'),
        array('title' => 'BFF 不是多套一层接口：从智能分析项目看前端服务边界', 'slug' => 'frontend-bff-smart-analysis', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('BFF','前端工程','Node.js','Egg.js','接口设计'), 'excerpt' => '一个分析详情页为什么会越来越依赖后端细节？从智能分析机器人项目里的多服务数据编排，重新认识 BFF 该做什么、又不该做什么。', 'date' => '2026-08-30', 'file' => 'frontend-bff-smart-analysis.html'),
        array('title' => '家里的 Wi‑Fi 总是差一点：我先移动路由器，再考虑换设备', 'slug' => 'home-wifi-rearrangement', 'category' => '生活随笔', 'category_slug' => 'life', 'tags' => array('生活','家庭网络','Wi‑Fi','路由器'), 'excerpt' => '家里的网络不是完全不能用，而是在最需要的时候卡一下。先把问题看清楚，再决定要不要买 Mesh，往往比换一套设备更有效。', 'date' => '2026-08-29', 'file' => 'home-wifi-rearrangement.html'),
        array('title' => '浏览器从打开网页到显示像素：一次页面加载的八个阶段', 'slug' => 'browser-navigation-rendering-pipeline', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('浏览器','渲染','性能','网络'), 'excerpt' => '从输入 URL、建立连接、解析 HTML，到样式计算、布局、绘制、分层、光栅化和合成，沿着八个阶段看页面怎样变成屏幕上的像素。', 'date' => '2026-08-24', 'file' => 'browser-navigation-rendering-pipeline.html'),
        array('title' => 'script 标签的加载顺序：async、defer、module 和资源提示', 'slug' => 'script-loading-attributes', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('浏览器','JavaScript','性能','HTML'), 'excerpt' => '把 script 的执行时机、模块脚本、CSP 和优先级属性拆开，再说明 dns-prefetch、preconnect、preload、prefetch 到底属于哪一种资源提示。', 'date' => '2026-08-22', 'file' => 'script-loading-attributes.html'),
        array('title' => '从 IIFE 到 ESM：JavaScript 模块化是怎么走到今天的', 'slug' => 'javascript-module-systems-history', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('JavaScript','模块化','ESM','Vite'), 'excerpt' => '从早期的 IIFE 和 script 顺序，到 AMD、CMD、CommonJS，再到浏览器原生 ESM，沿着每一代模块方案解决的问题看它们的差别。', 'date' => '2026-08-20', 'file' => 'javascript-module-systems-history.html'),
        array('title' => 'HTTP/1.0、1.1、2、3 到底差在哪：从一条请求看协议演进', 'slug' => 'http-version-evolution', 'category' => '前端工程', 'category_slug' => 'frontend', 'tags' => array('HTTP','网络','性能','浏览器'), 'excerpt' => '不把 HTTP/1.0、1.1、2、3 写成一张特性表，从请求格式、连接复用、多路复用、队头阻塞、头部压缩和 QUIC 看它们各自解决了什么。', 'date' => '2026-08-24', 'file' => 'http-version-evolution.html'),
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
        $post = array('post_title'=>$spec['title'],'post_name'=>$spec['slug'],'post_content'=>$content,'post_excerpt'=>$spec['excerpt'],'post_status'=>'publish','post_type'=>'post','comment_status'=>'closed','post_date'=>$local_date,'post_date_gmt'=>get_gmt_from_date($local_date));
        if ($existing) { $post['ID'] = (int) $existing->ID; }
        $id = wp_insert_post($post, true);
        if (is_wp_error($id)) { continue; }
        wp_set_post_terms($id, array((int)$term->term_id), 'category', false);
        wp_set_post_terms($id, $spec['tags'], 'post_tag', false);
        $ids[] = (int) $id;
    }
    eno_workbench_retire_articles(array(
        'rust-ownership-cache-boundary',
        'rust-async-shutdown-order',
        'deployment-rollback-last-mile',
        'node-memory-growth-debugging',
        'http-timeout-boundaries',
        'mysql-index-slow-query-replay',
        'cache-breakdown-singleflight',
        'shell-ci-failure-modes',
        'linux-file-descriptors-practical',
        'observability-three-signals',
    ));
    update_option('eno_workbench_import_last_ids', $ids, false);
    update_option('eno_workbench_import_last_at', current_time('mysql'), false);
    eno_workbench_draft_default_hello_world();
}

function eno_workbench_retire_articles($slugs) {
    foreach ($slugs as $slug) {
        $post = get_page_by_path($slug, OBJECT, 'post');
        if ($post && $post->post_status !== 'draft') {
            wp_update_post(array('ID' => (int) $post->ID, 'post_status' => 'draft'));
        }
    }
}
register_activation_hook(__FILE__, 'eno_workbench_import_articles');

function eno_workbench_draft_default_hello_world() {
    $post = get_page_by_path('hello-world', OBJECT, 'post');
    if (!$post) { return; }
    $title = trim(wp_strip_all_tags($post->post_title));
    $body = preg_replace('/\\s+/', ' ', trim(wp_strip_all_tags($post->post_content)));
    $known_titles = array('世界，您好！', '世界，您好!', 'Hello world', 'Hello world!', 'Hello World', 'Hello World!');
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
