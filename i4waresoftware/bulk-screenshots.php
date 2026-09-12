<?php
/**
 * Bulk Add Screenshots Module (Simplified - No Categories Required)
 * 
 * Supports bulk creating screenshot posts from Media Library files for:
 * 1. Timesheets Screenshots (tfj_screenshot)
 * 2. SDK Screenshots (sdk_screenshot)
 * 3. WordPress Screenshots (wordpress_screenshot)
 * 
 * Features:
 * - Native WordPress Media Library Bulk Actions (upload.php)
 * - Simple & Fast Admin Tool (Tools / Media / CPT Submenus) with wp.media multi-picker
 * - Polylang multi-language support (FI, EN, AR & multi-language translation linking)
 * - Automatic Featured Image assignment + ACF fields synchronization
 * - Live preview with editable titles and optional captions before creation
 * 
 * @package i4waresoftware
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ----------------------------------------------------------------------
 * 1. Admin Menus & Submenus
 * ----------------------------------------------------------------------
 */
add_action('admin_menu', 'i4ware_bulk_screenshots_admin_menus');
function i4ware_bulk_screenshots_admin_menus()
{
    $cap = 'edit_posts';

    // Under Tools
    add_submenu_page(
        'tools.php',
        __('Bulk Add Screenshots', 'i4waresoftware'),
        __('Bulk Add Screenshots', 'i4waresoftware'),
        $cap,
        'i4ware-bulk-screenshots',
        'i4ware_render_bulk_screenshots_page'
    );

    // Under Media
    add_submenu_page(
        'upload.php',
        __('Bulk Add Screenshots', 'i4waresoftware'),
        __('Bulk Add Screenshots', 'i4waresoftware'),
        $cap,
        'i4ware-bulk-screenshots-media',
        'i4ware_render_bulk_screenshots_page'
    );

    // Under SDK Screenshots CPT
    add_submenu_page(
        'edit.php?post_type=sdk_screenshot',
        __('Bulk Add from Media', 'i4waresoftware'),
        __('Bulk Add from Media', 'i4waresoftware'),
        $cap,
        'i4ware-bulk-screenshots-sdk',
        'i4ware_render_bulk_screenshots_page'
    );

    // Under WordPress Screenshots CPT
    add_submenu_page(
        'edit.php?post_type=wordpress_screenshot',
        __('Bulk Add from Media', 'i4waresoftware'),
        __('Bulk Add from Media', 'i4waresoftware'),
        $cap,
        'i4ware-bulk-screenshots-wp',
        'i4ware_render_bulk_screenshots_page'
    );

    // Under Timesheets Screenshots CPT
    add_submenu_page(
        'edit.php?post_type=tfj_screenshot',
        __('Bulk Add from Media', 'i4waresoftware'),
        __('Bulk Add from Media', 'i4waresoftware'),
        $cap,
        'i4ware-bulk-screenshots-tfj',
        'i4ware_render_bulk_screenshots_page'
    );
}

/**
 * Enqueue scripts and styles for the bulk add screenshots admin page
 */
add_action('admin_enqueue_scripts', 'i4ware_bulk_screenshots_admin_assets');
function i4ware_bulk_screenshots_admin_assets($hook)
{
    // Check if we are on one of our bulk screenshot pages
    $valid_pages = array(
        'tools_page_i4ware-bulk-screenshots',
        'media_page_i4ware-bulk-screenshots-media',
        'sdk_screenshot_page_i4ware-bulk-screenshots-sdk',
        'wordpress_screenshot_page_i4ware-bulk-screenshots-wp',
        'tfj_screenshot_page_i4ware-bulk-screenshots-tfj',
    );

    if (in_array($hook, $valid_pages, true) || (isset($_GET['page']) && strpos($_GET['page'], 'i4ware-bulk-screenshots') !== false)) {
        wp_enqueue_media();
        wp_enqueue_script('jquery');
    }
}

/**
 * ----------------------------------------------------------------------
 * 2. WordPress Media Library (upload.php) Bulk Actions
 * ----------------------------------------------------------------------
 */
add_filter('bulk_actions-upload', 'i4ware_register_media_bulk_actions');
function i4ware_register_media_bulk_actions($bulk_actions)
{
    $bulk_actions['i4ware_bulk_tfj_screenshot'] = __('⚡ Bulk Add: Timesheet Screenshots', 'i4waresoftware');
    $bulk_actions['i4ware_bulk_sdk_screenshot'] = __('⚡ Bulk Add: SDK Screenshots', 'i4waresoftware');
    $bulk_actions['i4ware_bulk_wp_screenshot'] = __('⚡ Bulk Add: WordPress Screenshots', 'i4waresoftware');
    return $bulk_actions;
}

add_filter('handle_bulk_actions-upload', 'i4ware_handle_media_bulk_actions', 10, 3);
function i4ware_handle_media_bulk_actions($redirect_to, $doaction, $post_ids)
{
    if (!in_array($doaction, array('i4ware_bulk_tfj_screenshot', 'i4ware_bulk_sdk_screenshot', 'i4ware_bulk_wp_screenshot'), true)) {
        return $redirect_to;
    }

    if (!current_user_can('edit_posts')) {
        wp_die(__('Permission denied.', 'i4waresoftware'));
    }

    $cpt = 'sdk_screenshot';
    if ($doaction === 'i4ware_bulk_tfj_screenshot') {
        $cpt = 'tfj_screenshot';
    } elseif ($doaction === 'i4ware_bulk_wp_screenshot') {
        $cpt = 'wordpress_screenshot';
    }

    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
    $created_ids = array();

    foreach ($post_ids as $attachment_id) {
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            continue;
        }

        // Clean title
        $title = $attachment->post_title;
        if (empty($title)) {
            $filename = basename(get_attached_file($attachment_id));
            $title = preg_replace('/\.[^.]+$/', '', $filename);
            $title = ucwords(str_replace(array('-', '_'), ' ', $title));
        }

        $excerpt = !empty($attachment->post_excerpt) ? $attachment->post_excerpt : $attachment->post_content;

        $post_id = i4ware_create_single_screenshot(array(
            'attachment_id' => $attachment_id,
            'title'         => $title,
            'excerpt'       => $excerpt,
            'post_type'     => $cpt,
            'external_url'  => '',
            'lang'          => $lang,
        ));

        if ($post_id && !is_wp_error($post_id)) {
            $created_ids[] = $post_id;
        }
    }

    $redirect_to = add_query_arg(array(
        'i4ware_bulk_created_count' => count($created_ids),
        'i4ware_bulk_cpt'           => $cpt,
    ), $redirect_to);

    return $redirect_to;
}

