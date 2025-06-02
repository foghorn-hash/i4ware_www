<?php
// functions.php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Theme setup
function i4waresoftware_setup() {
    // Add support for title tag
    add_theme_support( 'title-tag' );

    // Register navigation menus
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'i4waresoftware' ),
    ) );

    // Add support for post thumbnails
    add_theme_support( 'post-thumbnails' );
}

// Hook the setup function to the after_setup_theme action
add_action( 'after_setup_theme', 'i4waresoftware_setup' );

// Enqueue styles and scripts
function i4waresoftware_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style( 'i4waresoftware-style', get_stylesheet_uri() );

    wp_enqueue_style('i4waresoftware-main', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0');

    // Enqueue main JavaScript file
    wp_enqueue_script( 'i4waresoftware-main', get_template_directory_uri() . '/assets/js/main.js', array(), null, true );
}

function i4ware_enqueue_dropdown_menu_script() {
    wp_enqueue_script('dropdown-menu', get_template_directory_uri() . '/assets/js/dropdown-menu.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'i4ware_enqueue_dropdown_menu_script');

// Hook the scripts function to the wp_enqueue_scripts action
add_action( 'wp_enqueue_scripts', 'i4waresoftware_scripts' );

function i4waresoftware_register_menus() {
    register_nav_menu('primary', __('Primary Menu', 'i4waresoftware'));
    register_nav_menu('footer', __('Footer Menu', 'i4waresoftware'));
}
add_action('after_setup_theme', 'i4waresoftware_register_menus');

function i4ware_customize_register($wp_customize) {
    // Selvitetään kielet Polylangista
    $languages = array(
        'fi' => __('Finnish', 'i4waresoftware'),
        'en' => __('English', 'i4waresoftware')
    );
    if (function_exists('pll_languages_list')) {
        $pll_langs = pll_languages_list();
        $languages = array();
        foreach ($pll_langs as $lang) {
            $languages[$lang] = strtoupper($lang);
        }
    }

    // Hero Section
    $wp_customize->add_section('hero_section', array(
        'title'    => __('Hero Section', 'i4waresoftware'),
        'priority' => 30,
    ));

    foreach ($languages as $lang_code => $lang_label) {
        // Hero Title
        $wp_customize->add_setting("hero_title_$lang_code", array(
            'default'   => ($lang_code === 'fi') ? 'Mitä teemme?' : 'What do we do?',
            'transport' => 'refresh',
        ));
        $wp_customize->add_control("hero_title_$lang_code", array(
            'label'    => __('Hero Title', 'i4waresoftware') . " ($lang_label)",
            'section'  => 'hero_section',
            'type'     => 'text',
        ));

        // Hero Text
        $wp_customize->add_setting("hero_text_$lang_code", array(
            'default'   => ($lang_code === 'fi') ? 'Luomme koodia, joka ratkaisee ongelmasi.' : 'We create code that solves your problems.',
            'transport' => 'refresh',
        ));
        $wp_customize->add_control("hero_text_$lang_code", array(
            'label'    => __('Hero Text', 'i4waresoftware') . " ($lang_label)",
            'section'  => 'hero_section',
            'type'     => 'textarea',
        ));

        // Hero Button Text
        $wp_customize->add_setting("hero_button_text_$lang_code", array(
            'default'           => ($lang_code === 'fi') ? 'Lue lisää' : 'Learn More',
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ));
        $wp_customize->add_control("hero_button_text_$lang_code", array(
            'label'    => __('Hero Button Text', 'i4waresoftware') . " ($lang_label)",
            'section'  => 'hero_section',
            'type'     => 'text',
        ));
    }

    // Hero Button Link (sama kaikille kielille)
    $wp_customize->add_setting('hero_button_link', array(
        'default'           => 'https://marketplace.atlassian.com/search?query=i4ware',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ));
    $wp_customize->add_control('hero_button_link', array(
        'label'    => __('Hero Button Link', 'i4waresoftware'),
        'section'  => 'hero_section',
        'type'     => 'url',
    ));

    // Footer Section
    $wp_customize->add_section('footer_section', array(
        'title'    => __('Footer', 'i4waresoftware'),
        'priority' => 40,
    ));

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting("footer_text_$lang_code", array(
            'default'   => ($lang_code === 'fi') ? '© 2025 i4ware Software. Kaikki oikeudet pidätetään.' : '© 2025 i4ware Software. All rights reserved.',
            'transport' => 'refresh',
        ));
        $wp_customize->add_control("footer_text_$lang_code", array(
            'label'    => __('Footer Text', 'i4waresoftware') . " ($lang_label)",
            'section'  => 'footer_section',
            'type'     => 'textarea',
        ));
    }
}
add_action('customize_register', 'i4ware_customize_register');

function i4ware_register_partner_logo_cpt() {
    $labels = array(
        'name' => 'Partner Logot',
        'singular_name' => 'Partner Logo',
        'add_new' => 'Lisää uusi',
        'add_new_item' => 'Lisää uusi logo',
        'edit_item' => 'Muokkaa logoa',
        'new_item' => 'Uusi logo',
        'view_item' => 'Näytä logo',
        'search_items' => 'Etsi logoja',
        'not_found' => 'Ei logoja',
        'menu_name' => 'Partner Logot',
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => array('title', 'thumbnail'),
    );
    register_post_type('partner_logo', $args);
}
add_action('init', 'i4ware_register_partner_logo_cpt');

function i4ware_partnerships_shortcode() {
    $groups = array(
        'top' => array('container' => 'top-logo-container', 'img_class' => 'top-partner-logo'),
        'main' => array('container' => 'main-logo-container', 'img_class' => 'partner-logo'),
        'bottom' => array('container' => 'bottom-logo-container', 'img_class' => 'partner-logo'),
    );
    $output = '<div id="partners">';
    foreach ($groups as $group => $info) {
        $args = array(
            'post_type' => 'partner_logo',
            'posts_per_page' => -1,
            'meta_key' => 'logo_group',
            'meta_value' => $group,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        );
        $logos = get_posts($args);
        $output .= '<div class="' . esc_attr($info['container']) . '">';
        foreach ($logos as $logo) {
            $url = get_field('logo_url', $logo->ID); // ACF
            $img = get_the_post_thumbnail_url($logo->ID, 'large');
            // Polylang: get current language
            if (function_exists('pll_current_language')) {
                $lang = pll_current_language();
            } else {
                $lang = 'fi'; // oletus
            }
            // Hae alt-teksti oikealla kielellä
            if ($lang === 'en') {
                $alt = get_field('logo_alt_en', $logo->ID);
            } else {
                $alt = get_field('logo_alt_fi', $logo->ID);
            }
            if (!$alt) {
                $alt = get_the_title($logo->ID);
            }
            $alt = esc_attr($alt);
            $output .= '<a href="' . esc_url($url) . '" target="_blank">';
            $output .= '<img src="' . esc_url($img) . '" class="' . esc_attr($info['img_class']) . '" alt="' . $alt . '">';
            $output .= '</a>';
        }
        $output .= '</div>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('partnerships', 'i4ware_partnerships_shortcode');

function i4waresoftware_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar 1', 'i4waresoftware' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Add widgets here.', 'i4waresoftware' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Sidebar 2', 'i4waresoftware' ),
        'id'            => 'sidebar-2',
        'description'   => __( 'Add widgets here.', 'i4waresoftware' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'i4waresoftware_widgets_init' );
?>