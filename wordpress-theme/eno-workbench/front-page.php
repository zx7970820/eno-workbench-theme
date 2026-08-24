<?php get_header(); ?>
<section class="search-row" aria-label="文章搜索">
  <i class="ti ti-search" aria-hidden="true"></i>
  <form class="wp-search-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="eno-search">搜索文章</label>
    <input id="eno-search" name="s" type="search" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="搜索文章、主题或标签">
    <button type="submit" aria-label="提交搜索"><i class="ti ti-arrow-right" aria-hidden="true"></i></button>
  </form>
  <kbd><i class="ti ti-command" aria-hidden="true"></i> K</kbd>
</section>

<section class="feature panel">
  <div class="feature-copy"><span class="eyebrow">精选深度</span><h1>一次请求如何穿过 Shell、服务、运行时与数据库</h1><p>不把技术栈切成孤岛：沿着一条真实请求，观察进程、协议、调度、缓存与持久化如何彼此影响。</p><div class="tags"><span>系统设计</span><span>Linux</span><span>后端</span><span>深度阅读</span></div></div>
  <img class="feature-art" src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/systems-topology.png'); ?>" alt="请求穿过多个系统层的拓扑图">
  <div class="feature-meta"><span><i class="ti ti-calendar" aria-hidden="true"></i><?php echo esc_html(wp_date('Y-m-d')); ?></span><span><i class="ti ti-clock" aria-hidden="true"></i>32 min read</span><a href="<?php echo esc_url(home_url('/?s=系统设计')); ?>">浏览专题 <i class="ti ti-arrow-right" aria-hidden="true"></i></a></div>
</section>

<section class="content-layout">
  <div class="feed-column">
    <div class="section-title"><i></i><h2>最新文章</h2><span><?php echo esc_html(wp_count_posts()->publish); ?> 篇</span></div>
    <div class="wp-post-grid">
      <?php $latest = new WP_Query(array('post_type' => 'post', 'posts_per_page' => 6, 'ignore_sticky_posts' => false)); ?>
      <?php if ($latest->have_posts()) : while ($latest->have_posts()) : $latest->the_post(); ?>
        <article <?php post_class('post-card panel'); ?>>
          <div class="post-content"><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html(get_the_excerpt()); ?></p><div class="tags"><?php $cats = get_the_category(); foreach (array_slice($cats, 0, 2) as $cat) : ?><span><?php echo esc_html($cat->name); ?></span><?php endforeach; ?></div><footer><span><?php echo esc_html(get_the_date('Y-m-d')); ?></span><span>·</span><span><?php echo esc_html(eno_reading_time()); ?> min read</span></footer></div>
          <div class="post-icon"><i class="ti <?php echo esc_attr(eno_post_icon_class()); ?>" aria-hidden="true"></i></div>
        </article>
      <?php endwhile; wp_reset_postdata(); else : ?>
        <div class="empty panel"><i class="ti ti-file-off" aria-hidden="true"></i><h3>还没有文章</h3><p>登录后台发布第一篇笔记。</p></div>
      <?php endif; ?>
    </div>
    <a class="more-link" href="<?php echo esc_url(home_url('/?post_type=post')); ?>">查看全部文章 <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
  </div>

  <aside class="right-column">
    <section class="side-panel panel"><h2><i class="ti ti-flame" aria-hidden="true"></i>热门文章</h2>
      <?php $popular = new WP_Query(array('posts_per_page' => 5, 'orderby' => 'comment_count', 'order' => 'DESC')); if ($popular->have_posts()) : while ($popular->have_posts()) : $popular->the_post(); ?>
        <a class="hot-item" href="<?php the_permalink(); ?>"><span><?php the_title(); ?></span><small><?php echo esc_html(get_comments_number()); ?> 评论</small></a>
      <?php endwhile; wp_reset_postdata(); endif; ?>
      <a class="text-link" href="<?php echo esc_url(home_url('/?post_type=post')); ?>">查看全部文章 <i class="ti ti-arrow-right" aria-hidden="true"></i></a>
    </section>
    <section class="side-panel panel research"><h2>正在研究</h2>
      <?php $research = array(array('Rust async 运行时',72,'任务调度与 Waker'),array('Linux I/O 多路复用',48,'epoll、事件循环与背压'),array('数据库索引与查询计划',24,'从 B+Tree 到代价估算')); foreach ($research as $index => $item) : ?>
        <div class="research-item"><span class="check <?php echo $index === 0 ? 'active' : ''; ?>"><?php if ($index === 0) : ?><i class="ti ti-check" aria-hidden="true"></i><?php endif; ?></span><div><strong><?php echo esc_html($item[0]); ?></strong><small><?php echo esc_html($item[2]); ?></small></div><div class="meter"><i style="width:<?php echo esc_attr($item[1]); ?>%"></i><b><?php echo esc_html($item[1]); ?>%</b></div></div>
      <?php endforeach; ?>
    </section>
    <section class="side-panel panel series"><h2>系列进度 <b>3 / 6</b></h2>
      <?php $series = array('进程、线程与协程','网络请求的生命周期','运行时调度与背压','缓存一致性','持久化与恢复','可观测性与故障定位'); foreach ($series as $index => $item) : ?><div class="series-item <?php echo $index < 3 ? 'done' : ''; ?>"><span><?php echo esc_html(str_pad($index + 1, 2, '0', STR_PAD_LEFT)); ?></span><p><?php echo esc_html($item); ?></p><?php if ($index < 2) : ?><i class="ti ti-check" aria-hidden="true"></i><?php elseif ($index === 2) : ?><i></i><?php else : ?><b></b><?php endif; ?></div><?php endforeach; ?>
    </section>
  </aside>
</section>
<?php get_footer(); ?>