/**
 * Display admin notice after Media Library bulk action
 */
add_action('admin_notices', 'i4ware_bulk_media_action_admin_notice');
function i4ware_bulk_media_action_admin_notice()
{
    if (empty($_GET['i4ware_bulk_created_count'])) {
        return;
    }

    $count = (int)$_GET['i4ware_bulk_created_count'];
    $cpt = isset($_GET['i4ware_bulk_cpt']) ? sanitize_key($_GET['i4ware_bulk_cpt']) : 'screenshot';

    $cpt_label = 'Screenshots';
    $view_link = admin_url('edit.php?post_type=' . $cpt);
    if ($cpt === 'tfj_screenshot') {
        $cpt_label = 'Timesheet Screenshots';
    } elseif ($cpt === 'sdk_screenshot') {
        $cpt_label = 'SDK Screenshots';
    } elseif ($cpt === 'wordpress_screenshot') {
        $cpt_label = 'WordPress Screenshots';
    }

    ?>
    <div class="notice notice-success is-dismissible" style="border-left-color: #0070ba; padding: 12px 16px;">
        <p style="font-size: 14px; margin: 0;">
            <strong>✅ <?php echo esc_html(sprintf(__('Successfully created %d %s from media files!', 'i4waresoftware'), $count, $cpt_label)); ?></strong>
            &nbsp;—&nbsp;
            <a href="<?php echo esc_url($view_link); ?>" class="button button-small button-secondary" style="font-weight: 600;">
                <?php echo esc_html(sprintf(__('View %s List', 'i4waresoftware'), $cpt_label)); ?> &rarr;
            </a>
        </p>
    </div>
    <?php
}

/**
 * ----------------------------------------------------------------------
 * 3. Core Function: Create Single Screenshot Post
 * ----------------------------------------------------------------------
 */
function i4ware_create_single_screenshot($data)
{
    $attachment_id = isset($data['attachment_id']) ? (int)$data['attachment_id'] : 0;
    $post_type     = isset($data['post_type']) ? sanitize_key($data['post_type']) : 'sdk_screenshot';
    $title         = isset($data['title']) && trim($data['title']) !== '' ? sanitize_text_field($data['title']) : '';
    $excerpt       = isset($data['excerpt']) ? sanitize_textarea_field($data['excerpt']) : '';
    $external_url  = isset($data['external_url']) ? esc_url_raw($data['external_url']) : '';
    $lang          = isset($data['lang']) ? sanitize_key($data['lang']) : '';
    $menu_order    = isset($data['menu_order']) ? (int)$data['menu_order'] : 0;

    if (!$attachment_id) {
        return new WP_Error('missing_attachment', __('Attachment ID is required.', 'i4waresoftware'));
    }

    if (empty($title)) {
        $filename = basename(get_attached_file($attachment_id));
        $title = preg_replace('/\.[^.]+$/', '', $filename);
        $title = ucwords(str_replace(array('-', '_'), ' ', $title));
    }

    $image_url = wp_get_attachment_url($attachment_id);

    // 1. Insert Post
    $post_arr = array(
        'post_title'   => $title,
        'post_excerpt' => $excerpt,
        'post_status'  => 'publish',
        'post_type'    => $post_type,
        'menu_order'   => $menu_order,
    );

    $post_id = wp_insert_post($post_arr);
    if (!$post_id || is_wp_error($post_id)) {
        return $post_id;
    }

    // 2. Set Featured Image
    set_post_thumbnail($post_id, $attachment_id);

    // 3. Set ACF & Meta Fields according to CPT
    if ($post_type === 'tfj_screenshot') {
        // Timesheets Screenshot
        if (function_exists('update_field')) {
            update_field('tfj_screenshot_image', $attachment_id, $post_id);
            update_field('tfj_screenshot_category', 'dev', $post_id);
        } else {
            update_post_meta($post_id, 'tfj_screenshot_image', $image_url);
            update_post_meta($post_id, 'tfj_screenshot_category', 'dev');
        }
    } elseif ($post_type === 'sdk_screenshot') {
        // SDK Screenshot
        if (function_exists('update_field')) {
            update_field('sdk_screenshot_image', $attachment_id, $post_id);
            update_field('sdk_screenshot_badge', '', $post_id);
            update_field('sdk_screenshot_external_url', $external_url, $post_id);
        } else {
            update_post_meta($post_id, 'sdk_screenshot_image', $attachment_id);
            update_post_meta($post_id, 'sdk_screenshot_badge', '');
            update_post_meta($post_id, 'sdk_screenshot_external_url', $external_url);
        }
    } elseif ($post_type === 'wordpress_screenshot') {
        // WordPress Screenshot
        if (function_exists('update_field')) {
            update_field('screenshot_image', $attachment_id, $post_id);
            update_field('screenshot_badge', '', $post_id);
            update_field('screenshot_external_url', $external_url, $post_id);
        } else {
            update_post_meta($post_id, 'screenshot_image', $attachment_id);
            update_post_meta($post_id, 'screenshot_badge', '');
            update_post_meta($post_id, 'screenshot_external_url', $external_url);
        }
    }

    // 4. Polylang language assignment
    if (!empty($lang) && function_exists('pll_set_post_language')) {
        pll_set_post_language($post_id, $lang);
    }

    return $post_id;
}

