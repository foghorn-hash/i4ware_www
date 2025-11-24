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

?>