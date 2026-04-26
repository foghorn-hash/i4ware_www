<?php
/*
Plugin Name: Legal React App
Description: Embeds a React app from the plugin's static folder via shortcode [legal_react_app].
Version: 1.0
Author: Matti Kiviharju
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue React build assets
function legal_react_app_enqueue_assets() {
    $plugin_url = plugin_dir_url( __FILE__ ) . 'static/';
    echo  '<!-- Enqueuing Legal React App assets -->';
    echo  '<!-- CSS: ' . $plugin_url . 'css/main.749b7140.css -->';
    echo  '<!-- JS: ' . $plugin_url . 'js/main.f24abccf.js -->';
    // Adjust filenames if your build uses hashes
    wp_enqueue_style( 'legal-react-app-css', $plugin_url . 'css/main.749b7140.css', array(), null );
    wp_enqueue_script( 'legal-react-app-js', $plugin_url . 'js/main.f24abccf.js', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'legal_react_app_enqueue_assets' );

// Shortcode to render the React app
function legal_react_app_shortcode() {
    // The React app will mount to this div
    return '<div id="legal-root"></div>';
}
add_shortcode( 'legal_react_app', 'legal_react_app_shortcode' );