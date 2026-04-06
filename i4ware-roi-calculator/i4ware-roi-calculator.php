<?php
/**
 * Plugin Name: I4ware ROI Calculator
 * Description: React-based ROI and hourly pricing calculator shortcode.
 * Version: 1.0.0
 * Author: i4ware Software
 * License: GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class I4ware_ROI_Calculator {
    const VERSION = '1.0.0';
    const SHORTCODE = 'i4ware_roi_calculator';

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_action( 'init', array( $this, 'register_shortcode' ) );
    }

    public function register_assets() {
        $base_url  = plugin_dir_url( __FILE__ ) . 'assets/';
        $base_path = plugin_dir_path( __FILE__ ) . 'assets/';

        $css_file = $base_path . 'roi-calculator.css';
        $js_file  = $base_path . 'roi-calculator.js';

        wp_register_style(
            'i4ware-roi-calculator-style',
            $base_url . 'roi-calculator.css',
            array(),
            file_exists( $css_file ) ? filemtime( $css_file ) : self::VERSION
        );

        wp_register_script(
            'i4ware-roi-calculator-app',
            $base_url . 'roi-calculator.js',
            array( 'wp-element' ),
            file_exists( $js_file ) ? filemtime( $js_file ) : self::VERSION,
            true
        );
    }

    public function register_shortcode() {
        add_shortcode( self::SHORTCODE, array( $this, 'shortcode' ) );
    }

    public function shortcode() {
        wp_enqueue_style( 'i4ware-roi-calculator-style' );
        wp_enqueue_script( 'i4ware-roi-calculator-app' );

        wp_localize_script(
            'i4ware-roi-calculator-app',
            'i4wareRoiCalculator',
            array(
                'lang' => $this->get_language_code(),
            )
        );

        ob_start();
        ?>
        <div id="i4ware-roi-calculator-root"></div>
        <?php
        return ob_get_clean();
    }

    private function get_language_code() {
        $lang = '';

        if ( function_exists( 'pll_current_language' ) ) {
            $lang = (string) pll_current_language( 'slug' );
        }

        if ( '' === $lang ) {
            $locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
            $lang   = strtolower( substr( (string) $locale, 0, 2 ) );
        }

        return 'fi' === $lang ? 'fi' : 'en';
    }
}

new I4ware_ROI_Calculator();
