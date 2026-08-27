    </div>
    <footer class="site-footer">
      <a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span>&gt;_</span> <span class="brand-name" data-theme-brand data-dark-label="eno 的小黑屋" data-light-label="eno 的小白屋">eno 的小黑屋</span></a>
      <p>记录系统如何运行，也记录我如何理解它。</p>
      <?php wp_nav_menu(array('theme_location' => 'footer', 'container' => 'nav', 'fallback_cb' => false, 'depth' => 1)); ?>
    </footer>
  </main>
  <button class="scrim" data-menu-close aria-label="<?php esc_attr_e('关闭菜单', 'eno-workbench'); ?>"></button>
</div>
<?php wp_footer(); ?>
</body>
</html>
