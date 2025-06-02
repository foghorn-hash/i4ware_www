<?php
// This file is responsible for rendering the main content of the theme, typically used in the loop to display posts or pages.
$lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
if ( have_posts() ) :
    while ( have_posts() ) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <div class="entry-header">
                <h1 class="section-title"><?php the_title(); ?></h1>
            </div>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile;
else :
    ?>
    <p>
    <?php
      echo ($lang === 'fi')
        ? esc_html__('Ei sisältöä.', 'i4waresoftware')
        : esc_html__('No content.', 'i4waresoftware');
      ?>
    </p>
    <?php
endif;
?>