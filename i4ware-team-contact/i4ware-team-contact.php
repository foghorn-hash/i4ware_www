<?php
/*
Plugin Name: i4ware Team & Contact Section
Description: Team section with editable content and AJAX contact form via shortcode [i4ware_team].
Version: 1.0
Author: Matti Kiviharju
Author URI: https://www.i4ware.fi
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('team-contact', plugin_dir_url(__FILE__) . 'style.css');
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
    if ($lang === 'fi') {
        wp_enqueue_script('team-contact', plugin_dir_url(__FILE__) . 'yhteys.js', array('jquery'), null, true);
    } else {
        wp_enqueue_script('team-contact', plugin_dir_url(__FILE__) . 'contact.js', array('jquery'), null, true);
    }
    wp_localize_script('team-contact', 'i4ware_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('contact_nonce')
    ));
});

add_action('customize_register', function($wp_customize) {
    // Register customizer settings and controls for Team & Contact section
    $languages = array(
        'fi' => __('Finnish', 'i4ware'),
        'en' => __('English', 'i4ware')
    );
    if (function_exists('pll_languages_list')) {
        $pll_langs = pll_languages_list();
        $languages = array();
        foreach ($pll_langs as $lang) {
            $languages[$lang] = strtoupper($lang);
        }
    }

    $wp_customize->add_section('i4ware_team_section', array(
        'title' => __('Team & Contact', 'i4ware'),
        'priority' => 30,
    ));

    foreach ($languages as $lang_code => $lang_label) {
        $wp_customize->add_setting("i4ware_team_name_$lang_code", array(
            'default' => ($lang_code === 'fi') ? 'Matti Kiviharju, IT/ICT tradenomi' : 'Matti Kiviharju, Specialization in IT/ICT and BBA',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("i4ware_team_name_$lang_code", array(
            'label' => __('Team Member Name', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_team_section',
            'type' => 'text',
        ));

        $wp_customize->add_setting("i4ware_team_title_$lang_code", array(
            'default' => ($lang_code === 'fi') ? 'Yrittäjä, perustaja ja kokenut ohjelmistoarkkitehti' : 'Entrepreneur, Founder, and Expert Full-Stack Developer and Architect',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("i4ware_team_title_$lang_code", array(
            'label' => __('Team Member Title', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_team_section',
            'type' => 'text',
        ));

        $wp_customize->add_setting("i4ware_team_bio_$lang_code", array(
            'default' => ($lang_code === 'fi') ? 'Matti Kiviharju on kokenut ohjelmistoarkkitehti ja Full-Stack-kehittäjä...' : 'Matti Kiviharju is an experienced software architect and Full-Stack developer...',
            'sanitize_callback' => 'wp_kses_post',
        ));
        $wp_customize->add_control("i4ware_team_bio_$lang_code", array(
            'label' => __('Team Member Bio', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_team_section',
            'type' => 'textarea',
        ));

       $wp_customize->add_setting("i4ware_contact_details_$lang_code", array(
            'default' => ($lang_code === 'fi') ? "Sähköposti: info@i4ware.fi\nPuhelin: +358 40 123 4567" : "Email: info@i4ware.fi\nPhone: +358 40 123 4567",
            'sanitize_callback' => 'sanitize_textarea_field',
        ));

        $wp_customize->add_control("i4ware_contact_details_$lang_code", array(
            'label' => __('Contact Details', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_team_section',
            'type' => 'textarea',
        ));

        $wp_customize->add_setting("i4ware_vat_id_$lang_code", array(
            'default' => 'FI12345678',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("i4ware_vat_id_$lang_code", array(
            'label' => __('VAT ID', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_team_section',
            'type' => 'text',
        ));

        $wp_customize->add_setting("i4ware_business_id_$lang_code", array(
            'default' => '1234567-8',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        $wp_customize->add_control("i4ware_business_id_$lang_code", array(
            'label' => __('Business ID', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_team_section',
            'type' => 'text',
        ));

        $wp_customize->add_setting("i4ware_address_$lang_code", array(
            'default' => ($lang_code === 'fi') ? "Esimerkkikatu 1\n00100 Helsinki, Suomi" : "Example Street 1\n00100 Helsinki, Finland",
            'sanitize_callback' => 'sanitize_textarea_field',
        ));
        $wp_customize->add_control("i4ware_address_$lang_code", array(
            'label' => __('Address', 'i4ware') . " ($lang_label)",
            'section' => 'i4ware_team_section',
            'type' => 'textarea',
        ));
    }

    $wp_customize->add_setting('i4ware_team_img', array(
        'default' => plugin_dir_url(__FILE__) . 'assets/matti.jpg',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'i4ware_team_img', array(
        'label' => __('Team Member Image', 'i4ware'),
        'section' => 'i4ware_team_section',
        'settings' => 'i4ware_team_img',
    )));
    $wp_customize->add_setting('i4ware_team_cert1', array(
        'default' => plugin_dir_url(__FILE__) . 'assets/redhat1.png',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'i4ware_team_cert1', array(
        'label' => __('Certificate Image 1', 'i4ware'),
        'section' => 'i4ware_team_section',
        'settings' => 'i4ware_team_cert1',
    )));
    $wp_customize->add_setting('i4ware_team_cert2', array(
        'default' => plugin_dir_url(__FILE__) . 'assets/redhat2.png',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'i4ware_team_cert2', array(
        'label' => __('Certificate Image 2', 'i4ware'),
        'section' => 'i4ware_team_section',
        'settings' => 'i4ware_team_cert2',
    )));
});

add_shortcode('i4ware_team', function() {
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
    ob_start();
    ?>
    <section id="team" class="team-contact-wrap">
        <div class="contact-left">
            <h2><?php
                if ($lang === 'fi') {
                    echo 'Yhteystiedot';
                } else {
                    echo 'Contact Information';
                }
                ?></h2>
            <div class="contact-details">
                <strong><?php
                if ($lang === 'fi') {
                    echo 'Osoite';
                } else {
                    echo 'Address';
                }
                ?></strong><br>
                <?php echo nl2br(esc_html(get_theme_mod("i4ware_address_$lang", "Example Street 1\n00100 Helsinki, Finland"))); ?><br><br>
                <strong><?php
                if ($lang === 'fi') {
                    echo 'ALV-tunnus';
                } else {
                    echo 'VAT-ID';
                }
                ?></strong>
                <?php echo esc_html(get_theme_mod("i4ware_vat_id_$lang", 'FI12345678')); ?><br>
                <strong><?php
                if ($lang === 'fi') {
                    echo 'Y-tunnus';
                } else {
                    echo 'Corporate ID';
                }
                ?></strong>
                <?php echo esc_html(get_theme_mod("i4ware_business_id_$lang", '1234567-8')); ?><br><br>
                <?php echo nl2br(esc_html(get_theme_mod("i4ware_contact_details_$lang", "Email: info@i4ware.fi\nPhone: +358 40 123 4567"))); ?>
            </div>
            <h2><?php
                if ($lang === 'fi') {
                    echo 'Ota yhteyttälomake';
                } else {
                    echo 'Contact Us Form';
                }
                ?></h2>
            <form id="contact-form">
                <label for="contact-name">
                    <?php echo ($lang === 'fi') ? 'Koko nimesi' : 'Your Full Name'; ?>
                </label>
                <input type="text" id="contact-name" name="name" placeholder="<?php echo ($lang === 'fi') ? 'Matti Meikäläinen' : 'Matti Meikäläinen'; ?>" required>

                <label for="contact-email">
                    <?php echo ($lang === 'fi') ? 'Sähköposti' : 'Email'; ?>
                </label>
                <input type="email" id="contact-email" name="email" placeholder="<?php echo ($lang === 'fi') ? 'matti.meikalainen@osoite.com' : 'matti.meikalainen@address.com'; ?>" required>

                <label for="contact-message">
                    <?php echo ($lang === 'fi') ? 'Viesti' : 'Message'; ?>
                </label>
                <textarea id="contact-message" name="message" placeholder="<?php echo ($lang === 'fi') ? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis at lectus tortor.' : 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis at lectus tortor.'; ?>" required></textarea>

                <button type="submit">
                    <?php echo ($lang === 'fi') ? 'Lähetä viesti' : 'Send Message'; ?>
                </button>
                <div class="contact-response"></div>
            </form>
        </div>
        <div class="team-right">
            <h2><?php
                if ($lang === 'fi') {
                    echo 'Tiimi';
                } else {
                    echo 'Team';
                }
                ?></h2>
            <div class="team-member">
                <img src="<?php echo esc_url(get_theme_mod('i4ware_team_img', plugin_dir_url(__FILE__).'assets/matti.jpg')); ?>" alt="Matti Kiviharju" />
                <h3><?php echo esc_html(get_theme_mod("i4ware_team_name_$lang", 'Matti Kiviharju, Specialization in IT/ICT and BBA')); ?></h3>
                <p><strong><?php echo esc_html(get_theme_mod("i4ware_team_title_$lang", 'Entrepreneur, Founder, and Expert Full-Stack Developer and Architect')); ?></strong></p>
                <p><?php echo nl2br(esc_html(get_theme_mod("i4ware_team_bio_$lang", 'Matti Kiviharju is an experienced software architect and Full-Stack developer...'))); ?></p>
                <div class="certificate-container">
                    <img src="<?php echo esc_url(get_theme_mod('i4ware_team_cert1', plugin_dir_url(__FILE__).'assets/redhat1.png')); ?>" alt="Red Hat Certificate 1" />
                    <img src="<?php echo esc_url(get_theme_mod('i4ware_team_cert2', plugin_dir_url(__FILE__).'assets/redhat2.png')); ?>" alt="Red Hat Certificate 2" />
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
});

add_action('wp_ajax_i4ware_contact', 'i4ware_contact_form_handler');
add_action('wp_ajax_nopriv_i4ware_contact', 'i4ware_contact_form_handler');
function i4ware_contact_form_handler() {
    check_ajax_referer('contact_nonce', 'nonce');
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';

    $errors = array(
        'fi' => array(
            'required' => 'Kaikki kentät ovat pakollisia.',
            'success'  => 'Kiitos yhteydenotostasi!',
            'fail'     => 'Virhe viestin lähetyksessä.'
        ),
        'en' => array(
            'required' => 'All fields are required.',
            'success'  => 'Thank you for your message!',
            'fail'     => 'Error sending message.'
        )
    );
    
    $msg = $errors[$lang] ?? $errors['en'];

    if (!$name || !$email || !$message) {
        wp_send_json_error($msg['required']);
    }
    // Send email to admin
    $to = get_option('admin_email');
    $subject = 'Contact Form: ' . $name;
    $body = "Name: $name\nEmail: $email\n\n$message";
    $headers = array('Reply-To: ' . $email);
    if (wp_mail($to, $subject, $body, $headers)) {
        wp_send_json_success($msg['success']);
    } else {
        wp_send_json_error($msg['fail']);
    }
}