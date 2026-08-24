<form class="wp-search-form" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
  <label class="screen-reader-text" for="eno-search-input">搜索文章</label>
  <input id="eno-search-input" name="s" type="search" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="搜索文章、主题或标签" autocomplete="off">
  <button type="submit" aria-label="提交搜索"><i class="ti ti-arrow-right" aria-hidden="true"></i></button>
</form>
