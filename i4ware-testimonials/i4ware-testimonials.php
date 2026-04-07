<?php
/**
 * Plugin Name: i4ware Testimonials
 * Description: Adds anonymous customer testimonials with a shortcode form and Google reCAPTCHA spam protection.
 * Version: 1.0.0
 * Author: i4ware Software
 * Requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class I4ware_Testimonials_Plugin {
    const OPTION_GROUP = 'i4ware_testimonials_settings';
    const OPTION_SITE_KEY = 'i4ware_testimonials_recaptcha_site_key';
    const OPTION_SECRET_KEY = 'i4ware_testimonials_recaptcha_secret_key';
    const OPTION_RECAPTCHA_TYPE = 'i4ware_testimonials_recaptcha_type';
    const NONCE_ACTION = 'i4ware_submit_testimonial';

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'settings_page'));
        add_action('admin_post_i4ware_submit_testimonial', array($this, 'handle_submission'));
        add_action('admin_post_nopriv_i4ware_submit_testimonial', array($this, 'handle_submission'));
        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
    }

    public function register_assets() {
        wp_register_style(
            'i4ware-testimonials-style',
            plugin_dir_url(__FILE__) . 'assets/testimonials.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'assets/testimonials.css')
        );
    }

    public function register_post_type() {
        register_post_type('i4ware_testimonial', array(
            'labels' => array(
                'name' => __('Testimonials', 'i4ware-testimonials'),
                'singular_name' => __('Testimonial', 'i4ware-testimonials'),
                'menu_name' => __('Testimonials', 'i4ware-testimonials'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'supports' => array('editor'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-format-status',
        ));
    }

    public function register_shortcodes() {
        add_shortcode('i4ware_testimonial_form', array($this, 'render_form_shortcode'));
        add_shortcode('i4ware_testimonials', array($this, 'render_testimonials_shortcode'));
    }

    public function register_settings() {
        register_setting(self::OPTION_GROUP, self::OPTION_SITE_KEY, 'sanitize_text_field');
        register_setting(self::OPTION_GROUP, self::OPTION_SECRET_KEY, 'sanitize_text_field');
        register_setting(self::OPTION_GROUP, self::OPTION_RECAPTCHA_TYPE, 'sanitize_text_field');

        add_settings_section(
            'i4ware_testimonials_recaptcha_section',
            __('Google reCAPTCHA Settings', 'i4ware-testimonials'),
            function () {
                echo '<p>' . esc_html__('Select your reCAPTCHA key type. If you see "Invalid key type", your key type does not match the selected form mode.', 'i4ware-testimonials') . '</p>';
            },
            self::OPTION_GROUP
        );

        add_settings_field(
            self::OPTION_RECAPTCHA_TYPE,
            __('reCAPTCHA Type', 'i4ware-testimonials'),
            function () {
                $value = $this->get_recaptcha_type();
                echo '<select name="' . esc_attr(self::OPTION_RECAPTCHA_TYPE) . '">';
                echo '<option value="v2_checkbox" ' . selected($value, 'v2_checkbox', false) . '>' . esc_html__('v2 Checkbox (I am not a robot)', 'i4ware-testimonials') . '</option>';
                echo '<option value="v3" ' . selected($value, 'v3', false) . '>' . esc_html__('v3 (score-based, no checkbox)', 'i4ware-testimonials') . '</option>';
                echo '</select>';
            },
            self::OPTION_GROUP,
            'i4ware_testimonials_recaptcha_section'
        );

        add_settings_field(
            self::OPTION_SITE_KEY,
            __('Site Key', 'i4ware-testimonials'),
            function () {
                $value = esc_attr(get_option(self::OPTION_SITE_KEY, ''));
                echo '<input type="text" name="' . esc_attr(self::OPTION_SITE_KEY) . '" value="' . $value . '" class="regular-text" />';
            },
            self::OPTION_GROUP,
            'i4ware_testimonials_recaptcha_section'
        );

        add_settings_field(
            self::OPTION_SECRET_KEY,
            __('Secret Key', 'i4ware-testimonials'),
            function () {
                $value = esc_attr(get_option(self::OPTION_SECRET_KEY, ''));
                echo '<input type="password" name="' . esc_attr(self::OPTION_SECRET_KEY) . '" value="' . $value . '" class="regular-text" />';
            },
            self::OPTION_GROUP,
            'i4ware_testimonials_recaptcha_section'
        );
    }

    public function settings_page() {
        add_options_page(
            __('i4ware Testimonials', 'i4ware-testimonials'),
            __('i4ware Testimonials', 'i4ware-testimonials'),
            'manage_options',
            'i4ware-testimonials',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('i4ware Testimonials', 'i4ware-testimonials') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::OPTION_GROUP);
        do_settings_sections(self::OPTION_GROUP);
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function render_form_shortcode() {
        wp_enqueue_style('i4ware-testimonials-style');

        $site_key = get_option(self::OPTION_SITE_KEY, '');
        $recaptcha_type = $this->get_recaptcha_type();
        if (!empty($site_key)) {
            if ($recaptcha_type === 'v3') {
                wp_enqueue_script('google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($site_key), array(), null, true);
                $inline_script = 'document.addEventListener("DOMContentLoaded", function () {'
                    . 'var form = document.querySelector(".i4ware-testimonials-form");'
                    . 'if (!form || typeof grecaptcha === "undefined") { return; }'
                    . 'form.addEventListener("submit", function (event) {'
                    . 'if (form.getAttribute("data-recaptcha-ready") === "1") { return; }'
                    . 'event.preventDefault();'
                    . 'grecaptcha.ready(function () {'
                    . 'grecaptcha.execute(' . wp_json_encode($site_key) . ', {action: "i4ware_testimonial_submit"}).then(function (token) {'
                    . 'var input = form.querySelector("input[name=\"g-recaptcha-response\"]");'
                    . 'if (!input) { return; }'
                    . 'input.value = token;'
                    . 'form.setAttribute("data-recaptcha-ready", "1");'
                    . 'form.submit();'
                    . '});'
                    . '});'
                    . '});'
                    . '});';
                wp_add_inline_script('google-recaptcha-v3', $inline_script);
            } else {
                wp_enqueue_script('google-recaptcha-v2', 'https://www.google.com/recaptcha/api.js', array(), null, true);
            }
        }

        $status = isset($_GET['i4t_status']) ? sanitize_key($_GET['i4t_status']) : '';
        $status_messages = array(
            'success' => $this->t('status_success'),
            'invalid_nonce' => $this->t('status_invalid_nonce'),
            'missing_fields' => $this->t('status_missing_fields'),
            'spam' => $this->t('status_spam'),
            'error' => $this->t('status_error'),
        );

        ob_start();
        ?>
        <div class="i4ware-testimonials-form-wrap">
            <?php if (isset($status_messages[$status])) : ?>
                <div class="i4ware-testimonials-notice i4ware-testimonials-notice-<?php echo esc_attr($status === 'success' ? 'success' : 'error'); ?>">
                    <?php echo esc_html($status_messages[$status]); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="i4ware-testimonials-form">
                <input type="hidden" name="action" value="i4ware_submit_testimonial" />
                <?php wp_nonce_field(self::NONCE_ACTION, 'i4ware_testimonial_nonce'); ?>

                <label for="i4ware-industry"><?php echo esc_html($this->t('label_industry')); ?></label>
                <input id="i4ware-industry" type="text" name="industry" required maxlength="120" placeholder="<?php echo esc_attr($this->t('placeholder_industry')); ?>" />

                <label for="i4ware-writer-title"><?php echo esc_html($this->t('label_writer_title')); ?></label>
                <input id="i4ware-writer-title" type="text" name="writer_title" required maxlength="120" placeholder="<?php echo esc_attr($this->t('placeholder_writer_title')); ?>" />

                <label for="i4ware-feedback"><?php echo esc_html($this->t('label_testimonial')); ?></label>
                <textarea id="i4ware-feedback" name="testimonial" required rows="5" maxlength="2000" placeholder="<?php echo esc_attr($this->t('placeholder_testimonial')); ?>"></textarea>

                <fieldset class="i4ware-rating-fieldset">
                    <legend><?php echo esc_html($this->t('label_rating')); ?></legend>
                    <div class="i4ware-rating-options">
                        <?php for ($rating = 1; $rating <= 5; $rating++) : ?>
                            <label>
                                <input type="radio" name="rating" value="<?php echo esc_attr((string) $rating); ?>" <?php checked($rating, 5); ?> required />
                                <span><?php echo esc_html((string) $rating); ?> ★</span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </fieldset>

                <input type="text" name="website" value="" class="i4ware-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true" />

                <?php if (!empty($site_key) && $recaptcha_type === 'v2_checkbox') : ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site_key); ?>"></div>
                <?php elseif (!empty($site_key) && $recaptcha_type === 'v3') : ?>
                    <input type="hidden" name="g-recaptcha-response" value="" />
                <?php else : ?>
                    <p class="i4ware-testimonials-warning">
                        <?php echo esc_html($this->t('admin_note_recaptcha')); ?>
                    </p>
                <?php endif; ?>

                <button type="submit"><?php echo esc_html($this->t('submit_button')); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_testimonials_shortcode($atts) {
        wp_enqueue_style('i4ware-testimonials-style');

        $atts = shortcode_atts(array(
            'limit' => 10,
        ), $atts, 'i4ware_testimonials');

        $query = new WP_Query(array(
            'post_type' => 'i4ware_testimonial',
            'post_status' => 'publish',
            'posts_per_page' => max(1, absint($atts['limit'])),
            'orderby' => 'date',
            'order' => 'DESC',
        ));

        ob_start();
        echo '<div class="i4ware-testimonials-list">';

        if (!$query->have_posts()) {
            echo '<p>' . esc_html($this->t('no_testimonials')) . '</p>';
            echo '</div>';
            return ob_get_clean();
        }

        while ($query->have_posts()) {
            $query->the_post();
            $industry = get_post_meta(get_the_ID(), '_i4ware_industry', true);
            $writer_title = get_post_meta(get_the_ID(), '_i4ware_writer_title', true);
            $rating = (int) get_post_meta(get_the_ID(), '_i4ware_rating', true);
            if ($rating < 1 || $rating > 5) {
                $rating = 5;
            }
            $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

            echo '<article class="i4ware-testimonial-card">';
            echo '<div class="i4ware-testimonial-rating" aria-label="' . esc_attr(sprintf($this->t('rating_aria'), $rating)) . '">' . esc_html($stars) . '</div>';
            echo '<div class="i4ware-testimonial-content">' . wpautop(wp_kses_post(get_the_content())) . '</div>';
            echo '<p class="i4ware-testimonial-meta">';
            echo esc_html($writer_title);
            if (!empty($industry)) {
                echo esc_html(' · ' . $industry);
            }
            echo '</p>';
            echo '</article>';
        }
        wp_reset_postdata();

        echo '</div>';
        return ob_get_clean();
    }

    public function handle_submission() {
        $redirect_url = wp_get_referer();
        if (empty($redirect_url)) {
            $redirect_url = home_url('/');
        }

        if (!isset($_POST['i4ware_testimonial_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['i4ware_testimonial_nonce'])), self::NONCE_ACTION)) {
            wp_safe_redirect(add_query_arg('i4t_status', 'invalid_nonce', $redirect_url));
            exit;
        }

        $industry = isset($_POST['industry']) ? sanitize_text_field(wp_unslash($_POST['industry'])) : '';
        $writer_title = isset($_POST['writer_title']) ? sanitize_text_field(wp_unslash($_POST['writer_title'])) : '';
        $testimonial = isset($_POST['testimonial']) ? sanitize_textarea_field(wp_unslash($_POST['testimonial'])) : '';
        $rating = isset($_POST['rating']) ? absint($_POST['rating']) : 0;
        $honeypot = isset($_POST['website']) ? trim(wp_unslash($_POST['website'])) : '';

        if ($industry === '' || $writer_title === '' || $testimonial === '' || $rating < 1 || $rating > 5) {
            wp_safe_redirect(add_query_arg('i4t_status', 'missing_fields', $redirect_url));
            exit;
        }

        if (!empty($honeypot)) {
            wp_safe_redirect(add_query_arg('i4t_status', 'spam', $redirect_url));
            exit;
        }

        if (!$this->verify_recaptcha()) {
            wp_safe_redirect(add_query_arg('i4t_status', 'spam', $redirect_url));
            exit;
        }

        $post_id = wp_insert_post(array(
            'post_type' => 'i4ware_testimonial',
            'post_status' => 'pending',
            'post_title' => sprintf($this->t('post_title_anonymous'), $industry),
            'post_content' => $testimonial,
            'post_author' => 0,
        ), true);

        if (is_wp_error($post_id)) {
            wp_safe_redirect(add_query_arg('i4t_status', 'error', $redirect_url));
            exit;
        }

        update_post_meta($post_id, '_i4ware_industry', $industry);
        update_post_meta($post_id, '_i4ware_writer_title', $writer_title);
        update_post_meta($post_id, '_i4ware_rating', $rating);

        wp_safe_redirect(add_query_arg('i4t_status', 'success', $redirect_url));
        exit;
    }

    private function verify_recaptcha() {
        $secret_key = get_option(self::OPTION_SECRET_KEY, '');
        $token = isset($_POST['g-recaptcha-response']) ? sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'])) : '';
        $recaptcha_type = $this->get_recaptcha_type();

        if (empty($secret_key)) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'timeout' => 10,
            'body' => array(
                'secret' => $secret_key,
                'response' => $token,
                'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['success']) || $body['success'] !== true) {
            return false;
        }

        if ($recaptcha_type === 'v3') {
            $score = isset($body['score']) ? (float) $body['score'] : 0.0;
            $action = isset($body['action']) ? sanitize_text_field($body['action']) : '';
            if ($action !== 'i4ware_testimonial_submit') {
                return false;
            }
            return $score >= 0.5;
        }

        return true;
    }

    private function get_recaptcha_type() {
        $type = get_option(self::OPTION_RECAPTCHA_TYPE, 'v2_checkbox');
        if (!in_array($type, array('v2_checkbox', 'v3'), true)) {
            return 'v2_checkbox';
        }
        return $type;
    }

    private function get_language() {
        if (function_exists('pll_current_language')) {
            $lang = pll_current_language('slug');
            if (in_array($lang, array('fi', 'en'), true)) {
                return $lang;
            }
        }

        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        return strpos((string) $locale, 'fi') === 0 ? 'fi' : 'en';
    }

    private function t($key) {
        $lang = $this->get_language();
        $translations = array(
            'en' => array(
                'status_success' => 'Thank you. Your anonymous testimonial was submitted for review.',
                'status_invalid_nonce' => 'Security check failed. Please try again.',
                'status_missing_fields' => 'Please fill all required fields.',
                'status_spam' => 'Spam check failed. Please confirm you are not a robot.',
                'status_error' => 'Something went wrong. Please try again later.',
                'label_industry' => 'Customer Industry',
                'placeholder_industry' => 'e.g. Healthcare',
                'label_writer_title' => 'Writer Title',
                'placeholder_writer_title' => 'e.g. CEO',
                'label_testimonial' => 'Testimonial',
                'placeholder_testimonial' => 'Share your anonymous feedback',
                'label_rating' => 'How happy are you? (1-5)',
                'admin_note_recaptcha' => 'Admin note: add Google reCAPTCHA keys in Settings > i4ware Testimonials.',
                'submit_button' => 'Submit Testimonial',
                'no_testimonials' => 'No testimonials yet.',
                'rating_aria' => 'Rating: %d out of 5',
                'post_title_anonymous' => 'Anonymous testimonial from %s',
            ),
            'fi' => array(
                'status_success' => 'Kiitos. Anonyymi asiakaspalautteesi on vastaanotettu ja odottaa tarkistusta.',
                'status_invalid_nonce' => 'Tietoturvatarkistus epannistui. Yrita uudelleen.',
                'status_missing_fields' => 'Tayta kaikki pakolliset kentat.',
                'status_spam' => 'Roskapostitarkistus epannistui. Vahvista, ettet ole robotti.',
                'status_error' => 'Jokin meni pieleen. Yrita myohemmin uudelleen.',
                'label_industry' => 'Asiakkaan toimiala',
                'placeholder_industry' => 'esim. Terveydenhuolto',
                'label_writer_title' => 'Kirjoittajan titteli',
                'placeholder_writer_title' => 'esim. Toimitusjohtaja',
                'label_testimonial' => 'Asiakaspalaute',
                'placeholder_testimonial' => 'Kirjoita anonyymi palautteesi',
                'label_rating' => 'Kuinka tyytyvainen olet? (1-5)',
                'admin_note_recaptcha' => 'Yllapito: lisaa Google reCAPTCHA -avaimet asetuksiin kohdassa Asetukset > i4ware Testimonials.',
                'submit_button' => 'Laheta palaute',
                'no_testimonials' => 'Palautteita ei ole viela.',
                'rating_aria' => 'Arvio: %d / 5',
                'post_title_anonymous' => 'Anonyymi palaute toimialalta %s',
            ),
        );

        if (isset($translations[$lang][$key])) {
            return $translations[$lang][$key];
        }

        if (isset($translations['en'][$key])) {
            return $translations['en'][$key];
        }

        return '';
    }
}

new I4ware_Testimonials_Plugin();
