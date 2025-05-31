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
    wp_enqueue_style('i4ware-team-contact', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('i4ware-team-contact', plugin_dir_url(__FILE__) . 'contact.js', array('jquery'), null, true);
    wp_localize_script('i4ware-team-contact', 'i4ware_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('i4ware_contact_nonce')
    ));
});

// Customizer settings
add_action('customize_register', function($wp_customize) {
    $wp_customize->add_section('i4ware_team_section', array(
        'title' => __('Team & Contact', 'i4ware'),
        'priority' => 30,
    ));
    $wp_customize->add_setting('i4ware_team_name', array(
        'default' => 'Matti Kiviharju, Specialization in IT/ICT and BBA',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('i4ware_team_name', array(
        'label' => __('Team Member Name', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'text',
    ));
    $wp_customize->add_setting('i4ware_team_title', array(
        'default' => 'Entrepreneur, Founder, and Expert Full-Stack Developer and Architect',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('i4ware_team_title', array(
        'label' => __('Team Member Title', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'text',
    ));
    $wp_customize->add_setting('i4ware_team_bio', array(
        'default' => 'Matti Kiviharju is an experienced software architect and Full-Stack developer...',
        'sanitize_callback' => 'wp_kses_post',
    ));
    $wp_customize->add_control('i4ware_team_bio', array(
        'label' => __('Team Member Bio', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'textarea',
    ));
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
    $wp_customize->add_setting('i4ware_contact_details', array(
        'default' => "Email: info@i4ware.fi\nPhone: +358 40 123 4567",
        'sanitize_callback' => 'wp_kses_post',
    ));
    $wp_customize->add_control('i4ware_contact_details', array(
        'label' => __('Contact Details', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'textarea',
    ));
    $wp_customize->add_setting('i4ware_vat_id', array(
        'default' => 'FI12345678',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('i4ware_vat_id', array(
        'label' => __('VAT ID', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'text',
    ));
    $wp_customize->add_setting('i4ware_business_id', array(
        'default' => '1234567-8',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('i4ware_business_id', array(
        'label' => __('Business ID', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'text',
    ));
    $wp_customize->add_setting('i4ware_address', array(
        'default' => "Example Street 1\n00100 Helsinki, Finland",
        'sanitize_callback' => 'sanitize_textarea_field',
    ));
    $wp_customize->add_control('i4ware_address', array(
        'label' => __('Address', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'textarea',
    ));
});

// Shortcode
add_shortcode('i4ware_team', function() {
    ob_start();
    ?>
    <section id="team" class="i4ware-team-contact-wrap">
        <div class="i4ware-contact-left">
            <h2><?php _e('Contact', 'i4ware'); ?></h2>
            <div class="i4ware-contact-details">
                <strong><?php esc_html_e('Address:', 'i4ware'); ?></strong><br>
                <?php echo nl2br(esc_html(get_theme_mod('i4ware_address', "Example Street 1\n00100 Helsinki, Finland"))); ?><br><br>
                <strong><?php esc_html_e('VAT ID:', 'i4ware'); ?></strong>
                <?php echo esc_html(get_theme_mod('i4ware_vat_id', 'FI12345678')); ?><br>
                <strong><?php esc_html_e('Business ID:', 'i4ware'); ?></strong>
                <?php echo esc_html(get_theme_mod('i4ware_business_id', '1234567-8')); ?><br><br>
                <?php echo nl2br(esc_html(get_theme_mod('i4ware_contact_details', "Email: info@i4ware.fi\nPhone: +358 40 123 4567"))); ?>
            </div>
            <form id="i4ware-contact-form">
                <input type="text" name="name" placeholder="<?php esc_attr_e('Your Name', 'i4ware'); ?>" required>
                <input type="email" name="email" placeholder="<?php esc_attr_e('Your Email', 'i4ware'); ?>" required>
                <textarea name="message" placeholder="<?php esc_attr_e('Your Message', 'i4ware'); ?>" required></textarea>
                <button type="submit"><?php esc_html_e('Send', 'i4ware'); ?></button>
                <div class="i4ware-contact-response"></div>
            </form>
        </div>
        <div class="i4ware-team-right">
            <h2><?php _e('Team', 'i4ware'); ?></h2>
            <div class="team-member">
                <img src="<?php echo esc_url(get_theme_mod('i4ware_team_img', plugin_dir_url(__FILE__).'assets/matti.jpg')); ?>" alt="Matti Kiviharju" />
                <h3><?php echo esc_html(get_theme_mod('i4ware_team_name', 'Matti Kiviharju, Specialization in IT/ICT and BBA')); ?></h3>
                <p><strong><?php echo esc_html(get_theme_mod('i4ware_team_title', 'Entrepreneur, Founder, and Expert Full-Stack Developer and Architect')); ?></strong></p>
                <p><?php echo nl2br(esc_html(get_theme_mod('i4ware_team_bio', 'Matti Kiviharju is an experienced software architect and Full-Stack developer...'))); ?></p>
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

// AJAX handler for contact form
add_action('wp_ajax_i4ware_contact', 'i4ware_contact_form_handler');
add_action('wp_ajax_nopriv_i4ware_contact', 'i4ware_contact_form_handler');
function i4ware_contact_form_handler() {
    check_ajax_referer('i4ware_contact_nonce', 'nonce');
    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');
    if (!$name || !$email || !$message) {
        wp_send_json_error('All fields are required.');
    }
    // Send email to admin
    $to = get_option('admin_email');
    $subject = 'Contact Form: ' . $name;
    $body = "Name: $name\nEmail: $email\n\n$message";
    $headers = array('Reply-To: ' . $email);
    if (wp_mail($to, $subject, $body, $headers)) {
        wp_send_json_success('Thank you for contacting us!');
    } else {
        wp_send_json_error('Failed to send. Please try again.');
    }
}