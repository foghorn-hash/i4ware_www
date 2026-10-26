<?php
/*
Plugin Name: Revenue React App
Description: Embeds a React app from the plugin's static folder via shortcode [revenue_react_app].
Version: 1.1
Author: Matti Kiviharju
*/

if (!defined('ABSPATH'))
    exit;

// Enqueue React build assets
function revenue_react_app_enqueue_assets()
{
    $plugin_dir = plugin_dir_path(__FILE__) . 'static/';
    $plugin_url = plugin_dir_url(__FILE__) . 'static/';

    // Find the latest main CSS and JS files dynamically (sorted by modification time)
    $css_files = glob($plugin_dir . 'css/main.*.css');
    $js_files = glob($plugin_dir . 'js/main.*.js');

    if (!empty($css_files)) {
        usort($css_files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
    }
    if (!empty($js_files)) {
        usort($js_files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
    }

    $css_file = !empty($css_files) ? basename($css_files[0]) : 'main.933d19d0.css';
    $js_file = !empty($js_files) ? basename($js_files[0]) : 'main.626b6662.js';

    $css_ver = !empty($css_files) ? filemtime($css_files[0]) : '1.2';
    $js_ver = !empty($js_files) ? filemtime($js_files[0]) : '1.2';

    wp_enqueue_style('revenue-react-app-css', $plugin_url . 'css/' . $css_file, array(), $css_ver);
    wp_enqueue_script('revenue-react-app-js', $plugin_url . 'js/' . $js_file, array(), $js_ver, true);
}
add_action('wp_enqueue_scripts', 'revenue_react_app_enqueue_assets');

// Shortcode to render the React app
function revenue_react_app_shortcode()
{
    // The React app will mount to this div
    return '<div id="root"></div>';
}
add_shortcode('revenue_react_app', 'revenue_react_app_shortcode');