/**
 * ----------------------------------------------------------------------
 * 4. AJAX Handler for Interactive Bulk Creation
 * ----------------------------------------------------------------------
 */
add_action('wp_ajax_i4ware_bulk_create_screenshots', 'i4ware_ajax_bulk_create_screenshots');
function i4ware_ajax_bulk_create_screenshots()
{
    check_ajax_referer('i4ware_bulk_screenshots_nonce', 'security');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('Unauthorized access.', 'i4waresoftware')));
    }

    $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : 'sdk_screenshot';
    $lang_mode = isset($_POST['lang_mode']) ? sanitize_text_field($_POST['lang_mode']) : 'current';
    $selected_lang = isset($_POST['selected_lang']) ? sanitize_key($_POST['selected_lang']) : '';
    $items = isset($_POST['items']) ? (array)$_POST['items'] : array();

    if (empty($items)) {
        wp_send_json_error(array('message' => __('No media items selected.', 'i4waresoftware')));
    }

    // Supported languages
    $all_languages = array('fi', 'en', 'ar');
    if (function_exists('pll_languages_list')) {
        $all_languages = pll_languages_list();
    }

    $results = array();
    $created_count = 0;

    foreach ($items as $idx => $item) {
        $attachment_id = isset($item['attachment_id']) ? (int)$item['attachment_id'] : 0;
        if (!$attachment_id) {
            continue;
        }

        $title = isset($item['title']) ? sanitize_text_field($item['title']) : '';
        $excerpt = isset($item['excerpt']) ? sanitize_textarea_field($item['excerpt']) : '';
        $external_url = isset($item['external_url']) ? esc_url_raw($item['external_url']) : '';
        $menu_order = isset($item['menu_order']) ? (int)$item['menu_order'] : ($idx + 1);

        if ($lang_mode === 'all' && function_exists('pll_set_post_language') && function_exists('pll_save_post_translations')) {
            // Create post for each active language and link them
            $translations = array();
            $first_id = null;

            foreach ($all_languages as $l) {
                $pid = i4ware_create_single_screenshot(array(
                    'attachment_id' => $attachment_id,
                    'title'         => $title,
                    'excerpt'       => $excerpt,
                    'post_type'     => $post_type,
                    'external_url'  => $external_url,
                    'lang'          => $l,
                    'menu_order'    => $menu_order,
                ));

                if ($pid && !is_wp_error($pid)) {
                    $translations[$l] = $pid;
                    $created_count++;
                    if (!$first_id) {
                        $first_id = $pid;
                    }
                }
            }

            if (!empty($translations)) {
                pll_save_post_translations($translations);
                $results[] = array(
                    'id'            => $first_id,
                    'title'         => $title,
                    'post_type'     => $post_type,
                    'attachment_id' => $attachment_id,
                    'edit_url'      => get_edit_post_link($first_id, 'raw'),
                    'thumbnail'     => wp_get_attachment_thumb_url($attachment_id),
                    'translations'  => $translations,
                    'status'        => 'success',
                );
            }
        } else {
            // Single language mode
            $target_lang = $selected_lang;
            if (empty($target_lang)) {
                $target_lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
            }

            $pid = i4ware_create_single_screenshot(array(
                'attachment_id' => $attachment_id,
                'title'         => $title,
                'excerpt'       => $excerpt,
                'post_type'     => $post_type,
                'external_url'  => $external_url,
                'lang'          => $target_lang,
                'menu_order'    => $menu_order,
            ));

            if ($pid && !is_wp_error($pid)) {
                $created_count++;
                $results[] = array(
                    'id'            => $pid,
                    'title'         => $title,
                    'post_type'     => $post_type,
                    'attachment_id' => $attachment_id,
                    'edit_url'      => get_edit_post_link($pid, 'raw'),
                    'thumbnail'     => wp_get_attachment_thumb_url($attachment_id),
                    'lang'          => $target_lang,
                    'status'        => 'success',
                );
            }
        }
    }

    wp_send_json_success(array(
        'count'   => $created_count,
        'results' => $results,
        'message' => sprintf(__('Successfully created %d screenshot post(s)!', 'i4waresoftware'), $created_count),
    ));
}

/**
 * ----------------------------------------------------------------------
 * 5. AJAX Handler: Scan Media Library for Screenshots
 * ----------------------------------------------------------------------
 */
add_action('wp_ajax_i4ware_scan_media_screenshots', 'i4ware_ajax_scan_media_screenshots');
function i4ware_ajax_scan_media_screenshots()
{
    check_ajax_referer('i4ware_bulk_screenshots_nonce', 'security');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => __('Unauthorized access.', 'i4waresoftware')));
    }

    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $limit  = isset($_POST['limit']) ? min((int)$_POST['limit'], 100) : 60;

    $query_args = array(
        'post_type'      => 'attachment',
        'post_mime_type' => 'image',
        'post_status'    => 'inherit',
        'posts_per_page' => $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if (!empty($search)) {
        $query_args['s'] = $search;
    }

    $attachments = get_posts($query_args);
    $media_items = array();

    foreach ($attachments as $att) {
        $filename = basename(get_attached_file($att->ID));
        $clean_title = $att->post_title;
        if (empty($clean_title)) {
            $clean_title = preg_replace('/\.[^.]+$/', '', $filename);
            $clean_title = ucwords(str_replace(array('-', '_'), ' ', $clean_title));
        }

        $thumb_url = wp_get_attachment_image_url($att->ID, 'thumbnail');
        $full_url  = wp_get_attachment_url($att->ID);

        $media_items[] = array(
            'id'        => $att->ID,
            'title'     => $clean_title,
            'filename'  => $filename,
            'excerpt'   => !empty($att->post_excerpt) ? $att->post_excerpt : $att->post_content,
            'thumb_url' => $thumb_url ? $thumb_url : $full_url,
            'full_url'  => $full_url,
            'date'      => get_the_date('Y-m-d', $att->ID),
        );
    }

    wp_send_json_success(array('items' => $media_items));
}

