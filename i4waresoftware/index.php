<?php get_template_part('template-parts/header'); ?>
<?php
$lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
?>
<body>
 <header>
  <div class="container">
    <nav>
      <div class="logo">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/i4ware-software.png" alt="i4ware Software Logo" />
        </a>
      </div>
      <?php if ( function_exists('tk_render_mega_menu') ) tk_render_mega_menu(); ?>
      <div class="burger" id="burger"><span></span><span></span><span></span></div>
    </nav>
  </div>
</header>
<div class="space-container"></div>
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
<div class="top-container">
  <div class="container">
    <?php if ( is_active_sidebar( 'sidebar-2-'.$lang ) ) : ?>
        <?php dynamic_sidebar( 'sidebar-2-'.$lang ); ?>
    <?php endif; ?>
    <?php if ( function_exists( 'pll_the_languages' ) ) : ?>
        <div class="language-switcher">
          <?php
            pll_the_languages( array(
              'show_flags' => 1,
              'show_names' => 1,
              'hide_if_no_translation' => 0,
              'display_names_as' => 'name',
              'dropdown' => 0,
            ) );
          ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php if ( is_front_page() ) : ?>
<?php
$lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
?>
<section class="hero">
  <div class="container hero-content">
    <h1><?php echo esc_html( get_theme_mod("hero_title_$lang", 'What do we do?') ); ?></h1>
    <p><?php echo esc_html( get_theme_mod("hero_text_$lang", 'We create code that solves your problems.') ); ?></p>
    <a href="<?php echo esc_url( get_theme_mod('hero_button_link', 'https://marketplace.atlassian.com/search?query=i4ware' ) ); ?>" class="btn" target="_blank">
      <?php echo esc_html( get_theme_mod("hero_button_text_$lang", 'Learn More') ); ?>
    </a>
    <div class="top-logo-container">
      <a href="https://marketplace.atlassian.com/" target="_blank">
        <img decoding="async" src="https://www.i4ware.fi/wp-content/uploads/partners/marketplace_partner_wht_nobg.png" class="partner-logo" alt="<?php echo ($lang === 'fi') ? 'Atlassian Marketplace -kumppani' : 'Atlassian Marketplace Partner'; ?>" />
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
  <?php get_template_part('template-parts/blog'); ?>
<?php else : ?>
  <?php if ( have_posts() ) : the_post(); ?>
    <article <?php post_class(); ?>>
      <div class="entry-header">
        <h1 class="section-title"><?php the_title(); ?></h1>
        <div class="entry-meta">
          <span class="posted-on"><?php echo get_the_date(); ?></span>
          <span class="cat-links"><?php echo ($lang === 'fi') ? '-' : '-'; ?> <?php the_category(', '); ?></span>
          <span class="comments-link">
            <?php
            if ($lang === 'fi') {
                comments_popup_link(
                    esc_html__('Ei kommentteja', 'i4waresoftware'),
                    esc_html__('1 kommentti', 'i4waresoftware'),
                    esc_html__('% kommenttia', 'i4waresoftware')
                );
            } else {
                comments_popup_link(
                    esc_html__('No comments', 'i4waresoftware'),
                    esc_html__('1 comment', 'i4waresoftware'),
                    esc_html__('% comments', 'i4waresoftware')
                );
            }
            ?>
          </span>
        </div>
      </div>
      <div class="entry-content">
        <?php the_content(); ?>
      </div>
    </article>
<?php else : ?>
    <p>
      <?php
      echo ($lang === 'fi')
        ? esc_html__('Ei sisältöä.', 'i4waresoftware')
        : esc_html__('No content.', 'i4waresoftware');
      ?>
    </p>
<?php endif; ?>
<?php endif; ?>
<?php if ( (is_single() || is_page()) && (comments_open() || get_comments_number()) ) : ?>
  <div class="container-comments">
    <?php
    if ($lang === 'fi') {
        echo '<h2>Kommentit</h2>';
    } else {
        echo '<h2>Comments</h2>';
    }

    $comments_number = get_comments_number();
    if ($lang === 'fi') {
        echo sprintf(
            esc_html(_n('%s kommentti', '%s kommenttia', $comments_number, 'i4waresoftware')),
            number_format_i18n($comments_number)
        );
    } else {
        echo sprintf(
            esc_html(_n('%s comment', '%s comments', $comments_number, 'i4waresoftware')),
            number_format_i18n($comments_number)
        );
    }
    ?>
    <?php comments_template(); ?>
<?php endif; ?>
  </div>
</section>

    <?php get_template_part('template-parts/footer'); ?>
    <?php wp_footer(); ?>


</body>
</html>