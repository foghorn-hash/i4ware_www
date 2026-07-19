<?php
// functions.php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once(get_template_directory() . '/google-ai-shortcode.php');
require_once(get_template_directory() . '/jira-timesheet-shortcode.php');
require_once(get_template_directory() . '/wordpress-kehitys-shortcode.php');
require_once(get_template_directory() . '/web-hotellipalvelu-shortcode.php');

// Theme setup
function i4waresoftware_setup()
{
    // Add support for title tag
    add_theme_support('title-tag');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'i4waresoftware'),
    ));

    // Add support for post thumbnails
    add_theme_support('post-thumbnails');
}

// Hook the setup function to the after_setup_theme action
add_action('after_setup_theme', 'i4waresoftware_setup');

// Enqueue styles and scripts
function i4waresoftware_scripts()
{
    // Enqueue main stylesheet
    wp_enqueue_style('i4waresoftware-style', get_stylesheet_uri());

    wp_enqueue_style('i4waresoftware-main', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0');

    // Enqueue main JavaScript file
    wp_enqueue_script('i4waresoftware-main', get_template_directory_uri() . '/assets/js/main.js', array(), null, true);
}

function i4ware_enqueue_dropdown_menu_script()
{
    wp_enqueue_script('dropdown-menu', get_template_directory_uri() . '/assets/js/dropdown-menu.js', array('jquery'), null, true);
}
add_action('wp_enqueue_scripts', 'i4ware_enqueue_dropdown_menu_script');

// Hook the scripts function to the wp_enqueue_scripts action
add_action('wp_enqueue_scripts', 'i4waresoftware_scripts');

function i4waresoftware_register_menus()
{
    register_nav_menu('primary', __('Primary Menu', 'i4waresoftware'));
    register_nav_menu('footer', __('Footer Menu', 'i4waresoftware'));
}
add_action('after_setup_theme', 'i4waresoftware_register_menus');

function i4ware_customize_register($wp_customize)
{
    // Add customizer settings for the hero section and footer
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
        'title' => __('Hero Section', 'i4waresoftware'),
        'priority' => 30,
    ));

    foreach ($languages as $lang_code => $lang_label) {
        // Hero Title
        $wp_customize->add_setting("hero_title_$lang_code", array(
            'default' => ($lang_code === 'fi') ? 'Mitä teemme?' : 'What do we do?',
            'transport' => 'refresh',
        ));
        $wp_customize->add_control("hero_title_$lang_code", array(
            'label' => __('Hero Title', 'i4waresoftware') . " ($lang_label)",
            'section' => 'hero_section',
            'type' => 'text',
        ));

        // Hero Text
        $wp_customize->add_setting("hero_text_$lang_code", array(
            'default' => ($lang_code === 'fi') ? 'Luomme koodia, joka ratkaisee ongelmasi.' : 'We create code that solves your problems.',
            'transport' => 'refresh',
        ));
        $wp_customize->add_control("hero_text_$lang_code", array(
            'label' => __('Hero Text', 'i4waresoftware') . " ($lang_label)",
            'section' => 'hero_section',
            'type' => 'textarea',
        ));

        // Hero Button Text
        $wp_customize->add_setting("hero_button_text_$lang_code", array(
            'default' => ($lang_code === 'fi') ? 'Lue lisää' : 'Learn More',
            'sanitize_callback' => 'sanitize_text_field',
            'transport' => 'refresh',
        ));
        $wp_customize->add_control("hero_button_text_$lang_code", array(
            'label' => __('Hero Button Text', 'i4waresoftware') . " ($lang_label)",
            'section' => 'hero_section',
            'type' => 'text',
        ));
    }

    // Hero Button Link (sama kaikille kielille)
    $wp_customize->add_setting('hero_button_link', array(
        'default' => 'https://marketplace.atlassian.com/search?query=i4ware',
        'sanitize_callback' => 'esc_url_raw',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('hero_button_link', array(
        'label' => __('Hero Button Link', 'i4waresoftware'),
        'section' => 'hero_section',
        'type' => 'url',
    ));

    // Footer Section
    $wp_customize->add_section('footer_section', array(
        'title' => __('Footer', 'i4waresoftware'),
        'priority' => 40,
    ));

    // Add Social Media text setting and control
    $wp_customize->add_setting("footer_social_text_$lang_code", array(
        'default' => ($lang_code === 'fi') ? 'Seuraa meitä YouTubessa' : 'Follow us on YouTube',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control("footer_social_text_$lang_code", array(
        'label' => __('Footer Social Media Text', 'i4waresoftware') . " ($lang_label)",
        'section' => 'footer_section',
        'type' => 'text',
    ));

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting("footer_text_$lang_code", array(
            'default' => ($lang_code === 'fi') ? '© 2025 i4ware Software. Kaikki oikeudet pidätetään.' : '© 2025 i4ware Software. All rights reserved.',
            'transport' => 'refresh',
        ));
        $wp_customize->add_control("footer_text_$lang_code", array(
            'label' => __('Footer Text', 'i4waresoftware') . " ($lang_label)",
            'section' => 'footer_section',
            'type' => 'textarea',
        ));
    }

    $wp_customize->add_section('i4ware_cta_section', [
        'title' => __('CTA Painike', 'i4ware'),
        'priority' => 30,
    ]);

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting("i4ware_cta_url_$lang_code", [
            'default' => '#',
            'transport' => 'refresh',
            'sanitize_callback' => 'esc_url_raw',
        ]);
        $wp_customize->add_control("i4ware_cta_url_control_$lang_code", [
            'label' => __('CTA Painikkeen URL', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_cta_section',
            'settings' => "i4ware_cta_url_$lang_code",
            'type' => 'url',
        ]);
    }

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting("i4ware_cta_saas_url_$lang_code", [
            'default' => '#',
            'transport' => 'refresh',
            'sanitize_callback' => 'esc_url_raw',
        ]);
        $wp_customize->add_control("i4ware_cta_saas_url_control_$lang_code", [
            'label' => __('SaaS CTA Painikkeen URL', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_cta_section',
            'settings' => "i4ware_cta_saas_url_$lang_code",
            'type' => 'url',
        ]);
    }

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting("i4ware_cta_text_$lang_code", [
            'default' => ($lang_code === 'fi') ? __('Pyydä tarjous', 'i4ware') : __('Request a quote', 'i4ware'),
            'transport' => 'refresh',
        ]);
        $wp_customize->add_control("i4ware_cta_text_control_$lang_code", [
            'label' => __('CTA Button Text', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_cta_section',
            'settings' => "i4ware_cta_text_$lang_code",
            'type' => 'text',
        ]);
    }

    // CTA: headline & description per language
    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting("i4ware_cta_headline_$lang_code", [
            'default' => ($lang_code === 'fi') ? 'SaaS-tuoteideasi tuotantoon i4ware SDK:lla kustannustehokkaasti' : 'Get your SaaS product to market cost-effectively with i4ware SDK',
            'transport' => 'refresh',
        ]);
        $wp_customize->add_control("i4ware_cta_headline_control_$lang_code", [
            'label' => __('CTA Headline', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_cta_section',
            'settings' => "i4ware_cta_headline_$lang_code",
            'type' => 'text',
        ]);

        $wp_customize->add_setting("i4ware_cta_desc_$lang_code", [
            'default' => ($lang_code === 'fi') ? 'Rakennamme MVP- ja SaaS-ratkaisut puolestasi. Low-code i4ware SDK ja AI-avusteinen kehitys mahdollistavat nopean ja kustannustehokkaan toteutuksen.' : 'We build MVP and SaaS solutions for you. Low-code i4ware SDK and AI-assisted development enable fast and cost-effective delivery.',
            'transport' => 'refresh',
        ]);
        $wp_customize->add_control("i4ware_cta_desc_control_$lang_code", [
            'label' => __('CTA Description', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_cta_section',
            'settings' => "i4ware_cta_desc_$lang_code",
            'type' => 'textarea',
        ]);
    }

    // Video settings
    $wp_customize->add_section('i4ware_video_section', [
        'title' => __('Embedded Video', 'i4ware'),
        'priority' => 35,
    ]);

    $wp_customize->add_setting('i4ware_video_url', [
        'default' => '',
        'transport' => 'refresh',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('i4ware_video_url_control', [
        'label' => __('YouTube URL (watch or share link or embed URL)', 'i4ware'),
        'section' => 'i4ware_video_section',
        'settings' => 'i4ware_video_url',
        'type' => 'url',
    ]);

    $wp_customize->add_setting('i4ware_video_blur', [
        'default' => false,
        'transport' => 'refresh',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('i4ware_video_blur_control', [
        'label' => __('Enable blur overlay on video', 'i4ware'),
        'section' => 'i4ware_video_section',
        'settings' => 'i4ware_video_blur',
        'type' => 'checkbox',
    ]);

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting("i4ware_video_overlay_text_$lang_code", [
            'default' => '',
            'transport' => 'refresh',
        ]);
        $wp_customize->add_control("i4ware_video_overlay_text_control_$lang_code", [
            'label' => __('Video overlay text', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_video_section',
            'settings' => "i4ware_video_overlay_text_$lang_code",
            'type' => 'text',
        ]);
    }
}
add_action('customize_register', 'i4ware_customize_register');

function i4ware_register_partner_logo_cpt()
{
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

function i4ware_partnerships_shortcode()
{
    $groups = array(
        'top' => array('container' => 'top-logo-container', 'img_class' => 'top-partner-logo'),
        'main' => array('container' => 'main-logo-container', 'img_class' => 'partner-logo'),
        'bottom' => array('container' => 'bottom-logo-container', 'img_class' => 'partner-logo'),
    );
    $output = '<div id="partners">';
    foreach ($groups as $group => $info) {
        if ($group === 'main') {
            $args = array(
                'post_type' => 'partner_logo',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'logo_group',
                        'value' => 'main',
                        'compare' => '='
                    ),
                    array(
                        'key' => 'logo_group',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => 'logo_group',
                        'value' => '',
                        'compare' => '='
                    )
                )
            );
        } else {
            $args = array(
                'post_type' => 'partner_logo',
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => 'logo_group',
                        'value' => $group,
                        'compare' => '='
                    )
                )
            );
        }
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

function i4ware_register_customer_logo_cpt()
{
    $labels = array(
        'name' => 'Asiakaslogot',
        'singular_name' => 'Asiakaslogo',
        'add_new' => 'Lisää uusi',
        'add_new_item' => 'Lisää uusi asiakaslogo',
        'edit_item' => 'Muokkaa asiakaslogoa',
        'new_item' => 'Uusi asiakaslogo',
        'view_item' => 'Näytä asiakaslogo',
        'search_items' => 'Etsi asiakaslogoja',
        'not_found' => 'Ei asiakaslogoja',
        'menu_name' => 'Asiakaslogot',
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => array('title', 'thumbnail'),
    );
    register_post_type('customer_logo', $args);
}
add_action('init', 'i4ware_register_customer_logo_cpt');

function i4ware_customers_shortcode()
{
    $output = '<div id="customers">';
    $args = array(
        'post_type' => 'customer_logo',
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC'
    );
    $customers = get_posts($args);
    foreach ($customers as $customer) {
        $url = get_field('customer_url', $customer->ID); // ACF
        $img = get_the_post_thumbnail_url($customer->ID, 'large');
        // Polylang: get current language
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language();
        } else {
            $lang = 'fi';
        }
        // Hae kuvausteksti oikealla kielellä
        if ($lang === 'en') {
            $use_case = get_field('use_case_en', $customer->ID);
        } else {
            $use_case = get_field('use_case_fi', $customer->ID);
        }
        if (!$use_case) {
            $use_case = '';
        }
        $alt = get_the_title($customer->ID);
        $output .= '<div class="customer-logo-block">';
        if ($url) {
            $output .= '<a href="' . esc_url($url) . '" target="_blank">';
        }
        $output .= '<img src="' . esc_url($img) . '" class="customer-logo" alt="' . esc_attr($alt) . '" align="left">';
        if ($url) {
            $output .= '</a>';
        }

        if ($use_case) {
            $output .= nl2br(esc_html($use_case));
        }
        $output .= '</div>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('customers', 'i4ware_customers_shortcode');

function i4waresoftware_widgets_init()
{
    // Register sidebars
    $languages = array(
        'fi' => __('Finnish', 'i4waresoftware'),
        'en' => __('English', 'i4waresoftware')
    );
    // If Polylang is active, get the list of languages
    if (function_exists('pll_languages_list')) {
        $pll_langs = pll_languages_list();
        $languages = array();
        foreach ($pll_langs as $lang) {
            $languages[$lang] = strtoupper($lang);
        }
    }

    foreach ($languages as $lang_code => $lang_label) {
        register_sidebar(array(
            'name' => sprintf(__('Sidebar 1 (%s)', 'i4waresoftware'), $lang_label),
            'id' => 'sidebar-1-' . $lang_code,
            'description' => sprintf(__('Add widgets here for %s.', 'i4waresoftware'), $lang_label),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>',
        ));
    }

    foreach ($languages as $lang_code => $lang_label) {
        register_sidebar(array(
            'name' => sprintf(__('Sidebar 2 (%s)', 'i4waresoftware'), $lang_label),
            'id' => 'sidebar-2-' . $lang_code,
            'description' => sprintf(__('Add widgets here for %s.', 'i4waresoftware'), $lang_label),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>',
        ));
    }

    register_sidebar(array(
        'name' => __('Sidebar 1', 'i4waresoftware'),
        'id' => 'sidebar-1',
        'description' => __('Add widgets here.', 'i4waresoftware'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));
    register_sidebar(array(
        'name' => __('Sidebar 2', 'i4waresoftware'),
        'id' => 'sidebar-2',
        'description' => __('Add widgets here.', 'i4waresoftware'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ));
}
add_action('widgets_init', 'i4waresoftware_widgets_init');

// === [ Shortcode: i4ware_pricing ] — Polylang-ready Redesign =========================
if (!function_exists('i4w_t')) {
    function i4w_t($text, $context = 'i4ware_pricing')
    {
        if (function_exists('pll__'))
            return pll__($text);
        return __($text, 'i4ware'); // fallback
    }
}

add_action('init', function () {
    if (!function_exists('pll_register_string'))
        return;

    $strings = [
        'Technical Partnership',
        'Tekninen kumppanuus',
        'Enterprise Digital Architecture & Development',
        'Yritystason ohjelmistoarkkitehtuuri & kehitys',
        'We design, build, and maintain custom high-performance software for B2B.',
        'Suunnittelemme, rakennamme ja ylläpidämme räätälöityjä korkean suorituskyvyn ohjelmistoratkaisuja.',
        'Ready to align your technology with your business objectives?',
        'Haluatko linjata teknologiasi liiketoiminnan tavoitteiden kanssa?',
        'Discuss your project or request a technical consultation.',
        'Keskustele asiantuntijoidemme kanssa tai pyydä arkkitehtuurikatselmus.',
        'Start Your Digital Transformation',
        'Aloita digitaalinen uudistus',
        'Request Architecture Review',
        'Tilaa arkkitehtuurikatselmus'
    ];

    foreach ($strings as $s) {
        pll_register_string('i4ware_pricing', $s, 'i4ware_pricing');
    }
});

if (!function_exists('i4ware_pricing_shortcode')) {
    function i4ware_pricing_shortcode($atts)
    {
        $atts = is_array($atts) ? $atts : [];
        $atts = array_change_key_case($atts, CASE_LOWER);

        $a = shortcode_atts([
            'contact_page_id' => '',
            'contact_url' => '/ota-yhteytta/',
            'default_tab' => 'journey'
        ], $atts, 'i4ware_pricing');

        // Get the target contact URL
        $url = '';
        if (!empty($a['contact_page_id'])) {
            $page_id = intval($a['contact_page_id']);
            if (function_exists('pll_get_post')) {
                $tr_id = pll_get_post($page_id);
                if ($tr_id)
                    $page_id = $tr_id;
            }
            $url = get_permalink($page_id);
        }
        if (!$url)
            $url = esc_url($a['contact_url']);

        // Detect language
        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        if ($lang !== 'fi' && $lang !== 'en') {
            $lang = 'en'; // fallback
        }

        // High-end multi-concept dictionary
        $data = [
            'fi' => [
                'tabs' => [
                    'journey' => 'Asiakaspolku',
                    'services' => 'Palvelut',
                    'solutions' => 'Ratkaisut',
                    'industries' => 'Toimialat',
                    'consulting' => 'Konsultointi'
                ],
                'journey' => [
                    [
                        'badge' => 'Vaihe 01',
                        'title' => 'Kartoitus & Auditointi',
                        'subtitle' => 'Nykytilan ja tarpeiden selvitys',
                        'price' => 'Aloitusvaihe',
                        'desc' => 'Analysoimme olemassa olevan koodin, työnkulut ja määrittelemme tekniset pullonkaulat.',
                        'features' => ['Työnkulkujen kartoitus', 'Suorituskyvyn auditointi', 'Tietoturvariskien arviointi', 'Esitutkimusdokumentti'],
                        'btn' => 'Varaa kartoitus-<br />konsultointi'
                    ],
                    [
                        'badge' => 'Vaihe 02',
                        'title' => 'Arkkitehtuuri-<br />suunnittelu',
                        'subtitle' => 'Tekninen blueprint ennen koodausta',
                        'price' => 'Suunnitteluvaihe',
                        'desc' => 'Suunnittelemme tietokantarakenteet, tietovirrat, rajapintakytkennät ja teknologiastackin.',
                        'features' => ['Tietokantakaavion blueprint', 'API-rajapintojen sekvenssikaaviot', 'Teknologiavalinnat', 'Järjestelmäarkkitehtuurin dokumentointi'],
                        'btn' => 'Tilaa arkkiteh-<br />tuurikatselmus',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Vaihe 03',
                        'title' => 'Ketterä kehitys',
                        'subtitle' => 'Laadukas ja testattu toteutus',
                        'price' => 'Toteutusvaihe',
                        'desc' => 'Kehitämme ohjelmiston sprinteissä noudattaen testivetoista kehitystä ja puhdasta koodia.',
                        'features' => ['Test-Driven Development (TDD)', 'Jatkuva integraatio (CI/CD)', 'Sprinttidemot ja palaute', 'Puhdas, ylläpidettävä koodi'],
                        'btn' => 'Aloita tuotekehitys'
                    ],
                    [
                        'badge' => 'Vaihe 04',
                        'title' => 'Jatkuva ylläpito',
                        'subtitle' => 'SLA-tuki ja elinkaaren hallinta',
                        'price' => 'Ylläpitovaihe',
                        'desc' => 'Huolehdimme palvelimen toimivuudesta, tietoturvakorjauksista ja jatkuvasta jatkokehityksestä.',
                        'features' => ['Ylläpito SLA-sopimus', 'Tietoturvapäivitykset', 'Proaktiivinen monitorointi', 'Jatkuva pienkehitys'],
                        'btn' => 'Tutustu ylläpitoon'
                    ]
                ],
                'services' => [
                    [
                        'badge' => 'Kehitys',
                        'title' => 'Räätälöidyt<br />verkkosovellukset',
                        'subtitle' => 'Laravel, React & Node.js',
                        'price' => 'Projektikohtainen tarjous',
                        'desc' => 'Tietoturvalliset, korkean suorituskyvyn taustajärjestelmät ja modernit käyttöliittymät.',
                        'features' => ['Puhdas koodiarkkitehtuuri', 'Automaattinen testaus', 'Skaalautuva tietokantasuunnittelu', 'Käyttäjäystävälliset liittymät'],
                        'btn' => 'Keskustele projektistasi'
                    ],
                    [
                        'badge' => 'Integraatiot',
                        'title' => 'Järjestelmä-<br />integraatiot',
                        'subtitle' => 'CRM, ERP & API-sillat',
                        'price' => 'Projektikohtainen tarjous',
                        'desc' => 'Saumattomat tiedonsiirrot ja automaatiot liiketoimintakriittisten järjestelmien välillä.',
                        'features' => ['API-rajapintojen kartoitus', 'Turvallinen OAuth-tunnistus', 'Reaaliaikainen synkronointi', 'Datan eheyden varmistus'],
                        'btn' => 'Tilaa integraatio-<br />arviointi',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Atlassian',
                        'title' => 'Atlassian Marketplace-<br />sovellukset',
                        'subtitle' => 'Atlassian Forge & Data Center',
                        'price' => 'Projektikohtainen tarjous',
                        'desc' => 'Räätälöidyt lisäosat ja työnkulkujen laajennukset Jiraan ja Confluenceen.',
                        'features' => ['Atlassian Forge Cloud', 'Jira-työnkulkujen automaatiot', 'Data Center -yhteensopivuus', 'Marketplace-julkaisuapu'],
                        'btn' => 'Pyydä Atlassian-<br />konsultointia'
                    ],
                    [
                        'badge' => 'Alustat',
                        'title' => 'Verkkosivustot<br />sovellusalustana',
                        'subtitle' => 'Headless WordPress & Laravel',
                        'price' => 'Projektikohtainen tarjous',
                        'desc' => 'WordPressin hyödyntäminen tietoturvallisena ja nopeana sovellusalustana.',
                        'features' => ['Erilliset frontend-ratkaisut', 'Muokatut API-integraatiot', 'Huipputason suorituskyky', 'Ylläpito- ja tietoturvasopimukset'],
                        'btn' => 'Aloita digitaalinen uudistus'
                    ]
                ],
                'solutions' => [
                    [
                        'badge' => 'Tekoäly',
                        'title' => 'AI-ohjelmisto-<br>ratkaisut',
                        'subtitle' => 'Automaatio & LLM-integraatiot',
                        'price' => 'Projektikohtainen tarjous',
                        'desc' => 'Älykkäät botit, datan automaattinen käsittely ja räätälöidyt LLM-integraatiot työnkulkuihin.',
                        'features' => ['Räätälöidyt LLM-integraatiot', 'Automaatio- ja agenttibotit', 'Tietoturvallinen paikallinen AI', 'Semanttiset hakukoneet'],
                        'btn' => 'Kartoita tekoäly-<br />potentiaali'
                    ],
                    [
                        'badge' => 'Bisnes',
                        'title' => 'Liiketoiminta-<br>sovellukset',
                        'subtitle' => 'ERP & CRM -portaalit',
                        'price' => 'Projektikohtainen tarjous',
                        'desc' => 'Asiakasportaalit, sisäiset hallintajärjestelmät ja työnkulkujen automatisointisovellukset.',
                        'features' => ['Tietoturvalliset asiakasportaalit', 'Dokumenttien automaattinen luonti', 'Interaktiiviset raportointinäkymät', 'Roolipohjainen pääsynhallinta'],
                        'btn' => 'Suunnittele liiketoiminta-<br />sovellus',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Atlassian',
                        'title' => 'Atlassian-<br>integraatiot',
                        'subtitle' => 'Jira & Confluence -automaatio',
                        'price' => 'Projektikohtainen tarjous',
                        'desc' => 'Työnkulkujen automatisoinnit, räätälöidyt lisäosat ja integraatiosillat muihin ohjelmistoihin.',
                        'features' => ['Atlassian API-sillat', 'Interaktiiviset Confluence-makrot', 'Datan siirtoskriptit', 'Lisäosien ylläpito'],
                        'btn' => 'Modernisoi Jira-työnkulut'
                    ],
                    [
                        'badge' => 'SaaS',
                        'title' => 'Digitaaliset alustat',
                        'subtitle' => 'Skaalautuva SaaS-arkkitehtuuri',
                        'price' => 'Projektikohtainen tarjous',
                        'desc' => 'Monikäyttäjäalustat ja verkkopalvelut, jotka skaalautuvat miljoonille pyynnöille.',
                        'features' => ['Monivuokralaisuus (Multi-tenant)', 'Stripe-maksupalvelun integrointi', 'Tehokkaat API-rajapinnat', 'Pilvi-infrastruktuurin optimointi'],
                        'btn' => 'Rakenna SaaS-alustasi'
                    ]
                ],
                'industries' => [
                    [
                        'badge' => 'Terveys',
                        'title' => 'Terveyden&shy;huolto & Bio',
                        'subtitle' => 'Tietoturva & Regulaatio',
                        'price' => 'Enterprise-ratkaisu',
                        'desc' => 'GDPR-yhteensopiva datankäsittely, suojatut potilasrekisterit ja laboratorion tietojärjestelmät.',
                        'features' => ['GDPR & HIPAA -valmius', 'Salatut tietokantayhteydet', 'Muuttumattomat lokitiedot (Audit Log)', 'Integraatiot laboratoriolaitteisiin'],
                        'btn' => 'Pyydä tietoturva-<br />katselmus'
                    ],
                    [
                        'badge' => 'Teollisuus',
                        'title' => 'Valmistava<br>teollisuus & IoT',
                        'subtitle' => 'Logistiikka & Työnkulut',
                        'price' => 'Enterprise-ratkaisu',
                        'desc' => 'Tuotantolinjojen seurantasovellukset, toimitusketjun automaatiot ja IoT-datan visualisointi.',
                        'features' => ['IoT-laitteiden rajapinnat', 'Tuotannon seurantanäkymät', 'Toimitusketjun automaatio', 'Resurssienhallinnan integraatiot'],
                        'btn' => 'Automatisoi teollisuusprosessit',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Asiantuntijat',
                        'title' => 'Asiantuntija-<br>palvelut & B2B',
                        'subtitle' => 'Tehokkuus & Laskutus',
                        'price' => 'Enterprise-ratkaisu',
                        'desc' => 'Automaattiset tarjousten generoinnit, CRM-integraatiot ja asiantuntijoiden tuntikirjausjärjestelmät.',
                        'features' => ['Laskutusintegraatiot', 'Tarjousprosessin automaatio', 'HubSpot/Salesforce-synkronointi', 'Työaikakirjausten hallinta'],
                        'btn' => 'Tehosta asiantuntijatyötä'
                    ],
                    [
                        'badge' => 'Julkinen',
                        'title' => 'Julkis-<br>hallinto & Järjestöt',
                        'subtitle' => 'Saavutettavuus & Turvallisuus',
                        'price' => 'Enterprise-ratkaisu',
                        'desc' => 'Saavutettavat verkkosovellukset (WCAG 2.1), suojatut tietokannat ja avoimen datan rajapinnat.',
                        'features' => ['WCAG 2.1 AA -saavutettavuus', 'Turvallinen valtionhallinnon pilvi', 'Avoimen datan API-julkaisut', 'Laajat tietoturvatestaukset'],
                        'btn' => 'Toteuta saavutettava sovellus'
                    ]
                ],
                'consulting' => [
                    [
                        'badge' => 'Vaihe A',
                        'title' => 'Kartoitus-<br>työpaja',
                        'subtitle' => 'Vaatimus-<br>määrittely',
                        'price' => 'Alkaen 1 950 €',
                        'desc' => 'Määrittelemme järjestelmän vaatimukset, teemme rautalankamallit ja luomme tarkan projektisuunnitelman.',
                        'features' => ['Tarvekartoitus ja työpaja', 'Interaktiiviset rautalankamallit', 'Budjetti- ja aikatauluraamit', 'Yksityiskohtainen määrittelydokumentti'],
                        'btn' => 'Tilaa kartoitus-<br />työpaja'
                    ],
                    [
                        'badge' => 'Vaihe B',
                        'title' => 'Arkkitehtuurin<br>suunnittelu',
                        'subtitle' => 'Tekniset määrittelyt',
                        'price' => 'Alkaen 2 500 €',
                        'desc' => 'Luomme tietokantakaaviot, luokkakaaviot, rajapintamallinnukset ja arvioimme tietoturvariskit.',
                        'features' => ['Tietokannan ER-kaavio', 'API-rajapintojen UML-sekvenssit', 'Tietoturvan riskiarvio', 'Teknologiavalintojen suositusraportti'],
                        'btn' => 'Tilaa arkkitehtuuri-<br />suunnittelu',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Vaihe C',
                        'title' => 'Proof of Concept (PoC)',
                        'subtitle' => 'Tekninen koetoteutus',
                        'price' => 'Alkaen 3 950 €',
                        'desc' => 'Rakennetaan pieni toimiva prototyyppi, joka varmistaa haastavimpien teknisten ominaisuuksien toimivuuden.',
                        'features' => ['Kriittisten toiminnallisuujen testaus', 'Hiekkalaatikkointegraatiot', 'Teknisen toteutettavuuden todiste', 'Toteutusriskien minimointi'],
                        'btn' => 'Käynnistä PoC-projekti'
                    ],
                    [
                        'badge' => 'Vaihe D',
                        'title' => 'MVP-kehitys',
                        'subtitle' => 'Kevytversio (MVP)',
                        'price' => 'Kustomoitu tarjous',
                        'desc' => 'Ensimmäinen toimiva versio ohjelmistosta todellisille käyttäjille palautteen keräämistä varten.',
                        'features' => ['Karsittu ydinominaisuusluettelo', 'Nopea julkaisu tuotantoon', 'Palautteenkeruujärjestelmä', 'Valmis pohja jatkokehitykselle'],
                        'btn' => 'Rakenna MVP-tuote'
                    ]
                ]
            ],
            'en' => [
                'tabs' => [
                    'journey' => 'Journey',
                    'services' => 'Services',
                    'solutions' => 'Solutions',
                    'industries' => 'Industries',
                    'consulting' => 'Consulting First'
                ],
                'journey' => [
                    [
                        'badge' => 'Phase 01',
                        'title' => 'Discovery & Audit',
                        'subtitle' => 'Legacy Analysis & Scoping',
                        'price' => 'Initiation Phase',
                        'desc' => 'We analyze existing codebases, inspect processes, and uncover architecture bottlenecks.',
                        'features' => ['Workflow Mapping', 'Performance Audits', 'Security Risk Assessment', 'Technical Feasibility Study'],
                        'btn' => 'Book a Discovery Call'
                    ],
                    [
                        'badge' => 'Phase 02',
                        'title' => 'Architecture Design',
                        'subtitle' => 'Technical Blueprinting',
                        'price' => 'Design Phase',
                        'desc' => 'We design database schemas, data flows, API specifications, and stack choices.',
                        'features' => ['Database Schema Blueprints', 'API Sequence Mapping', 'Stack Selection Reports', 'System Specifications Docs'],
                        'btn' => 'Request Architecture Review',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Phase 03',
                        'title' => 'Agile Engineering',
                        'subtitle' => 'Quality Test-Driven Coding',
                        'price' => 'Development Phase',
                        'desc' => 'We write robust code in short sprints, implementing TDD and clean architecture patterns.',
                        'features' => ['Test-Driven Development', 'Continuous CI/CD Pipelines', 'Sprint Review Demos', 'Clean, Documented Code'],
                        'btn' => 'Start Agile Development'
                    ],
                    [
                        'badge' => 'Phase 04',
                        'title' => 'Lifecycle Maintenance',
                        'subtitle' => 'SLA Support & Performance',
                        'price' => 'Maintenance Phase',
                        'desc' => 'We manage hosting, server configurations, critical security updates, and performance tuning.',
                        'features' => ['Support SLA Agreements', 'Security Patch Management', 'Active Uptime Monitoring', 'Continuous Feature Evolution'],
                        'btn' => 'View Maintenance Specs'
                    ]
                ],
                'services' => [
                    [
                        'badge' => 'Development',
                        'title' => 'Custom Web Applications',
                        'subtitle' => 'Laravel, React & Node.js',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Secure, high-performance backends paired with modern, responsive frontends.',
                        'features' => ['Clean Code Architecture', 'Automated Testing', 'Scalable Database Design', 'Bespoke User Interfaces'],
                        'btn' => 'Discuss Your Project'
                    ],
                    [
                        'badge' => 'Integrations',
                        'title' => 'System Integrations',
                        'subtitle' => 'CRM, ERP & API Bridges',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Seamless data syncing and automation between business-critical legacy databases.',
                        'features' => ['API Mapping & Scoping', 'Secure OAuth Authentication', 'Real-time Syncing', 'Data Integrity Audits'],
                        'btn' => 'Request Integration Audit',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Atlassian',
                        'title' => 'Atlassian Marketplace Apps',
                        'subtitle' => 'Atlassian Forge & Data Center',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Bespoke add-ons and workflow extensions for Jira and Confluence.',
                        'features' => ['Atlassian Forge Cloud', 'Jira Workflow Automation', 'Data Center Compatibility', 'Marketplace Publishing Support'],
                        'btn' => 'Request Atlassian Consultation'
                    ],
                    [
                        'badge' => 'Platforms',
                        'title' => 'Websites as App Platforms',
                        'subtitle' => 'Headless WordPress & Laravel',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Utilizing WordPress as a highly secure, fast and modern headless database system.',
                        'features' => ['Decoupled Frontend Architectures', 'Custom API Integrations', 'High-Performance Tuning', 'Enterprise Maintenance SLAs'],
                        'btn' => 'Start Your Transformation'
                    ]
                ],
                'solutions' => [
                    [
                        'badge' => 'Artificial Intelligence',
                        'title' => 'AI-Powered Business Solutions',
                        'subtitle' => 'Automation & LLM Integration',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Intelligent workflow automations, custom LLM integrations, and document agents.',
                        'features' => ['Custom LLM Integrations', 'Workflow Automation Bots', 'Secure Local AI Deployments', 'Semantic Search Systems'],
                        'btn' => 'Explore AI Opportunities'
                    ],
                    [
                        'badge' => 'Enterprise',
                        'title' => 'Custom Business Applications',
                        'subtitle' => 'ERP & CRM Client Portals',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Secure customer portals, dashboard visualizations, and custom back-office applications.',
                        'features' => ['Secure Client Hubs', 'Automated Document Generation', 'Interactive Dashboards', 'Role-Based Access Controls'],
                        'btn' => 'Discuss Your Business App',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Atlassian Solutions',
                        'title' => 'Atlassian Enterprise Solutions',
                        'subtitle' => 'Jira & Confluence Customization',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Automation of workflow behaviors, plugins, and custom integrations to external legacy software.',
                        'features' => ['Atlassian API Integrations', 'Interactive Confluence Addons', 'Large Database Migrations', 'Enterprise Plugin Maintenance'],
                        'btn' => 'Customize Jira Systems'
                    ],
                    [
                        'badge' => 'SaaS',
                        'title' => 'Scalable Digital Platforms',
                        'subtitle' => 'Multi-tenant SaaS Architecture',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Cloud database systems designed to handle thousands of requests seamlessly.',
                        'features' => ['Multi-tenant Architectures', 'Stripe Billing Integrations', 'High-Throughput Web APIs', 'Elastic Cloud Configs'],
                        'btn' => 'Build Your SaaS Platform'
                    ]
                ],
                'industries' => [
                    [
                        'badge' => 'Healthcare',
                        'title' => 'Healthcare & Biotech',
                        'subtitle' => 'GDPR Compliancy & Security',
                        'price' => 'Enterprise Solution',
                        'desc' => 'High-security GDPR patient portals, clinical registry databases, and secure diagnostic software.',
                        'features' => ['GDPR & HIPAA Compliance', 'Encrypted Databases', 'Immutable Audit Logging', 'Secure Laboratory Syncing'],
                        'btn' => 'Discuss GDPR Compliance'
                    ],
                    [
                        'badge' => 'Manufacturing',
                        'title' => 'Manufacturing & Logistics',
                        'subtitle' => 'IoT Data & Workflows',
                        'price' => 'Enterprise Solution',
                        'desc' => 'Production floor tracking dashboards, shipping automation, and real-time IoT visualization.',
                        'features' => ['IoT Device Integration', 'Live Production Dashboards', 'Supply Chain Automation', 'Asset Tracking Interfaces'],
                        'btn' => 'Automate Workflows',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Professional',
                        'title' => 'Professional Services',
                        'subtitle' => 'Billing & CRM Syncing',
                        'price' => 'Enterprise Solution',
                        'desc' => 'Bespoke document creators, proposal automators, and CRM synchronization triggers.',
                        'features' => ['Billing Integrations', 'Proposal Generation Tools', 'HubSpot/Salesforce Syncs', 'Internal Activity Portals'],
                        'btn' => 'Optimize B2B Workflows'
                    ],
                    [
                        'badge' => 'Public Sector',
                        'title' => 'Public Sector & NGOs',
                        'subtitle' => 'Accessibility & Gov-Cloud',
                        'price' => 'Enterprise Solution',
                        'desc' => 'Highly secure database portals adhering to WCAG 2.1 accessibility laws and open data demands.',
                        'features' => ['WCAG 2.1 AA Compliance', 'Secure Gov-Cloud Hosting', 'Open Data API Publishing', 'Strict Pentest Verification'],
                        'btn' => 'Request Accessible App'
                    ]
                ],
                'consulting' => [
                    [
                        'badge' => 'Step A',
                        'title' => 'Discovery Workshop',
                        'subtitle' => 'Requirements Scoping',
                        'price' => 'From €1,950',
                        'desc' => 'We define project objectives, create interactive wireframes, and compile a clear roadmap.',
                        'features' => ['Needs Assessment Workshop', 'Interactive Layout Wireframes', 'Budget & Time Frame Scoping', 'Detailed System Spec Sheet'],
                        'btn' => 'Book Discovery Workshop'
                    ],
                    [
                        'badge' => 'Step B',
                        'title' => 'Architecture Blueprint',
                        'subtitle' => 'Technical specifications',
                        'price' => 'From €2,500',
                        'desc' => 'We detail database tables, API mappings, object relationships, and risk assessment maps.',
                        'features' => ['Database ER Schema Map', 'API UML Sequence Diagrams', 'Security Risk Assessment', 'Tech Selection Report'],
                        'btn' => 'Request Architecture Blueprint',
                        'featured' => true
                    ],
                    [
                        'badge' => 'Step C',
                        'title' => 'Proof of Concept (PoC)',
                        'subtitle' => 'Technical Feasibility Prototype',
                        'price' => 'From €3,950',
                        'desc' => 'A minimal sandbox build verifying high-risk technical features work flawlessly.',
                        'features' => ['Critical Feature Testing', 'API Sandbox Integration', 'Tech Feasibility Proofs', 'Minimized Development Risk'],
                        'btn' => 'Initiate Proof of Concept'
                    ],
                    [
                        'badge' => 'Step D',
                        'title' => 'MVP Development',
                        'subtitle' => 'Minimum Viable Product',
                        'price' => 'Bespoke Quote',
                        'desc' => 'Initial launchable software version containing core features, optimized to collect early user feedback.',
                        'features' => ['Focused Feature Set', 'Rapid Production Launch', 'Feedback Collection Integration', 'Scalable Code Foundation'],
                        'btn' => 'Develop MVP Release'
                    ]
                ]
            ]
        ];

        $lang_data = $data[$lang];
        $tabs = $lang_data['tabs'];
        $default_tab = isset($tabs[$a['default_tab']]) ? $a['default_tab'] : 'journey';

        ob_start(); ?>
        <div class="i4w-pricing-container">

            <!-- Section Header -->
            <div class="i4w-pricing-header">
                <span class="i4w-section-badge">
                    <?php echo esc_html(
                        ($lang === 'fi')
                        ? i4w_t('Tekninen kumppanuus')
                        : i4w_t('Technical Partnership')
                    ); ?>
                </span>
                <h2>
                    <?php echo esc_html(
                        ($lang === 'fi')
                        ? i4w_t('Yritystason ohjelmistoarkkitehtuuri & kehitys')
                        : i4w_t('Enterprise Digital Architecture & Development')
                    ); ?>
                </h2>
                <p>
                    <?php echo esc_html(
                        ($lang === 'fi')
                        ? i4w_t('Suunnittelemme, rakennamme ja ylläpidämme räätälöityjä korkean suorituskyvyn ohjelmistoratkaisuja.')
                        : i4w_t('We design, build, and maintain custom high-performance software for B2B.')
                    ); ?>
                </p>
            </div>

            <!-- Tab Navigation Menu -->
            <div class="i4w-tabs-nav" role="tablist" aria-label="Positioning Concepts">
                <?php foreach ($tabs as $key => $label): ?>
                    <button class="i4w-tab-btn<?php echo ($key === $default_tab) ? ' active' : ''; ?>" role="tab"
                        aria-selected="<?php echo ($key === $default_tab) ? 'true' : 'false'; ?>"
                        aria-controls="pane-<?php echo esc_attr($key); ?>" data-tab="<?php echo esc_attr($key); ?>"
                        id="tab-<?php echo esc_attr($key); ?>">
                        <?php echo esc_html($label); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Tab Contents (Panes) -->
            <?php foreach ($tabs as $key => $label):
                $cards = $lang_data[$key];
                $is_hidden = ($key !== $default_tab);
                ?>
                <div class="i4w-pricing-pane<?php echo $is_hidden ? ' i4w-hide' : ''; ?>" id="pane-<?php echo esc_attr($key); ?>"
                    role="tabpanel" aria-labelledby="tab-<?php echo esc_attr($key); ?>">
                    <?php foreach ($cards as $card):
                        $is_featured = !empty($card['featured']);
                        ?>
                        <div class="i4w-card<?php echo $is_featured ? ' i4w-featured-card' : ''; ?>">
                            <div class="i4w-card-content">
                                <div class="i4w-badge"><?php echo esc_html($card['badge']); ?></div>
                                <h3 class="i4w-card-title">
                                    <?php echo wp_kses($card['title'], array('br' => array(), 'wbr' => array())); ?>
                                </h3>
                                <div class="i4w-card-subtitle">
                                    <?php echo wp_kses($card['subtitle'], array('br' => array(), 'wbr' => array())); ?>
                                </div>
                                <div class="i4w-price">
                                    <span class="i4w-ask"><?php echo esc_html($card['price']); ?></span>
                                </div>
                                <p class="i4w-desc"><?php echo esc_html($card['desc']); ?></p>

                                <ul class="i4w-features">
                                    <?php foreach ($card['features'] as $feat): ?>
                                        <li><?php echo esc_html($feat); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <a class="i4w-button" href="<?php echo esc_url($url); ?>">
                                <?php echo wp_kses($card['btn'], array('br' => array(), 'wbr' => array(), 'span' => array())); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <!-- Bottom B2B Focus Block / Footer CTAs -->
            <div class="i4w-pricing-footer">
                <h3>
                    <?php echo esc_html(
                        ($lang === 'fi')
                        ? i4w_t('Haluatko linjata teknologiasi liiketoiminnan tavoitteiden kanssa?')
                        : i4w_t('Ready to align your technology with your business objectives?')
                    ); ?>
                </h3>
                <p>
                    <?php echo esc_html(
                        ($lang === 'fi')
                        ? i4w_t('Keskustele asiantuntijoidemme kanssa tai pyydä arkkitehtuurikatselmus.')
                        : i4w_t('Discuss your project or request a technical consultation.')
                    ); ?>
                </p>
                <div class="i4w-footer-ctas">
                    <a class="i4w-button i4w-cta-primary" href="<?php echo esc_url($url); ?>">
                        <?php echo esc_html(
                            ($lang === 'fi')
                            ? i4w_t('Aloita digitaalinen uudistus')
                            : i4w_t('Start Your Digital Transformation')
                        ); ?>
                    </a>
                    <a class="i4w-button" href="<?php echo esc_url($url); ?>">
                        <?php echo esc_html(
                            ($lang === 'fi')
                            ? i4w_t('Tilaa arkkitehtuurikatselmus')
                            : i4w_t('Request Architecture Review')
                        ); ?>
                    </a>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
    add_shortcode('i4ware_pricing', 'i4ware_pricing_shortcode');
}

add_action('after_setup_theme', function () {
    register_nav_menus([
        'tk_mega' => __('TK Mega Menu', 'i4waresoftware'),
    ]);
});

add_action('wp_enqueue_scripts', function () {
    // Tyylit
    $css = <<<CSS
:root{--tk-bg:transparent;--tk-bg-2:#091b42;--tk-text:#eaf0ff;--tk-muted:#9fb2ff;--tk-accent:#40b5ff;--tk-focus:#6bd3ff;--tk-shadow:0 20px 40px rgba(0,0,0,.35);--tk-radius:16px;--tk-speed:.28s;--tk-ease:cubic-bezier(.2,.7,.2,1);--tk-col-w:60px;}
.tk-nav {
  display: block;
  position: relative;         /* poista absolute/left:800px */
  z-index: 30;
  width: 80%;
  margin: 0 auto;
}
.tk-nav a{color:inherit;text-decoration:none}.tk-nav :focus-visible{outline:2px solid var(--tk-focus);outline-offset:2px}
.tk-nav{position:relative;z-index:30;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Arial,sans-serif;color:var(--tk-text)}
.tk-bar { display: flex; align-items: center; gap: 16px;padding: 14px 20px;background: var(--tk-bg);position: relative; }
.tk-logo{display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%}
.tk-logo-dot{width:22px;height:22px;background:var(--tk-accent);border-radius:50%;box-shadow:0 0 0 6px rgba(64,181,255,.15);display:block}
.tk-search{flex:1;display:flex;max-width:680px}
.tk-search input{width:100%;background:var(--tk-bg-2);color:var(--tk-text);border:1px solid transparent;border-radius:999px;padding:12px 14px;transition:border-color var(--tk-speed) var(--tk-ease),box-shadow var(--tk-speed) var(--tk-ease)}
.tk-search input::placeholder{color:#c7d2fe88}
.tk-search input:focus{border-color:#3c74ff55;box-shadow:0 0 0 4px #3c74ff22}
.tk-menu-btn{margin-left:auto;background:transparent;border:1px solid #ffffff22;color:var(--tk-text);padding:10px 14px;border-radius:999px;display:inline-flex;align-items:center;gap:10px;cursor:pointer;transition:background var(--tk-speed) var(--tk-ease),transform var(--tk-speed) var(--tk-ease)}
.tk-menu-btn:hover{background:#ffffff0f}.tk-menu-btn:active{transform:translateY(1px)}
.tk-menu-btn__icon{width:18px;height:12px;position:relative;display:inline-block}
.tk-menu-btn__icon:before,.tk-menu-btn__icon:after{content:"";position:absolute;left:0;right:0;height:2px;background:currentColor;border-radius:2px;transition:transform var(--tk-speed) var(--tk-ease)}
.tk-menu-btn__icon:before{top:0;box-shadow:0 5px 0 currentColor}.tk-menu-btn__icon:after{bottom:0}
.tk-mega {
  position: absolute;
  top: 100%;        /* suoraan nav-barin alapuolelle */
  left: 50%;        /* keskitys */
  transform: translateX(-50%); 
  width: 60vw;      /* 70 % viewportista */
  margin-top: 8px;  /* pieni rako */
  padding: 0;       /* gridin padding hoitaa sisätilan */
  perspective: 1200px;
}

.tk-mega__grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(var(--tk-col-w, 200px), 1fr));
  gap: 10px;
  background: linear-gradient(180deg, var(--tk-bg) 0%, var(--tk-bg-2) 100%);
  border: 1px solid #ffffff22;
  border-radius: var(--tk-radius);
  box-shadow: var(--tk-shadow);
  padding: 18px 20px;
  opacity: 0;
  transform: translateY(-8px) scale(.98);
  transform-origin: top center;
  transition: transform var(--tk-speed) var(--tk-ease), opacity var(--tk-speed) var(--tk-ease);
}

.tk-nav.is-open .tk-mega__grid {
  opacity: 1;
  transform: translateY(0) scale(1);
}
.tk-col h3 {
  margin: 0 0 4px; /* aiemmin 10px */
  font-size: 18px;
}
.tk-col a {
  display: block;
  padding: 3px 6px;   /* pienempi ylä- ja alapadding */
  margin-bottom: 5px; /* lähes kiinni toisiinsa */
  border-radius: 6px;
  font-weight: 500;
  line-height: 1.8;   /* tiukempi riviväli */
  font-size: 14px;    /* (valinnainen) hieman pienempi fontti */
  transition: background var(--tk-speed) var(--tk-ease),
              transform var(--tk-speed) var(--tk-ease);
}
.menu-open .tk-nav {
  display: none;
}
@media (max-width: 900px) {
  .tk-nav {
    display: none;
  }
}
.tk-col a:hover{background:#ffffff12;transform:translateX(2px)}
@media (max-width:840px){.tk-search{display:none}.tk-mega{top:60px;left:0;right:0;padding:12px}.tk-mega__grid{grid-template-columns:1fr}}
.tk-mega[hidden]{display:block;height:0;overflow:hidden}
CSS;
    wp_register_style('tk-mega-style', false);
    wp_enqueue_style('tk-mega-style');
    wp_add_inline_style('tk-mega-style', $css);

    // Skripti
    $js = <<<JS
(function(){
  const nav=document.querySelector('.tk-nav'); if(!nav) return;
  const btn=nav.querySelector('#tkMenuBtn'); const panel=nav.querySelector('#tk-mega');
  if(!btn||!panel) return;
  function open(){nav.classList.add('is-open');btn.setAttribute('aria-expanded','true');panel.hidden=false;}
  function close(){nav.classList.remove('is-open');btn.setAttribute('aria-expanded','false');panel.hidden=true;}
  function toggle(){(btn.getAttribute('aria-expanded')==='true')?close():open();}
  btn.addEventListener('click',toggle);
  document.addEventListener('keydown',(e)=>{if(e.key==='Escape') close();});
  document.addEventListener('click',(e)=>{if(!nav.contains(e.target)&&nav.classList.contains('is-open')) close();});
})();
JS;
    wp_register_script('tk-mega-script', false, [], null, true);
    wp_enqueue_script('tk-mega-script');
    wp_add_inline_script('tk-mega-script', $js);
});

/**
 * Tulostaa mega-menun. 
 * Käytä headerissä:  <?php if (function_exists('tk_render_mega_menu')) tk_render_mega_menu(); ?>
 *
 * @param array $args ['location' => 'tk_mega', 'logo_html' => '<span class="tk-logo-dot"></span>', 'show_search' => true]
 */
function tk_render_mega_menu($args = [])
{
    $defaults = [
        'location' => 'tk_mega',
        'logo_html' => '<span class="tk-logo-dot" aria-hidden="true"></span>',
        'show_search' => true,
    ];
    $args = wp_parse_args($args, $defaults);
    // Haetaan CTA arvot Customizerista
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();
    } else {
        $lang = 'fi';
    }
    $cta_text = get_theme_mod("i4ware_cta_text_$lang", __('Pyydä tarjous', 'i4ware'));
    $cta_url = get_theme_mod('i4ware_cta_url_' . $lang, '#');
    $locations = get_nav_menu_locations();
    if (empty($locations[$args['location']])) {
        // Ei valikkoa määritelty – voidaan silti tulostaa kehyksenä
        echo '<nav class="tk-nav"><div class="tk-bar"><a class="tk-logo" href="/">' . $args['logo_html'] . '</a>';
        if ($args['show_search'])
            echo '<form class="tk-search" role="search">' . get_search_form(['echo' => false]) . '</form>';
        echo '<button class="tk-menu-btn" aria-expanded="false" aria-controls="tk-mega" id="tkMenuBtn"><span class="tk-menu-btn__label">Menu</span><span class="tk-menu-btn__icon" aria-hidden="true"></span></button></div><div class="tk-mega" id="tk-mega" hidden><div class="tk-mega__grid"><section class="tk-col"><h3>' . esc_html__('Setup', 'your-textdomain') . '</h3><a href="' . esc_url(admin_url('nav-menus.php')) . '">' . esc_html__('Create “TK Mega Menu” in Appearance → Menus', 'your-textdomain') . '</a></section></div></div></nav>';
        return;
    }

    $menu_id = $locations[$args['location']];
    $items = wp_get_nav_menu_items($menu_id, ['update_post_term_cache' => false]);
    if (empty($items))
        return;

    // Järjestä vanhempi->lapset -rakenteeksi
    usort($items, function ($a, $b) {
        return (int) $a->menu_order <=> (int) $b->menu_order;
    });

    $parents = [];
    $children = [];
    foreach ($items as $it) {
        if ($it->menu_item_parent == 0) {
            $parents[$it->ID] = $it;
        } else {
            $children[$it->menu_item_parent][] = $it;
        }
    }

    // Renderöinti
    echo '<nav class="tk-nav" aria-label="Main">';
    echo '  <div class="tk-bar">';
    echo '    <div class="tk-bar-right">';
    echo '    <div class="menu-left">';
    echo '      <a href="' . esc_url($cta_url) . '" class="cta-button">' . esc_html($cta_text) . '</a>';
    echo '    </div>';
    echo '    <button class="tk-menu-btn" aria-expanded="false" aria-controls="tk-mega" id="tkMenuBtn"><span class="tk-menu-btn__label">' . esc_html__('Menu', 'i4ware') . '</span><span class="tk-menu-btn__icon" aria-hidden="true"></span></button>';
    echo '    </div>';
    echo '  </div>';

    echo '  <div class="tk-mega" id="tk-mega" hidden>';
    echo '    <div class="tk-mega__grid">';

    foreach ($parents as $pid => $parent) {
        echo '<section class="tk-col">';
        echo '  <h3>' . esc_html($parent->title) . '</h3>';

        if (!empty($children[$pid])) {
            foreach ($children[$pid] as $child) {
                $title = $child->title ?: $child->post_title;
                $url = $child->url ?: '#';
                $target = $child->target ? ' target="' . esc_attr($child->target) . '" rel="noopener"' : '';
                echo '  <a href="' . esc_url($url) . '"' . $target . '>' . esc_html($title) . '</a>';
            }
        } else {
            // Jos vanhemmalla on linkki, tulosta se yhtenä kohteena
            if (!empty($parent->url)) {
                echo '  <a href="' . esc_url($parent->url) . '">' . esc_html($parent->title) . '</a>';
            }
        }

        echo '</section>';
    }

    echo '    </div>';
    echo '  </div>';
    echo '</nav>';
}

add_action('rest_api_init', function () {
    register_rest_route('jaf/v1', '/submit', [
        'methods' => 'POST',
        'callback' => 'jaf_handle_submit',
        'permission_callback' => '__return_true',
    ]);
});

function jaf_handle_submit(WP_REST_Request $req)
{
    $token = sanitize_text_field($req->get_param('recaptcha'));
    if (empty($token)) {
        return new WP_Error('missing_token', 'Missing reCAPTCHA token', ['status' => 400]);
    }

    $secret = '6Ldw-1ArAAAAAK2fVIjizyeobp3Ki0c0iVYBug-m'; // keep secret on server only

    $resp = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
        'body' => [
            'secret' => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ],
        'timeout' => 10,
    ]);

    if (is_wp_error($resp)) {
        return new WP_Error('recaptcha_http', $resp->get_error_message(), ['status' => 500]);
    }

    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($data['success'])) {
        return new WP_Error('recaptcha_failed', 'reCAPTCHA failed', ['status' => 403, 'details' => $data]);
    }

    // TODO: handle and persist $req->get_json_params() (form data)
    return ['ok' => true];
}

// functions.php

function i4ware_add_cta_button()
{
    $cta_text = get_theme_mod('i4ware_cta_text', __('Pyydä tarjous', 'i4ware'));
    $cta_url = get_theme_mod('i4ware_cta_url', '#');

    echo '<div class="header-cta"><a class="cta-button" href="' . esc_url($cta_url) . '">' . esc_html($cta_text) . '</a></div>';
}

// Lisää CTA painike wp_nav_menu() -funktion eteen
add_action('wp_nav_menu_items', function ($items, $args) {
    if ($args->theme_location == 'primary') {
        $cta_text = get_theme_mod('i4ware_cta_text', __('Pyydä tarjous', 'i4ware'));
        $cta_url = get_theme_mod('i4ware_cta_url', '#');

        $cta_html = '<li class="menu-item cta-button"><a href="' . esc_url($cta_url) . '">' . esc_html($cta_text) . '</a></li>';

        // Lisää CTA linkki vasemmalle
        return $cta_html . $items;
    }
    return $items;
}, 10, 2);

// i4ware CTA shortcode — supports Polylang
function i4ware_cta_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'url' => '#',     // default fallback URL
        'url_en' => '',      // optional language specific URL for English
        'url_fi' => '',      // optional language specific URL for Finnish
        'class' => '',      // extra classes
    ), $atts, 'i4ware_cta');

    // detect language (Polylang if present, fallback to WP locale)
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();
    } else {
        $lang = substr(get_locale(), 0, 2);
    }

    // copy translations — read from Customizer when available (fallback to defaults)
    $defaults = array(
        'en' => array(
            'headline' => 'Get your SaaS product to market cost-effectively with i4ware SDK',
            'desc' => 'We build MVP and SaaS solutions for you. Low-code i4ware SDK and AI-assisted development enable fast and cost-effective delivery.',
            'button' => 'Request a quote',
        ),
        'fi' => array(
            'headline' => 'SaaS-tuoteideasi tuotantoon i4ware SDK:lla kustannustehokkaasti',
            'desc' => 'Rakennamme MVP- ja SaaS-ratkaisut puolestasi. Low-code i4ware SDK ja AI-avusteinen kehitys mahdollistavat nopean ja kustannustehokkaan toteutuksen.',
            'button' => 'Pyydä tarjous',
        ),
    );

    // Use language-specific Customizer values when available
    $t = array(
        'headline' => get_theme_mod("i4ware_cta_headline_{$lang}", $defaults[$lang]['headline'] ?? $defaults['en']['headline']),
        'desc' => get_theme_mod("i4ware_cta_desc_{$lang}", $defaults[$lang]['desc'] ?? $defaults['en']['desc']),
        'button' => get_theme_mod("i4ware_cta_text_{$lang}", $defaults[$lang]['button'] ?? $defaults['en']['button']),
    );

    // choose best URL (language-specific > attribute url > fallback url)
    $url = $atts['url'];
    if ($lang === 'en' && !empty($atts['url_en']))
        $url = $atts['url_en'];
    if ($lang === 'fi' && !empty($atts['url_fi']))
        $url = $atts['url_fi'];

    // markup (keeps styling minimal and self-contained)
    $html = '<aside class="i4ware-cta-box ' . esc_attr($atts['class']) . '" role="region" aria-label="' . esc_attr__('CTA', 'i4ware') . '">';
    $html .= '<h3>' . esc_html($t['headline']) . '</h3>';
    $html .= '<p>' . esc_html($t['desc']) . '</p>';
    $html .= '<a href="' . esc_url($url) . '" class="i4ware-cta-btn" aria-label="' . esc_attr($t['button']) . '">' . esc_html($t['button']) . '</a>';
    $html .= '</aside>';

    return $html;
}
add_shortcode('i4ware_cta', 'i4ware_cta_shortcode');

// Shortcode to output embedded YouTube video with optional blur and per-language overlay text
function i4ware_video_shortcode($atts)
{
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();
    } else {
        $lang = substr(get_locale(), 0, 2);
    }

    $video_url = get_theme_mod('i4ware_video_url', '');
    if (empty($video_url))
        return '';

    // Normalize common YouTube URL formats to embed URL
    if (strpos($video_url, 'watch?v=') !== false) {
        $embed = str_replace('watch?v=', 'embed/', $video_url);
    } elseif (strpos($video_url, 'youtu.be/') !== false) {
        preg_match('#youtu\.be/([A-Za-z0-9_-]+)#', $video_url, $m);
        $id = $m[1] ?? '';
        $embed = $id ? 'https://www.youtube.com/embed/' . $id : esc_url($video_url);
    } else {
        $embed = $video_url;
    }

    $blur = get_theme_mod('i4ware_video_blur', false);
    $overlay_text = get_theme_mod("i4ware_video_overlay_text_{$lang}", '');

    $wrap_classes = 'i4ware-video-wrap' . ($blur ? ' blur' : '');

    $html = '<div class="' . esc_attr($wrap_classes) . '" style="position:relative;max-width:900px;margin:16px auto;border-radius:8px;overflow:hidden;">';
    $html .= '<iframe src="' . esc_url($embed) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%;height:420px;border:0;display:block;"></iframe>';
    if ($overlay_text) {
        $html .= '<div class="i4ware-video-overlay" style="position:absolute;left:16px;top:16px;color:#fff;font-weight:700;text-shadow:0 2px 8px rgba(0,0,0,0.6);">' . esc_html($overlay_text) . '</div>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('i4ware_video', 'i4ware_video_shortcode');

add_action('init', function () {

    pll_register_string('i4ware', 'Project starting phase');
    pll_register_string('i4ware', 'Idea');
    pll_register_string('i4ware', 'Prototype');
    pll_register_string('i4ware', 'MVP');
    pll_register_string('i4ware', 'Production enhancement');

    pll_register_string('i4ware', 'Project details');
    pll_register_string('i4ware', 'Describe your SaaS idea, goals and requirements');

    pll_register_string('i4ware', 'SRS specification ready');
    pll_register_string('i4ware', 'Mockups ready (e.g. Figma)');

    pll_register_string('i4ware', 'Estimated hours');
    pll_register_string('i4ware', 'Hourly rate');
    pll_register_string('i4ware', 'Estimated total price');
    pll_register_string('i4ware', 'Send order request');

    pll_register_string('i4ware', 'Financing');
    pll_register_string('i4ware', 'External funding available');
    pll_register_string('i4ware', 'External funding details');
    pll_register_string('i4ware', 'Describe funding source, amount and type');
    pll_register_string('i4ware', 'Revenue funded');

    pll_register_string('i4ware', 'Company founding year');
    pll_register_string('i4ware', 'Orderer contact details');
    pll_register_string('i4ware', 'Company name');
    pll_register_string('i4ware', 'Contact person');
    pll_register_string('i4ware', 'Email address');
    pll_register_string('i4ware', 'Phone number');

    // Email sending messages
    pll_register_string('i4ware', 'New order request received');
    pll_register_string('i4ware', 'You have received a new order request from your website.');
    pll_register_string('i4ware', 'Order details:');
    pll_register_string('i4ware', 'Please contact the orderer as soon as possible.');
    pll_register_string('i4ware', 'Thank you for using our service.');
    // JS lomakkeen tekstit
    pll_register_string('i4ware', 'Send order request');
    pll_register_string('i4ware', 'Sending...');
    pll_register_string('i4ware', 'Order request sent successfully');
    pll_register_string('i4ware', 'Submission failed');
    pll_register_string('i4ware', 'Server error');

    pll_register_string('i4ware', 'Asiakas-/Projektityyppi');
    pll_register_string('i4ware', 'Alihankinta');
    pll_register_string('i4ware', 'Suora loppuasiakas');

    pll_register_string('i4ware', 'Tietoa projektista');
    pll_register_string('i4ware', 'Kuvaile projektia');

    pll_register_string('i4ware', 'Tilauksen taso:');
    pll_register_string('i4ware', 'Valitse');
    pll_register_string('i4ware', 'Pronssi (Urakka)');
    pll_register_string('i4ware', 'Hopea (Urakka)');
    pll_register_string('i4ware', 'Kulta (Urakka)');
    pll_register_string('i4ware', 'Tuntityö');

    pll_register_string('i4ware', 'Materiaali');
    pll_register_string('i4ware', 'PSD (Photoshop, layereilla)');
    pll_register_string('i4ware', 'XD (Adobe XD, layereilla)');
    pll_register_string('i4ware', 'Sketch (layereilla)');
    pll_register_string('i4ware', 'Figma (komponentit, layereilla)');
    pll_register_string('i4ware', 'InVision (komponentit, layereilla)');
    pll_register_string('i4ware', 'Staattinen HTML-kooste');
    pll_register_string('i4ware', 'Tekstit valmiina');
    pll_register_string('i4ware', 'Muu');
    pll_register_string('i4ware', 'Muu materiaali (jos valittu)');

    pll_register_string('i4ware', 'Tuntimäärä (vain Tuntityö)');
    pll_register_string('i4ware', 'Tunnit');
    pll_register_string('i4ware', 'Hinta');

    pll_register_string('i4ware', 'Yhteystiedot');
    pll_register_string('i4ware', 'Nimi');
    pll_register_string('i4ware', 'Yritys / Organisaatio');
    pll_register_string('i4ware', 'Sähköposti');
    pll_register_string('i4ware', 'Puhelinnumero');
    pll_register_string('i4ware', 'Lisätiedot / kommentit');
    pll_register_string('i4ware', 'En osaa arvioida tuntimäärää itse - pyydän tuntiarvion toimittajalta');
    pll_register_string('i4ware', 'Saat tarjouksen');

    // Uudet – checkbox-tekstit
    pll_register_string('i4ware', 'Olen hyväksynyt Toimitusehdot:');
    pll_register_string('i4ware', 'Lue Toimitusehdot');

    pll_register_string('i4ware', 'Olen lukenut ja hyväksynyt Yksityisyydensuojaselosteen:');
    pll_register_string('i4ware', 'Lue Yksityisyydensuojaseloste');

    // Uusi – AJAX-validoinnin virheilmoitus
    pll_register_string(
        'i4ware',
        'Sinun tulee hyväksyä Toimitusehdot ja Yksityisyydensuojaseloste ennen lähettämistä.'
    );

    // Lähetä-painike
    pll_register_string('i4ware', 'Lähetä tarjouspyyntö');

    // reCAPTCHA
    pll_register_string('i4ware', 'Ole hyvä ja vahvista reCAPTCHA.');
});

/**
 * Plugin Name: i4ware SaaS Order Form
 * Description: i4ware SDK low-code SaaS tilauslomake (FI/EN, Polylang)
 */

function i4ware_saas_order_form_shortcode()
{

    // Oletustuntihinta (muokkaa tarvittaessa)
    $hourly_rate = 95;

    $current_lang = 'en';
    if (function_exists('pll_current_language')) {
        $current_lang = pll_current_language();
    }
    $terms_link = get_theme_mod("wp_quote_terms_link_$current_lang", '#');
    $privacy_link = get_theme_mod("wp_quote_privacy_link_$current_lang", '#');

    $recaptcha_site_key = defined('I4WARE_RECAPTCHA_SITE_KEY') ? I4WARE_RECAPTCHA_SITE_KEY : get_option('i4ware_recaptcha_site_key', '');

    ob_start();
    ?>
    <form id="i4ware-saas-form" class="i4ware-saas-form" data-hourly-rate="<?php echo (int) $hourly_rate; ?>">
        <?php wp_nonce_field('i4ware_saas_order', 'i4ware_nonce'); ?>
        <input type="hidden" name="action" value="i4ware_submit_order">

        <label>
            <?php pll_e('Project starting phase'); ?>
        </label>
        <select name="project_phase" required>
            <option value="idea"><?php pll_e('Idea'); ?></option>
            <option value="prototype"><?php pll_e('Prototype'); ?></option>
            <option value="mvp"><?php pll_e('MVP'); ?></option>
            <option value="production"><?php pll_e('Production enhancement'); ?></option>
        </select>

        <label>
            <?php pll_e('Project details'); ?>
        </label>
        <textarea name="project_details" rows="5"
            placeholder="<?php pll_e('Describe your SaaS idea, goals and requirements'); ?>"></textarea>

        <div class="i4ware-checkbox-group">
            <label>
                <input type="checkbox" name="has_srs">
                <?php pll_e('SRS specification ready'); ?>
            </label>

            <label>
                <input type="checkbox" name="has_mockups">
                <?php pll_e('Mockups ready (e.g. Figma)'); ?>
            </label>
        </div>

        <label>
            <?php pll_e('Estimated hours'); ?>
        </label>
        <input type="number" name="estimated_hours" min="1" required oninput="i4wareCalcPrice(this.value)">

        <div class="i4ware-pricing">
            <p><?php pll_e('Hourly rate'); ?>:
                <strong>95 €</strong>
            </p>
            <p><?php pll_e('Estimated total price'); ?>:
                <strong><span id="i4ware-total-price">0</span> €</strong>
            </p>
        </div>

        <!-- Financing -->
        <label class="i4ware-section-title i4ware-section-title--financing"><?php pll_e('Financing'); ?></label>

        <div class="i4ware-checkbox-group">
            <label>
                <input type="checkbox" id="external_funding_checkbox" name="external_funding_available">
                <?php pll_e('External funding available'); ?>
            </label>

            <label>
                <input type="checkbox" name="revenue_funded">
                <?php pll_e('Revenue funded'); ?>
            </label>
        </div>

        <div id="external_funding_details" style="display:none; margin-top:12px;">
            <label>
                <?php pll_e('External funding details'); ?>
            </label>
            <textarea name="external_funding_details"
                placeholder="<?php pll_e('Describe funding source, amount and type'); ?>"></textarea>
        </div>

        <!-- Additional services -->
        <label><?php pll_e('Additional services'); ?></label>

        <div class="i4ware-checkbox-group">
            <label>
                <input type="checkbox" name="additional_services[]" value="pitch_deck">
                <?php echo function_exists('pll__') ? pll__('Pitch Deck design for VC/Angel investors') : 'Pitch Deck design for VC/Angel investors'; ?>
            </label>

            <label>
                <input type="checkbox" name="additional_services[]" value="financing_consultation">
                <?php echo function_exists('pll__') ? pll__('Consultation on other financing models (Business Finland, ELY-center, EU-funds)') : 'Consultation on other financing models (Business Finland, ELY-center, EU-funds)'; ?>
            </label>

            <label>
                <input type="checkbox" name="additional_services[]" value="finvera_loan">
                <?php echo function_exists('pll__') ? pll__("Why not too early apply for Finvera's guaranteed bank loan") : "Why not too early apply for Finvera's guaranteed bank loan"; ?>
            </label>

            <label>
                <input type="checkbox" name="additional_services[]" value="organizations_advice">
                <?php echo function_exists('pll__') ? pll__('Listing organizations for additional advice, market research') : 'Listing organizations for additional advice, market research'; ?>
            </label>
        </div>

        <!-- Company info -->
        <label><?php pll_e('Company founding year'); ?></label>
        <input type="number" name="company_founding_year" min="1800" max="<?php echo date('Y'); ?>">

        <!-- Contact details -->
        <label
            style="margin-top:30px !important; margin-bottom:30px !important;"><?php pll_e('Orderer contact details'); ?></label>

        <label><?php pll_e('Company name'); ?></label>
        <input type="text" name="company_name" required>

        <label><?php pll_e('Contact person'); ?></label>
        <input type="text" name="contact_person" required>

        <label><?php pll_e('Email address'); ?></label>
        <input type="email" name="email" required>

        <label><?php pll_e('Phone number'); ?></label>
        <input type="tel" name="phone">

        <label>
            <input type="checkbox" id="terms" name="terms" required>
            <?php pll_e('Olen hyväksynyt Toimitusehdot:'); ?>
            <a href="<?php echo esc_url($terms_link); ?>" target="_blank"><?php pll_e('Lue Toimitusehdot'); ?></a>
        </label>

        <label>
            <input type="checkbox" id="privacy" name="privacy" required>
            <?php pll_e('Olen lukenut ja hyväksynyt Yksityisyydensuojaselosteen:'); ?>
            <a href="<?php echo esc_url($privacy_link); ?>"
                target="_blank"><?php pll_e('Lue Yksityisyydensuojaseloste'); ?></a>
        </label>

        <?php if (!empty($recaptcha_site_key)) : ?>
            <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>" style="margin-bottom: 20px;"></div>
            <script src="https://www.google.com/recaptcha/api.js?hl=<?php echo $current_lang; ?>" async defer></script>
        <?php endif; ?>

        <button type="submit">
            <?php pll_e('Send order request'); ?>
        </button>

        <div id="i4ware-form-message"></div>
    </form>

    <script>
        function i4wareCalcPrice(hours) {
            const form = document.getElementById('i4ware-saas-form');
            const rate = parseInt(form.getAttribute('data-hourly-rate')) || 95;
            const totalEl = document.getElementById('i4ware-total-price');

            hours = parseFloat(hours) || 0;
            totalEl.innerText = hours > 0 ? (hours * rate).toFixed(0) : 0;
        }

        jQuery(document).ready(function ($) {
            $('#external_funding_checkbox').on('change', function () {
                $('#external_funding_details').toggle(this.checked);
            });

            $('#i4ware-saas-form').on('submit', function (e) {
                e.preventDefault();

                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const messageEl = $('#i4ware-form-message');

                if (!form.find('input[name="terms"]').is(':checked') || !form.find('input[name="privacy"]').is(':checked')) {
                    messageEl.text('<?php echo esc_js(pll__("Sinun tulee hyväksyä Toimitusehdot ja Yksityisyydensuojaseloste ennen lähettämistä.")); ?>');
                    return;
                }

                if (typeof grecaptcha !== 'undefined' && form.find('.g-recaptcha').length) {
                    const recaptchaResponse = grecaptcha.getResponse();
                    if (!recaptchaResponse) {
                        messageEl.text('<?php echo esc_js(pll__("Ole hyvä ja vahvista reCAPTCHA.")); ?>');
                        return;
                    }
                }

                const textSending = '<?php echo esc_js(pll__("Sending...")); ?>';
                const textSuccess = '<?php echo esc_js(pll__("Order request sent successfully")); ?>';
                const textFailed = '<?php echo esc_js(pll__("Submission failed")); ?>';
                const textServerError = '<?php echo esc_js(pll__("Server error")); ?>';
                const textSendOrder = '<?php echo esc_js(pll__("Send order request")); ?>';

                submitBtn.prop('disabled', true).text(textSending);

                $.ajax({
                    url: '<?php echo esc_url(admin_url("admin-ajax.php")); ?>',
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            messageEl.text(textSuccess);

                            const hours = parseInt(form.find('input[name="estimated_hours"]').val() || 0, 10);
                            const rate = parseInt(form.attr('data-hourly-rate')) || 95;
                            const totalValue = hours * rate;

                            if (typeof window.i4wareTrackSaasOrderSuccess === 'function') {
                                window.i4wareTrackSaasOrderSuccess(totalValue);
                            }

                            form[0].reset();
                            $('#external_funding_details').hide();
                            i4wareCalcPrice(0);
                            if (typeof grecaptcha !== 'undefined') {
                                grecaptcha.reset();
                            }
                        } else {
                            messageEl.text(response.data || textFailed);
                            if (typeof grecaptcha !== 'undefined') {
                                grecaptcha.reset();
                            }
                        }
                    },
                    error: function () {
                        messageEl.text(textServerError);
                        if (typeof grecaptcha !== 'undefined') {
                            grecaptcha.reset();
                        }
                    },
                    complete: function () {
                        submitBtn.prop('disabled', false).text(textSendOrder);
                    }
                });
            });
        });
    </script>

    <style>
        /* i4ware SaaS Order Form – Modern UI */

        .i4ware-saas-form {
            width: 100%;
            margin: 40px auto;
            padding: 32px;
            background: #111;
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            color: #f1f1f1;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .i4ware-saas-form label {
            display: block;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #ccc;
        }

        /* Extra top padding for the Financing / "Rahoitus" header */
        .i4ware-section-title--financing {
            padding-top: 18px;
            display: block;
        }

        .i4ware-saas-form select,
        .i4ware-saas-form input[type="number"],
        .i4ware-saas-form input[type="text"],
        .i4ware-saas-form input[type="email"],
        .i4ware-saas-form input[type="tel"],
        .i4ware-saas-form textarea {
            width: 100%;
            padding: 14px 16px;
            background: #1c1c1c;
            border: 1px solid #2c2c2c;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: border 0.2s ease, box-shadow 0.2s ease;
            margin-bottom: 20px;
        }

        .i4ware-saas-form textarea {
            resize: vertical;
            min-height: 120px;
        }

        .i4ware-saas-form select:focus,
        .i4ware-saas-form input:focus,
        .i4ware-saas-form textarea:focus {
            outline: none;
            border-color: #4da3ff;
            box-shadow: 0 0 0 2px rgba(77, 163, 255, 0.25);
        }

        /* Checkbox group */
        .i4ware-checkbox-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 10px;
        }

        .i4ware-checkbox-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            background: #1a1a1a;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #2a2a2a;
        }

        .i4ware-checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #4da3ff;
        }

        /* Pricing section */
        .i4ware-pricing {
            margin-top: 24px;
            padding: 20px;
            background: linear-gradient(135deg, #161616, #1f1f1f);
            border-radius: 12px;
            border: 1px solid #2a2a2a;
        }

        .i4ware-pricing p {
            margin: 6px 0;
            font-size: 0.95rem;
            color: #bbb;
        }

        .i4ware-pricing strong {
            color: #fff;
            font-size: 1.1rem;
        }

        /* Submit button */
        .i4ware-saas-form button {
            width: 100%;
            margin-top: 28px;
            padding: 16px;
            font-size: 1.05rem;
            font-weight: 700;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            color: #000;
            background: linear-gradient(135deg, #4da3ff, #6dd5fa);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .i4ware-saas-form button:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 25px rgba(77, 163, 255, 0.35);
        }

        .i4ware-saas-form button:active {
            transform: translateY(0);
        }
    </style>

    <?php
    return ob_get_clean();
}

add_shortcode('i4ware_saas_order_form', 'i4ware_saas_order_form_shortcode');

add_action('wp_ajax_i4ware_submit_order', 'i4ware_submit_order');
add_action('wp_ajax_nopriv_i4ware_submit_order', 'i4ware_submit_order');

/**
 * Register Polylang strings on init so they appear in WP admin -> Strings translations
 */
function i4ware_register_polylang_strings()
{
    if (function_exists('pll_register_string')) {
        pll_register_string('i4ware_pitch_deck', 'Pitch Deck design for VC/Angel investors', 'i4ware-saas');
        pll_register_string('i4ware_financing_consultation', 'Consultation on other financing models (Business Finland, ELY-center, EU-funds)', 'i4ware-saas');
        pll_register_string('i4ware_finvera_loan', "Why not too early apply for Finvera's guaranteed bank loan", 'i4ware-saas');
        pll_register_string('i4ware_organizations_advice', 'Listing organizations for additional advice, market research', 'i4ware-saas');
        pll_register_string('i4ware_additional_services', 'Additional services', 'i4ware-saas');
    }
}
add_action('init', 'i4ware_register_polylang_strings');

function i4ware_submit_order()
{

    // Nonce check
    if (
        !isset($_POST['i4ware_nonce']) ||
        !wp_verify_nonce($_POST['i4ware_nonce'], 'i4ware_saas_order')
    ) {
        wp_send_json_error('Invalid security token');
    }

    // Verify reCAPTCHA
    $recaptcha_site_key = defined('I4WARE_RECAPTCHA_SITE_KEY') ? I4WARE_RECAPTCHA_SITE_KEY : get_option('i4ware_recaptcha_site_key', '');
    $recaptcha_secret_key = defined('I4WARE_RECAPTCHA_SECRET_KEY') ? I4WARE_RECAPTCHA_SECRET_KEY : get_option('i4ware_recaptcha_secret_key', '');

    if (!empty($recaptcha_site_key) && !empty($recaptcha_secret_key)) {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptcha_response)) {
            wp_send_json_error(pll__('Ole hyvä ja vahvista reCAPTCHA.'));
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $recaptcha_secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('reCAPTCHA verification server error.');
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        if (empty($result['success'])) {
            wp_send_json_error(pll__('Ole hyvä ja vahvista reCAPTCHA.'));
        }
    }

    // Tarkistetaan, että checkboxit on valittu
    if (empty($_POST['terms']) || empty($_POST['privacy'])) {
        echo pll__('Sinun tulee hyväksyä Toimitusehdot ja Yksityisyydensuojaseloste ennen lähettämistä.');
        wp_die();
    }

    // Sanitize inputs
    $data = [
        'project_phase' => sanitize_text_field($_POST['project_phase'] ?? ''),
        'project_details' => sanitize_textarea_field($_POST['project_details'] ?? ''),
        'has_srs' => isset($_POST['has_srs']) ? 'Yes' : 'No',
        'has_mockups' => isset($_POST['has_mockups']) ? 'Yes' : 'No',
        'estimated_hours' => intval($_POST['estimated_hours'] ?? 0),
        'external_funding' => isset($_POST['external_funding_available']) ? 'Yes' : 'No',
        'external_funding_details' => sanitize_textarea_field($_POST['external_funding_details'] ?? ''),
        'revenue_funded' => isset($_POST['revenue_funded']) ? 'Yes' : 'No',
        'additional_services' => isset($_POST['additional_services']) && is_array($_POST['additional_services']) ? implode(', ', array_map('sanitize_text_field', $_POST['additional_services'])) : '',
        'company_founding_year' => intval($_POST['company_founding_year'] ?? ''),
        'company_name' => sanitize_text_field($_POST['company_name'] ?? ''),
        'contact_person' => sanitize_text_field($_POST['contact_person'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? '')
    ];

    // Email content
    $to = get_option('admin_email');
    $subject = 'New i4ware SaaS Order Request';

    $message = "";
    foreach ($data as $key => $value) {
        $message .= ucfirst(str_replace('_', ' ', $key)) . ": $value\n";
    }

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $data['email']
    ];

    wp_mail($to, $subject, $message, $headers);

    // Response
    wp_send_json_success('Order submitted');
}

// Settings page to swap reCAPTCHA keys
add_action('admin_menu', function() {
    add_options_page(
        'i4ware reCAPTCHA',
        'i4ware reCAPTCHA',
        'manage_options',
        'i4ware-recaptcha',
        'i4ware_recaptcha_settings_page'
    );
});

function i4ware_recaptcha_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Save settings
    if (isset($_POST['i4ware_recaptcha_submit']) && check_admin_referer('i4ware_recaptcha_save', 'i4ware_recaptcha_nonce')) {
        update_option('i4ware_recaptcha_site_key', sanitize_text_field($_POST['i4ware_recaptcha_site_key']));
        update_option('i4ware_recaptcha_secret_key', sanitize_text_field($_POST['i4ware_recaptcha_secret_key']));
        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }
    
    $site_key = get_option('i4ware_recaptcha_site_key', '');
    $secret_key = get_option('i4ware_recaptcha_secret_key', '');
    
    ?>
    <div class="wrap">
        <h1>i4ware Google reCAPTCHA Settings</h1>
        <form method="post">
            <?php wp_nonce_field('i4ware_recaptcha_save', 'i4ware_recaptcha_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="i4ware_recaptcha_site_key">Site Key</label></th>
                    <td><input type="text" name="i4ware_recaptcha_site_key" id="i4ware_recaptcha_site_key" value="<?php echo esc_attr($site_key); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="i4ware_recaptcha_secret_key">Secret Key</label></th>
                    <td><input type="password" name="i4ware_recaptcha_secret_key" id="i4ware_recaptcha_secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text"></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="i4ware_recaptcha_submit" class="button button-primary" value="Save Settings">
            </p>
        </form>
    </div>
    <?php
}

// Lyhytkoodi wp_quote AJAX-lähetyksellä
function wp_quote_form_shortcode()
{
    ob_start();
    ?>
    <form id="wp_quote-form" method="post">
        <h3><?php echo pll__('Asiakas-/Projektityyppi'); ?></h3>
        <label><input type="radio" name="tilaaja" value="Alihankinta" required> <?php echo pll__('Alihankinta'); ?></label>
        <label><input type="radio" name="tilaaja" value="Suora loppuasiakas" required>
            <?php echo pll__('Suora loppuasiakas'); ?></label>

        <h3><?php echo pll__('Tietoa projektista'); ?></h3>
        <textarea name="projektikuvaus" placeholder="<?php echo pll__('Kuvaile projektia'); ?>" required></textarea>

        <label for="tilaus_taso"><?php echo pll__('Tilauksen taso:'); ?></label>
        <select id="tilaus_taso" name="tilaus_taso" required>
            <option value=""><?php echo pll__('Valitse'); ?></option>
            <option value="Pronssi"><?php echo pll__('Pronssi (Urakka)'); ?></option>
            <option value="Hopea"><?php echo pll__('Hopea (Urakka)'); ?></option>
            <option value="Kulta"><?php echo pll__('Kulta (Urakka)'); ?></option>
            <option value="Tuntityo"><?php echo pll__('Tuntityö'); ?></option>
        </select>

        <h3><?php echo pll__('Materiaali'); ?></h3>
        <label><input type="checkbox" name="materiaali[]" value="PSD">
            <?php echo pll__('PSD (Photoshop, layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="XD">
            <?php echo pll__('XD (Adobe XD, layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="Sketch">
            <?php echo pll__('Sketch (layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="Figma">
            <?php echo pll__('Figma (komponentit, layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="InVision">
            <?php echo pll__('InVision (komponentit, layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="HTML">
            <?php echo pll__('Staattinen HTML-kooste'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="Tekstit"> <?php echo pll__('Tekstit valmiina'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="Muu"> <?php echo pll__('Muu'); ?></label>
        <input type="text" name="materiaali_muu" placeholder="<?php echo pll__('Muu materiaali (jos valittu)'); ?>">

        <div id="tuntimaara-wrapper" style="display:none;">
            <h3><?php echo pll__('Tuntimäärä (vain Tuntityö)'); ?></h3>
            <input type="number" id="tuntimaara" name="tuntimaara" min="1" placeholder="<?php echo pll__('Tunnit'); ?>">
            <label style="margin-top:-10px; font-weight:500;">
                <input type="checkbox" id="request_estimate" name="request_estimate">
                <?php pll_e('En osaa arvioida tuntimäärää itse - pyydän tuntiarvion toimittajalta'); ?>
            </label>
        </div>

        <h3><?php echo pll__('Hinta'); ?></h3>
        <p id="hinta">0 €</p>

        <h3><?php echo pll__('Yhteystiedot'); ?></h3>
        <input type="text" name="nimi" placeholder="<?php echo pll__('Nimi'); ?>" required>
        <input type="text" name="yritys" placeholder="<?php echo pll__('Yritys / Organisaatio'); ?>">
        <input type="email" name="sahkoposti" placeholder="<?php echo pll__('Sähköposti'); ?>" required>
        <input type="tel" name="puhelin" placeholder="<?php echo pll__('Puhelinnumero'); ?>">

        <textarea name="lisatiedot" placeholder="<?php echo pll__('Lisätiedot / kommentit'); ?>"></textarea>

        <?php
        // Hae kielikohtaiset linkit Polylangin nykyisen kielen mukaan
        $current_lang = pll_current_language(); // 'fi' tai 'en'
        $terms_link = get_theme_mod("wp_quote_terms_link_$current_lang", '#');
        $privacy_link = get_theme_mod("wp_quote_privacy_link_$current_lang", '#');
        ?>

        <label>
            <input type="checkbox" id="terms" name="terms" required>
            <?php pll_e('Olen hyväksynyt Toimitusehdot:'); ?>
            <a href="<?php echo esc_url($terms_link); ?>" target="_blank"><?php pll_e('Lue Toimitusehdot'); ?></a>
        </label>

        <label>
            <input type="checkbox" id="privacy" name="privacy" required>
            <?php pll_e('Olen lukenut ja hyväksynyt Yksityisyydensuojaselosteen:'); ?>
            <a href="<?php echo esc_url($privacy_link); ?>"
                target="_blank"><?php pll_e('Lue Yksityisyydensuojaseloste'); ?></a>
        </label>

        <input type="submit" name="tarjous_lähetä" value="<?php echo pll__('Lähetä tarjouspyyntö'); ?>">
        <p id="form-message"></p>
    </form>

    <script>
        jQuery(document).ready(function ($) {
            const tilausTasoEl = $('#tilaus_taso');
            const tuntimaaraWrapper = $('#tuntimaara-wrapper');
            const tuntimaaraEl = $('#tuntimaara');
            const hintaEl = $('#hinta');
            const form = $('#wp_quote-form');
            const messageEl = $('#form-message');
            const estimateCheckbox = $('#request_estimate');

            function naytaTaiPiilotaTuntimaara() {
                if (tilausTasoEl.val() === 'Tuntityo') {
                    tuntimaaraWrapper.show();
                } else {
                    tuntimaaraWrapper.hide();
                    tuntimaaraEl.val('');
                }
                laskeHinta();
            }

            // Tuntiarvion pyyntö
            estimateCheckbox.on('change', function () {
                if (this.checked) {
                    tuntimaaraEl.val(0);
                    tuntimaaraEl.prop('disabled', true);
                    laskeHinta();
                } else {
                    tuntimaaraEl.prop('disabled', false);
                }
            });

            function laskeHinta() {
                let taso = tilausTasoEl.val();
                let tunti = parseFloat(tuntimaaraEl.val()) || 0;
                let hinta = 0;

                if (taso === 'Pronssi') hinta = 950
                else if (taso === 'Hopea') hinta = "1250-6500";
                else if (taso === 'Kulta') hinta = "<?php echo pll__('Saat tarjouksen'); ?>";
                else if (taso === 'Tuntityo') {
                    if (tunti === 0) {
                        hinta = '<?php echo pll__("Saat tarjouksen"); ?>';
                    } else if (tunti > 0) {
                        hinta = tunti * 95;
                    }
                }

                hintaEl.text(hinta + ' €');
            }

            naytaTaiPiilotaTuntimaara();
            tilausTasoEl.change(naytaTaiPiilotaTuntimaara);
            tuntimaaraEl.on('input', laskeHinta);

            // AJAX-lähetys
            form.submit(function (e) {
                e.preventDefault();
                // Tarkistetaan, että checkboxit on valittu
                if (!$('#terms').is(':checked') || !$('#privacy').is(':checked')) {
                    messageEl.text('<?php echo pll__("Sinun tulee hyväksyä Toimitusehdot ja Yksityisyydensuojaseloste ennen lähettämistä."); ?>');
                    return;
                }

                messageEl.text('<?php echo pll__('Sending...'); ?>');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: form.serialize() + '&action=wp_quote_send',
                    success: function (response) {
                        messageEl.text(response);
                        if (typeof window.i4wareTrackQuoteSuccess === 'function') {
                            window.i4wareTrackQuoteSuccess();
                        }
                        form[0].reset();
                        naytaTaiPiilotaTuntimaara();
                        laskeHinta();
                    },
                    error: function () {
                        messageEl.text('<?php echo pll__('Submission failed'); ?>');
                    }
                });
            });
        });
        (function ($) {
            function gaEvent(name, params = {}) {
                if (typeof gtag === 'function') {
                    gtag('event', name, params);
                }
            }

            // WP quote form
            $(document).on('focusin', '#wp_quote-form input, #wp_quote-form textarea, #wp_quote-form select', function () {
                const form = $('#wp_quote-form');
                if (!form.data('ga-started')) {
                    form.data('ga-started', true);
                    gaEvent('quote_form_start', {
                        form_name: 'wp_quote',
                        form_type: 'wordpress_quote'
                    });
                }
            });

            $(document).on('change', '#tilaus_taso', function () {
                gaEvent('quote_level_selected', {
                    form_name: 'wp_quote',
                    quote_level: $(this).val()
                });
            });

            $(document).on('submit', '#wp_quote-form', function () {
                gaEvent('quote_form_submit_attempt', {
                    form_name: 'wp_quote'
                });
            });

            // SaaS order form
            $(document).on('focusin', '#i4ware-saas-form input, #i4ware-saas-form textarea, #i4ware-saas-form select', function () {
                const form = $('#i4ware-saas-form');
                if (!form.data('ga-started')) {
                    form.data('ga-started', true);
                    gaEvent('saas_order_form_start', {
                        form_name: 'i4ware_saas_order',
                        form_type: 'saas_order'
                    });
                }
            });

            $(document).on('input', '#i4ware-saas-form input[name="estimated_hours"]', function () {
                const hours = parseInt($(this).val() || 0, 10);
                gaEvent('saas_hours_changed', {
                    form_name: 'i4ware_saas_order',
                    estimated_hours: hours,
                    value: hours * 95,
                    currency: 'EUR'
                });
            });

            window.i4wareTrackQuoteSuccess = function () {
                gaEvent('quote_request_success', {
                    form_name: 'wp_quote',
                    currency: 'EUR'
                });
            };

            window.i4wareTrackSaasOrderSuccess = function (value) {
                gaEvent('saas_order_request_success', {
                    form_name: 'i4ware_saas_order',
                    value: value || 0,
                    currency: 'EUR'
                });
            };
        })(jQuery);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('wp_quote', 'wp_quote_form_shortcode');

// AJAX-handler
add_action('wp_ajax_wp_quote_send', 'wp_quote_send_handler');
add_action('wp_ajax_nopriv_wp_quote_send', 'wp_quote_send_handler');

function wp_quote_send_handler()
{
    // Tarkistetaan, että checkboxit on valittu
    if (empty($_POST['terms']) || empty($_POST['privacy'])) {
        echo pll__('Sinun tulee hyväksyä Toimitusehdot ja Yksityisyydensuojaseloste ennen lähettämistä.');
        wp_die();
    }
    $nimi = sanitize_text_field($_POST['nimi']);
    $yritys = sanitize_text_field($_POST['yritys']);
    $sahkoposti = sanitize_email($_POST['sahkoposti']);
    $puhelin = sanitize_text_field($_POST['puhelin']);
    $tilaaja = sanitize_text_field($_POST['tilaaja']);
    $tilaus_taso = sanitize_text_field($_POST['tilaus_taso']);
    $projektikuvaus = sanitize_textarea_field($_POST['projektikuvaus']);
    $materiaali = isset($_POST['materiaali']) ? implode(', ', array_map('sanitize_text_field', $_POST['materiaali'])) : '';
    $materiaali_muu = sanitize_text_field($_POST['materiaali_muu']);
    $tuntimaara = intval($_POST['tuntimaara']);
    $estimate = isset($_POST['request_estimate']) ? sanitize_text_field($_POST['request_estimate']) : '';
    $lisatiedot = sanitize_textarea_field($_POST['lisatiedot']);

    $viesti = pll__('New order request received') . "\n\n"
        . pll__('Order details:') . "\n"
        . pll__('Tilaaja:') . " $tilaaja\n"
        . pll__('Tilauksen taso:') . " $tilaus_taso\n"
        . pll__('Projektikuvaus:') . " $projektikuvaus\n"
        . pll__('Materiaali:') . " $materiaali $materiaali_muu\n"
        . pll__('Tuntimäärä:') . " $tuntimaara\n\n"
        . pll__('Tuntiarvio:') . " $estimate\n\n"
        . pll__('Yhteystiedot:') . "\n"
        . pll__('Nimi:') . " $nimi\n"
        . pll__('Yritys:') . " $yritys\n"
        . pll__('Sähköposti:') . " $sahkoposti\n"
        . pll__('Puhelin:') . " $puhelin\n\n"
        . pll__('Lisätiedot:') . " $lisatiedot";

    $admin_email = get_option('admin_email');
    wp_mail($admin_email, pll__('New order request received'), $viesti);

    echo pll__('Order request sent successfully');
    wp_die();
}

function wp_quote_customizer_settings($wp_customize)
{
    $languages = array('fi' => 'Suomi', 'en' => 'English');

    foreach ($languages as $lang_code => $lang_name) {
        $wp_customize->add_section("wp_quote_links_section_$lang_code", array(
            'title' => __("WP Quote Links ($lang_name)", 'textdomain'),
            'priority' => 30,
        ));

        // Toimitusehdot
        $wp_customize->add_setting("wp_quote_terms_link_$lang_code", array(
            'default' => '#',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control("wp_quote_terms_link_$lang_code", array(
            'label' => __("Toimitusehdot linkki ($lang_name)", 'textdomain'),
            'section' => "wp_quote_links_section_$lang_code",
            'type' => 'url',
        ));

        // Yksityisyydensuojakäytäntö
        $wp_customize->add_setting("wp_quote_privacy_link_$lang_code", array(
            'default' => '#',
            'sanitize_callback' => 'esc_url_raw',
        ));
        $wp_customize->add_control("wp_quote_privacy_link_$lang_code", array(
            'label' => __("Yksityisyydensuojakäytännön linkki ($lang_name)", 'textdomain'),
            'section' => "wp_quote_links_section_$lang_code",
            'type' => 'url',
        ));
    }
}
add_action('customize_register', 'wp_quote_customizer_settings');

add_action('wp_footer', function () {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            function trackLinkClick(eventName, url, label) {
                if (typeof gtag === 'function') {
                    gtag('event', eventName, {
                        link_url: url,
                        link_text: label,
                        page_location: window.location.href,
                        transport_type: 'beacon'
                    });
                }
            }

            document.querySelectorAll('a').forEach(function (link) {

                link.addEventListener('click', function () {

                    const url = link.href || '';
                    const text = (link.innerText || '').trim();

                    // Atlassian Marketplace
                    if (
                        url.includes('marketplace.atlassian.com')
                    ) {
                        trackLinkClick(
                            'marketplace_click',
                            url,
                            text
                        );
                    }

                    // Documentation
                    if (
                        url.includes('/documentation') ||
                        url.includes('/docs') ||
                        text.toLowerCase().includes('documentation') ||
                        text.toLowerCase().includes('docs')
                    ) {
                        trackLinkClick(
                            'documentation_click',
                            url,
                            text
                        );
                    }

                    // Terms / Agreements / Privacy
                    if (
                        url.includes('/privacy') ||
                        url.includes('/terms') ||
                        url.includes('/agreement') ||
                        text.toLowerCase().includes('privacy') ||
                        text.toLowerCase().includes('terms') ||
                        text.toLowerCase().includes('sopimus') ||
                        text.toLowerCase().includes('toimitusehdot')
                    ) {
                        trackLinkClick(
                            'agreement_click',
                            url,
                            text
                        );
                    }

                });

            });

        });
    </script>
    <?php
});

add_action('wp_footer', function () {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            function sendCTAEvent(eventName, buttonText, url) {

                if (typeof gtag !== 'function') {
                    return;
                }

                gtag('event', eventName, {
                    cta_text: buttonText,
                    cta_url: url,
                    page_title: document.title,
                    page_location: window.location.href,
                    transport_type: 'beacon'
                });
            }

            document.querySelectorAll('a, button').forEach(function (el) {

                el.addEventListener('click', function () {

                    const text = (el.innerText || '').trim().toLowerCase();
                    const url = el.href || '';

                    // Marketplace CTA
                    if (
                        url.includes('marketplace.atlassian.com')
                    ) {
                        sendCTAEvent(
                            'timesheet_marketplace_click',
                            text,
                            url
                        );
                    }

                    // Documentation CTA
                    if (
                        text.includes('documentation') ||
                        text.includes('docs') ||
                        text.includes('api') ||
                        url.includes('/documentation') ||
                        url.includes('/docs')
                    ) {
                        sendCTAEvent(
                            'timesheet_docs_click',
                            text,
                            url
                        );
                    }

                    // Trial CTA
                    if (
                        text.includes('trial') ||
                        text.includes('free trial') ||
                        text.includes('try') ||
                        text.includes('kokeile')
                    ) {
                        sendCTAEvent(
                            'timesheet_trial_click',
                            text,
                            url
                        );
                    }

                    // Pricing CTA
                    if (
                        text.includes('pricing') ||
                        text.includes('price') ||
                        text.includes('hinta')
                    ) {
                        sendCTAEvent(
                            'timesheet_pricing_click',
                            text,
                            url
                        );
                    }

                    // Contact CTA
                    if (
                        text.includes('contact') ||
                        text.includes('support') ||
                        text.includes('sales') ||
                        text.includes('ota yhteyttä')
                    ) {
                        sendCTAEvent(
                            'timesheet_contact_click',
                            text,
                            url
                        );
                    }

                    // Agreement / Terms CTA
                    if (
                        text.includes('privacy') ||
                        text.includes('terms') ||
                        text.includes('agreement') ||
                        text.includes('toimitusehdot') ||
                        text.includes('yksityisyys')
                    ) {
                        sendCTAEvent(
                            'timesheet_agreement_click',
                            text,
                            url
                        );
                    }

                });

            });

        });
    </script>
    <?php
});

// i4ware SDK page shortcode — supports Polylang localizations and enqueues separate CSS
add_shortcode('i4ware_sdk_page', function() {
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';

    // Enqueue the separate CSS file
    wp_enqueue_style('i4ware-sdk-page', get_template_directory_uri() . '/assets/css/sdk-page.css', array(), '1.1');

    // Localized strings
    $data = array(
        'fi' => array(
            'eyebrow' => 'Low-code · Open Source · MIT',
            'hero_title' => 'Rakenna SaaS-tuotteesi viikoissa, ei vuosissa.',
            'hero_desc' => 'i4ware SDK on kevyt, modulaarinen ja skaalautuva low-code-alusta startupeille ja yrityksille. Yhdistä moderni React-käyttöliittymä ja Laravel-taustajärjestelmä valmiiksi tuotteeksi nopeasti ja tietoturvallisesti.',
            'request_quote' => 'Pyydä tarjous →',
            'github_code' => 'Lähdekoodi GitHubissa',
            'cooperation' => 'Yhteistyö',
            'coop_title' => 'Kehitetty yhteistyössä oppilaitosten kanssa.',
            'coop_lead' => 'i4ware SDK on syntynyt tiiviissä yhteistyössä TREDU:n ja Business College Helsingin Full‑Stack‑kehitysopiskelijoiden kanssa.',
            'coop_p1' => 'Tämä ratkaisu yhdistää oppilaitosten tuoreen osaamisen ja yritystason tarpeet. SDK sisältää valmiita käyttöliittymäkomponentteja, työkaluja ja automaatioita, jotka vähentävät kehitysaikaa merkittävästi.',
            'coop_p2' => 'Visuaaliset logiikkarakentajat ja valmiit integraatiot mahdollistavat nopean toimituksen aina startup‑projekteista yritystason järjestelmiin tinkimättä laajennettavuudesta.',
            'redhat_catalog' => '📦 Red Hat Catalog',
            'github_src' => '💻 GitHub Source Code',
            'features' => 'Keskeiset Ominaisuudet',
            'features_title' => 'Kaikki tarvittava nopeaan kehitykseen.',
            'features_lead' => 'Valmiit komponentit ja joustava arkkitehtuuri, joka skaalautuu MVP:stä yritystason järjestelmiin.',
            'feat1_title' => 'Low-code-arkkitehtuuri',
            'feat1_desc' => 'Nopea prototypointi ja välitön käyttöönotto ilman raskasta koodausta.',
            'feat2_title' => 'Valmiit komponentit',
            'feat2_desc' => 'Uudelleenkäytettävät UI-elementit, valmiit työnkulut ja selkeät tietomallit.',
            'feat3_title' => 'Laaja toiminnallisuus',
            'feat3_desc' => 'Käyttäjähallinta, tietoturva, dataintegraatiot ja raportointi sisäänrakennettuna.',
            'feat4_title' => 'Joustavat integraatiot',
            'feat4_desc' => 'Yhdistä saumattomasti API-rajapintoihin, taustajärjestelmiin ja pilveen.',
            'feat5_title' => 'Modulaarinen & skaalautuva',
            'feat5_desc' => 'Kasvata ratkaisuasi joustavasti MVP-vaiheesta aina yritystason tarpeisiin.',
            'feat6_title' => 'Monialustatuki',
            'feat6_desc' => 'Valmiiksi responisiiviset web-sovellukset ja PWA-tuki kaikille laitteille.',
            'comparison' => 'Vertailu',
            'matrix_title' => 'Ominaisuusmatriisi',
            'matrix_lead' => 'Miksi i4ware SDK on nykyaikainen ja kustannustehokas valinta.',
            'scroll_hint' => 'Pyyhkäise sivusuunnassa nähdäksesi koko taulukon →',
            'th_feature' => 'Ominaisuus',
            'th_db' => 'Tietokanta',
            'th_frontend' => 'Front-end',
            'th_backend' => 'Back-end',
            'th_http' => 'HTTP-palvelin',
            'th_schedule' => 'Ajastukset',
            'th_license' => 'Lisenssi',
            'foot_outdated' => '* Vanhentunut arkkitehtuuri',
            'foot_resources' => '** Vie paljon palvelinresursseja (RAM / CPU)',
            'foot_light' => '*** Kevyt palvelinkuormitus',
            'quote_request' => 'Tarjouspyyntö',
            'quote_title' => 'Tilaa projektisi — selkeällä hinnoittelulla.',
            'quote_desc' => 'Tuntihinta <strong>95 €</strong> (+alv). Tarkistamme tilaajayrityksen taloudellisen vakavaraisuuden <a href="https://www.asiakastieto.fi/" target="_blank" rel="noopener">Suomen Asiakastieto Oy:n</a> kautta ennen sopimuksen hyväksymistä.',
            'help_text' => '💡 Apua täyttöön? Soita +358 40 8200 491 tai meilaa osoitteeseen',
            'videos' => 'Katso Videot',
            'videos_title' => 'Näin tilaat — ja miksi se kannattaa.',
            'videos_lead' => 'Tutustu alustan filosofiaan, tilausprosessiin ja usein kysyttyihin kysymyksiin.',
            'vid1_cap' => 'SaaS-tuotteen tilaus askel askeleelta',
            'vid2_cap' => 'UKK — Usein kysytyt kysymykset',
            'vid3_cap' => 'Alustan kehitysfilosofia & perusteet',
            'todo_link' => '📋 Tiimin To-Do',
            'cv_link' => '👤 Matin CV',
        ),
        'en' => array(
            'eyebrow' => 'Low-code · Open Source · MIT',
            'hero_title' => 'Build your SaaS product in weeks, not years.',
            'hero_desc' => 'i4ware SDK is a lightweight, modular, and scalable low-code platform for startups and enterprises. Combine a modern React frontend and a Laravel backend into a production-ready product quickly and securely.',
            'request_quote' => 'Request a quote →',
            'github_code' => 'Source Code on GitHub',
            'cooperation' => 'Collaboration',
            'coop_title' => 'Developed in collaboration with educational institutions.',
            'coop_lead' => 'i4ware SDK was born in close collaboration with Full‑Stack development students from TREDU and Business College Helsinki.',
            'coop_p1' => 'This solution combines the fresh expertise of educational institutions with enterprise-grade requirements. The SDK contains ready-to-use UI components, tools, and automations that significantly reduce development time.',
            'coop_p2' => 'Visual logic builders and ready-made integrations enable fast delivery from startup projects to enterprise-level systems without compromising scalability.',
            'redhat_catalog' => '📦 Red Hat Catalog',
            'github_src' => '💻 GitHub Source Code',
            'features' => 'Key Features',
            'features_title' => 'Everything you need for rapid development.',
            'features_lead' => 'Pre-built components and flexible architecture that scales from MVP to enterprise systems.',
            'feat1_title' => 'Low-code Architecture',
            'feat1_desc' => 'Fast prototyping and instant deployment without heavy coding.',
            'feat2_title' => 'Ready-made Components',
            'feat2_desc' => 'Reusable UI elements, pre-built workflows, and clean data models.',
            'feat3_title' => 'Broad Functionality',
            'feat3_desc' => 'User management, security, data integrations, and reporting built-in.',
            'feat4_title' => 'Flexible Integrations',
            'feat4_desc' => 'Connect seamlessly with APIs, backend systems, and the cloud.',
            'feat5_title' => 'Modular & Scalable',
            'feat5_desc' => 'Grow your solution flexibly from the MVP stage to enterprise needs.',
            'feat6_title' => 'Multi-platform Support',
            'feat6_desc' => 'Fully responsive web applications and PWA support for all devices.',
            'comparison' => 'Comparison',
            'matrix_title' => 'Feature Matrix',
            'matrix_lead' => 'Why i4ware SDK is a modern and cost-effective choice.',
            'scroll_hint' => 'Swipe horizontally to view the full table →',
            'th_feature' => 'Feature',
            'th_db' => 'Database',
            'th_frontend' => 'Frontend',
            'th_backend' => 'Backend',
            'th_http' => 'HTTP Server',
            'th_schedule' => 'Schedulers',
            'th_license' => 'License',
            'foot_outdated' => '* Outdated architecture',
            'foot_resources' => '** Consumes high server resources (RAM / CPU)',
            'foot_light' => '*** Light server load',
            'quote_request' => 'Request Quote',
            'quote_title' => 'Order your project — with transparent pricing.',
            'quote_desc' => 'Hourly rate <strong>95 €</strong> (+VAT). We verify the financial credit standing of the client company via <a href="https://www.asiakastieto.fi/" target="_blank" rel="noopener">Suomen Asiakastieto Oy</a> before agreement approval.',
            'help_text' => '💡 Need help filling in? Call +358 40 8200 491 or email us at',
            'videos' => 'Watch Videos',
            'videos_title' => 'How to order — and why it is worth it.',
            'videos_lead' => 'Learn more about the platform philosophy, ordering process, and frequently asked questions.',
            'vid1_cap' => 'SaaS product ordering step-by-step',
            'vid2_cap' => 'FAQ — Frequently Asked Questions',
            'vid3_cap' => 'Platform development philosophy & basics',
            'todo_link' => '📋 Team To-Do',
            'cv_link' => '👤 Matti\'s CV',
        )
    );

    $s = $data[$lang];

    ob_start();
    ?>
    <div class="ok-wrap">

        <!-- HERO -->
        <section class="ok-hero">
            <span class="ok-eyebrow"><?php echo esc_html($s['eyebrow']); ?></span>
            <h1><?php echo esc_html($s['hero_title']); ?></h1>
            <p><?php echo esc_html($s['hero_desc']); ?></p>
            <div class="ok-cta">
                <a class="ok-btn ok-btn-primary" href="#tarjous"><?php echo esc_html($s['request_quote']); ?></a>
                <a class="ok-btn ok-btn-ghost" href="https://github.com/foghorn-hash/i4ware_SDK" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                    </svg>
                    <?php echo esc_html($s['github_code']); ?>
                </a>
            </div>
            <div class="ok-links">
                <a class="ok-chip" href="https://antigravity.google/" target="_blank" rel="noopener">⚡ Antigravity</a>
                <a class="ok-chip" href="https://github.com/features/copilot" target="_blank" rel="noopener">🤖 GitHub Copilot</a>
                <a class="ok-chip" href="https://code.visualstudio.com/" target="_blank" rel="noopener">💻 VS Code</a>
            </div>
        </section>

        <!-- COOPERATION -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['cooperation']); ?></span>
                <h2><?php echo esc_html($s['coop_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['coop_lead']); ?></p>
            </div>

            <div class="ok-coop-grid">
                <div class="ok-coop-info">
                    <p><?php echo esc_html($s['coop_p1']); ?></p>
                    <p><?php echo esc_html($s['coop_p2']); ?></p>
                    <div class="ok-links" style="margin-top: 20px;">
                        <a class="ok-chip" href="https://catalog.redhat.com/en/software/container-stacks/detail/6559987a23420e5882e0f61c" target="_blank" rel="noopener">
                            <?php echo esc_html($s['redhat_catalog']); ?>
                        </a>
                        <a class="ok-chip" href="https://github.com/foghorn-hash/i4ware_SDK" target="_blank" rel="noopener">
                            <?php echo esc_html($s['github_src']); ?>
                        </a>
                    </div>
                </div>
                <div class="ok-coop-badge-container">
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        TREDU
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        Business College Helsinki
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        Oulu University of Applied Sciences (OAMK)
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        Vaasa University of Applied Sciences (VAMK)
                    </div>
                </div>
            </div>
        </section>

        <!-- FEATURES -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['features']); ?></span>
                <h2><?php echo esc_html($s['features_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['features_lead']); ?></p>
            </div>

            <div class="ok-grid">
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat1_title']); ?></h3>
                    <p><?php echo esc_html($s['feat1_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="9"></rect>
                            <rect x="14" y="3" width="7" height="5"></rect>
                            <rect x="14" y="12" width="7" height="9"></rect>
                            <rect x="3" y="16" width="7" height="5"></rect>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat2_title']); ?></h3>
                    <p><?php echo esc_html($s['feat2_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat3_title']); ?></h3>
                    <p><?php echo esc_html($s['feat3_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 3 21 3 21 8"></polyline>
                            <line x1="4" y1="20" x2="21" y2="3"></line>
                            <polyline points="21 16 21 21 16 21"></polyline>
                            <line x1="15" y1="15" x2="21" y2="21"></line>
                            <line x1="4" y1="4" x2="9" y2="9"></line>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat4_title']); ?></h3>
                    <p><?php echo esc_html($s['feat4_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                            <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                            <line x1="6" y1="6" x2="6.01" y2="6"></line>
                            <line x1="6" y1="18" x2="6.01" y2="18"></line>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat5_title']); ?></h3>
                    <p><?php echo esc_html($s['feat5_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect>
                            <line x1="12" y1="18" x2="12.01" y2="18"></line>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat6_title']); ?></h3>
                    <p><?php echo esc_html($s['feat6_desc']); ?></p>
                </div>
            </div>
        </section>

        <!-- MATRIX -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['comparison']); ?></span>
                <h2><?php echo esc_html($s['matrix_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['matrix_lead']); ?></p>
            </div>

            <div class="ok-table-scroll-hint">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 16 16 12 12 8"></polyline>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                <span><?php echo esc_html($s['scroll_hint']); ?></span>
            </div>

            <div class="ok-tablewrap">
                <table class="ok-table">
                    <thead>
                        <tr>
                            <th><?php echo esc_html($s['th_feature']); ?></th>
                            <th class="ok-col-us">i4ware SDK</th>
                            <th>WordPress</th>
                            <th>Drupal</th>
                            <th>JBoss Portal</th>
                            <th>Liferay Portal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo esc_html($s['th_db']); ?></td>
                            <td class="ok-col-us">MySQL, PostgreSQL, SQLite</td>
                            <td>MySQL</td>
                            <td>MySQL</td>
                            <td>Riippumaton</td>
                            <td>Riippumaton</td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html($s['th_frontend']); ?></td>
                            <td class="ok-col-us">React / TypeScript</td>
                            <td>HTML/CSS/jQuery *</td>
                            <td>HTML/CSS/JS *</td>
                            <td>HTML/CSS/JS *</td>
                            <td>HTML/CSS/JS *</td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html($s['th_backend']); ?></td>
                            <td class="ok-col-us">Laravel (PHP) ***</td>
                            <td>PHP ***</td>
                            <td>PHP ***</td>
                            <td>J2EE **</td>
                            <td>J2EE **</td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html($s['th_http']); ?></td>
                            <td class="ok-col-us">Apache 2</td>
                            <td>Apache 2</td>
                            <td>Apache 2</td>
                            <td>Apache / JBoss **</td>
                            <td>Apache / Tomcat **</td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html($s['th_schedule']); ?></td>
                            <td class="ok-col-us">Cron-tehtävät</td>
                            <td>Cron-tehtävät</td>
                            <td>Cron-tehtävät</td>
                            <td>Sisäänrakennettu</td>
                            <td>Sisäänrakennettu</td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html($s['th_license']); ?></td>
                            <td class="ok-col-us">MIT (Vapaa)</td>
                            <td>GNU GPL v2+</td>
                            <td>GNU GPL v2+</td>
                            <td>GPL / Kaupallinen</td>
                            <td>GPL / Kaupallinen</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="ok-foot">
                <span><?php echo esc_html($s['foot_outdated']); ?></span>
                <span><?php echo esc_html($s['foot_resources']); ?></span>
                <span><?php echo esc_html($s['foot_light']); ?></span>
            </div>
        </section>

        <!-- PRICING & ORDER FORM -->
        <section class="ok-section" id="tarjous">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['quote_request']); ?></span>
                <h2><?php echo wp_kses($s['quote_title'], array('strong' => array(), 'br' => array())); ?></h2>
                <p class="ok-lead">
                    <?php echo wp_kses($s['quote_desc'], array('strong' => array(), 'a' => array('href' => array(), 'target' => array(), 'rel' => array()))); ?>
                </p>
            </div>

            <div class="ok-form-area">
                <div class="ok-note" style="margin-bottom: 28px;">
                    <strong><?php echo wp_kses($s['help_text'], array('strong' => array())); ?></strong> <a href="mailto:matti.kiviharju@i4ware.fi">matti.kiviharju@i4ware.fi</a>.
                </div>
                <?php echo do_shortcode('[i4ware_saas_order_form]'); ?>
            </div>
        </section>

        <!-- VIDEOS & DOCS -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['videos']); ?></span>
                <h2><?php echo esc_html($s['videos_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['videos_lead']); ?></p>
            </div>

            <div class="ok-videos">
                <div class="ok-video-card">
                    <div class="ok-video">
                        <iframe src="https://www.youtube.com/embed/TIo-szPd4PI" title="Tilaa SaaS tuoteesi i4ware SDK low-code-alustalla" allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <p class="ok-video-cap"><?php echo esc_html($s['vid1_cap']); ?></p>
                </div>

                <div class="ok-video-card">
                    <div class="ok-video">
                        <iframe src="https://www.youtube.com/embed/gGYLYKyQ1Bc" title="SDK UKK #sdk #react #laravel" allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <p class="ok-video-cap"><?php echo esc_html($s['vid2_cap']); ?></p>
                </div>

                <div class="ok-video-card">
                    <div class="ok-video">
                        <iframe src="https://www.youtube.com/embed/5bzoizWG3aM" title="i4ware SDK low-code-alustan idea" allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                    </div>
                    <p class="ok-video-cap"><?php echo esc_html($s['vid3_cap']); ?></p>
                </div>
            </div>

            <div class="ok-links" style="margin-top: 32px">
                <a class="ok-chip" href="https://i4ware-sdk.atlassian.net/wiki/spaces/IS/overview" target="_blank" rel="noopener"><?php echo esc_html($s['todo_link']); ?></a>
                <a class="ok-chip" href="https://www.i4ware.fi/matti-kiviharjun-ansioluettelo/" target="_blank" rel="noopener"><?php echo esc_html($s['cv_link']); ?></a>
            </div>
        </section>

    </div>
    <?php
    return ob_get_clean();
});

// Vanilla JS/CSS Lightbox for Certificate Images in [i4ware_team] / [i4ware_team_members]
add_action('wp_footer', function() {
    ?>
    <style>
    /* i4ware Lightbox Styles */
    .i4ware-lightbox {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(10, 10, 15, 0.95);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    .i4ware-lightbox.active {
        opacity: 1;
        visibility: visible;
    }
    .i4ware-lightbox-content {
        max-width: 90%;
        max-height: 90%;
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .i4ware-lightbox-img {
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        transform: scale(0.9);
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .i4ware-lightbox.active .i4ware-lightbox-img {
        transform: scale(1);
    }
    .i4ware-lightbox-close {
        position: absolute;
        top: -48px;
        right: 0;
        color: #fff;
        font-size: 32px;
        cursor: pointer;
        background: none;
        border: none;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s, transform 0.2s;
    }
    .i4ware-lightbox-close:hover {
        color: #6366f1;
        transform: scale(1.1);
    }
    .i4ware-lightbox-caption {
        color: #94a3b8;
        margin-top: 16px;
        font-family: 'Outfit', sans-serif;
        font-size: 16px;
        text-align: center;
    }
    .certificate-container img {
        cursor: pointer;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Create Lightbox Markup dynamically
        const lightbox = document.createElement('div');
        lightbox.className = 'i4ware-lightbox';
        lightbox.setAttribute('role', 'dialog');
        lightbox.setAttribute('aria-modal', 'true');
        lightbox.innerHTML = `
            <div class="i4ware-lightbox-content">
                <button class="i4ware-lightbox-close" aria-label="Close">&times;</button>
                <img class="i4ware-lightbox-img" src="" alt="" />
                <div class="i4ware-lightbox-caption"></div>
            </div>
        `;
        document.body.appendChild(lightbox);

        const lightboxImg = lightbox.querySelector('.i4ware-lightbox-img');
        const lightboxClose = lightbox.querySelector('.i4ware-lightbox-close');
        const lightboxCaption = lightbox.querySelector('.i4ware-lightbox-caption');

        // Add click event for certificate images
        document.body.addEventListener('click', function(e) {
            const target = e.target;
            // Check if the clicked element is an image inside a certificate container
            if (target.closest('.certificate-container') && target.tagName === 'IMG') {
                const src = target.src;
                const alt = target.alt || '';
                
                // Set image source and caption
                lightboxImg.src = src;
                lightboxImg.alt = alt;
                lightboxCaption.textContent = alt;
                
                // Open lightbox
                lightbox.classList.add('active');
                document.body.style.overflow = 'hidden'; // prevent scrolling
            }
        });

        // Close lightbox
        function closeLightbox() {
            lightbox.classList.remove('active');
            document.body.style.overflow = '';
            setTimeout(() => {
                lightboxImg.src = '';
                lightboxImg.alt = '';
                lightboxCaption.textContent = '';
            }, 300);
        }

        lightboxClose.addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox || e.target.classList.contains('i4ware-lightbox-content')) {
                closeLightbox();
            }
        });

        // Close with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && lightbox.classList.contains('active')) {
                closeLightbox();
            }
        });
    });
    </script>
    <?php
});

?>