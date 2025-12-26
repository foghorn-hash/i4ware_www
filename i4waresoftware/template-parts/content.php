<?php
// Main content renderer (posts & pages)

$lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';

if (have_posts()) :
    while (have_posts()) : the_post();

        // Hae alasivut TÄLLE sivulle
        $child_pages = get_pages([
            'child_of'    => get_the_ID(),
            'sort_column' => 'menu_order',
        ]);
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

            <header class="entry-header">
                <?php if (is_front_page()) : ?>
                    <h2 class="section-title"><?php the_title(); ?></h2>
                <?php else : ?>
                    <h1 class="section-title"><?php the_title(); ?></h1>
                <?php endif; ?>
            </header>

            <div class="entry-content">

                <?php if (!empty($child_pages)) : ?>

                    <?php the_content(); ?>

                    <!-- Alasivut ja niiden excerptit -->
                    <ul class="child-pages">
                        <?php foreach ($child_pages as $page) : ?>
                            <li>
                                <a href="<?php echo esc_url(get_permalink($page->ID)); ?>">
                                    <?php echo esc_html($page->post_title); ?>
                                </a>
                                <?php 
                                $excerpt = get_the_excerpt($page->ID);
                                if ($excerpt) : ?>
                                    <p><?php echo esc_html($excerpt); ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                <?php else : ?>

                    <!-- Normaali sisältö jos ei alasivuja -->
                    <?php the_content(); ?>

                <?php endif; ?>

            </div>

        </article>

    <?php endwhile;

else : ?>
    <p>
        <?php
        echo ($lang === 'fi')
            ? esc_html__('Ei sisältöä.', 'i4waresoftware')
            : esc_html__('No content.', 'i4waresoftware');
        ?>
    </p>
<?php endif; ?>