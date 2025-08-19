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
<div class="footer-social">
    <!-- Social Media Links -->
    <a href="https://www.youtube.com/@i4wareSoftware-ot5jk" target="_blank" rel="noopener" title="YouTube" style="margin-right:10px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-youtube" viewBox="0 0 16 16">
            <path d="M8.051 1.999h-.002C3.634 2.017 2.01 2.2 1.292 2.482c-.72.282-1.28.87-1.292 1.684C0 5.36 0 8 0 8s0 2.64.001 3.834c.012.814.572 1.402 1.292 1.684.718.282 2.342.465 6.757.483h.002c4.415-.018 6.039-.201 6.757-.483.72-.282 1.28-.87 1.292-1.684C16 10.64 16 8 16 8s0-2.64-.001-3.834c-.012-.814-.572-1.402-1.292-1.684-.718-.282-2.342-.465-6.757-.483zM6.545 10.568V5.432l4.545 2.568-4.545 2.568z"/>
        </svg>
    </a>
    <!-- Add more social links here if needed -->
</div>