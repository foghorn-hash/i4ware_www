<?php
/**
 * Plugin Name: CV OpenAI Polylang Translator
 * Description: Manually translate Finnish blog posts into English using OpenAI and Polylang.
 * Version: 1.0.0
 * Author: GitHub Copilot
 */

if (!defined('ABSPATH')) {
    exit;
}

function cv_oai_pll_register_settings() {
    register_setting('cv_oai_pll_settings', 'cv_oai_pll_api_key');
    register_setting('cv_oai_pll_settings', 'cv_oai_pll_model');
    register_setting('cv_oai_pll_settings', 'cv_oai_pll_content_type');
    register_setting('cv_oai_pll_settings', 'cv_oai_pll_prompt');
}
add_action('admin_init', 'cv_oai_pll_register_settings');

function cv_oai_pll_add_settings_page() {
    add_options_page(
        'OpenAI Polylang Translator',
        'OpenAI Polylang Translator',
        'manage_options',
        'cv-oai-polylang-translator',
        'cv_oai_pll_render_settings_page'
    );
}
add_action('admin_menu', 'cv_oai_pll_add_settings_page');

function cv_oai_pll_default_prompt() {
    return 'Translate the following content from Finnish to {LANG}. Preserve meaning, tone, and any HTML. Return JSON with keys: title, excerpt, content.';
}

function cv_oai_pll_build_image_prompt($title, $excerpt, $content, $lang = 'fi') {
    $title = trim(wp_strip_all_tags($title));
    $excerpt = trim(wp_strip_all_tags($excerpt));
    $content = trim(wp_strip_all_tags($content));
    $subject = $title ?: $excerpt ?: preg_replace('/\s+/', ' ', $content);
    $subject = trim(mb_substr($subject, 0, 240));

    return sprintf(
        'Create a high-quality, cinematic Full-HD (1920x1080) image for a %s blog post about: %s. Make the image visually engaging, relevant to the topic, and suitable for an online blog article.',
        $lang === 'fi' ? 'Finnish' : 'English',
        $subject
    );
}

function cv_oai_pll_call_openai_image($prompt, $api_key) {
    $response = wp_remote_post('https://api.openai.com/v1/images/generations', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'timeout' => 60,
        'body' => wp_json_encode(array(
            'model' => 'gpt-image-1',
            'prompt' => $prompt,
            'size' => '1920x1080',
            'n' => 1,
            'response_format' => 'url',
        )),
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error('openai_image_error', 'OpenAI image error: ' . $body);
    }

    $json = json_decode($body, true);
    if (!is_array($json) || empty($json['data'][0]['url'])) {
        return new WP_Error('openai_image_invalid', 'Invalid OpenAI image response.');
    }

    return $json['data'][0]['url'];
}

function cv_oai_pll_generate_and_attach_image($post_id, $prompt, $api_key) {
    $image_url = cv_oai_pll_call_openai_image($prompt, $api_key);
    if (is_wp_error($image_url)) {
        return $image_url;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp_file = download_url($image_url);
    if (is_wp_error($tmp_file)) {
        return $tmp_file;
    }

    $file_name = basename(parse_url($image_url, PHP_URL_PATH));
    if (empty($file_name)) {
        $file_name = 'openai-generated-image-' . $post_id . '.png';
    }

    $file_array = array(
        'name' => $file_name,
        'tmp_name' => $tmp_file,
    );

    $attachment_id = media_handle_sideload($file_array, $post_id, 'OpenAI generated image');
    if (is_wp_error($attachment_id)) {
        @unlink($tmp_file);
        return $attachment_id;
    }

    set_post_thumbnail($post_id, $attachment_id);
    return $attachment_id;
}

function cv_oai_pll_default_content_type() {
    return 'post';
}

function cv_oai_pll_get_selected_post_types() {
    $content_type = get_option('cv_oai_pll_content_type', cv_oai_pll_default_content_type());
    if (!in_array($content_type, array('post', 'page'), true)) {
        $content_type = cv_oai_pll_default_content_type();
    }

    return array($content_type);
}

function cv_oai_pll_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $api_key = get_option('cv_oai_pll_api_key', '');
    $model = get_option('cv_oai_pll_model', 'gpt-4o-mini');
    $content_type = get_option('cv_oai_pll_content_type', cv_oai_pll_default_content_type());
    $post_types = cv_oai_pll_get_selected_post_types();
    $prompt = get_option('cv_oai_pll_prompt', cv_oai_pll_default_prompt());

    if (!empty($_GET['cv_oai_pll_message'])) {
        echo '<div class="notice notice-success"><p>' . esc_html(wp_unslash($_GET['cv_oai_pll_message'])) . '</p></div>';
    }

    if (!function_exists('pll_get_post')) {
        echo '<div class="notice notice-error"><p>Polylang is not active. Please activate Polylang to use this plugin.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>OpenAI Polylang Translator</h1>
        <form method="post" action="options.php">
            <?php settings_fields('cv_oai_pll_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="cv_oai_pll_api_key">OpenAI API Key</label></th>
                    <td><input type="password" id="cv_oai_pll_api_key" name="cv_oai_pll_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cv_oai_pll_model">Model</label></th>
                    <td><input type="text" id="cv_oai_pll_model" name="cv_oai_pll_model" value="<?php echo esc_attr($model); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Translate</th>
                    <td>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="radio" name="cv_oai_pll_content_type" value="post" <?php checked('post', $content_type); ?> /> Blog posts
                        </label>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="radio" name="cv_oai_pll_content_type" value="page" <?php checked('page', $content_type); ?> /> Pages
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cv_oai_pll_prompt">Prompt</label></th>
                    <td><textarea id="cv_oai_pll_prompt" name="cv_oai_pll_prompt" rows="4" class="large-text"><?php echo esc_textarea($prompt); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button('Save settings'); ?>
        </form>

        <hr />

        <h2>Run translation</h2>
        <p>Creates English (en) translations for Finnish (fi) blog posts or pages depending on the selected content type. New translations are created as drafts.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('cv_oai_pll_run'); ?>
            <input type="hidden" name="action" value="cv_oai_pll_run" />
            <?php submit_button('Run translations'); ?>
        </form>
    </div>
    <?php
}

function cv_oai_pll_call_openai($payload, $api_key) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'timeout' => 60,
        'body' => wp_json_encode($payload),
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error('openai_error', 'OpenAI API error: ' . $body);
    }

    $json = json_decode($body, true);
    if (!is_array($json) || empty($json['choices'][0]['message']['content'])) {
        return new WP_Error('openai_invalid', 'Invalid OpenAI response.');
    }

    return $json['choices'][0]['message']['content'];
}

