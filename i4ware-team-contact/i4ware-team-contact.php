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
    wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css');
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

    // LinkedIn, Email and Phone customizer settings
    $wp_customize->add_setting('i4ware_team_linkedin', array(
        'default' => 'https://www.linkedin.com/in/walkout/',
        'sanitize_callback' => 'esc_url_raw',
    ));
    $wp_customize->add_control('i4ware_team_linkedin', array(
        'label' => __('LinkedIn Profile URL', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'url',
    ));

    $wp_customize->add_setting('i4ware_team_email', array(
        'default' => 'matti.kiviharju@i4ware.fi',
        'sanitize_callback' => 'sanitize_email',
    ));
    $wp_customize->add_control('i4ware_team_email', array(
        'label' => __('Personal Email', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'email',
    ));

    $wp_customize->add_setting('i4ware_team_phone', array(
        'default' => '+358 40 8200 491',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('i4ware_team_phone', array(
        'label' => __('Phone Number', 'i4ware'),
        'section' => 'i4ware_team_section',
        'type' => 'text',
    ));
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

                <?php
                $recaptcha_site_key = '';
                if (defined('I4WARE_RECAPTCHA_SITE_KEY')) {
                    $recaptcha_site_key = I4WARE_RECAPTCHA_SITE_KEY;
                } elseif (defined('RECAPTCHA_SITE_KEY')) {
                    $recaptcha_site_key = RECAPTCHA_SITE_KEY;
                } elseif (defined('GOOGLE_RECAPTCHA_SITE_KEY')) {
                    $recaptcha_site_key = GOOGLE_RECAPTCHA_SITE_KEY;
                } else {
                    $recaptcha_site_key = get_option('i4ware_recaptcha_site_key', '');
                }

                if (!empty($recaptcha_site_key)) : ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>" style="margin-bottom: 20px;"></div>
                    <script src="https://www.google.com/recaptcha/api.js?hl=<?php echo $lang; ?>" async defer></script>
                <?php endif; ?>

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
                
                <div class="team-member-contact">
                    <?php
                    $linkedin = get_theme_mod('i4ware_team_linkedin', 'https://www.linkedin.com/in/walkout/');
                    $email = get_theme_mod('i4ware_team_email', 'matti.kiviharju@i4ware.fi');
                    $phone = get_theme_mod('i4ware_team_phone', '+358 40 8200 491');

                    if ($linkedin) : ?>
                        <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener" class="contact-icon-link linkedin-link" title="LinkedIn">
                            <i class="bi bi-linkedin"></i>
                        </a>
                    <?php endif;

                    if ($email) : ?>
                        <a href="mailto:<?php echo sanitize_email($email); ?>" class="contact-icon-link email-link" title="<?php echo esc_attr(($lang === 'fi') ? 'Sähköposti' : 'Email'); ?>">
                            <i class="bi bi-envelope-fill"></i>
                        </a>
                    <?php endif;

                    if ($phone) : ?>
                        <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>" class="contact-icon-link phone-link" title="<?php echo esc_attr(($lang === 'fi') ? 'Puhelin' : 'Phone'); ?>">
                            <i class="bi bi-telephone-fill"></i>
                        </a>
                    <?php endif; ?>
                </div>

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

    // Verify reCAPTCHA
    $recaptcha_site_key = '';
    if (defined('I4WARE_RECAPTCHA_SITE_KEY')) {
        $recaptcha_site_key = I4WARE_RECAPTCHA_SITE_KEY;
    } elseif (defined('RECAPTCHA_SITE_KEY')) {
        $recaptcha_site_key = RECAPTCHA_SITE_KEY;
    } elseif (defined('GOOGLE_RECAPTCHA_SITE_KEY')) {
        $recaptcha_site_key = GOOGLE_RECAPTCHA_SITE_KEY;
    } else {
        $recaptcha_site_key = get_option('i4ware_recaptcha_site_key', '');
    }

    $recaptcha_secret_key = '';
    if (defined('I4WARE_RECAPTCHA_SECRET_KEY')) {
        $recaptcha_secret_key = I4WARE_RECAPTCHA_SECRET_KEY;
    } elseif (defined('RECAPTCHA_SECRET_KEY')) {
        $recaptcha_secret_key = RECAPTCHA_SECRET_KEY;
    } elseif (defined('GOOGLE_RECAPTCHA_SECRET_KEY')) {
        $recaptcha_secret_key = GOOGLE_RECAPTCHA_SECRET_KEY;
    } else {
        $recaptcha_secret_key = get_option('i4ware_recaptcha_secret_key', '');
    }

    if (!empty($recaptcha_site_key) && !empty($recaptcha_secret_key)) {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptcha_response)) {
            wp_send_json_error(($lang === 'fi') ? 'Ole hyvä ja vahvista reCAPTCHA.' : 'Please verify the reCAPTCHA.');
        }

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $recaptcha_secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('reCAPTCHA verification server error.');
        }

        $response_body = wp_remote_retrieve_body($response);
        $result = json_decode($response_body, true);

        if (empty($result['success'])) {
            wp_send_json_error(($lang === 'fi') ? 'Ole hyvä ja vahvista reCAPTCHA.' : 'Please verify the reCAPTCHA.');
        }
    }

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

// Register Custom Post Type for Team Members
add_action('init', function() {
    $labels = array(
        'name'               => _x( 'Team Members', 'post type general name', 'i4ware' ),
        'singular_name'      => _x( 'Team Member', 'post type singular name', 'i4ware' ),
        'menu_name'          => _x( 'Team Members', 'admin menu', 'i4ware' ),
        'name_admin_bar'     => _x( 'Team Member', 'add new on admin bar', 'i4ware' ),
        'add_new'            => _x( 'Add New', 'team member', 'i4ware' ),
        'add_new_item'       => __( 'Add New Team Member', 'i4ware' ),
        'new_item'           => __( 'New Team Member', 'i4ware' ),
        'edit_item'          => __( 'Edit Team Member', 'i4ware' ),
        'view_item'          => __( 'View Team Member', 'i4ware' ),
        'all_items'          => __( 'All Team Members', 'i4ware' ),
        'search_items'       => __( 'Search Team Members', 'i4ware' ),
        'parent_item_colon'  => __( 'Parent Team Members:', 'i4ware' ),
        'not_found'          => __( 'No team members found.', 'i4ware' ),
        'not_found_in_trash' => __( 'No team members found in Trash.', 'i4ware' )
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'team-member' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'thumbnail', 'editor' )
    );

    register_post_type( 'team_member', $args );
});

