<?php
get_header();
$home_page = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$featured = new WP_Query(array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'ignore_sticky_posts' => false,
));
$latest_args = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 8,
    'paged' => $home_page,
    'ignore_sticky_posts' => false,
);
if (!empty($featured->posts)) {
    $latest_args['post__not_in'] = array((int) $featured->posts[0]->ID);
}
$latest = new WP_Query($latest_args);
$home_pagination = $latest->max_num_pages > 1 ? paginate_links(array(
    'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
    'format' => '',
    'current' => $home_page,
    'total' => $latest->max_num_pages,
    'mid_size' => 1,
    'prev_text' => '上一页',
    'next_text' => '下一页',
    'type' => 'plain',
)) : '';
?>
<section class="search-row" aria-label="文章搜索"><i class="ti ti-search" aria-hidden="true"></i><?php get_search_form(); ?><kbd><i class="ti ti-command" aria-hidden="true"></i> K</kbd></section>
<?php if ($home_page === 1 && $featured->have_posts()) : $featured->the_post(); ?>
<section class="feature panel"><div class="feature-copy"><span class="eyebrow"><?php $cats = get_the_category(); echo esc_html($cats ? $cats[0]->name : '深度阅读'); ?></span><h1 data-post-transition-title data-post-url="<?php echo esc_url(get_permalink()); ?>"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1><p><?php echo esc_html(get_the_excerpt()); ?></p><div class="tags"><?php foreach (array_slice(get_the_category(), 0, 3) as $cat) : ?><a href="<?php echo esc_url(get_category_link($cat)); ?>"><span><?php echo esc_html($cat->name); ?></span></a><?php endforeach; ?></div></div><?php if (has_post_thumbnail()) : ?><a href="<?php the_permalink(); ?>" class="feature-art-link"><?php the_post_thumbnail('large', array('class' => 'feature-art')); ?></a><?php endif; ?><div class="feature-meta"><span><i class="ti ti-calendar" aria-hidden="true"></i><?php echo eno_format_date(); ?></span><a href="<?php the_permalink(); ?>">阅读全文 <i class="ti ti-arrow-right" aria-hidden="true"></i></a></div></section>
<?php wp_reset_postdata(); endif; ?>
<section class="content-layout"><div class="feed-column"><div class="section-title"><i></i><h2>最新文章</h2><span><?php echo esc_html(wp_count_posts('post')->publish); ?> 篇</span></div><div class="wp-post-grid">
<?php if ($latest->have_posts()) : while ($latest->have_posts()) : $latest->the_post(); ?><article <?php post_class('post-card panel'); ?>><div class="post-content"><div class="eyebrow"><?php $cats = get_the_category(); echo esc_html($cats ? $cats[0]->name : '未分类'); ?></div><h3 data-post-transition-title data-post-url="<?php echo esc_url(get_permalink()); ?>"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(get_the_excerpt()); ?></p><footer><span><?php echo eno_format_date(); ?></span></footer></div><div class="post-icon"><i class="ti <?php echo esc_attr(eno_post_icon_class()); ?>" aria-hidden="true"></i></div></article><?php endwhile; wp_reset_postdata(); else : ?><div class="empty panel"><i class="ti ti-file-off" aria-hidden="true"></i><h3>还没有文章</h3><p>登录后台发布第一篇笔记。</p></div><?php endif; ?>
</div><?php if ($home_pagination) : ?><nav class="navigation pagination" aria-label="文章分页"><?php echo wp_kses_post($home_pagination); ?></nav><?php endif; ?></div>
<aside class="right-column"><section class="side-panel panel"><h2>分类</h2><?php foreach (get_categories(array('hide_empty' => true)) as $cat) : ?><a class="hot-item" href="<?php echo esc_url(get_category_link($cat)); ?>"><span><?php echo esc_html($cat->name); ?></span><small><?php echo esc_html($cat->count); ?> 篇</small></a><?php endforeach; ?></section></aside></section>
<?php get_footer(); ?>
