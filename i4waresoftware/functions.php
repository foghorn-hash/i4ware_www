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

    // Add Social Media text setting and control
    $wp_customize->add_setting("footer_social_text_$lang_code", array(
        'default'   => ($lang_code === 'fi') ? 'Seuraa meitä YouTubessa' : 'Follow us on YouTube',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control("footer_social_text_$lang_code", array(
        'label'    => __('Footer Social Media Text', 'i4waresoftware') . " ($lang_label)",
        'section'  => 'footer_section',
        'type'     => 'text',
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

    $wp_customize->add_section( 'i4ware_cta_section', [
        'title'    => __( 'CTA Painike', 'i4ware' ),
        'priority' => 30,
    ] );

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting( "i4ware_cta_url_$lang_code", [
            'default'   => '#',
            'transport' => 'refresh',
            'sanitize_callback' => 'esc_url_raw',
        ] );
        $wp_customize->add_control( "i4ware_cta_url_control_$lang_code", [
            'label'    => __( 'CTA Painikkeen URL', 'i4ware' ) . " ($lang_label)",
            'section'  => 'i4ware_cta_section',
            'settings' => "i4ware_cta_url_$lang_code",
            'type'     => 'url',
        ] );
    }

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting( "i4ware_cta_saas_url_$lang_code", [
            'default'   => '#',
            'transport' => 'refresh',
            'sanitize_callback' => 'esc_url_raw',
        ] );
        $wp_customize->add_control( "i4ware_cta_saas_url_control_$lang_code", [
            'label'    => __( 'SaaS CTA Painikkeen URL', 'i4ware' ) . " ($lang_label)",
            'section'  => 'i4ware_cta_section',
            'settings' => "i4ware_cta_saas_url_$lang_code",
            'type'     => 'url',
        ] );
    }

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting( "i4ware_cta_text_$lang_code", [
            'default'   => ($lang_code === 'fi') ? __( 'Pyydä tarjous', 'i4ware' ) : __( 'Request a quote', 'i4ware' ),
            'transport' => 'refresh',
        ] );
        $wp_customize->add_control( "i4ware_cta_text_control_$lang_code", [
            'label'    => __( 'CTA Button Text', 'i4ware' ) . " ($lang_label)",
            'section'  => 'i4ware_cta_section',
            'settings' => "i4ware_cta_text_$lang_code",
            'type'     => 'text',
        ] );
}

// CTA: headline & description per language
foreach ($languages as $lang_code => $lang_label) {
    $wp_customize->add_setting( "i4ware_cta_headline_$lang_code", [
        'default'   => ($lang_code === 'fi') ? 'SaaS-tuoteideasi tuotantoon i4ware SDK:lla kustannustehokkaasti' : 'Get your SaaS product to market cost-effectively with i4ware SDK',
        'transport' => 'refresh',
    ] );
    $wp_customize->add_control( "i4ware_cta_headline_control_$lang_code", [
        'label'    => __( 'CTA Headline', 'i4ware' ) . " ($lang_label)",
        'section'  => 'i4ware_cta_section',
        'settings' => "i4ware_cta_headline_$lang_code",
        'type'     => 'text',
    ] );

    $wp_customize->add_setting( "i4ware_cta_desc_$lang_code", [
        'default'   => ($lang_code === 'fi') ? 'Rakennamme MVP- ja SaaS-ratkaisut puolestasi. Low-code i4ware SDK ja AI-avusteinen kehitys mahdollistavat nopean ja kustannustehokkaan toteutuksen.' : 'We build MVP and SaaS solutions for you. Low-code i4ware SDK and AI-assisted development enable fast and cost-effective delivery.',
        'transport' => 'refresh',
    ] );
    $wp_customize->add_control( "i4ware_cta_desc_control_$lang_code", [
        'label'    => __( 'CTA Description', 'i4ware' ) . " ($lang_label)",
        'section'  => 'i4ware_cta_section',
        'settings' => "i4ware_cta_desc_$lang_code",
        'type'     => 'textarea',
    ] );
}

// Video settings
$wp_customize->add_section( 'i4ware_video_section', [
    'title'    => __( 'Embedded Video', 'i4ware' ),
    'priority' => 35,
] );

$wp_customize->add_setting( 'i4ware_video_url', [
    'default' => '',
    'transport' => 'refresh',
    'sanitize_callback' => 'esc_url_raw',
] );
$wp_customize->add_control( 'i4ware_video_url_control', [
    'label' => __( 'YouTube URL (watch or share link or embed URL)', 'i4ware' ),
    'section' => 'i4ware_video_section',
    'settings' => 'i4ware_video_url',
    'type' => 'url',
] );

$wp_customize->add_setting( 'i4ware_video_blur', [
    'default' => false,
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_text_field',
] );
$wp_customize->add_control( 'i4ware_video_blur_control', [
    'label' => __( 'Enable blur overlay on video', 'i4ware' ),
    'section' => 'i4ware_video_section',
    'settings' => 'i4ware_video_blur',
    'type' => 'checkbox',
] );

foreach ($languages as $lang_code => $lang_label) {
    $wp_customize->add_setting( "i4ware_video_overlay_text_$lang_code", [
        'default' => '',
        'transport' => 'refresh',
    ] );
    $wp_customize->add_control( "i4ware_video_overlay_text_control_$lang_code", [
        'label' => __( 'Video overlay text', 'i4ware' ) . " ($lang_label)",
        'section' => 'i4ware_video_section',
        'settings' => "i4ware_video_overlay_text_$lang_code",
        'type' => 'text',
    ] );
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

function i4ware_register_customer_logo_cpt() {
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

function i4ware_customers_shortcode() {
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

function i4waresoftware_widgets_init() {
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
        register_sidebar( array(
            'name'          => sprintf( __( 'Sidebar 1 (%s)', 'i4waresoftware' ), $lang_label ),
            'id'            => 'sidebar-1-' . $lang_code,
            'description'   => sprintf( __( 'Add widgets here for %s.', 'i4waresoftware' ), $lang_label ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ) );
    }

    foreach ($languages as $lang_code => $lang_label) {
        register_sidebar( array(
            'name'          => sprintf( __( 'Sidebar 2 (%s)', 'i4waresoftware' ), $lang_label ),
            'id'            => 'sidebar-2-' . $lang_code,
            'description'   => sprintf( __( 'Add widgets here for %s.', 'i4waresoftware' ), $lang_label ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ) );
    }

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

// === [ Shortcode: i4ware_pricing ] — Polylang-ready =================================
if (!function_exists('i4w_t')) {
  function i4w_t($text, $context = 'i4ware_pricing') {
    if (function_exists('pll__')) return pll__($text);
    return __($text, 'i4ware'); // fallback
  }
}

add_action('init', function () {
  if (!function_exists('pll_register_string')) return;

  // Otsikot & peruskuvaukset
  $strings = [
    'Basic','Premium','Vaativampi kokonaisuus',
    'Kaikki, mitä pienempi yritys tai aloitteleva yritys tarvitsee menestyäkseen.',
    'Kaikki, mitä kasvuhaluiset pk-yritykset ja startupit tarvitsevat menestyäkseen.',
    'Kerro meille lisää projektistasi niin annamme tarjouksen vaativastakin projektista.',
    'Ota yhteyttä','Kysy tarjous','Kysy tarjous',
    '+ alv 25,5%','Kysy tarjous'
  ];

  // Featuret (pidä listojen kirjaimet identtisinä shortcode-oletuksiin)
  $features = [
    'Visuaalisen ilmeen suunnittelu',
    'Sivuston rakennus (WordPress)',
    '1 viikon toimitus','1–5 sivua','Lisäosat',
    '1–2 viikon toimitus','6–20 sivua',
    '2–4 viikon toimitus','Rajaton määrä sivuja',
  ];

  foreach (array_merge($strings, $features) as $s) {
    pll_register_string('i4ware_pricing', $s, 'i4ware_pricing');
  }
});

if (!function_exists('i4ware_pricing_shortcode')) {
  function i4ware_pricing_shortcode($atts) {
    // Normalisoi attribuutit: nollaa oudot välit ja lainausmerkit
    $atts = is_array($atts) ? $atts : [];
    $norm_keys = [];
    $norm_vals = [];
    foreach ($atts as $k => $v) {
    $k = trim( wp_strip_all_tags( (string)$k ) );
    $v = trim( wp_strip_all_tags( (string)$v ) );
    // Muunna “älykkäät” lainausmerkit tavallisiksi
    $v = str_replace(['“','”','„','‟','’','‘'], '"', $v);
    $norm_keys[] = $k;
    $norm_vals[] = $v;
    }
    $atts = array_combine($norm_keys, $norm_vals);

    // Tee avaimista varmuuden vuoksi pienaakkoset
    $atts = array_change_key_case($atts, CASE_LOWER);

    $a = shortcode_atts([
      'currency'       => '€',
      'vat_note'       => i4w_t('+ alv 25,5%'),
      // Linkitys: suositus käyttää contact_page_id:tä (WP-sivun ID)
      'contact_page_id'=> '',
      'contact_url'    => '/ota-yhteytta/',

      // Hinnat
      'basic_price'    => '950',
      'premium_min'    => '1250',
      'premium_max'    => '6500',

      // Napit
      'basic_btn'      => i4w_t('Ota yhteyttä'),
      'premium_btn'    => i4w_t('Kysy tarjous'),
      'enterprise_btn' => i4w_t('Kysy tarjous'),

      // Otsikot & kuvaukset
      'basic_title'    => i4w_t('Basic'),
      'basic_desc'     => i4w_t('Kaikki, mitä pienempi yritys tai aloitteleva yritys tarvitsee menestyäkseen.'),
      'premium_title'  => i4w_t('Premium'),
      'premium_desc'   => i4w_t('Kaikki, mitä kasvuhaluiset pk-yritykset ja startupit tarvitsevat menestyäkseen.'),
      'enterprise_title'=> i4w_t('Vaativampi kokonaisuus'),
      'enterprise_desc' => i4w_t('Kerro meille lisää projektistasi niin annamme tarjouksen vaativastakin projektista.'),

      // Feature-listat
      'features_basic' =>
        'Visuaalisen ilmeen suunnittelu,Sivuston rakennus (WordPress),1 viikon toimitus,1–5 sivua,Lisäosat',
      'features_premium' =>
        'Visuaalisen ilmeen suunnittelu,Sivuston rakennus (WordPress),1–2 viikon toimitus,6–20 sivua,Lisäosat',
      'features_enterprise' =>
        'Visuaalisen ilmeen suunnittelu,Sivuston rakennus (WordPress),2–4 viikon toimitus,Rajaton määrä sivuja,Lisäosat',
    ], $atts, 'i4ware_pricing');

    // Yritä käyttää contact_page_id:tä ja kielen mukaista käännösversiota
    $url = '';
    if (!empty($a['contact_page_id'])) {
      $page_id = intval($a['contact_page_id']);
      if (function_exists('pll_get_post')) {
        $tr_id = pll_get_post($page_id);
        if ($tr_id) $page_id = $tr_id;
      }
      $url = get_permalink($page_id);
    }
    if (!$url) $url = esc_url($a['contact_url']);

    // Featuret -> käännä jokainen yksitellen (jos rekisteröity)
    $mk = function ($csv) {
      $arr = array_map('trim', explode(',', $csv));
      return array_map(function($s){ return i4w_t($s); }, $arr);
    };
    $feat = [
      'basic'      => $mk($a['features_basic']),
      'premium'    => $mk($a['features_premium']),
      'enterprise' => $mk($a['features_enterprise']),
    ];

    $currency = esc_html($a['currency']);
    $vat      = esc_html($a['vat_note']);

    ob_start(); ?>
    <div class="i4w-pricing">
      <!-- Basic -->
      <div class="i4w-card">
        <div class="i4w-badge"><?php echo esc_html($a['basic_title']); ?></div>
        <div class="i4w-price">
          <span class="i4w-num"><?php echo esc_html($a['basic_price']); ?></span> <span class="i4w-cur"><?php echo $currency; ?></span>
        </div>
        <div class="i4w-vat"><?php echo $vat; ?></div>
        <a class="i4w-button" href="<?php echo esc_url($url); ?>"><?php echo esc_html($a['basic_btn']); ?></a>
        <ul class="i4w-features">
          <?php foreach ($feat['basic'] as $f): ?><li><?php echo esc_html($f); ?></li><?php endforeach; ?>
        </ul>
      </div>

      <!-- Premium -->
      <div class="i4w-card i4w-featured">
        <div class="i4w-badge"><?php echo esc_html($a['premium_title']); ?></div>
        <div class="i4w-price">
          <span class="i4w-num"><?php echo esc_html($a['premium_min']); ?> <?php echo $currency; ?>–<?php echo esc_html($a['premium_max']); ?> <?php echo $currency; ?></span>
        </div>
        <div class="i4w-vat"><?php echo $vat; ?></div>
        <a class="i4w-button" href="<?php echo esc_url($url); ?>"><?php echo esc_html($a['premium_btn']); ?></a>
        <ul class="i4w-features">
          <?php foreach ($feat['premium'] as $f): ?><li><?php echo esc_html($f); ?></li><?php endforeach; ?>
        </ul>
      </div>

      <!-- Enterprise -->
      <div class="i4w-card">
        <div class="i4w-badge"><?php echo esc_html($a['enterprise_title']); ?></div>
        <div class="i4w-price"><span class="i4w-num i4w-ask"><?php echo esc_html(i4w_t('Kysy tarjous')); ?></span></div>
        <div class="i4w-vat"><?php echo $vat; ?></div>
        <a class="i4w-button" href="<?php echo esc_url($url); ?>"><?php echo esc_html($a['enterprise_btn']); ?></a>
        <ul class="i4w-features">
          <?php foreach ($feat['enterprise'] as $f): ?><li><?php echo esc_html($f); ?></li><?php endforeach; ?>
        </ul>
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
function tk_render_mega_menu( $args = [] ) {
    $defaults = [
        'location'   => 'tk_mega',
        'logo_html'  => '<span class="tk-logo-dot" aria-hidden="true"></span>',
        'show_search'=> true,
    ];
    $args = wp_parse_args( $args, $defaults );
    // Haetaan CTA arvot Customizerista
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();
    } else {
        $lang = 'fi';
    }
    $cta_text = get_theme_mod( "i4ware_cta_text_$lang", __( 'Pyydä tarjous', 'i4ware' ) );
    $cta_url  = get_theme_mod( 'i4ware_cta_url_'.$lang, '#' );
    $locations = get_nav_menu_locations();
    if ( empty($locations[ $args['location'] ]) ) {
        // Ei valikkoa määritelty – voidaan silti tulostaa kehyksenä
        echo '<nav class="tk-nav"><div class="tk-bar"><a class="tk-logo" href="/">'.$args['logo_html'].'</a>';
        if ($args['show_search']) echo '<form class="tk-search" role="search">'.get_search_form(['echo'=>false]).'</form>';
        echo '<button class="tk-menu-btn" aria-expanded="false" aria-controls="tk-mega" id="tkMenuBtn"><span class="tk-menu-btn__label">Menu</span><span class="tk-menu-btn__icon" aria-hidden="true"></span></button></div><div class="tk-mega" id="tk-mega" hidden><div class="tk-mega__grid"><section class="tk-col"><h3>'.esc_html__('Setup','your-textdomain').'</h3><a href="'.esc_url(admin_url('nav-menus.php')).'">'.esc_html__('Create “TK Mega Menu” in Appearance → Menus','your-textdomain').'</a></section></div></div></nav>';
        return;
    }

    $menu_id = $locations[ $args['location'] ];
    $items   = wp_get_nav_menu_items( $menu_id, [ 'update_post_term_cache' => false ] );
    if ( empty( $items ) ) return;

    // Järjestä vanhempi->lapset -rakenteeksi
    usort($items, function($a,$b){ return (int)$a->menu_order <=> (int)$b->menu_order; });

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
    echo '      <a href="' . esc_url( $cta_url ) . '" class="cta-button">' . esc_html( $cta_text ) . '</a>';
    echo '    </div>';
    echo '    <button class="tk-menu-btn" aria-expanded="false" aria-controls="tk-mega" id="tkMenuBtn"><span class="tk-menu-btn__label">'.esc_html__('Menu','i4ware').'</span><span class="tk-menu-btn__icon" aria-hidden="true"></span></button>';
    echo '    </div>';
    echo '  </div>';

    echo '  <div class="tk-mega" id="tk-mega" hidden>';
    echo '    <div class="tk-mega__grid">';

    foreach ($parents as $pid => $parent) {
        echo '<section class="tk-col">';
        echo '  <h3>'.esc_html( $parent->title ).'</h3>';

        if ( ! empty( $children[$pid] ) ) {
            foreach ( $children[$pid] as $child ) {
                $title = $child->title ?: $child->post_title;
                $url   = $child->url ?: '#';
                $target= $child->target ? ' target="'.esc_attr($child->target).'" rel="noopener"' : '';
                echo '  <a href="'.esc_url($url).'"'.$target.'>'.esc_html($title).'</a>';
            }
        } else {
            // Jos vanhemmalla on linkki, tulosta se yhtenä kohteena
            if ( !empty($parent->url) ) {
                echo '  <a href="'.esc_url($parent->url).'">'.esc_html($parent->title).'</a>';
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
    'methods'  => 'POST',
    'callback' => 'jaf_handle_submit',
    'permission_callback' => '__return_true',
  ]);
});

function jaf_handle_submit( WP_REST_Request $req ) {
  $token  = sanitize_text_field( $req->get_param('recaptcha') );
  if ( empty($token) ) {
    return new WP_Error('missing_token', 'Missing reCAPTCHA token', ['status' => 400]);
  }

  $secret = '6Ldw-1ArAAAAAK2fVIjizyeobp3Ki0c0iVYBug-m'; // keep secret on server only

  $resp = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
    'body' => [
      'secret'   => $secret,
      'response' => $token,
      'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ],
    'timeout' => 10,
  ]);

  if ( is_wp_error($resp) ) {
    return new WP_Error('recaptcha_http', $resp->get_error_message(), ['status' => 500]);
  }

  $data = json_decode( wp_remote_retrieve_body($resp), true );
  if ( empty($data['success']) ) {
    return new WP_Error('recaptcha_failed', 'reCAPTCHA failed', ['status' => 403, 'details' => $data]);
  }

  // TODO: handle and persist $req->get_json_params() (form data)
  return [ 'ok' => true ];
}

// functions.php

function i4ware_add_cta_button() {
    $cta_text = get_theme_mod( 'i4ware_cta_text', __( 'Pyydä tarjous', 'i4ware' ) );
    $cta_url  = get_theme_mod( 'i4ware_cta_url', '#' );

    echo '<div class="header-cta"><a class="cta-button" href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_text ) . '</a></div>';
}

// Lisää CTA painike wp_nav_menu() -funktion eteen
add_action( 'wp_nav_menu_items', function( $items, $args ) {
    if ( $args->theme_location == 'primary' ) {
        $cta_text = get_theme_mod( 'i4ware_cta_text', __( 'Pyydä tarjous', 'i4ware' ) );
        $cta_url  = get_theme_mod( 'i4ware_cta_url', '#' );

        $cta_html = '<li class="menu-item cta-button"><a href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_text ) . '</a></li>';

        // Lisää CTA linkki vasemmalle
        return $cta_html . $items;
    }
    return $items;
}, 10, 2 );

// i4ware CTA shortcode — supports Polylang
function i4ware_cta_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'url'    => '#',     // default fallback URL
        'url_en' => '',      // optional language specific URL for English
        'url_fi' => '',      // optional language specific URL for Finnish
        'class'  => '',      // extra classes
    ), $atts, 'i4ware_cta' );

    // detect language (Polylang if present, fallback to WP locale)
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language();
    } else {
        $lang = substr( get_locale(), 0, 2 );
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
      'headline' => get_theme_mod( "i4ware_cta_headline_{$lang}", $defaults[$lang]['headline'] ?? $defaults['en']['headline'] ),
      'desc'     => get_theme_mod( "i4ware_cta_desc_{$lang}", $defaults[$lang]['desc'] ?? $defaults['en']['desc'] ),
      'button'   => get_theme_mod( "i4ware_cta_text_{$lang}", $defaults[$lang]['button'] ?? $defaults['en']['button'] ),
    );

    // choose best URL (language-specific > attribute url > fallback url)
    $url = $atts['url'];
    if ( $lang === 'en' && !empty( $atts['url_en'] ) ) $url = $atts['url_en'];
    if ( $lang === 'fi' && !empty( $atts['url_fi'] ) ) $url = $atts['url_fi'];

    // markup (keeps styling minimal and self-contained)
    $html  = '<aside class="i4ware-cta-box ' . esc_attr( $atts['class'] ) . '" role="region" aria-label="' . esc_attr__( 'CTA', 'i4ware' ) . '">';
    $html .= '<h3>' . esc_html( $t['headline'] ) . '</h3>';
    $html .= '<p>' . esc_html( $t['desc'] ) . '</p>'; 
    $html .= '<a href="' . esc_url( $url ) . '" class="i4ware-cta-btn" aria-label="' . esc_attr( $t['button'] ) . '">' . esc_html( $t['button'] ) . '</a>';
    $html .= '</aside>';

    return $html;
}
add_shortcode( 'i4ware_cta', 'i4ware_cta_shortcode' );

// Shortcode to output embedded YouTube video with optional blur and per-language overlay text
function i4ware_video_shortcode( $atts ) {
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language();
    } else {
        $lang = substr( get_locale(), 0, 2 );
    }

    $video_url = get_theme_mod('i4ware_video_url', '');
    if ( empty( $video_url ) ) return '';

    // Normalize common YouTube URL formats to embed URL
    if ( strpos( $video_url, 'watch?v=' ) !== false ) {
        $embed = str_replace( 'watch?v=', 'embed/', $video_url );
    } elseif ( strpos( $video_url, 'youtu.be/' ) !== false ) {
        preg_match('#youtu\.be/([A-Za-z0-9_-]+)#', $video_url, $m);
        $id = $m[1] ?? '';
        $embed = $id ? 'https://www.youtube.com/embed/' . $id : esc_url( $video_url );
    } else {
        $embed = $video_url;
    }

    $blur = get_theme_mod('i4ware_video_blur', false);
    $overlay_text = get_theme_mod("i4ware_video_overlay_text_{$lang}", '');

    $wrap_classes = 'i4ware-video-wrap' . ( $blur ? ' blur' : '' );

    $html = '<div class="' . esc_attr( $wrap_classes ) . '" style="position:relative;max-width:900px;margin:16px auto;border-radius:8px;overflow:hidden;">';
    $html .= '<iframe src="' . esc_url( $embed ) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen style="width:100%;height:420px;border:0;display:block;"></iframe>';
    if ( $overlay_text ) {
        $html .= '<div class="i4ware-video-overlay" style="position:absolute;left:16px;top:16px;color:#fff;font-weight:700;text-shadow:0 2px 8px rgba(0,0,0,0.6);">' . esc_html( $overlay_text ) . '</div>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode( 'i4ware_video', 'i4ware_video_shortcode' );

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

    // Lähetä-painike
    pll_register_string('i4ware', 'Lähetä tarjouspyyntö');
});

/**
 * Plugin Name: i4ware SaaS Order Form
 * Description: i4ware SDK low-code SaaS tilauslomake (FI/EN, Polylang)
 */

if (!defined('ABSPATH')) exit;

function i4ware_saas_order_form_shortcode() {

    // Oletustuntihinta (muokkaa tarvittaessa)
    $hourly_rate = 95;

    ob_start();
    ?>
    <form id="i4ware-saas-form" class="i4ware-saas-form">
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
        <input type="number" name="estimated_hours" min="1" required
               oninput="i4wareCalcPrice(this.value)">

        <div class="i4ware-pricing">
            <p><?php pll_e('Hourly rate'); ?>:
                <strong>95 €</strong>
            </p>
            <p><?php pll_e('Estimated total price'); ?>:
                <strong><span id="i4ware-total-price">0</span> €</strong>
            </p>
        </div>

        <!-- Financing -->
        <label><?php pll_e('Financing'); ?></label>

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

        <!-- Company info -->
        <label><?php pll_e('Company founding year'); ?></label>
        <input type="number" name="company_founding_year" min="1800" max="<?php echo date('Y'); ?>">

        <!-- Contact details -->
        <h3 style="margin-top:30px;"><?php pll_e('Orderer contact details'); ?></h3>

        <label><?php pll_e('Company name'); ?></label>
        <input type="text" name="company_name" required>

        <label><?php pll_e('Contact person'); ?></label>
        <input type="text" name="contact_person" required>

        <label><?php pll_e('Email address'); ?></label>
        <input type="email" name="email" required>

        <label><?php pll_e('Phone number'); ?></label>
        <input type="tel" name="phone">

        <button type="submit">
            <?php pll_e('Send order request'); ?>
        </button>

        <div id="i4ware-form-message"></div>
    </form>

    <script>
        function i4wareCalcPrice(hours) {
            const rate = <?php echo (int)$hourly_rate; ?>;
            document.getElementById('i4ware-total-price').innerText =
                hours ? (hours * rate) : 0;
        }

        jQuery(document).ready(function($){
            // Piilotetaan/ näytetään rahoitusdetails
            $('#external_funding_checkbox').on('change', function(){
                $('#external_funding_details').toggle(this.checked);
            });

            // AJAX-lähetys
            $('#i4ware-saas-form').on('submit', function(e){
                e.preventDefault();
                const form = $(this);
                const submitBtn = form.find('button');
                const messageEl = $('#i4ware-form-message');

                const textSending = '<?php echo esc_js(pll__("Sending...")); ?>';
                const textSuccess = '<?php echo esc_js(pll__("Order request sent successfully")); ?>';
                const textFailed = '<?php echo esc_js(pll__("Submission failed")); ?>';
                const textServerError = '<?php echo esc_js(pll__("Server error")); ?>';
                const textSendOrder = '<?php echo esc_js(pll__("Send order request")); ?>';

                submitBtn.prop('disabled', true).text(textSending);

                $.ajax({
                    url: '<?php echo admin_url("admin-ajax.php"); ?>',
                    type: 'POST',
                    data: form.serialize(),
                    dataType: 'json',
                    success: function(response){
                        if(response.success){
                            messageEl.text(textSuccess);
                            form[0].reset();
                            $('#external_funding_details').hide();
                            i4wareCalcPrice(0);
                        } else {
                            messageEl.text(response.data || textFailed);
                        }
                    },
                    error: function(){
                        messageEl.text(textServerError);
                    },
                    complete: function(){
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
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
            box-shadow: 0 0 0 2px rgba(77,163,255,0.25);
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
            box-shadow: 0 10px 25px rgba(77,163,255,0.35);
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

function i4ware_submit_order() {

    // Nonce check
    if (
        !isset($_POST['i4ware_nonce']) ||
        !wp_verify_nonce($_POST['i4ware_nonce'], 'i4ware_saas_order')
    ) {
        wp_send_json_error('Invalid security token');
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

// Lyhytkoodi wp_quote AJAX-lähetyksellä
function wp_quote_form_shortcode() {
    ob_start();
    ?>
    <form id="wp_quote-form" method="post">
        <h3><?php echo pll__('Asiakas-/Projektityyppi'); ?></h3>
        <label><input type="radio" name="tilaaja" value="Alihankinta" required> <?php echo pll__('Alihankinta'); ?></label>
        <label><input type="radio" name="tilaaja" value="Suora loppuasiakas" required> <?php echo pll__('Suora loppuasiakas'); ?></label>

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
        <label><input type="checkbox" name="materiaali[]" value="PSD"> <?php echo pll__('PSD (Photoshop, layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="XD"> <?php echo pll__('XD (Adobe XD, layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="Sketch"> <?php echo pll__('Sketch (layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="Figma"> <?php echo pll__('Figma (komponentit, layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="InVision"> <?php echo pll__('InVision (komponentit, layereilla)'); ?></label>
        <label><input type="checkbox" name="materiaali[]" value="HTML"> <?php echo pll__('Staattinen HTML-kooste'); ?></label>
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

        <input type="submit" name="tarjous_lähetä" value="<?php echo pll__('Lähetä tarjouspyyntö'); ?>">
        <p id="form-message"></p>
    </form>

    <script>
    jQuery(document).ready(function($){
        const tilausTasoEl = $('#tilaus_taso');
        const tuntimaaraWrapper = $('#tuntimaara-wrapper');
        const tuntimaaraEl = $('#tuntimaara');
        const hintaEl = $('#hinta');
        const form = $('#wp_quote-form');
        const messageEl = $('#form-message');
        const estimateCheckbox = $('#request_estimate');

        function naytaTaiPiilotaTuntimaara() {
            if(tilausTasoEl.val() === 'Tuntityo') {
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

            if(taso === 'Pronssi') hinta = 500;
            else if(taso === 'Hopea') hinta = "800-4000";
            else if(taso === 'Kulta') hinta = "<?php echo pll__('Saat tarjouksen'); ?>";
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
        form.submit(function(e){
            e.preventDefault();
            messageEl.text('<?php echo pll__('Sending...'); ?>');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: form.serialize() + '&action=wp_quote_send',
                success: function(response){
                    messageEl.text(response);
                    form[0].reset();
                    naytaTaiPiilotaTuntimaara();
                    laskeHinta();
                },
                error: function(){
                    messageEl.text('<?php echo pll__('Submission failed'); ?>');
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('wp_quote', 'wp_quote_form_shortcode');

// AJAX-handler
add_action('wp_ajax_wp_quote_send', 'wp_quote_send_handler');
add_action('wp_ajax_nopriv_wp_quote_send', 'wp_quote_send_handler');

function wp_quote_send_handler() {
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

?>