<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
<article <?php post_class('entry-shell panel'); ?>>
  <header class="entry-header"><span class="eyebrow"><?php $cats = get_the_category(); echo esc_html($cats ? $cats[0]->name : '深度阅读'); ?></span><h1><?php the_title(); ?></h1><div class="entry-meta"><span><?php echo esc_html(get_the_date('Y-m-d')); ?></span><span><?php echo esc_html(eno_reading_time()); ?> min read</span><span class="comment-count"><?php comments_number('0 条评论', '1 条评论', '% 条评论'); ?></span></div></header>
  <div class="entry-content"><?php the_content(); wp_link_pages(); ?></div>
</article>
<?php if (comments_open() || get_comments_number()) { comments_template(); } endwhile; ?>
<?php get_footer(); ?>
