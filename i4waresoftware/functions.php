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
        <p class="i4w-desc"><?php echo esc_html($a['basic_desc']); ?></p>
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
        <p class="i4w-desc"><?php echo esc_html($a['premium_desc']); ?></p>
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
        <p class="i4w-desc"><?php echo esc_html($a['enterprise_desc']); ?></p>
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

?>