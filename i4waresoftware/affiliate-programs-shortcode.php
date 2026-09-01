<?php
/**
 * Affiliate Programs Shortcode [affiliate_programs]
 *
 * This shortcode displays partner/affiliate programs with purchase buttons
 * for Finnish (Fi), English (En), and Arabic (Ar) languages.
 */

if (!function_exists('i4ware_affiliate_programs_shortcode')) {
    function i4ware_affiliate_programs_shortcode($atts)
    {
        $atts = is_array($atts) ? $atts : [];
        $atts = array_change_key_case($atts, CASE_LOWER);

        $a = shortcode_atts([
            'group' => 'affiliate', // Default to 'affiliate'
            'limit' => -1,  // Limit the number of affiliate programs displayed
        ], $atts, 'affiliate_programs');

        $limit = intval($a['limit']);
        $group = sanitize_text_field($a['group']);

        $args = array(
            'post_type'      => 'partner_logo',
            'posts_per_page' => $limit,
            'orderby'        => 'menu_order',
            'order'          => 'ASC',
        );

        if (!empty($group) && $group !== 'all') {
            $args['meta_query'] = array(
                array(
                    'key'     => 'logo_group',
                    'value'   => $group,
                    'compare' => '='
                )
            );
        }

        $programs = get_posts($args);

        if (empty($programs)) {
            return '';
        }

        // Detect language
        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        if ($lang !== 'fi' && $lang !== 'en' && $lang !== 'ar') {
            $lang = 'en'; // fallback
        }

        // Button text translations
        $button_texts = [
            'fi' => 'Osta Nyt',
            'en' => 'Buy Now',
            'ar' => 'اشترِ الآن'
        ];
        $btn_text = $button_texts[$lang];

        // Title translations
        $titles = [
            'fi' => 'Affiliate-kumppanit',
            'en' => 'Affiliate Partners',
            'ar' => 'الشركاء التابعون'
        ];
        $title_text = $titles[$lang];

        // Description translations
        $descriptions = [
            'fi' => 'Osa tämän sivuston linkeistä on kumppanuuslinkkejä (affiliate-linkkejä). Jos klikkaat tällaista linkkiä ja ostat tuotteen, saatamme saada siitä komission ilman sinulle aiheutuvia lisäkuluja. Tämä tuki auttaa meitä ylläpitämään palveluitamme.',
            'en' => 'Some of the links on this website are affiliate links. If you click on one of these links and make a purchase, we may receive a commission at no additional cost to you. This support helps us maintain our services.',
            'ar' => 'بعض الروابط الموجودة على هذا الموقع هي روابط تسويق بالعمولة (أفلييت). إذا نقرت على أحد هذه الروابط وقمت بعملية شراء، فقد نتلقى عمولة دون أي تكلفة إضافية عليك. يساعدنا هذا الدعم في الحفاظ على خدماتنا.'
        ];
        $desc_text = $descriptions[$lang];

        // LTR vs RTL alignment
        $is_rtl = ($lang === 'ar');
        $dir_attr = $is_rtl ? 'dir="rtl"' : 'dir="ltr"';
        $align_class = $is_rtl ? 'align-right' : 'align-left';

        $output = '<div class="i4ware-affiliates-container" ' . $dir_attr . '>';
        $output .= '<h2 class="i4ware-affiliates-header">' . esc_html($title_text) . '</h2>';
        $output .= '<p class="i4ware-affiliates-desc">' . esc_html($desc_text) . '</p>';
        $output .= '<div class="i4ware-affiliates-grid">';

        foreach ($programs as $post) {
            $url = function_exists('get_field') ? get_field('logo_url', $post->ID) : '';
            if (empty($url)) {
                continue; // Skip programs with no purchase/logo link
            }

            $img = get_the_post_thumbnail_url($post->ID, 'medium');

            // Category translation
            $category = '';
            if (function_exists('get_field')) {
                if ($lang === 'en') {
                    $category = get_field('partner_category_en', $post->ID);
                } elseif ($lang === 'ar') {
                    $category = get_field('partner_category_ar', $post->ID);
                } else {
                    $category = get_field('partner_category_fi', $post->ID);
                }
            }

            // Alt translation
            $alt = '';
            if (function_exists('get_field')) {
                if ($lang === 'en') {
                    $alt = get_field('logo_alt_en', $post->ID);
                } elseif ($lang === 'ar') {
                    $alt = get_field('logo_alt_ar', $post->ID);
                } else {
                    $alt = get_field('logo_alt_fi', $post->ID);
                }
            }
            if (empty($alt)) {
                $alt = get_the_title($post->ID);
            }

            $title = get_the_title($post->ID);
            $unique_btn_id = 'i4ware-affiliate-btn-' . $post->ID;

            // Google Analytics Event OnClick
            $ga_onclick = sprintf(
                "if(typeof gtag==='function'){gtag('event','click_affiliate_purchase',{'program_name':'%s','language':'%s','url':'%s'});}",
                esc_js($title),
                esc_js($lang),
                esc_js($url)
            );

            $output .= '<div class="i4ware-affiliate-card">';
            
            // Logo section
            $output .= '<div class="i4ware-affiliate-logo-wrapper">';
            if (!empty($img)) {
                $output .= '<img src="' . esc_url($img) . '" alt="' . esc_attr($alt) . '" class="i4ware-affiliate-logo" />';
            } else {
                // Beautiful CSS Gradient placeholder if no thumbnail is present
                $output .= '<div class="i4ware-placeholder-gradient"></div>';
            }
            $output .= '</div>';

            // Information section
            $output .= '<div class="i4ware-affiliate-info ' . $align_class . '">';
            $output .= '<h3 class="i4ware-affiliate-title">' . esc_html($title) . '</h3>';
            if (!empty($category)) {
                $output .= '<span class="i4ware-affiliate-category">' . esc_html($category) . '</span>';
            }
            $output .= '</div>';

            // Action button
            $output .= '<div class="i4ware-affiliate-action">';
            $output .= '<a id="' . esc_attr($unique_btn_id) . '" href="' . esc_url($url) . '" target="_blank" rel="noopener" onclick="' . esc_attr($ga_onclick) . '" class="i4ware-affiliate-btn">' . esc_html($btn_text) . '</a>';
            $output .= '</div>';

            $output .= '</div>'; // .i4ware-affiliate-card
        }

        $output .= '</div>'; // .i4ware-affiliates-grid
        $output .= '</div>'; // .i4ware-affiliates-container

        return $output;
    }
    add_shortcode('affiliate_programs', 'i4ware_affiliate_programs_shortcode');
}

