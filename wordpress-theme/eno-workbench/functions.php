<?php
if (!defined('ABSPATH')) { exit; }

function eno_workbench_setup() {
    load_theme_textdomain('eno-workbench', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
    register_nav_menus(array('primary' => __('主导航', 'eno-workbench'), 'footer' => __('页脚导航', 'eno-workbench')));
}
add_action('after_setup_theme', 'eno_workbench_setup');

function eno_workbench_disable_comments() {
    return false;
}
add_filter('comments_open', 'eno_workbench_disable_comments', 20, 2);
add_filter('pings_open', 'eno_workbench_disable_comments', 20, 2);

function eno_workbench_assets() {
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('eno-workbench', get_stylesheet_uri(), array(), $version);
    wp_enqueue_script('eno-workbench', get_template_directory_uri() . '/assets/js/theme.js', array(), $version, true);
}
add_action('wp_enqueue_scripts', 'eno_workbench_assets');

function eno_workbench_search_posts_only($query) {
    if (!is_admin() && $query->is_main_query() && $query->is_search()) {
        $query->set('post_type', array('post'));
    }
}
add_action('pre_get_posts', 'eno_workbench_search_posts_only');

function eno_topic_url($slug) {
    $category = get_category_by_slug($slug);
    return $category ? get_category_link($category) : home_url('/?s=' . rawurlencode($slug));
}

function eno_active_topic($term_id) {
    return is_category($term_id) ? 'active' : '';
}

function eno_format_date($post_id = null) {
    return esc_html(get_the_date('Y-m-d', $post_id ?: get_the_ID()));
}

function eno_post_icon_class($post_id = null) {
    $slugs = wp_get_post_categories($post_id ?: get_the_ID(), array('fields' => 'slugs'));
    if (array_intersect($slugs, array('rust'))) { return 'ti-brand-rust'; }
    if (array_intersect($slugs, array('shell', 'linux'))) { return 'ti-terminal-2'; }
    if (array_intersect($slugs, array('backend', '后端'))) { return 'ti-database'; }
    if (array_intersect($slugs, array('vue', 'react', 'frontend', '前端工程'))) { return 'ti-code'; }
    return 'ti-file-code';
}

function eno_topic_icon_class($slug) {
    $icons = array(
        'frontend' => 'ti-code',
        'tooling' => 'ti-tool',
        'backend' => 'ti-server',
        'data-cache' => 'ti-database',
        'shell-linux' => 'ti-terminal-2',
        'rust' => 'ti-brand-rust',
        'observability' => 'ti-chart-dots-3',
        'deployment' => 'ti-rocket',
    );
    return isset($icons[$slug]) ? $icons[$slug] : 'ti-folder';
}

function eno_excerpt_length() { return 48; }
add_filter('excerpt_length', 'eno_excerpt_length');

function eno_body_classes($classes) {
    $classes[] = 'eno-workbench';
    return $classes;
}
add_filter('body_class', 'eno_body_classes');
