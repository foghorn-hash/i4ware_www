<?php
$lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
?>
<div class="main-row">
    <div class="main-content">
        <aside class="sidebar">
            <?php if ( is_active_sidebar( 'sidebar-1-' . $lang ) ) : ?>
                <?php dynamic_sidebar( 'sidebar-1-' . $lang ); ?>
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
                                    <span class="cat-links">
                                        <?php echo ($lang === 'fi') ? '-' : '-'; ?> <?php the_category(', '); ?>
                                    </span>
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
            <p>
                <?php
                echo ($lang === 'fi')
                    ? esc_html__('Ei sisältöä.', 'i4waresoftware')
                    : esc_html__('No content.', 'i4waresoftware');
                ?>
            </p>
        <?php endif; ?>
    </div>
</div>
<div class="footer-social">
    <?php
    echo ($lang === 'fi')
        ? esc_html__('YouTube', 'i4waresoftware')
        : esc_html__('YouTube', 'i4waresoftware');
    ?>
    <!-- Social Media Links -->
    <a href="https://www.youtube.com/@i4wareSoftware-ot5jk" target="_blank" rel="noopener" title="YouTube" style="margin-right:10px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-youtube" viewBox="0 0 16 16">
            <path d="M8.051 1.999h-.002C3.634 2.017 2.01 2.2 1.292 2.482c-.72.282-1.28.87-1.292 1.684C0 5.36 0 8 0 8s0 2.64.001 3.834c.012.814.572 1.402 1.292 1.684.718.282 2.342.465 6.757.483h.002c4.415-.018 6.039-.201 6.757-.483.72-.282 1.28-.87 1.292-1.684C16 10.64 16 8 16 8s0-2.64-.001-3.834c-.012-.814-.572-1.402-1.292-1.684-.718-.282-2.342-.465-6.757-.483zM6.545 10.568V5.432l4.545 2.568-4.545 2.568z"/>
        </svg>
    </a>
    <!-- Add more social links here if needed -->
</div>