// Register Team Member CPT with Polylang programmatically
add_filter( 'pll_get_post_types', function( $post_types ) {
    $post_types['team_member'] = 'team_member';
    return $post_types;
} );

// Programmatically register ACF local field group for Team Member Details
add_action('acf/init', function() {
    if( function_exists('acf_add_local_field_group') ) {
        acf_add_local_field_group(array(
            'key' => 'group_team_member_details',
            'title' => 'Team Member Details',
            'fields' => array(
                array(
                    'key' => 'field_team_position',
                    'label' => 'Position',
                    'name' => 'position',
                    'type' => 'text',
                    'instructions' => 'Job title or position (e.g. Senior Software Engineer)',
                    'required' => 1,
                ),
                array(
                    'key' => 'field_team_linkedin',
                    'label' => 'LinkedIn Profile URL',
                    'name' => 'linkedin_url',
                    'type' => 'url',
                    'instructions' => 'URL to the team member\'s LinkedIn profile',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_team_phone',
                    'label' => 'Mobile Phone',
                    'name' => 'mobile_phone',
                    'type' => 'text',
                    'instructions' => 'Mobile phone number',
                    'required' => 0,
                ),
                array(
                    'key' => 'field_team_email',
                    'label' => 'Email',
                    'name' => 'email',
                    'type' => 'email',
                    'instructions' => 'Email address',
                    'required' => 0,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'team_member',
                    ),
                ),
            ),
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
        ));
    }
});

// Shortcode to display list of team members
add_shortcode('i4ware_team_members', function() {
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
    
    $args = array(
        'post_type'      => 'team_member',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );
    
    if ( function_exists('pll_current_language') ) {
        $args['lang'] = pll_current_language();
    }
    
    $query = new WP_Query( $args );
    
    ob_start();
    ?>
    <div class="team-members-grid">
        <?php if ( $query->have_posts() ) : ?>
            <?php while ( $query->have_posts() ) : $query->the_post(); 
                $post_id = get_the_ID();
                $position = function_exists('get_field') ? get_field('position', $post_id) : get_post_meta($post_id, 'position', true);
                $linkedin = function_exists('get_field') ? get_field('linkedin_url', $post_id) : get_post_meta($post_id, 'linkedin_url', true);
                $phone = function_exists('get_field') ? get_field('mobile_phone', $post_id) : get_post_meta($post_id, 'mobile_phone', true);
                $email = function_exists('get_field') ? get_field('email', $post_id) : get_post_meta($post_id, 'email', true);
                
                $image_url = get_the_post_thumbnail_url($post_id, 'medium');
                if (!$image_url) {
                    $image_url = plugin_dir_url(__FILE__) . 'assets/matti.jpg'; // fallback
                }
                ?>
                <div class="team-member-card">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php the_title_attribute(); ?>" />
                    <h3><?php the_title(); ?></h3>
                    <?php if ($position) : ?>
                        <p><strong><?php echo esc_html($position); ?></strong></p>
                    <?php endif; ?>
                    <p><?php echo nl2br(esc_html(get_the_content())); ?></p>
                    
                    <div class="team-member-contact">
                        <?php if ($linkedin) : ?>
                            <a href="<?php echo esc_url($linkedin); ?>" target="_blank" rel="noopener" class="contact-icon-link linkedin-link" title="LinkedIn">
                                <i class="bi bi-linkedin"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($email) : ?>
                            <a href="mailto:<?php echo sanitize_email($email); ?>" class="contact-icon-link email-link" title="<?php echo esc_attr(($lang === 'fi') ? 'Sähköposti' : 'Email'); ?>">
                                <i class="bi bi-envelope-fill"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($phone) : ?>
                            <a href="tel:<?php echo esc_attr(str_replace(' ', '', $phone)); ?>" class="contact-icon-link phone-link" title="<?php echo esc_attr(($lang === 'fi') ? 'Puhelin' : 'Phone'); ?>">
                                <i class="bi bi-telephone-fill"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else : ?>
            <p><?php echo ($lang === 'fi') ? 'Ei tiimin jäseniä löydetty.' : 'No team members found.'; ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});