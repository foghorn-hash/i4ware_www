<?php
// This file is responsible for rendering the main content of the theme, typically used in the loop to display posts or pages.

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
    echo '<p>No content found</p>';
endif;
?>