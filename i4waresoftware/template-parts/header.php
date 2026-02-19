<!DOCTYPE html>
<?php
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';

function i4ware_breadcrumbs() {
    $home_text = '#';
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
        } else {
            if ($lang === 'fi') {
                $desc = 'Senior React-, PHP- ja full-stack-kehitystä Tampereella. i4ware Software rakentaa nopeita ja turvallisia web-ratkaisuja.';
            } else {
                $desc = 'Senior React, PHP, and full-stack development in Tampere. i4ware Software builds fast and secure web solutions.';
            }
        }

        $meta_title = wp_get_document_title();
        if (is_singular()) {
            $meta_url = get_permalink();
        } elseif (is_front_page() || is_home()) {
            $meta_url = home_url('/');
        } else {
            $meta_url = home_url($_SERVER['REQUEST_URI'] ?? '/');
        }

        $og_image_rel_path = '/assets/i4ware-software-og.jpg';
        if (!file_exists(get_template_directory() . $og_image_rel_path)) {
            $og_image_rel_path = '/assets/i4ware-software.png';
        }
        $og_image_url = get_template_directory_uri() . $og_image_rel_path;
    ?>
   <meta name="description" content="<?php echo esc_attr($desc); ?>">
   <meta property="og:site_name" content="i4ware Software" />
   <meta property="og:type" content="website" />
   <meta property="og:title" content="<?php echo esc_attr($meta_title); ?>" />
   <meta property="og:description" content="<?php echo esc_attr($desc); ?>" />
   <meta property="og:url" content="<?php echo esc_url($meta_url); ?>" />
   <meta property="og:image" content="<?php echo esc_url($og_image_url); ?>" />
   <meta property="og:image:alt" content="i4ware Software Logo" />
   <meta name="twitter:card" content="summary_large_image" />
   <meta name="twitter:title" content="<?php echo esc_attr($meta_title); ?>" />
   <meta name="twitter:description" content="<?php echo esc_attr($desc); ?>" />
   <meta name="twitter:image" content="<?php echo esc_url($og_image_url); ?>" />
</head>    