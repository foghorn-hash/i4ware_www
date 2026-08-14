<?php
/*
Plugin Name: Legal React App
Description: Embeds a React app from the plugin's static folder via shortcode [legal_react_app].
Version: 1.0
Author: Matti Kiviharju
*/

if (!defined('ABSPATH'))
    exit;

// Register React build assets
function legal_react_app_register_assets()
{
    $plugin_dir = plugin_dir_path(__FILE__) . 'static/';
    $plugin_url = plugin_dir_url(__FILE__) . 'static/';

    // Find the main CSS and JS files dynamically (handling build hashes)
    $css_files = glob($plugin_dir . 'css/main.*.css');
    $js_files = glob($plugin_dir . 'js/main.*.js');

    $css_file = !empty($css_files) ? basename($css_files[0]) : 'main.330f75a7.css';
    $js_file = !empty($js_files) ? basename($js_files[0]) : 'main.05ad0ef4.js';

    wp_register_style('legal-react-app-css', $plugin_url . 'css/' . $css_file, array(), null);
    wp_register_script('legal-react-app-js', $plugin_url . 'js/' . $js_file, array(), null, true);
}
add_action('wp_enqueue_scripts', 'legal_react_app_register_assets');

// Shortcode to render the React app
function legal_react_app_shortcode()
{
    // Enqueue the registered assets only on pages where this shortcode is used
    wp_enqueue_style('legal-react-app-css');
    wp_enqueue_script('legal-react-app-js');

    // Pass the dynamic public path to Webpack runtime
    $plugin_url = plugin_dir_url(__FILE__) . 'static/';
    wp_localize_script('legal-react-app-js', 'legalReactAppConfig', array(
        'publicPath' => $plugin_url
    ));

    // The React app will mount to this div
    return '<div id="legal-root"></div>';
}
add_shortcode('legal_react_app', 'legal_react_app_shortcode');