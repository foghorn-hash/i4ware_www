<?php get_template_part('template-parts/header'); ?>


<body>
 <header>
  <div class="container">
    <nav>
      <div class="logo">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/i4ware-software.png" alt="i4ware Software Logo" />
        </a>
      </div>
      <ul class="nav-links" id="navLinks">
        <?php
        wp_nav_menu(array(
          'theme_location' => 'primary',
          'menu_class'     => 'nav-links',
          'container'      => false,
          'fallback_cb'    => false
        ));
        ?>
      </ul>
      <div class="burger" id="burger"><span></span><span></span><span></span></div>
    </nav>
  </div>
</header>

<div class="overlay" id="overlay">
 <button class="close-btn" id="closeBtn">×</button>
 <?php
   wp_nav_menu(array(
     'theme_location' => 'primary',
     'menu_class'     => 'overlay-menu',
     'container'      => false,
     'fallback_cb'    => false,
     'link_before'    => '',
     'link_after'     => '',
   ));
 ?>
</div>

<?php if ( is_front_page() ) : ?>
<section class="hero">
  <div class="container hero-content">
    <h1><?php echo esc_html( get_theme_mod('hero_title', 'What do we do?') ); ?></h1>
    <p><?php echo esc_html( get_theme_mod('hero_text', 'We create code that solves your problems.') ); ?></p>
    <a href="<?php echo esc_url( get_theme_mod('hero_button_link', 'https://marketplace.atlassian.com/search?query=i4ware' ) ); ?>" class="btn" target="_blank">
      <?php echo esc_html( get_theme_mod('hero_button_text', 'Learn More') ); ?>
    </a>
    <div class="up-logo-container">
      <a href="https://marketplace.atlassian.com/" target="_blank">
        <img decoding="async" src="https://www.i4ware.fi/wp-content/uploads/partners/marketplace_partner_wht_nobg.png" class="partner-logo" alt="Atlassian Marketplace -kumppani" />
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="main">
  <div class="container">
     <?php if ( ! is_home() && ! is_archive() && ! is_single() ) : ?>
  <?php get_template_part('template-parts/content'); ?>
<?php elseif ( is_home() || is_archive() ) : ?>
  <div class="main-row">
    <div class="main-content">
      <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
          <article <?php post_class(); ?>>
            <div class="post-row">
              <?php if ( has_post_thumbnail() ) : ?>
                <div class="post-thumbnail">
                  <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('large'); ?>
                  </a>
                </div>
              <?php endif; ?>
              <div class="post-content">
                <header class="entry-header">
                  <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                  <div class="entry-meta">
                    <span class="posted-on"><?php echo get_the_date(); ?></span>
                    <span class="byline"><?php esc_html_e('-', 'i4waresoftware'); ?> <?php echo esc_html( get_the_author_meta('display_name') ); ?>  <?php echo get_avatar( get_the_author_meta('ID'), 16 ); ?></span>
                    <span class="cat-links"><?php esc_html_e('-', 'i4waresoftware'); ?> <?php the_category(', '); ?></span>
                    <span class="comments-link"><?php comments_popup_link( esc_html__('Ei kommentteja', 'i4waresoftware'), esc_html__('1 kommentti', 'i4waresoftware'), esc_html__('% kommenttia', 'i4waresoftware') ); ?></span>
                  </div>
                </header>
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
    <aside class="sidebar">
      <?php if ( is_active_sidebar( 'sidebar-1' ) ) : ?>
        <?php dynamic_sidebar( 'sidebar-1' ); ?>
      <?php endif; ?>
    </aside>
  </div>
<?php else : ?>
  <?php if ( have_posts() ) : the_post(); ?>
    <article <?php post_class(); ?>>
      <header class="entry-header">
        <h1><?php the_title(); ?></h1>
        <div class="entry-meta">
          <span class="posted-on"><?php echo get_the_date(); ?></span>
          <span class="byline"><?php esc_html_e('-', 'i4waresoftware'); ?> <?php echo esc_html( get_the_author_meta('display_name') ); ?>  <?php echo get_avatar( get_the_author_meta('ID'), 16 ); ?></span>
          <span class="cat-links"><?php esc_html_e('-', 'i4waresoftware'); ?> <?php the_category(', '); ?></span>
          <span class="comments-link"><?php comments_popup_link( esc_html__('Ei kommentteja', 'i4waresoftware'), esc_html__('1 kommentti', 'i4waresoftware'), esc_html__('% kommenttia', 'i4waresoftware') ); ?></span>
        </div>
      </header>
      <div class="entry-content">
        <?php the_content(); ?>
      </div>
    </article>
  <?php else : ?>
    <p><?php esc_html_e( 'Ei sisältöä.', 'i4waresoftware' ); ?></p>
  <?php endif; ?>
<?php endif; ?>
<?php if ( (is_single() || is_page()) && (comments_open() || get_comments_number()) ) : ?>
  <div class="container-comments">
    <h2><?php esc_html_e('Kommentit', 'i4waresoftware'); ?></h2>
    <?php
$comments_number = get_comments_number();
if ( $comments_number == 0 ) {
    echo sprintf(
        esc_html(_n('%s kommentti', '%s kommenttia', $comments_number, 'i4waresoftware')),
        number_format_i18n($comments_number)
    );
} else {

}
?>
    <?php comments_template(); ?>
  </div>
<?php endif; ?>
  </div>
</section>

    <?php get_template_part('template-parts/footer'); ?>
    <?php wp_footer(); ?>


</body>
</html>