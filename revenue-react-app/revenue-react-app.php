<?php
/*
Plugin Name: Revenue React App
Description: Embeds a React app from the plugin's static folder via shortcode [revenue_react_app].
Version: 1.0
Author: Matti Kiviharju
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue React build assets
function revenue_react_app_enqueue_assets() {
    $plugin_url = plugin_dir_url( __FILE__ ) . 'static/';
    // Adjust filenames if your build uses hashes
    wp_enqueue_style( 'revenue-react-app-css', $plugin_url . 'css/main.330f75a7.css', array(), null );
    wp_enqueue_script( 'revenue-react-app-js', $plugin_url . 'js/main.f8c9c5b6.js', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'revenue_react_app_enqueue_assets' );

// Shortcode to render the React app
function revenue_react_app_shortcode() {
    // The React app will mount to this div
    return '<div id="root"></div>';
}
add_shortcode( 'revenue_react_app', 'revenue_react_app_shortcode' );