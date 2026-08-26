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
<a class="skip-link" href="#content">跳到正文</a>
<div class="site-shell">
  <aside class="left-rail" aria-label="<?php esc_attr_e('主题导航', 'eno-workbench'); ?>">
    <div class="brand-block">
      <a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span>&gt;_</span> <span class="brand-name" data-theme-brand data-dark-label="eno 的小黑屋" data-light-label="eno 的小白屋">eno 的小黑屋</span></a>
      <p><?php echo esc_html(get_bloginfo('description') ?: '系统、编程与长期实践'); ?></p>
      <button class="mobile-close" data-menu-close aria-label="<?php esc_attr_e('关闭菜单', 'eno-workbench'); ?>"><i class="ti ti-x" aria-hidden="true"></i></button>
    </div>
    <nav class="topic-nav" id="site-navigation">
      <span class="rail-label">主题</span>
      <?php
      $topics = get_categories(array('hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC'));
      ?><a class="<?php echo is_home() && !is_paged() ? 'active' : ''; ?>" <?php echo is_home() && !is_paged() ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url(home_url('/')); ?>"><i class="ti ti-home" aria-hidden="true"></i><span>全部文章</span></a>
      <?php foreach ($topics as $topic) : ?>
        <a class="<?php echo esc_attr(eno_active_topic($topic->term_id)); ?>" <?php echo is_category($topic->term_id) ? 'aria-current="page"' : ''; ?> href="<?php echo esc_url(get_category_link($topic->term_id)); ?>"><i class="ti <?php echo esc_attr(eno_topic_icon_class($topic->slug)); ?>" aria-hidden="true"></i><span><?php echo esc_html($topic->name); ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="rail-footer"><div><a href="<?php echo esc_url(get_bloginfo('rss2_url')); ?>" aria-label="RSS"><i class="ti ti-rss" aria-hidden="true"></i></a><button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="切换到浅色模式" title="切换到浅色模式"><i class="ti ti-sun" aria-hidden="true"></i><span class="screen-reader-text">切换到浅色模式</span></button></div><small>© <?php echo esc_html(wp_date('Y')); ?> <span class="brand-name" data-theme-brand data-dark-label="eno 的小黑屋" data-light-label="eno 的小白屋">eno 的小黑屋</span></small></div>
  </aside>
  <main class="main-area" id="content">
    <header class="mobile-header"><button data-menu-open aria-expanded="false" aria-controls="site-navigation" aria-label="<?php esc_attr_e('打开菜单', 'eno-workbench'); ?>"><i class="ti ti-menu-2" aria-hidden="true"></i></button><a href="<?php echo esc_url(home_url('/')); ?>"><span class="brand-name" data-theme-brand data-dark-label="eno 的小黑屋" data-light-label="eno 的小白屋">eno 的小黑屋</span></a><button class="theme-toggle" type="button" data-theme-toggle aria-pressed="false" aria-label="切换到浅色模式" title="切换到浅色模式"><i class="ti ti-sun" aria-hidden="true"></i><span class="screen-reader-text">切换到浅色模式</span></button></header>
