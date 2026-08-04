<?php
/**
 * Plugin Name: i4ware Lightbox Gallery
 * Description: A premium Gutenberg block to insert an image gallery with a clean dark-themed lightbox overlay navigation.
 * Version: 1.0.0
 * Author: Antigravity AI
 * Text Domain: i4ware-lightbox-gallery
 */

if (!defined('ABSPATH')) {
    exit;
}

function i4ware_lightbox_gallery_register_block() {
    // Register Editor Script
    wp_register_script(
        'i4ware-lightbox-gallery-block-js',
        plugins_url('assets/js/block.js', __FILE__),
        ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components'],
        '1.0.0'
    );

    // Register Editor Style
    wp_register_style(
        'i4ware-lightbox-gallery-block-css',
        plugins_url('assets/css/block.css', __FILE__),
        ['wp-edit-blocks'],
        '1.0.0'
    );

    // Register Frontend Style
    wp_register_style(
        'i4ware-lightbox-gallery-frontend-css',
        plugins_url('assets/css/frontend.css', __FILE__),
        [],
        '1.0.0'
    );

    // Register Frontend Script
    wp_register_script(
        'i4ware-lightbox-gallery-frontend-js',
        plugins_url('assets/js/frontend.js', __FILE__),
        [],
        '1.0.0',
        true
    );

    // Register Gutenberg block using assets
    register_block_type('i4ware/lightbox-gallery', [
        'editor_script' => 'i4ware-lightbox-gallery-block-js',
        'editor_style'  => 'i4ware-lightbox-gallery-block-css',
        'style'         => 'i4ware-lightbox-gallery-frontend-css',
        'script'        => 'i4ware-lightbox-gallery-frontend-js',
    ]);
}
add_action('init', 'i4ware_lightbox_gallery_register_block');
