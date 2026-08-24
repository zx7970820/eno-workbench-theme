<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script>(function(){try{var k='eno-theme',v=localStorage.getItem(k),d=window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches?'light':'dark';document.documentElement.dataset.theme=v==='light'||v==='dark'?v:d;}catch(e){document.documentElement.dataset.theme='dark';}})();</script>
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
    <nav class="topic-nav">
      <span class="rail-label">主题</span>
      <?php
      $topics = get_categories(array('hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC'));
      ?><a class="<?php echo is_home() && !is_paged() ? 'active' : ''; ?>" href="<?php echo esc_url(home_url('/')); ?>"><i class="ti ti-home" aria-hidden="true"></i><span>全部文章</span></a>
      <?php foreach ($topics as $topic) : ?>
        <a class="<?php echo esc_attr(eno_active_topic($topic->term_id)); ?>" href="<?php echo esc_url(get_category_link($topic->term_id)); ?>"><i class="ti ti-code" aria-hidden="true"></i><span><?php echo esc_html($topic->name); ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="archive-list"><span class="rail-label">归档</span><ul><?php echo eno_archive_years(); ?></ul></div>
    <div class="rail-footer"><div><a href="<?php echo esc_url(get_bloginfo('rss2_url')); ?>" aria-label="RSS"><i class="ti ti-rss" aria-hidden="true"></i></a><button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="切换到浅色模式" title="切换到浅色模式"><i class="ti ti-sun" aria-hidden="true"></i><span class="screen-reader-text">切换到浅色模式</span></button></div><small>© <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></small></div>
  </aside>
  <main class="main-area" id="content">
    <header class="mobile-header"><button data-menu-open aria-label="<?php esc_attr_e('打开菜单', 'eno-workbench'); ?>"><i class="ti ti-menu-2" aria-hidden="true"></i></button><a href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a><button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="切换到浅色模式" title="切换到浅色模式"><i class="ti ti-sun" aria-hidden="true"></i><span class="screen-reader-text">切换到浅色模式</span></button></header>
