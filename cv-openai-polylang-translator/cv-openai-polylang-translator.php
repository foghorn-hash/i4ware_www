<?php
/**
 * Plugin Name: CV OpenAI Polylang Translator
 * Description: Manually translate Finnish pages/posts into English and B2B Arabic drafts using OpenAI and Polylang.
 * Version: 2.0.0
 * Author: Antigravity AI
 * Text Domain: cv-openai-polylang-translator
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Load main plugin controller class
require_once plugin_dir_path(__FILE__) . 'includes/class-cv-oai-pll-plugin.php';

// 2. Instantiate and run the plugin lifecycle hooks
function cv_oai_pll_bootstrap() {
    $plugin = new CV_OAI_PLL_Plugin();
    $plugin->run();
}
cv_oai_pll_bootstrap();
