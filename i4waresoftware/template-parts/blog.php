<div class="main-row">
    <div class="main-content">
        <aside class="sidebar">
      <?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
        <?php dynamic_sidebar( 'sidebar-1' ); ?>
      <?php endif; ?>
    </aside>
      <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
          <article <?php post_class(); ?>>
            <div class="post-row">
              <div class="post-content">
                <div class="entry-header">
                  <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                  <div class="entry-meta">
                    <span class="posted-on"><?php echo get_the_date(); ?></span>
                    <span class="cat-links"><?php esc_html_e('-', 'i4waresoftware'); ?> <?php the_category(', '); ?></span>
                    <span class="comments-link"><?php comments_popup_link( esc_html__('Ei kommentteja', 'i4waresoftware'), esc_html__('1 kommentti', 'i4waresoftware'), esc_html__('% kommenttia', 'i4waresoftware') ); ?></span>
                  </div>
                  <?php if ( has_post_thumbnail() ) : ?>
                <div class="post-thumbnail">
                  <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('large'); ?>
                  </a>
                </div>
              <?php endif; ?>
              </div>
                <div class="entry-summary">
                  <?php the_excerpt(); ?>
                </div>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
        <div class="pagination">
          <?php the_posts_pagination(); ?>
        </div>
      <?php else : ?>
        <p><?php esc_html_e( 'Ei sisältöä.', 'i4waresoftware' ); ?></p>
      <?php endif; ?>
    </div>
  </div>