/**
 * ----------------------------------------------------------------------
 * 6. Admin Page UI Renderer
 * ----------------------------------------------------------------------
 */
function i4ware_render_bulk_screenshots_page()
{
    if (!current_user_can('edit_posts')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'i4waresoftware'));
    }

    // Determine default post type from URL parameter or page slug
    $default_target = 'sdk_screenshot';
    if (isset($_GET['target'])) {
        $default_target = sanitize_key($_GET['target']);
    } elseif (isset($_GET['page'])) {
        if (strpos($_GET['page'], 'tfj') !== false) {
            $default_target = 'tfj_screenshot';
        } elseif (strpos($_GET['page'], 'wp') !== false) {
            $default_target = 'wordpress_screenshot';
        } elseif (strpos($_GET['page'], 'sdk') !== false) {
            $default_target = 'sdk_screenshot';
        }
    }

    // Polylang languages
    $languages = array(
        'fi' => __('Finnish (FI)', 'i4waresoftware'),
        'en' => __('English (EN)', 'i4waresoftware'),
        'ar' => __('Arabic (AR)', 'i4waresoftware'),
    );
    if (function_exists('pll_languages_list')) {
        $pll_langs = pll_languages_list();
        $lang_names = array('fi' => 'Finnish (FI)', 'en' => 'English (EN)', 'ar' => 'Arabic (AR)');
        $languages = array();
        foreach ($pll_langs as $l) {
            $languages[$l] = isset($lang_names[$l]) ? $lang_names[$l] : strtoupper($l);
        }
    }

    $current_lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
    $nonce = wp_create_nonce('i4ware_bulk_screenshots_nonce');
    ?>
    <div class="wrap i4ware-bulk-wrap" style="max-width: 1200px; margin-top: 20px;">
        <style>
            .i4ware-bulk-card {
                background: #ffffff;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                border-radius: 8px;
                padding: 24px;
                margin-bottom: 24px;
            }
            .i4ware-cpt-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 16px;
                margin: 16px 0 24px;
            }
            .i4ware-cpt-card {
                border: 2px solid #dcdcde;
                border-radius: 8px;
                padding: 16px 20px;
                cursor: pointer;
                transition: all 0.2s ease;
                background: #f6f7f7;
                display: flex;
                align-items: center;
                gap: 14px;
                position: relative;
            }
            .i4ware-cpt-card:hover {
                border-color: #2271b1;
                background: #f0f6fc;
            }
            .i4ware-cpt-card.active {
                border-color: #2271b1;
                background: #ffffff;
                box-shadow: 0 4px 12px rgba(34, 113, 177, 0.15);
            }
            .i4ware-cpt-card .dashicons {
                font-size: 32px;
                width: 32px;
                height: 32px;
                color: #2271b1;
            }
            .i4ware-cpt-card input[type="radio"] {
                position: absolute;
                right: 16px;
                top: 50%;
                transform: translateY(-50%);
            }
            .i4ware-toolbar {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                align-items: center;
                justify-content: space-between;
                background: #f0f6fc;
                border: 1px solid #c8d7e1;
                border-radius: 6px;
                padding: 14px 18px;
                margin: 20px 0;
            }
            .i4ware-media-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                gap: 12px;
                max-height: 280px;
                overflow-y: auto;
                padding: 10px;
                background: #fafafa;
                border: 1px solid #dcdcde;
                border-radius: 6px;
                margin-bottom: 20px;
            }
            .i4ware-media-thumb-item {
                position: relative;
                border: 2px solid #dcdcde;
                border-radius: 6px;
                overflow: hidden;
                background: #fff;
                cursor: pointer;
                aspect-ratio: 1;
                transition: transform 0.15s;
            }
            .i4ware-media-thumb-item:hover {
                transform: scale(1.03);
                border-color: #2271b1;
            }
            .i4ware-media-thumb-item.selected {
                border-color: #00a32a;
                box-shadow: 0 0 0 2px #00a32a;
            }
            .i4ware-media-thumb-item img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .i4ware-media-thumb-item .thumb-check {
                position: absolute;
                top: 6px;
                right: 6px;
                background: rgba(0,0,0,0.6);
                border-radius: 50%;
                width: 22px;
                height: 22px;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
            }
            .i4ware-media-thumb-item.selected .thumb-check {
                background: #00a32a;
            }
            .i4ware-media-thumb-item .thumb-title {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(0,0,0,0.7);
                color: #fff;
                font-size: 10px;
                padding: 2px 4px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .i4ware-preview-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            .i4ware-preview-table th {
                background: #f0f0f1;
                padding: 10px 12px;
                text-align: left;
                font-weight: 600;
                border-bottom: 1px solid #c3c4c7;
            }
            .i4ware-preview-table td {
                padding: 10px 12px;
                border-bottom: 1px solid #e2e4e7;
                vertical-align: top;
            }
            .i4ware-preview-table tr:hover {
                background: #fbfbfb;
            }
            .i4ware-badge-pill {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                background: #e7f5ff;
                color: #0070ba;
            }
            .i4ware-progress-bar {
                height: 12px;
                background: #e2e4e7;
                border-radius: 6px;
                overflow: hidden;
                margin: 15px 0;
                display: none;
            }
            .i4ware-progress-fill {
                height: 100%;
                width: 0%;
                background: linear-gradient(90deg, #2271b1, #00a32a);
                transition: width 0.3s ease;
            }
        </style>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
            <h1 style="font-size: 24px; font-weight: 700; color: #1d2327; margin: 0; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-images-alt2" style="font-size: 28px; width: 28px; height: 28px; color: #0070ba;"></span>
                <?php _e('Bulk Add Screenshots from Media Files', 'i4waresoftware'); ?>
            </h1>
            <div>
                <a href="<?php echo esc_url(admin_url('upload.php')); ?>" class="button button-secondary">
                    <span class="dashicons dashicons-admin-media" style="vertical-align: middle; margin-top: -2px;"></span>
                    <?php _e('Open Media Library', 'i4waresoftware'); ?>
                </a>
            </div>
        </div>

        <p style="font-size: 14px; color: #646970; margin-top: 0; margin-bottom: 20px;">
            <?php _e('Select images from your Media Library and generate screenshot posts in one click.', 'i4waresoftware'); ?>
        </p>

        <!-- STEP 1: TARGET POST TYPE & LANGUAGE -->
        <div class="i4ware-bulk-card">
            <h2 style="font-size: 16px; font-weight: 600; margin-top: 0; margin-bottom: 8px;">
                1. <?php _e('Select Target Screenshot Gallery & Language', 'i4waresoftware'); ?>
            </h2>

            <!-- CPT Select Cards -->
            <div class="i4ware-cpt-cards">
                <label class="i4ware-cpt-card <?php echo $default_target === 'tfj_screenshot' ? 'active' : ''; ?>" data-target="tfj_screenshot">
                    <span class="dashicons dashicons-clock"></span>
                    <div>
                        <strong style="font-size: 14px; display: block; color: #1d2327;"><?php _e('Timesheets Screenshots', 'i4waresoftware'); ?></strong>
                        <span style="font-size: 12px; color: #646970;"><?php _e('For Jira Timesheets landing page gallery', 'i4waresoftware'); ?></span>
                    </div>
                    <input type="radio" name="i4ware_target_cpt" value="tfj_screenshot" <?php checked($default_target, 'tfj_screenshot'); ?>>
                </label>

                <label class="i4ware-cpt-card <?php echo $default_target === 'sdk_screenshot' ? 'active' : ''; ?>" data-target="sdk_screenshot">
                    <span class="dashicons dashicons-desktop"></span>
                    <div>
                        <strong style="font-size: 14px; display: block; color: #1d2327;"><?php _e('SDK Screenshots', 'i4waresoftware'); ?></strong>
                        <span style="font-size: 12px; color: #646970;"><?php _e('For SDK & Architecture landing page gallery', 'i4waresoftware'); ?></span>
                    </div>
                    <input type="radio" name="i4ware_target_cpt" value="sdk_screenshot" <?php checked($default_target, 'sdk_screenshot'); ?>>
                </label>

                <label class="i4ware-cpt-card <?php echo $default_target === 'wordpress_screenshot' ? 'active' : ''; ?>" data-target="wordpress_screenshot">
                    <span class="dashicons dashicons-format-gallery"></span>
                    <div>
                        <strong style="font-size: 14px; display: block; color: #1d2327;"><?php _e('WordPress Screenshots', 'i4waresoftware'); ?></strong>
                        <span style="font-size: 12px; color: #646970;"><?php _e('For WordPress Development landing gallery', 'i4waresoftware'); ?></span>
                    </div>
                    <input type="radio" name="i4ware_target_cpt" value="wordpress_screenshot" <?php checked($default_target, 'wordpress_screenshot'); ?>>
                </label>
            </div>

            <!-- Language & Translation Settings -->
            <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center; background: #fafafa; padding: 14px 18px; border-radius: 6px; border: 1px solid #e2e4e7;">
                <div>
                    <label for="i4ware_lang_mode" style="font-weight: 600; display: block; margin-bottom: 4px;">
                        <?php _e('Language Assignment:', 'i4waresoftware'); ?>
                    </label>
                    <select id="i4ware_lang_mode" style="min-width: 220px;">
                        <option value="single"><?php _e('Specific Language', 'i4waresoftware'); ?></option>
                        <option value="all"><?php _e('Create for All Languages (Linked Translations)', 'i4waresoftware'); ?></option>
                    </select>
                </div>

                <div id="i4ware_single_lang_wrap">
                    <label for="i4ware_selected_lang" style="font-weight: 600; display: block; margin-bottom: 4px;">
                        <?php _e('Language:', 'i4waresoftware'); ?>
                    </label>
                    <select id="i4ware_selected_lang" style="min-width: 160px;">
                        <?php foreach ($languages as $code => $name) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($current_lang, $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- STEP 2: SELECT MEDIA FILES -->
        <div class="i4ware-bulk-card">
            <h2 style="font-size: 16px; font-weight: 600; margin-top: 0; margin-bottom: 8px;">
                2. <?php _e('Choose Media Files to Convert into Screenshots', 'i4waresoftware'); ?>
            </h2>

            <div class="i4ware-toolbar">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" id="i4ware_open_media_btn" class="button button-primary button-large" style="display: flex; align-items: center; gap: 6px;">
                        <span class="dashicons dashicons-admin-media"></span>
                        <?php _e('Select Images from Media Library (Multi-select)', 'i4waresoftware'); ?>
                    </button>
                    <button type="button" id="i4ware_scan_recent_btn" class="button button-secondary button-large" style="display: flex; align-items: center; gap: 6px;">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Quick Browser / Scan Recent Media', 'i4waresoftware'); ?>
                    </button>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <span id="i4ware_selected_count_badge" class="i4ware-badge-pill" style="font-size: 13px; padding: 6px 12px; background: #e0f2fe; color: #0284c7;">
                        0 <?php _e('images selected', 'i4waresoftware'); ?>
                    </span>
                    <button type="button" id="i4ware_clear_all_btn" class="button button-link-delete" style="display: none;">
                        <?php _e('Clear Selection', 'i4waresoftware'); ?>
                    </button>
                </div>
            </div>

            <!-- Media Library Quick Browser (Collapsible) -->
            <div id="i4ware_quick_browser_section" style="display: none; margin-top: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-weight: 600; font-size: 13px; color: #1d2327;">
                        <?php _e('Click images below to toggle selection:', 'i4waresoftware'); ?>
                    </span>
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="i4ware_browser_search" placeholder="<?php esc_attr_e('Search media by filename...', 'i4waresoftware'); ?>" style="font-size: 12px; padding: 3px 8px;">
                        <button type="button" id="i4ware_select_all_browser_btn" class="button button-small">
                            <?php _e('Select All Visible', 'i4waresoftware'); ?>
                        </button>
                    </div>
                </div>
                <div id="i4ware_quick_media_grid" class="i4ware-media-grid">
                    <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #646970;">
                        <?php _e('Loading media library...', 'i4waresoftware'); ?>
                    </div>
                </div>
            </div>

            <!-- Selected Items Table -->
            <div id="i4ware_selected_table_container" style="display: none; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <h3 style="font-size: 14px; font-weight: 600; margin: 0;">
                        <?php _e('Selected Screenshots Preview', 'i4waresoftware'); ?>
                    </h3>
                </div>

                <div style="overflow-x: auto; border: 1px solid #dcdcde; border-radius: 6px;">
                    <table class="i4ware-preview-table">
                        <thead>
                            <tr>
                                <th style="width: 70px; text-align: center;"><?php _e('Image', 'i4waresoftware'); ?></th>
                                <th style="min-width: 240px;"><?php _e('Post Title', 'i4waresoftware'); ?></th>
                                <th style="min-width: 240px;"><?php _e('Excerpt / Description (Optional)', 'i4waresoftware'); ?></th>
                                <th style="min-width: 180px;" class="col-url-header"><?php _e('Demo / External URL (Optional)', 'i4waresoftware'); ?></th>
                                <th style="width: 50px; text-align: center;"><?php _e('Action', 'i4waresoftware'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="i4ware_preview_tbody">
                            <!-- Rows injected via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- STEP 3: ACTION & PROGRESS -->
        <div class="i4ware-bulk-card" id="i4ware_submit_section" style="display: none;">
            <div class="i4ware-progress-bar" id="i4ware_progress_bar">
                <div class="i4ware-progress-fill" id="i4ware_progress_fill"></div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span id="i4ware_status_msg" style="font-size: 14px; font-weight: 600; color: #1d2327;"></span>
                </div>
                <button type="button" id="i4ware_start_bulk_btn" class="button button-primary button-hero" style="font-weight: 700;">
                    🚀 <?php _e('Create All Screenshot Posts Now', 'i4waresoftware'); ?>
                </button>
            </div>
        </div>

        <!-- RESULTS SECTION -->
        <div class="i4ware-bulk-card" id="i4ware_results_card" style="display: none;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #00a32a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 22px;"></span>
                    <span id="i4ware_results_title"><?php _e('Screenshots Created Successfully!', 'i4waresoftware'); ?></span>
                </h3>
                <a id="i4ware_cpt_view_all_link" href="<?php echo esc_url(admin_url('edit.php?post_type=sdk_screenshot')); ?>" class="button button-secondary">
                    <?php _e('View All Screenshot Posts in Admin', 'i4waresoftware'); ?> &rarr;
                </a>
            </div>
            <div id="i4ware_results_list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; margin-top: 15px;">
                <!-- Created items injected here -->
            </div>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function ($) {
        var selectedItems = {}; // key: attachment_id -> item data
        var currentTargetCpt = '<?php echo esc_js($default_target); ?>';
        var securityNonce = '<?php echo esc_js($nonce); ?>';

        // 1. CPT Selection Card Handler
        $('.i4ware-cpt-card').on('click', function () {
            $('.i4ware-cpt-card').removeClass('active');
            $(this).addClass('active');
            $(this).find('input[type="radio"]').prop('checked', true);

            currentTargetCpt = $(this).data('target');

            // Update UI headers & inputs based on CPT
            if (currentTargetCpt === 'tfj_screenshot') {
                $('.col-url-header').hide();
                $('.col-url-cell').hide();
                $('#i4ware_cpt_view_all_link').attr('href', '<?php echo esc_url(admin_url('edit.php?post_type=tfj_screenshot')); ?>');
            } else {
                $('.col-url-header').show();
                $('.col-url-cell').show();
                if (currentTargetCpt === 'wordpress_screenshot') {
                    $('#i4ware_cpt_view_all_link').attr('href', '<?php echo esc_url(admin_url('edit.php?post_type=wordpress_screenshot')); ?>');
                } else {
                    $('#i4ware_cpt_view_all_link').attr('href', '<?php echo esc_url(admin_url('edit.php?post_type=sdk_screenshot')); ?>');
                }
            }

            renderPreviewTable();
        });

        // 2. Language Mode Toggle
        $('#i4ware_lang_mode').on('change', function () {
            if ($(this).val() === 'all') {
                $('#i4ware_single_lang_wrap').hide();
            } else {
                $('#i4ware_single_lang_wrap').show();
            }
        });

        // 3. Native WordPress Media Modal Multi-Select
        var mediaFrame;
        $('#i4ware_open_media_btn').on('click', function (e) {
            e.preventDefault();

            if (mediaFrame) {
                mediaFrame.open();
                return;
            }

            mediaFrame = wp.media({
                title: '<?php echo esc_js(__('Select or Upload Screenshots', 'i4waresoftware')); ?>',
                button: {
                    text: '<?php echo esc_js(__('Add Selected Images', 'i4waresoftware')); ?>'
                },
                multiple: true,
                library: {
                    type: 'image'
                }
            });

            mediaFrame.on('select', function () {
                var selection = mediaFrame.state().get('selection');
                selection.each(function (attachment) {
                    var att = attachment.toJSON();
                    addOrUpdateSelectedItem({
                        id: att.id,
                        title: att.title || att.filename,
                        filename: att.filename,
                        excerpt: att.caption || att.description || '',
                        thumb_url: (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url,
                        full_url: att.url
                    });
                });
                renderPreviewTable();
            });

            mediaFrame.open();
        });

        // 4. Quick Media Library Browser & Scanner
        $('#i4ware_scan_recent_btn').on('click', function () {
            $('#i4ware_quick_browser_section').slideToggle(200);
            if ($('#i4ware_quick_media_grid .i4ware-media-thumb-item').length === 0) {
                loadMediaBrowser();
            }
        });

        function loadMediaBrowser(searchQuery) {
            var $grid = $('#i4ware_quick_media_grid');
            $grid.html('<div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #646970;"><span class="spinner is-active" style="float:none; margin:0 5px 0 0;"></span> <?php echo esc_js(__('Loading media images...', 'i4waresoftware')); ?></div>');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'i4ware_scan_media_screenshots',
                    security: securityNonce,
                    search: searchQuery || '',
                    limit: 60
                },
                success: function (res) {
                    if (res.success && res.data.items && res.data.items.length > 0) {
                        var html = '';
                        $.each(res.data.items, function (i, item) {
                            var isSelected = !!selectedItems[item.id];
                            html += '<div class="i4ware-media-thumb-item ' + (isSelected ? 'selected' : '') + '" data-id="' + item.id + '" title="' + escapeHtml(item.title) + '">';
                            html += '<img src="' + escapeHtml(item.thumb_url) + '" alt="' + escapeHtml(item.title) + '">';
                            html += '<div class="thumb-check">' + (isSelected ? '✓' : '+') + '</div>';
                            html += '<div class="thumb-title">' + escapeHtml(item.title) + '</div>';
                            html += '</div>';

                            // Store metadata for easy lookup
                            $grid.data('item-' + item.id, item);
                        });
                        $grid.html(html);
                    } else {
                        $grid.html('<div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #646970;"><?php echo esc_js(__('No image attachments found.', 'i4waresoftware')); ?></div>');
                    }
                }
            });
        }

        // Live Search in Media Browser
        var searchTimer;
        $('#i4ware_browser_search').on('input', function () {
            clearTimeout(searchTimer);
            var query = $(this).val();
            searchTimer = setTimeout(function () {
                loadMediaBrowser(query);
            }, 350);
        });

        // Click on thumbnail in Quick Browser
        $(document).on('click', '.i4ware-media-thumb-item', function () {
            var id = $(this).data('id');
            var item = $('#i4ware_quick_media_grid').data('item-' + id);

            if (selectedItems[id]) {
                delete selectedItems[id];
                $(this).removeClass('selected').find('.thumb-check').text('+');
            } else if (item) {
                addOrUpdateSelectedItem(item);
                $(this).addClass('selected').find('.thumb-check').text('✓');
            }
            renderPreviewTable();
        });

        // Select all visible in quick browser
        $('#i4ware_select_all_browser_btn').on('click', function () {
            $('.i4ware-media-thumb-item').each(function () {
                var id = $(this).data('id');
                var item = $('#i4ware_quick_media_grid').data('item-' + id);
                if (item && !selectedItems[id]) {
                    addOrUpdateSelectedItem(item);
                    $(this).addClass('selected').find('.thumb-check').text('✓');
                }
            });
            renderPreviewTable();
        });

        // Helper to add item
        function addOrUpdateSelectedItem(item) {
            if (!selectedItems[item.id]) {
                selectedItems[item.id] = {
                    attachment_id: item.id,
                    title: item.title || '',
                    excerpt: item.excerpt || '',
                    external_url: '',
                    thumb_url: item.thumb_url || item.full_url
                };
            }
        }

        // 5. Render Preview Table
        function renderPreviewTable() {
            var keys = Object.keys(selectedItems);
            var count = keys.length;

            $('#i4ware_selected_count_badge').text(count + ' <?php echo esc_js(__('images selected', 'i4waresoftware')); ?>');

            if (count > 0) {
                $('#i4ware_selected_table_container').show();
                $('#i4ware_submit_section').show();
                $('#i4ware_clear_all_btn').show();
            } else {
                $('#i4ware_selected_table_container').hide();
                $('#i4ware_submit_section').hide();
                $('#i4ware_clear_all_btn').hide();
                return;
            }

            var tbody = '';
            $.each(keys, function (index, id) {
                var it = selectedItems[id];
                tbody += '<tr data-id="' + it.attachment_id + '">';
                
                // Thumbnail
                tbody += '<td style="text-align: center;"><img src="' + escapeHtml(it.thumb_url) + '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #dcdcde;"></td>';
                
                // Title
                tbody += '<td><input type="text" class="regular-text item-title-input" value="' + escapeHtml(it.title) + '" style="width: 100%;"></td>';

                // Excerpt / Description
                tbody += '<td><textarea class="item-excerpt-input" rows="2" style="width: 100%; font-size: 12px;" placeholder="<?php echo esc_js(__('Optional caption / description...', 'i4waresoftware')); ?>">' + escapeHtml(it.excerpt) + '</textarea></td>';

                // External URL
                if (currentTargetCpt === 'tfj_screenshot') {
                    tbody += '<td class="col-url-cell" style="display:none;"><input type="url" class="item-url-input" value="" style="width: 100%;"></td>';
                } else {
                    tbody += '<td class="col-url-cell"><input type="url" class="item-url-input" value="' + escapeHtml(it.external_url) + '" style="width: 100%;" placeholder="https://..."></td>';
                }

                // Delete Action
                tbody += '<td style="text-align: center;"><button type="button" class="button button-small button-link-delete item-remove-btn" title="<?php echo esc_js(__('Remove', 'i4waresoftware')); ?>">✕</button></td>';

                tbody += '</tr>';
            });

            $('#i4ware_preview_tbody').html(tbody);
        }

        // Live input update in selectedItems
        $(document).on('change keyup', '.item-title-input', function () {
            var id = $(this).closest('tr').data('id');
            if (selectedItems[id]) selectedItems[id].title = $(this).val();
        });
        $(document).on('change keyup', '.item-excerpt-input', function () {
            var id = $(this).closest('tr').data('id');
            if (selectedItems[id]) selectedItems[id].excerpt = $(this).val();
        });
        $(document).on('change keyup', '.item-url-input', function () {
            var id = $(this).closest('tr').data('id');
            if (selectedItems[id]) selectedItems[id].external_url = $(this).val();
        });

        // Remove single item
        $(document).on('click', '.item-remove-btn', function () {
            var id = $(this).closest('tr').data('id');
            delete selectedItems[id];
            $('.i4ware-media-thumb-item[data-id="' + id + '"]').removeClass('selected').find('.thumb-check').text('+');
            renderPreviewTable();
        });

        // Clear all
        $('#i4ware_clear_all_btn').on('click', function () {
            selectedItems = {};
            $('.i4ware-media-thumb-item').removeClass('selected').find('.thumb-check').text('+');
            renderPreviewTable();
        });

        // 6. SUBMIT & PROCESS BULK CREATION
        $('#i4ware_start_bulk_btn').on('click', function () {
            var keys = Object.keys(selectedItems);
            if (keys.length === 0) {
                alert('<?php echo esc_js(__('Please select at least one media image.', 'i4waresoftware')); ?>');
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('<?php echo esc_js(__('Creating Screenshots...', 'i4waresoftware')); ?>');
            $('#i4ware_progress_bar').show();
            $('#i4ware_progress_fill').css('width', '30%');
            $('#i4ware_status_msg').text('<?php echo esc_js(__('Submitting batch to WordPress...', 'i4waresoftware')); ?>');

            var itemsArray = [];
            $.each(selectedItems, function (id, it) {
                itemsArray.push(it);
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'i4ware_bulk_create_screenshots',
                    security: securityNonce,
                    post_type: currentTargetCpt,
                    lang_mode: $('#i4ware_lang_mode').val(),
                    selected_lang: $('#i4ware_selected_lang').val(),
                    items: itemsArray
                },
                success: function (res) {
                    $('#i4ware_progress_fill').css('width', '100%');

                    if (res.success) {
                        $('#i4ware_status_msg').html('✅ ' + escapeHtml(res.data.message));
                        $('#i4ware_results_card').show();
                        $('#i4ware_results_title').text(res.data.message);

                        var listHtml = '';
                        if (res.data.results && res.data.results.length > 0) {
                            $.each(res.data.results, function (i, r) {
                                listHtml += '<div style="background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 6px; padding: 12px; display: flex; align-items: center; gap: 10px;">';
                                if (r.thumbnail) {
                                    listHtml += '<img src="' + escapeHtml(r.thumbnail) + '" style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px;">';
                                }
                                listHtml += '<div style="overflow: hidden; flex-grow: 1;">';
                                listHtml += '<strong style="display: block; font-size: 13px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">' + escapeHtml(r.title) + '</strong>';
                                listHtml += '<a href="' + escapeHtml(r.edit_url) + '" target="_blank" style="font-size: 11px; text-decoration: none; color: #2271b1; font-weight: 600;"><?php echo esc_js(__('Edit Post', 'i4waresoftware')); ?> &rarr;</a>';
                                listHtml += '</div>';
                                listHtml += '</div>';
                            });
                        }
                        $('#i4ware_results_list').html(listHtml);

                        // Clear selection
                        selectedItems = {};
                        $('.i4ware-media-thumb-item').removeClass('selected').find('.thumb-check').text('+');
                        renderPreviewTable();

                        $('html, body').animate({
                            scrollTop: $('#i4ware_results_card').offset().top - 50
                        }, 400);
                    } else {
                        var err = (res.data && res.data.message) ? res.data.message : '<?php echo esc_js(__('An error occurred during bulk import.', 'i4waresoftware')); ?>';
                        $('#i4ware_status_msg').html('❌ ' + escapeHtml(err));
                        alert('Error: ' + err);
                    }
                },
                error: function (xhr, status, error) {
                    $('#i4ware_status_msg').html('❌ AJAX Error: ' + escapeHtml(error));
                    alert('AJAX Error: ' + error);
                },
                complete: function () {
                    $btn.prop('disabled', false).text('🚀 <?php echo esc_js(__('Create All Screenshot Posts Now', 'i4waresoftware')); ?>');
                }
            });
        });

        function escapeHtml(text) {
            if (!text) return '';
            return $('<div>').text(text).html();
        }
    });
    </script>
    <?php
}
