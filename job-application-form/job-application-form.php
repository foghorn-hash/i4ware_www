<?php
/**
 * Plugin Name: Job Application Form (React)
 * Description: Shortcode [job_application_form] renders a React job application form.
 * Version:     1.0.0
 * Author:      i4ware Software
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

define( 'JAF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'JAF_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'JAF_PLUGIN_VER', '1.0.0' );

class JAF_Plugin {
  public function __construct() {
    add_shortcode( 'job_application_form', [ $this, 'shortcode' ] );
    add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
  }

  /**
   * Register built assets (React bundle + CSS).
   */
  public function register_assets() {
    // Päivitä polut vastaamaan buildiasi
    $google  = 'https://www.google.com/recaptcha/api.js?render=explicit';
    $css_rel = 'css/main.2a240728.css';
    $js_rel  = 'js/main.364a50a4.js';
    $plugin_url = plugin_dir_url( __FILE__ ) . 'static/';
    $css_path = JAF_PLUGIN_PATH . $css_rel;
    $js_path  = JAF_PLUGIN_PATH . $js_rel;

    $css_ver = file_exists( $css_path ) ? filemtime( $css_path ) : JAF_PLUGIN_VER;
    $js_ver  = file_exists( $js_path ) ? filemtime( $js_path )  : JAF_PLUGIN_VER;

     wp_register_script(
      'google-recaptcha',
      $google,
      [],
      $js_ver,
      true
    );

    wp_register_style(
      'jaf-app',
      $plugin_url . $css_rel,
      [],
      $css_ver
    );

    // Oletus: React on bundlattu tähän tiedostoon
    wp_register_script(
      'jaf-app',
      $plugin_url . $js_rel,
      [],
      $js_ver,
      true
    );
  }

  /**
   * Shortcode handler.
   * Usage: [job_application_form]
   */
  public function shortcode() {
    // Enqueue assets
    wp_enqueue_style( 'jaf-app' );
    wp_enqueue_script( 'jaf-app' );
    wp_enqueue_script( 'google-recaptcha' );

    // Jos haluat tukea useita instansseja, käytä uniikkia ID:tä:
    // $id = 'jaf-root-' . wp_generate_uuid4();
    $id = 'jafroot';

    ob_start();
    ?>
      <div id="<?php echo esc_attr( $id ); ?>" class="job-app-form-root"></div>
    <?php
    return ob_get_clean();
  }
}

new JAF_Plugin();
// EOF
