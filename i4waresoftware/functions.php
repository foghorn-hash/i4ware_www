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
    $wp_customize->add_section('hero_section', array(
        'title'    => __('Hero Section', 'i4waresoftware'),
        'priority' => 30,
    ));

    $wp_customize->add_setting('hero_title', array(
        'default'   => 'What do we do?',
        'transport' => 'refresh',
    ));

    $wp_customize->add_setting('hero_text', array(
        'default'   => 'We create code that solves your problems.',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('hero_title', array(
        'label'    => __('Hero Title', 'i4waresoftware'),
        'section'  => 'hero_section',
        'type'     => 'text',
    ));

    $wp_customize->add_control('hero_text', array(
        'label'    => __('Hero Text', 'i4waresoftware'),
        'section'  => 'hero_section',
        'type'     => 'textarea',
    ));

    $wp_customize->add_setting( 'hero_button_link', array(
        'default'           => 'https://marketplace.atlassian.com/search?query=i4ware',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ) );

    $wp_customize->add_control( 'hero_button_link', array(
        'label'    => __( 'Hero Button Link', 'i4waresoftware' ),
        'section'  => 'title_tagline', // Or create a custom section
        'type'     => 'url',
    ) );

    // Hero Button Text
    $wp_customize->add_setting( 'hero_button_text', array(
        'default'           => 'Learn More',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'hero_button_text', array(
        'label'    => __( 'Hero Button Text', 'i4waresoftware' ),
        'section'  => 'title_tagline', // Or create a custom section
        'type'     => 'text',
    ) );

    // Footer Section
    $wp_customize->add_section('footer_section', array(
        'title'    => __('Footer', 'i4waresoftware'),
        'priority' => 40,
    ));

    $wp_customize->add_setting('footer_text', array(
        'default'   => '© 2025 i4ware Software. All rights reserved.',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('footer_text', array(
        'label'    => __('Footer Text', 'i4waresoftware'),
        'section'  => 'footer_section',
        'type'     => 'textarea',
    ));
}
add_action('customize_register', 'i4ware_customize_register');

function i4ware_partnerships_shortcode() {
    $upload_dir = wp_upload_dir();
    $base_url = $upload_dir['baseurl'] . '/partners';

    $output = '<div id="partners">
        <p>We are proud of our partnerships and certifications that support our clients in the best possible way. Our partnerships provide us access to the latest technologies and resources, enabling us to offer our clients the best possible service.</p>
        <p>We are partners with the following organizations:</p>
        <div class="up-logo-container">
            <a href="https://www.yrittajat.fi/" target="_blank">
                <img src="' . esc_url($base_url . '/jasesenyritys_banneri_25_1000x500_fin_musta.png') . '" class="up-partner-logo" alt="Entrepreneurs partner logo">
            </a>    
        </div>
        <div class="logo-container">
            <a href="https://www.redhat.com/" target="_blank">
                <img src="' . esc_url($base_url . '/red_hat-technology_partner.png') . '" class="partner-logo" alt="Red Hat technology partner logo">
            </a>
            <a href="https://www.redhat.com/" target="_blank">
                <img src="' . esc_url($base_url . '/rh_readyisvlogo_rgb.png') . '" class="partner-logo" alt="Red Hat technology partner logo">
            </a>
            <a href="https://marketplace.atlassian.com/" target="_blank">
                <img src="' . esc_url($base_url . '/marketplace_partner_wht_nobg.png') . '" class="partner-logo" alt="Atlassian Marketplace partner logo">
            </a>
            <a href="https://www.yrittajat.fi/" target="_blank">
                <img src="' . esc_url($base_url . '/jasenyritys_banneri_23_625x313px_fin_musta.jpg') . '" class="partner-logo" alt="Entrepreneurs partner logo">
            </a>
            <a href="https://www.yrittajat.fi/" target="_blank">
                <img src="' . esc_url($base_url . '/jasenyritys_banneri_24_625x312px_fin_musta.png') . '" class="partner-logo" alt="Entrepreneurs partner logo">
            </a>
            <a href="https://netvisor.fi/" target="_blank">
                <img src="' . esc_url($base_url . '/netvisor-logo-vud22-horizontal-cutoutwhite-900px.png') . '" class="partner-logo" alt="Entrepreneurs partner logo">
            </a>
        </div>
        <div class="ent-logo-container">
            <a href="https://www.yrittajat.fi/" target="_blank">
                <img src="' . esc_url($base_url . '/sy_jasenyritys_2017_150x75px.png') . '" class="partner-logo" alt="Entrepreneurs partner logo">
            </a>
            <a href="https://www.yrittajat.fi/" target="_blank">
                <img src="' . esc_url($base_url . '/sy_jasenyritys2018_suomi_150x75.png') . '" class="partner-logo" alt="Entrepreneurs partner logo">
            </a>
            <a href="https://www.yrittajat.fi/" target="_blank">
                <img src="' . esc_url($base_url . '/sy_jasenyritys2019_su_150x75_0.png') . '" class="partner-logo" alt="Entrepreneurs partner logo">
            </a>
        </div>
    </div>';

    return $output;
}
add_shortcode('partnerships', 'i4ware_partnerships_shortcode');

function i4waresoftware_enqueue_comment_reply() {
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'i4waresoftware_enqueue_comment_reply' );

function i4waresoftware_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Sidebar', 'i4waresoftware' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Add widgets here.', 'i4waresoftware' ),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'i4waresoftware_widgets_init' );
?>