<?php
/**
 * Class CV_OAI_PLL_Admin
 *
 * Handles WordPress admin settings, post editing meta boxes, asset enqueuing,
 * and AJAX translation request handling.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Admin {

    /**
     * Initializes hooks.
     */
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_cv_oai_pll_translate', [$this, 'handle_ajax_translation']);
    }

    /**
     * Registers settings in WordPress.
     */
    public function register_settings() {
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_api_key');
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_model');
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_post_types');
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_acf_fields');
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_cooldown');
    }

    /**
     * Registers the Options settings page.
     */
    public function add_settings_page() {
        add_options_page(
            __('OpenAI Polylang Translator', 'cv-openai-polylang-translator'),
            __('OpenAI Polylang', 'cv-openai-polylang-translator'),
            'manage_options',
            'cv-oai-polylang-translator',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Renders the options settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $api_key    = get_option('cv_oai_pll_api_key', '');
        $model      = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        $cooldown   = get_option('cv_oai_pll_cooldown', '0');
        $post_types = get_option('cv_oai_pll_post_types', ['post', 'page']);
        if (!is_array($post_types)) {
            $post_types = ['post', 'page'];
        }

        $acf_fields = get_option('cv_oai_pll_acf_fields', []);
        if (!is_array($acf_fields)) {
            $acf_fields = [];
        }

        // Fetch registered public post types
        $all_post_types = get_post_types(['public' => true], 'objects');

        // Fetch ACF fields if active
        $all_acf_fields = [];
        if (function_exists('acf_get_field_groups')) {
            $groups = acf_get_field_groups();
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    $fields = acf_get_fields($group);
                    if (is_array($fields)) {
                        foreach ($fields as $field) {
                            $all_acf_fields[] = [
                                'name'  => $field['name'],
                                'label' => $field['label'],
                                'type'  => $field['type'],
                            ];
                        }
                    }
                }
            }
        }
        ?>
        <div class="wrap cv-oai-pll-settings-wrap">
            <h1><?php esc_html_e('OpenAI Polylang Translator Settings', 'cv-openai-polylang-translator'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('cv_oai_pll_settings_group'); ?>
                
                <table class="form-table" role="presentation">
                    <!-- OpenAI API Key -->
                    <tr>
                        <th scope="row">
                            <label for="cv_oai_pll_api_key"><?php esc_html_e('OpenAI API Key', 'cv-openai-polylang-translator'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="cv_oai_pll_api_key" name="cv_oai_pll_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off" />
                            <p class="description"><?php esc_html_e('Your confidential API key remains hidden on load and is never logged.', 'cv-openai-polylang-translator'); ?></p>
                        </td>
                    </tr>

                    <!-- OpenAI Model -->
                    <tr>
                        <th scope="row">
                            <label for="cv_oai_pll_model"><?php esc_html_e('OpenAI Model', 'cv-openai-polylang-translator'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cv_oai_pll_model" name="cv_oai_pll_model" value="<?php echo esc_attr($model); ?>" class="regular-text" placeholder="gpt-4o-mini" />
                            <p class="description"><?php esc_html_e('Recommended: gpt-4o-mini or gpt-4o for best structural adherence and B2B translation quality.', 'cv-openai-polylang-translator'); ?></p>
                        </td>
                    </tr>

                    <!-- Cooldown -->
                    <tr>
                        <th scope="row">
                            <label for="cv_oai_pll_cooldown"><?php esc_html_e('Translation Cooldown (seconds)', 'cv-openai-polylang-translator'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cv_oai_pll_cooldown" name="cv_oai_pll_cooldown" value="<?php echo esc_attr($cooldown); ?>" class="small-text" min="0" max="60" />
                            <p class="description"><?php esc_html_e('Optional delay (cooldown) between consecutive OpenAI API chunk requests (0 to disable). Helps avoid API rate limit triggers.', 'cv-openai-polylang-translator'); ?></p>
                        </td>
                    </tr>

                    <!-- Post Types -->
                    <tr>
                        <th scope="row"><?php esc_html_e('Supported Post Types', 'cv-openai-polylang-translator'); ?></th>
                        <td>
                            <fieldset>
                                <?php foreach ($all_post_types as $pt_slug => $pt_obj) : ?>
                                    <label style="display:block; margin-bottom: 5px;">
                                        <input type="checkbox" name="cv_oai_pll_post_types[]" value="<?php echo esc_attr($pt_slug); ?>" <?php checked(in_array($pt_slug, $post_types, true)); ?> />
                                        <?php echo esc_html($pt_obj->label); ?> (<code><?php echo esc_html($pt_slug); ?></code>)
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>

                    <!-- Translate ACF Fields Option -->
                    <tr>
                        <th scope="row"><?php esc_html_e('Translate ACF fields', 'cv-openai-polylang-translator'); ?></th>
                        <td>
                            <?php if (empty($all_acf_fields)) : ?>
                                <p class="description">
                                    <?php if (!function_exists('acf_get_field_groups')) : ?>
                                        <?php esc_html_e('Advanced Custom Fields is not active.', 'cv-openai-polylang-translator'); ?>
                                    <?php else : ?>
                                        <?php esc_html_e('No Advanced Custom Fields registered.', 'cv-openai-polylang-translator'); ?>
                                    <?php endif; ?>
                                </p>
                            <?php else : ?>
                                <p class="description" style="margin-bottom: 10px;"><?php esc_html_e('Choose which ACF field names are enabled for translation. Only textual sub-fields of repeaters, flexible layouts, groups, or text areas will be parsed.', 'cv-openai-polylang-translator'); ?></p>
                                <div style="max-height: 250px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #fff; max-width: 500px;">
                                    <?php foreach ($all_acf_fields as $field) : ?>
                                        <label style="display:block; margin-bottom: 5px;">
                                            <input type="checkbox" name="cv_oai_pll_acf_fields[]" value="<?php echo esc_attr($field['name']); ?>" <?php checked(in_array($field['name'], $acf_fields, true)); ?> />
                                            <strong><?php echo esc_html($field['label']); ?></strong> (<code><?php echo esc_html($field['name']); ?></code>) - <span class="description"><?php echo esc_html($field['type']); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save settings', 'cv-openai-polylang-translator')); ?>
            </form>

            <hr style="margin: 30px 0;" />

            <h2><?php esc_html_e('Translation Run Logs & History', 'cv-openai-polylang-translator'); ?></h2>
            <?php
            $history = CV_OAI_PLL_Logger::get_global_history();
            if (empty($history)) :
                echo '<p>' . esc_html__('No translation history logged yet.', 'cv-openai-polylang-translator') . '</p>';
            else :
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'cv-openai-polylang-translator'); ?></th>
                            <th><?php esc_html_e('Source ID', 'cv-openai-polylang-translator'); ?></th>
                            <th><?php esc_html_e('Draft ID', 'cv-openai-polylang-translator'); ?></th>
                            <th><?php esc_html_e('Target Language', 'cv-openai-polylang-translator'); ?></th>
                            <th><?php esc_html_e('Model', 'cv-openai-polylang-translator'); ?></th>
                            <th><?php esc_html_e('Fields', 'cv-openai-polylang-translator'); ?></th>
                            <th><?php esc_html_e('Duration', 'cv-openai-polylang-translator'); ?></th>
                            <th><?php esc_html_e('Result', 'cv-openai-polylang-translator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log['date']); ?></td>
                                <td>
                                    <?php
                                    $src_title = get_the_title($log['source_post_id']);
                                    $src_title = $src_title ? $src_title : '#' . $log['source_post_id'];
                                    echo '<a href="' . esc_url(get_edit_post_link($log['source_post_id'])) . '">' . esc_html($src_title) . '</a>';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($log['success'] && $log['draft_id']) {
                                        $draft_title = get_the_title($log['draft_id']);
                                        $draft_title = $draft_title ? $draft_title : '#' . $log['draft_id'];
                                        echo '<a href="' . esc_url(get_edit_post_link($log['draft_id'])) . '">' . esc_html($draft_title) . '</a>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><code><?php echo esc_html($log['target_lang']); ?></code></td>
                                <td><?php echo esc_html($log['model']); ?></td>
                                <td><?php echo esc_html($log['num_fields']); ?></td>
                                <td><?php echo esc_html($log['duration']); ?>s</td>
                                <td>
                                    <?php if ($log['success']) : ?>
                                        <span style="color: green; font-weight: bold;"><?php esc_html_e('Success', 'cv-openai-polylang-translator'); ?></span>
                                    <?php else : ?>
                                        <span style="color: red; font-weight: bold;"><?php esc_html_e('Failed', 'cv-openai-polylang-translator'); ?></span>
                                        <div style="font-size: 11px; color: #666; margin-top: 3px; max-width: 250px; white-space: normal; word-break: break-all;">
                                            <?php echo esc_html($log['error_message']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Registers meta boxes for supported content types and translations.
     */
    public function add_meta_boxes($post_type) {
        $supported_post_types = get_option('cv_oai_pll_post_types', ['post', 'page']);
        if (!is_array($supported_post_types)) {
            $supported_post_types = ['post', 'page'];
        }

        // 1. Meta Box on source posts: OpenAI translation action interface
        if (in_array($post_type, $supported_post_types, true)) {
            add_meta_box(
                'cv_oai_pll_translator_box',
                __('OpenAI Polylang Translator', 'cv-openai-polylang-translator'),
                [$this, 'render_translation_meta_box'],
                $post_type,
                'side',
                'high'
            );
        }

        // 2. Meta Box on target posts: Human Review checkbox
        global $post;
        if ($post) {
            $source_post_id = get_post_meta($post->ID, '_cv_oai_source_post_id', true);
            if ($source_post_id) {
                add_meta_box(
                    'cv_oai_pll_review_box',
                    __('Translation Review Status', 'cv-openai-polylang-translator'),
                    [$this, 'render_review_meta_box'],
                    $post_type,
                    'side',
                    'high'
                );
            }
        }
    }

    /**
     * Renders the translation action meta box on source posts.
     */
    public function render_translation_meta_box($post) {
        if (!function_exists('pll_get_post_language') || !function_exists('pll_languages_list')) {
            echo '<p class="notice notice-warning" style="padding: 5px;">' . esc_html__('Polylang is not active.', 'cv-openai-polylang-translator') . '</p>';
            return;
        }

        $post_id     = $post->ID;
        $source_lang = pll_get_post_language($post_id);

        if ($source_lang !== 'fi') {
            echo '<p class="description">' . esc_html__('Only posts in Finnish (fi) can be translated using this plugin.', 'cv-openai-polylang-translator') . '</p>';
            return;
        }

        $api_key = get_option('cv_oai_pll_api_key', '');
        $missing_api_key = empty($api_key);

        // Fetch other configured languages
        $pll_languages = pll_languages_list(['fields' => null]);
        $target_languages = [];
        if (is_array($pll_languages)) {
            foreach ($pll_languages as $l) {
                if ($l->slug !== 'fi') {
                    $target_languages[] = $l;
                }
            }
        }

        // Ensure post is saved
        $is_saved = ($post->post_status !== 'auto-draft');

        // Check locks
        $is_locked = CV_OAI_PLL_Translation_Lock::is_locked();
        $locked_id = CV_OAI_PLL_Translation_Lock::get_locked_post_id();

        // Pass details to JS via HTML attributes
        $translations_data = [];
        $translations = pll_get_post_translations($post_id);
        if (is_array($translations)) {
            foreach ($translations as $lang => $tr_id) {
                if ($tr_id != $post_id) {
                    $tr_post = get_post($tr_id);
                    if ($tr_post) {
                        $translations_data[$lang] = [
                            'id'       => $tr_id,
                            'status'   => $tr_post->post_status,
                            'edit_url' => get_edit_post_link($tr_id, 'raw'),
                        ];
                    }
                }
            }
        }
        $translations_json = wp_json_encode($translations_data);
        ?>
        <div id="cv-oai-pll-meta-box-root" data-post-id="<?php echo (int) $post_id; ?>" data-existing-translations="<?php echo esc_attr($translations_json); ?>">
            
            <?php wp_nonce_field('cv_oai_pll_translate_nonce', 'cv_oai_pll_nonce'); ?>
            
            <?php if (!$is_saved) : ?>
                <p style="color: #d63638; font-weight: bold;">
                    <?php esc_html_e('Please save the post as a draft before attempting translation.', 'cv-openai-polylang-translator'); ?>
                </p>
            <?php endif; ?>

            <?php if ($missing_api_key) : ?>
                <p style="color: #d63638;">
                    <?php esc_html_e('OpenAI API key is missing. Configure it in plugin settings.', 'cv-openai-polylang-translator'); ?>
                </p>
            <?php endif; ?>

            <!-- Target Language Select -->
            <p>
                <label for="cv_oai_pll_target_lang"><strong><?php esc_html_e('Target Language:', 'cv-openai-polylang-translator'); ?></strong></label><br />
                <select id="cv_oai_pll_target_lang" style="width: 100%; margin-top: 5px;">
                    <option value=""><?php esc_html_e('-- Select Language --', 'cv-openai-polylang-translator'); ?></option>
                    <?php foreach ($target_languages as $l) : ?>
                        <option value="<?php echo esc_attr($l->slug); ?>"><?php echo esc_html($l->name); ?> (<code><?php echo esc_html($l->slug); ?></code>)</option>
                    <?php endforeach; ?>
                </select>
            </p>

            <!-- Translate sections checkboxes -->
            <p><strong><?php esc_html_e('Translate Sections:', 'cv-openai-polylang-translator'); ?></strong></p>
            <div style="margin-bottom: 15px; border-left: 2px solid #2271b1; padding-left: 10px;">
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_title" checked /> <?php esc_html_e('Post Title', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_excerpt" checked /> <?php esc_html_e('Excerpt', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_content" checked /> <?php esc_html_e('Main Body Content', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_acf" checked /> <?php esc_html_e('Selected ACF Fields', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_caption" /> <?php esc_html_e('Image Captions', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_alt" /> <?php esc_html_e('Image Alt Text', 'cv-openai-polylang-translator'); ?>
                </label>
            </div>

            <!-- Overwrite verification interface, populated dynamically via JS -->
            <div id="cv-oai-pll-overwrite-container" style="display: none; background: #fff8e5; border-left: 4px solid #f0b840; padding: 10px; margin-bottom: 15px;">
                <p style="margin:0 0 8px 0; font-size:12px; font-weight:600;" id="cv-oai-pll-overwrite-msg"></p>
                <a href="#" id="cv-oai-pll-open-existing-link" class="button button-small" target="_blank" style="margin-bottom: 8px; display:inline-block;"><?php esc_html_e('Open Existing Draft', 'cv-openai-polylang-translator'); ?></a>
                <label style="display:block; font-size: 11px; margin-top:5px; color:#2c3338;">
                    <input type="checkbox" id="cv_oai_pll_confirm_overwrite" />
                    <?php esc_html_e('I confirm that I want to Retranslate and Overwrite the existing draft translation.', 'cv-openai-polylang-translator'); ?>
                </label>
            </div>

            <div id="cv-oai-pll-published-warning" style="display: none; background: #fcf0f1; border-left: 4px solid #d63638; padding: 10px; margin-bottom: 15px;">
                <p style="margin:0; font-size:12px; color:#d63638; font-weight:600;"><?php esc_html_e('A published translation already exists for this language and cannot be overwritten.', 'cv-openai-polylang-translator'); ?></p>
            </div>

            <!-- Translate Action button -->
            <div style="margin-top: 15px;">
                <?php
                $disabled = (!$is_saved || $missing_api_key || $is_locked) ? 'disabled' : '';
                ?>
                <button type="button" id="cv_oai_pll_submit_btn" class="button button-primary button-large" style="width: 100%; text-align: center;" <?php echo esc_attr($disabled); ?>>
                    <?php esc_html_e('Translate with OpenAI', 'cv-openai-polylang-translator'); ?>
                </button>
                <div id="cv-oai-pll-spinner" class="spinner" style="float: none; display: block; margin: 10px auto; text-align: center;"></div>
            </div>

            <!-- Lock warning -->
            <?php if ($is_locked) : ?>
                <p id="cv-oai-pll-lock-warning" style="color: #b58100; font-size: 11px; margin-top: 8px;">
                    <?php
                    if ($locked_id == $post_id) {
                        esc_html_e('This post is currently locked as a translation job is already active for it.', 'cv-openai-polylang-translator');
                    } else {
                        esc_html_e('Another translation job is running on the server. Buttons are locked.', 'cv-openai-polylang-translator');
                    }
                    ?>
                </p>
            <?php endif; ?>

            <div id="cv-oai-pll-status-message" style="margin-top:10px; font-size: 12px; font-weight: 600;"></div>

            <!-- History log for this post -->
            <hr style="margin: 15px 0 10px 0;" />
            <p style="margin:0; font-size:11px; text-transform:uppercase; color:#646970;"><strong><?php esc_html_e('Translation History:', 'cv-openai-polylang-translator'); ?></strong></p>
            <div style="max-height: 120px; overflow-y: auto; font-size: 11px; color: #50575e; margin-top: 5px;" id="cv-oai-pll-history-list">
                <?php
                $post_history = CV_OAI_PLL_Logger::get_post_history($post_id);
                if (empty($post_history)) :
                    echo '<p style="margin:0;">' . esc_html__('No history for this item.', 'cv-openai-polylang-translator') . '</p>';
                else :
                    foreach ($post_history as $h) :
                        $status_str = $h['success'] ? __('Success', 'cv-openai-polylang-translator') : __('Failed', 'cv-openai-polylang-translator');
                        $color = $h['success'] ? 'green' : 'red';
                        echo '<div style="margin-bottom:6px; border-bottom:1px solid #f0f0f1; padding-bottom:4px;">';
                        echo '<strong>' . esc_html($h['date']) . '</strong> to <code>' . esc_html($h['target_lang']) . '</code><br />';
                        echo sprintf(
                            __('Status: <span style="color:%s;">%s</span> | Fields: %d | Time: %ss', 'cv-openai-polylang-translator'),
                            esc_attr($color),
                            esc_html($status_str),
                            (int) $h['num_fields'],
                            esc_html($h['duration'])
                        );
                        if (!$h['success'] && !empty($h['error_message'])) {
                            echo '<br /><span style="color:red; font-style:italic;">' . esc_html($h['error_message']) . '</span>';
                        }
                        echo '</div>';
                    endforeach;
                endif;
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renders the review status checkbox and warning notice on translated posts/pages.
     */
    public function render_review_meta_box($post) {
        $review_required = get_post_meta($post->ID, '_cv_oai_review_required', true);
        $source_post_id  = get_post_meta($post->ID, '_cv_oai_source_post_id', true);
        $target_lang     = get_post_meta($post->ID, '_cv_oai_target_language', true);

        // Render review warning notice
        ?>
        <div class="cv-oai-pll-review-box-inner" data-is-translation-draft="1" data-review-required="<?php echo esc_attr($review_required); ?>">
            <p style="background: #fff8e5; border-left: 4px solid #f0b840; padding: 10px; color: #2c3338; font-size:12px; margin:0 0 12px 0;">
                <strong><?php esc_html_e('Warning:', 'cv-openai-polylang-translator'); ?></strong><br />
                <?php esc_html_e('This content was translated by OpenAI and may contain errors or hallucinations. Check all facts, terminology, links, numbers, customer claims and formatting before publishing.', 'cv-openai-polylang-translator'); ?>
            </p>

            <label style="font-weight: bold; display: block; margin-bottom: 8px;">
                <input type="checkbox" name="cv_oai_review_completed" value="1" <?php checked($review_required, '0'); ?> />
                <?php esc_html_e('Translation reviewed by a human', 'cv-openai-polylang-translator'); ?>
            </label>

            <p class="description" style="font-size:11px;">
                <?php
                echo sprintf(
                    __('Source Post: <a href="%s" target="_blank">#%d</a> | Language: <code>%s</code>', 'cv-openai-polylang-translator'),
                    esc_url(get_edit_post_link($source_post_id)),
                    (int) $source_post_id,
                    esc_html($target_lang)
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Saves the meta box data when a post/page is saved.
     */
    public function save_meta_box_data($post_id) {
        // Prevent saving during autosave or revision
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verify nonce is NOT required here as we only save custom review status checkbox from editor
        if (isset($_POST['cv_oai_review_completed'])) {
            // Checked: review completed, meaning NOT required anymore
            update_post_meta($post_id, '_cv_oai_review_required', '0');
        } else {
            // Unchecked: review required
            // Only update if it is an OpenAI translation post (it has source_post_id)
            $source_post_id = get_post_meta($post_id, '_cv_oai_source_post_id', true);
            if ($source_post_id) {
                update_post_meta($post_id, '_cv_oai_review_required', '1');
            }
        }
    }

    /**
     * Enqueues administration JS and CSS assets.
     */
    public function enqueue_assets($hook) {
        if (in_array($hook, ['post.php', 'post-new.php', 'settings_page_cv-oai-polylang-translator'], true)) {
            // CSS
            wp_enqueue_style(
                'cv-oai-pll-admin-css',
                plugins_url('assets/css/admin.css', dirname(__FILE__)),
                [],
                '1.0.0'
            );

            // JS
            wp_enqueue_script(
                'cv-oai-pll-admin-js',
                plugins_url('assets/js/admin.js', dirname(__FILE__)),
                ['jquery'],
                '1.0.0',
                true
            );

            // Localize text variables for JS
            wp_localize_script('cv-oai-pll-admin-js', 'cvOaiPllL10n', [
                'ajax_url'                  => admin_url('admin-ajax.php'),
                'confirm_publish_warning'  => __('This translation has not been marked as human-reviewed. Are you sure you want to publish it?', 'cv-openai-polylang-translator'),
                'translating_msg'           => __('Translating with OpenAI... Please wait, processing content chunks sequentially.', 'cv-openai-polylang-translator'),
                'success_msg'               => __('Translation completed successfully! Draft created.', 'cv-openai-polylang-translator'),
                'open_draft_lbl'            => __('Open Draft Translation', 'cv-openai-polylang-translator'),
                'select_lang_error'         => __('Please select a target language first.', 'cv-openai-polylang-translator'),
                'confirm_overwrite_error'   => __('Please check the confirm box to overwrite the existing draft translation.', 'cv-openai-polylang-translator'),
                'translation_failed_lbl'    => __('Translation failed: ', 'cv-openai-polylang-translator'),
            ]);
        }
    }

    /**
     * AJAX handler for performing the single-post translation workflow.
     */
    public function handle_ajax_translation() {
        check_ajax_referer('cv_oai_pll_translate_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Insufficient permissions to edit this post.', 'cv-openai-polylang-translator')]);
        }

        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : '';
        if (empty($target_lang)) {
            wp_send_json_error(['message' => __('Please specify a target language.', 'cv-openai-polylang-translator')]);
        }

        // Get extraction options from AJAX payload
        $options = [
            'title'   => !empty($_POST['opt_title']),
            'excerpt' => !empty($_POST['opt_excerpt']),
            'content' => !empty($_POST['opt_content']),
            'acf'     => !empty($_POST['opt_acf']),
            'caption' => !empty($_POST['opt_caption']),
            'alt'     => !empty($_POST['opt_alt']),
        ];

        $overwrite_draft = !empty($_POST['confirm_overwrite']);

        // Run translation
        $result = CV_OAI_PLL_Translator::translate_post($post_id, $target_lang, $options, $overwrite_draft);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        $draft_url = get_edit_post_link($result, 'raw');
        wp_send_json_success([
            'draft_id'  => $result,
            'draft_url' => $draft_url,
        ]);
    }
}
