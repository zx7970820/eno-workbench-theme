<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
<div class="reading-progress" aria-hidden="true"><span></span></div>
<article <?php post_class('entry-shell panel'); ?>>
  <header class="entry-header">
    <?php $cats = get_the_category(); ?>
    <div class="entry-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">全部文章</a><span aria-hidden="true">/</span><?php if ($cats) : ?><a href="<?php echo esc_url(get_category_link($cats[0])); ?>"><?php echo esc_html($cats[0]->name); ?></a><?php else : ?><span>深度阅读</span><?php endif; ?></div>
    <h1 data-post-transition-title data-post-url="<?php echo esc_url(get_permalink()); ?>" data-post-id="<?php echo esc_attr(get_the_ID()); ?>"><?php the_title(); ?></h1>
    <div class="entry-meta"><span>发布于 <?php echo esc_html(get_the_date('Y-m-d')); ?></span><?php if (get_the_modified_date('Y-m-d') !== get_the_date('Y-m-d')) : ?><span>更新于 <?php echo esc_html(get_the_modified_date('Y-m-d')); ?></span><?php endif; ?></div>
    <?php $tags = get_the_tags(); if ($tags) : ?><div class="entry-tags" aria-label="文章标签"><?php foreach ($tags as $tag) : ?><a href="<?php echo esc_url(get_tag_link($tag)); ?>">#<?php echo esc_html($tag->name); ?></a><?php endforeach; ?></div><?php endif; ?>
  </header>
  <div class="entry-content"><?php the_content(); wp_link_pages(); ?></div>
</article>
<?php $previous = get_previous_post(); $next = get_next_post(); ?>
<?php if ($previous || $next) : ?>
<nav class="entry-nav" aria-label="文章导航">
  <?php if ($previous) : ?><a class="entry-nav-card entry-nav-card--previous" href="<?php echo esc_url(get_permalink($previous)); ?>"><small>上一篇</small><strong data-post-transition-title data-post-url="<?php echo esc_url(get_permalink($previous)); ?>" data-post-id="<?php echo esc_attr($previous->ID); ?>"><?php echo esc_html(get_the_title($previous)); ?></strong><span>←</span></a><?php endif; ?>
  <?php if ($next) : ?><a class="entry-nav-card entry-nav-card--next" href="<?php echo esc_url(get_permalink($next)); ?>"><small>下一篇</small><strong data-post-transition-title data-post-url="<?php echo esc_url(get_permalink($next)); ?>" data-post-id="<?php echo esc_attr($next->ID); ?>"><?php echo esc_html(get_the_title($next)); ?></strong><span>→</span></a><?php endif; ?>
</nav>
<?php endif; ?>
<?php endwhile; ?>
<?php get_footer(); ?>
