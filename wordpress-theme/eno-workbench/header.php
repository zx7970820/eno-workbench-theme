<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site-shell">
  <aside class="left-rail" aria-label="<?php esc_attr_e('主题导航', 'eno-workbench'); ?>">
    <div class="brand-block">
      <a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span>&gt;_</span> <?php bloginfo('name'); ?></a>
      <p><?php echo esc_html(get_bloginfo('description') ?: '系统、编程与长期实践'); ?></p>
      <button class="mobile-close" data-menu-close aria-label="<?php esc_attr_e('关闭菜单', 'eno-workbench'); ?>"><i class="ti ti-x" aria-hidden="true"></i></button>
    </div>
    <div class="author-card">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/author-avatar.png'); ?>" alt="<?php esc_attr_e('作者头像', 'eno-workbench'); ?>">
      <div><strong><i></i> eno</strong><span>Software Engineer</span><small>把问题写到可以复现</small></div>
    </div>
    <nav class="topic-nav">
      <span class="rail-label">主题</span>
      <?php
      $topics = array(
        array('全部文章', 'ti-home', home_url('/')),
        array('系统设计', 'ti-server', eno_topic_url('system-design')),
        array('后端与服务', 'ti-database', eno_topic_url('backend')),
        array('Rust', 'ti-brand-rust', eno_topic_url('rust')),
        array('Shell 与 Linux', 'ti-terminal-2', eno_topic_url('linux')),
        array('前端工程', 'ti-code', eno_topic_url('frontend')),
        array('工具与效率', 'ti-tool', eno_topic_url('tooling')),
      );
      foreach ($topics as $topic) : ?>
        <a class="<?php echo is_home() && $topic[0] === '全部文章' ? 'active' : ''; ?>" href="<?php echo esc_url($topic[2]); ?>"><i class="ti <?php echo esc_attr($topic[1]); ?>" aria-hidden="true"></i><span><?php echo esc_html($topic[0]); ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="archive-list"><span class="rail-label">归档</span><ul><?php wp_get_archives(array('type' => 'yearly', 'limit' => 4, 'show_post_count' => true)); ?></ul></div>
    <div class="rail-footer"><div><a href="https://github.com/" aria-label="GitHub"><i class="ti ti-brand-github" aria-hidden="true"></i></a><a href="mailto:hello@example.com" aria-label="邮件"><i class="ti ti-mail" aria-hidden="true"></i></a><a href="<?php bloginfo('rss2_url'); ?>" aria-label="RSS"><i class="ti ti-rss" aria-hidden="true"></i></a></div><small>© <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></small></div>
  </aside>
  <main class="main-area" id="content">
    <header class="mobile-header"><button data-menu-open aria-label="<?php esc_attr_e('打开菜单', 'eno-workbench'); ?>"><i class="ti ti-menu-2" aria-hidden="true"></i></button><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a><i class="ti ti-moon" aria-hidden="true"></i></header>
