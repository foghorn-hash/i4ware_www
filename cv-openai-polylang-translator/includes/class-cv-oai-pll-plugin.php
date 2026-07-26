<?php
/**
 * Class CV_OAI_PLL_Plugin
 *
 * The main controller of the CV OpenAI Polylang Translator plugin.
 * Handles inclusion of other files and boots the admin module.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Plugin {

    /**
     * Instance of CV_OAI_PLL_Admin.
     */
    private $admin;

    /**
     * Constructor: Autoloads files and instantiates hooks.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->admin = new CV_OAI_PLL_Admin();
    }

    /**
     * Includes all class files needed for the plugin.
     */
    private function load_dependencies() {
        $dir = dirname(__FILE__);
        
        require_once $dir . '/class-cv-oai-pll-translation-lock.php';
        require_once $dir . '/class-cv-oai-pll-logger.php';
        require_once $dir . '/class-cv-oai-pll-openai-client.php';
        require_once $dir . '/class-cv-oai-pll-validator.php';
        require_once $dir . '/class-cv-oai-pll-content-extractor.php';
        require_once $dir . '/class-cv-oai-pll-translator.php';
        require_once $dir . '/class-cv-oai-pll-admin.php';
    }

    /**
     * Boots the plugin admin area.
     */
    public function run() {
        $this->admin->init();
        
        // Register activation/deactivation hooks
        register_deactivation_hook(
            dirname(dirname(__FILE__)) . '/cv-openai-polylang-translator.php',
            [$this, 'deactivate']
        );
    }

    /**
     * Runs on plugin deactivation.
     * Cleans up transients and options.
     */
    public function deactivate() {
        // Ensure translation locks are freed
        CV_OAI_PLL_Translation_Lock::release();
    }
}