/**
 * Add inline styles for the affiliate programs shortcode
 */
if (!function_exists('i4ware_affiliate_programs_styles')) {
    function i4ware_affiliate_programs_styles()
    {
        ?>
        <style>
            .i4ware-affiliates-container {
                margin: 40px 0;
                width: 100%;
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }

            .i4ware-affiliates-header {
                font-size: 28px;
                font-weight: 800;
                color: #ffffff;
                margin: 0 0 12px 0;
                line-height: 1.2;
                background: linear-gradient(135deg, #ffffff 30%, #c084fc 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                text-align: start;
            }

            .i4ware-affiliates-desc {
                font-size: 15px;
                line-height: 1.6;
                color: #a0aec0;
                margin: 0 0 30px 0;
                max-width: 800px;
                text-align: start;
            }

            .i4ware-affiliates-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 24px;
                margin: 0;
                width: 100%;
                box-sizing: border-box;
            }

            .i4ware-affiliate-card {
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.01) 100%);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 16px;
                padding: 24px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items: stretch;
                transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.24);
                box-sizing: border-box;
            }

            .i4ware-affiliate-card:hover {
                transform: translateY(-6px);
                border-color: rgba(255, 255, 255, 0.16);
                box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35);
            }

            .i4ware-affiliate-logo-wrapper {
                height: 100px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 20px;
                border-radius: 12px;
                overflow: hidden;
                background: rgba(255, 255, 255, 0.02);
                border: 1px solid rgba(255, 255, 255, 0.04);
                position: relative;
            }

            .i4ware-affiliate-logo {
                max-height: 80%;
                max-width: 80%;
                object-fit: contain;
                transition: transform 0.3s ease;
            }

            .i4ware-affiliate-card:hover .i4ware-affiliate-logo {
                transform: scale(1.06);
            }

            .i4ware-placeholder-gradient {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, rgba(140, 0, 145, 0.15) 0%, rgba(106, 0, 244, 0.15) 100%);
            }

            .i4ware-affiliate-info {
                margin-bottom: 24px;
            }

            .i4ware-affiliate-info.align-left {
                text-align: left;
            }

            .i4ware-affiliate-info.align-right {
                text-align: right;
            }

            .i4ware-affiliate-title {
                font-size: 19px;
                font-weight: 700;
                color: #ffffff;
                margin: 0 0 10px 0;
                line-height: 1.4;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }

            .i4ware-affiliate-category {
                font-size: 12px;
                color: #c084fc;
                background: rgba(192, 132, 252, 0.1);
                padding: 4px 12px;
                border-radius: 99px;
                display: inline-block;
                font-weight: 600;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }

            .i4ware-affiliate-action {
                margin-top: auto;
            }

            .i4ware-affiliate-btn {
                display: block;
                width: 100%;
                text-align: center;
                background: linear-gradient(135deg, #8c0091 0%, #6a00f4 100%);
                color: #ffffff;
                text-decoration: none;
                font-weight: 700;
                font-size: 14px;
                padding: 14px 20px;
                border-radius: 30px;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(106, 0, 244, 0.3);
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }

            .i4ware-affiliate-btn:hover {
                background: linear-gradient(135deg, #a60ea9 0%, #7d1cff 100%);
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(106, 0, 244, 0.45);
                color: #ffffff !important;
                text-decoration: none !important;
                border: none !important;
            }

            .i4ware-affiliate-btn:active {
                transform: translateY(0);
            }

            /* RTL support for Arabic */
            .i4ware-affiliates-grid[dir="rtl"] .i4ware-affiliate-category {
                letter-spacing: 0;
            }
        </style>
        <?php
    }
}

if (!is_admin()) {
    add_action('wp_head', 'i4ware_affiliate_programs_styles');
}
