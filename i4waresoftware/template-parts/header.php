<!DOCTYPE html>
<?php
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';

function i4ware_breadcrumbs() {
    $home_text = 'Home';
    $delimiter = ' &raquo; '; // voit vaihtaa vaikka '›' tai '>'
    $before = '<span class="current">';
    $after = '</span>';

    echo '<nav class="breadcrumbs">';

    // Home-linkki
    echo '<a href="' . home_url() . '">' . $home_text . '</a>';

    if (is_category() || is_single()) {
        $category = get_the_category();
        if ($category) {
            $cat = $category[0];
            $parents = get_category_parents($cat, true, $delimiter);
            // Poistetaan mahdolliset ylimääräiset whitespace
            $parents = trim($parents);
            echo $delimiter . $parents;
        }
        if (is_single()) {
            echo $delimiter . $before . get_the_title() . $after;
        }
    } elseif (is_page()) {
        global $post;
        if ($post->post_parent) {
            $parent_id  = $post->post_parent;
            $breadcrumbs = array();
            while ($parent_id) {
                $page = get_post($parent_id);
                $breadcrumbs[] = '<a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a>';
                $parent_id = $page->post_parent;
            }
            $breadcrumbs = array_reverse($breadcrumbs);
            echo $delimiter . implode($delimiter, $breadcrumbs);
        }
        echo $delimiter . $before . get_the_title() . $after;
    } elseif (is_tag()) {
        echo $delimiter . $before . 'Tag: ' . single_tag_title('', false) . $after;
    } elseif (is_author()) {
        echo $delimiter . $before . 'Author: ' . get_the_author() . $after;
    } elseif (is_search()) {
        echo $delimiter . $before . 'Search results for "' . get_search_query() . '"' . $after;
    } elseif (is_404()) {
        echo $delimiter . $before . '404 Not Found' . $after;
    }

    echo '</nav>';
}

?>
<html lang="<?php echo esc_attr($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri(); ?>/assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_template_directory_uri(); ?>/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_template_directory_uri(); ?>/assets/favicon-16x16.png">
    <link rel="manifest" href="<?php echo get_template_directory_uri(); ?>/assets/site.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo get_template_directory_uri(); ?>/assets/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo get_template_directory_uri(); ?>/assets/android-chrome-512x512.png">
    <link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/assets/favicon.ico">
    <!-- Start cookieyes banner -->
    <script id="cookieyes" type="text/javascript" src="https://cdn-cookieyes.com/client_data/a1eed8ea8ae24b05c992cf15/script.js"></script>
    <!-- End cookieyes banner -->
     <link href="https://vjs.zencdn.net/8.23.3/video-js.css" rel="stylesheet" />
     <meta property="og:image" content="<?php echo get_template_directory_uri(); ?>/assets/i4ware-software-og.png" />
     <meta property="og:image:width" content="890" />
     <meta property="og:image:height" content="890" />
     <?php
        if (is_front_page()) {
            if ($lang === 'fi') {
                $desc = 'Senior React-, PHP- ja full-stack-kehitystä Tampereella. i4ware Software rakentaa nopeita ja turvallisia web-ratkaisuja.';
            } else {
                $desc = 'Senior React, PHP, and full-stack development in Tampere. i4ware Software builds fast and secure web solutions.';
            }
        } elseif (is_home()) {
            if ($lang === 'fi') {
                $desc = 'Senior React-, PHP- ja full-stack-kehitystä Tampereella. i4ware Software rakentaa nopeita ja turvallisia web-ratkaisuja.';
            } else {
                $desc = 'Senior React, PHP, and full-stack development in Tampere. i4ware Software builds fast and secure web solutions.';
            }
        } elseif (is_singular()) {
            $desc = get_post_meta(get_the_ID(), '_meta_description', true);
            if (!$desc) {
                $desc = wp_strip_all_tags(get_the_excerpt());
            }
        }
    ?>
   <meta name="description" content="<?php echo esc_attr($desc); ?>">
</head>    