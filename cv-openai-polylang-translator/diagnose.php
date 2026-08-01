<?php
/**
 * Remote Diagnostic tool for CV OpenAI Polylang Translator queue.
 */

// Load WordPress bootstrap
$wp_load_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    // Try current theme folder structure parent search
    $wp_load_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
}

if (!file_exists($wp_load_path)) {
    die('Could not load wp-load.php. Please place this file in your WordPress directory structure.');
}

require_once $wp_load_path;

if (!current_user_can('manage_options')) {
    die('Unauthorized access. Please log in as administrator first.');
}

global $wpdb;

echo '<html><head><style>body { font-family: sans-serif; padding: 20px; line-height: 1.5; } table { border-collapse: collapse; margin-top: 15px; width: 100%; } th, td { border: 1px solid #ccc; padding: 8px; text-align: left; } th { background-color: #f0f0f0; }</style></head><body>';
echo '<h1>OpenAI Polylang Translator Remote Diagnostics</h1>';

// 1. Check Tables
echo '<h2>1. Database Queue Summary</h2>';
$table_queue = $wpdb->prefix . 'cv_oai_pll_queue';
$table_memory = $wpdb->prefix . 'cv_oai_pll_memory';

if ($wpdb->get_var("SHOW TABLES LIKE '$table_queue'") !== $table_queue) {
    echo '<p style="color:red;">Error: Queue table does not exist!</p>';
} else {
    $results = $wpdb->get_results("SELECT item_type, status, target_language, COUNT(*) as count FROM $table_queue GROUP BY item_type, status, target_language");
    if (empty($results)) {
        echo '<p>Queue table is empty.</p>';
    } else {
        echo '<table><tr><th>Item Type</th><th>Status</th><th>Target Language</th><th>Count</th></tr>';
        foreach ($results as $row) {
            echo "<tr><td>{$row->item_type}</td><td>{$row->status}</td><td>{$row->target_language}</td><td>{$row->count}</td></tr>";
        }
        echo '</table>';
    }
}

// 2. Check Recent Failed Items
echo '<h2>2. Failed Queue Items (Last 10)</h2>';
$failed_items = $wpdb->get_results("SELECT * FROM $table_queue WHERE status = 'failed' ORDER BY id DESC LIMIT 10");
if (empty($failed_items)) {
    echo '<p>No failed items found.</p>';
} else {
    echo '<table><tr><th>Type</th><th>Item ID</th><th>Lang</th><th>Error Message</th><th>Attempts</th></tr>';
    foreach ($failed_items as $item) {
        echo "<tr><td>{$item->item_type}</td><td>{$item->item_id}</td><td>{$item->target_language}</td><td>" . esc_html($item->error_message) . "</td><td>{$item->attempts}</td></tr>";
    }
    echo '</table>';
}

// 3. Check Polylang configuration
echo '<h2>3. Polylang Configurations</h2>';
global $polylang;
if (!isset($polylang)) {
    echo '<p style="color:red;">Polylang is not initialized or active.</p>';
} else {
    $languages = $polylang->model->get_languages_list();
    echo '<p><strong>Languages:</strong> ';
    $lang_codes = [];
    foreach ($languages as $l) {
        $lang_codes[] = "{$l->name} ({$l->slug})";
    }
    echo implode(', ', $lang_codes) . '</p>';

    // Check strings
    echo '<h3>Registered Strings Sample</h3>';
    
    $registered_strings = [];
    if (isset($polylang->strings) && method_exists($polylang->strings, 'get_strings')) {
        $registered_strings = $polylang->strings->get_strings();
    } elseif (isset($polylang->model->strings) && method_exists($polylang->model->strings, 'get_strings')) {
        $registered_strings = $polylang->model->strings->get_strings();
    } elseif (isset($polylang->model) && method_exists($polylang->model, 'get_strings')) {
        $registered_strings = $polylang->model->get_strings();
    }

    if (empty($registered_strings)) {
        echo '<p>No registered strings found in Polylang.</p>';
    } else {
        echo '<p>Total registered strings: ' . count($registered_strings) . '</p>';
        echo '<table><tr><th>Name</th><th>Context</th><th>Value</th><th>MD5 Hash</th></tr>';
        $count = 0;
        foreach ($registered_strings as $str) {
            $hash = md5($str['context'] . '|||' . $str['name']);
            echo "<tr><td>{$str['name']}</td><td>{$str['context']}</td><td>" . esc_html($str['string']) . "</td><td>{$hash}</td></tr>";
            $count++;
            if ($count >= 10) {
                break;
            }
        }
        echo '</table>';
    }
}

echo '</body></html>';
