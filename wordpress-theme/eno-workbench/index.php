<?php get_header(); ?>
<section class="search-row" aria-label="文章搜索"><i class="ti ti-search" aria-hidden="true"></i><?php get_search_form(); ?></section>
<section class="content-layout">
  <div class="feed-column"><div class="section-title"><i></i><h1><?php
    if (is_search()) {
      echo '搜索：' . esc_html(get_search_query());
    } elseif (is_category()) {
      echo esc_html(single_cat_title('', false));
    } elseif (is_year()) {
      echo esc_html(get_query_var('year')) . ' 年归档';
    } elseif (is_tag()) {
      echo '标签：' . esc_html(single_tag_title('', false));
    } elseif (is_archive()) {
      echo esc_html(wp_strip_all_tags(get_the_archive_title()));
    } else {
      echo '全部文章';
    }
  ?></h1></div><div class="wp-post-grid">
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?><article <?php post_class('post-card panel'); ?>><div class="post-content"><h3 data-post-transition-title data-post-url="<?php echo esc_url(get_permalink()); ?>"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(get_the_excerpt()); ?></p><div class="tags"><?php foreach (array_slice(get_the_category(), 0, 2) as $cat) : ?><span><?php echo esc_html($cat->name); ?></span><?php endforeach; ?></div><footer><span><?php echo esc_html(get_the_date('Y-m-d')); ?></span></footer></div><div class="post-icon"><i class="ti <?php echo esc_attr(eno_post_icon_class()); ?>" aria-hidden="true"></i></div></article><?php endwhile; else : ?><div class="empty panel"><h2>没有找到文章</h2><p>试试其他关键词。</p></div><?php endif; ?>
  </div><?php the_posts_pagination(array('mid_size' => 1, 'prev_text' => '上一页', 'next_text' => '下一页', 'screen_reader_text' => '文章分页')); ?></div>
</section>
<?php get_footer(); ?>