function cv_oai_pll_parse_json($content) {
    $content = trim($content);
    if (str_starts_with($content, '```')) {
        $content = preg_replace('/^```(json)?/i', '', $content);
        $content = preg_replace('/```$/', '', $content);
        $content = trim($content);
    }

    $decoded = json_decode($content, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    return null;
}

function cv_oai_pll_translate_post($post, $target_lang, $api_key, $model, $prompt) {
    $prompt = str_replace('{LANG}', $target_lang, $prompt);

    $payload = array(
        'model' => $model,
        'temperature' => 0.2,
        'response_format' => array('type' => 'json_object'),
        'messages' => array(
            array(
                'role' => 'system',
                'content' => $prompt,
            ),
            array(
                'role' => 'user',
                'content' => wp_json_encode(array(
                    'title' => $post->post_title,
                    'excerpt' => $post->post_excerpt,
                    'content' => $post->post_content,
                )),
            ),
        ),
    );

    $content = cv_oai_pll_call_openai($payload, $api_key);
    if (is_wp_error($content)) {
        return $content;
    }

    $data = cv_oai_pll_parse_json($content);
    if (!$data) {
        return new WP_Error('openai_parse', 'Failed to parse translation response.');
    }

    return array(
        'title' => isset($data['title']) ? (string) $data['title'] : '',
        'excerpt' => isset($data['excerpt']) ? (string) $data['excerpt'] : '',
        'content' => isset($data['content']) ? (string) $data['content'] : '',
    );
}

function cv_oai_pll_run_translations() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }
    check_admin_referer('cv_oai_pll_run');

    if (!function_exists('pll_get_post')) {
        wp_safe_redirect(add_query_arg('cv_oai_pll_message', rawurlencode('Polylang is not active.'), wp_get_referer()));
        exit;
    }

    $api_key = get_option('cv_oai_pll_api_key', '');
    if (empty($api_key)) {
        wp_safe_redirect(add_query_arg('cv_oai_pll_message', rawurlencode('Missing OpenAI API key.'), wp_get_referer()));
        exit;
    }

    $model = get_option('cv_oai_pll_model', 'gpt-4o-mini');
    $post_types = cv_oai_pll_get_selected_post_types();
    $prompt = get_option('cv_oai_pll_prompt', cv_oai_pll_default_prompt());

    if (empty($post_types)) {
        wp_safe_redirect(add_query_arg('cv_oai_pll_message', rawurlencode('No post types selected.'), wp_get_referer()));
        exit;
    }

    $languages = array('en');
    $created = 0;
    $skipped = 0;
    $errors = 0;

    $query = new WP_Query(array(
        'post_type' => $post_types,
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'lang' => 'fi',
        'suppress_filters' => false,
    ));

    foreach ($query->posts as $post) {
        if (!has_post_thumbnail($post)) {
            $original_image_prompt = cv_oai_pll_build_image_prompt($post->post_title, $post->post_excerpt, $post->post_content, 'fi');
            cv_oai_pll_generate_and_attach_image($post->ID, $original_image_prompt, $api_key);
        }

        $translations = function_exists('pll_get_post_translations') ? pll_get_post_translations($post->ID) : array('fi' => $post->ID);
        foreach ($languages as $lang) {
            if (!empty($translations[$lang])) {
                $skipped++;
                continue;
            }

            $translated = cv_oai_pll_translate_post($post, $lang, $api_key, $model, $prompt);
            if (is_wp_error($translated)) {
                $errors++;
                continue;
            }

            $new_id = wp_insert_post(array(
                'post_type' => $post->post_type,
                'post_status' => $post->post_status,
                'post_title' => wp_strip_all_tags($translated['title']),
                'post_excerpt' => wp_kses_post($translated['excerpt']),
                'post_content' => wp_kses_post($translated['content']),
                'menu_order' => (int) $post->menu_order,
            ));

            if (is_wp_error($new_id) || !$new_id) {
                $errors++;
                continue;
            }

            if (!has_post_thumbnail($new_id)) {
                $translated_image_prompt = cv_oai_pll_build_image_prompt($translated['title'], $translated['excerpt'], $translated['content'], 'en');
                cv_oai_pll_generate_and_attach_image($new_id, $translated_image_prompt, $api_key);
            }

            if (function_exists('pll_set_post_language')) {
                pll_set_post_language($new_id, $lang);
            }

            $translations[$lang] = $new_id;
            if (function_exists('pll_save_post_translations')) {
                pll_save_post_translations($translations);
            }

            $created++;
        }
    }

    $message = sprintf('Created: %d, Skipped: %d, Errors: %d', $created, $skipped, $errors);
    wp_safe_redirect(add_query_arg('cv_oai_pll_message', rawurlencode($message), wp_get_referer()));
    exit;
}
add_action('admin_post_cv_oai_pll_run', 'cv_oai_pll_run_translations');
