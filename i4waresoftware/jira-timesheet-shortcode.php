<?php
/**
 * Timesheet for Jira - Landing Page Shortcode
 * 
 * Renders a premium, interactive landing page matching html-en.html / html-fi.html
 * Integrates ACF (free version) and Polylang translations (EN/FI) with automatic fallbacks.
 * 
 * Usage: [jira_timesheet_landing]
 */

// Define defaults & fallback arrays
global $tfj_defaults;
$tfj_defaults = array(
    'en' => array(
        'hero_badge' => 'Atlassian Marketplace App',
        'hero_title' => 'Log Time in Jira <span>10x Faster</span>',
        'hero_lead' => 'Replace click-heavy dialogs with an Excel-style keyboard grid. Start tracking, approve worklogs, and export billable hours without leaving Jira.',
        'btn_1_text' => 'Start Free 30-Day Trial',
        'btn_1_url' => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira',
        'btn_2_text' => 'View on Atlassian Marketplace',
        'btn_2_url' => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira',
        
        'trust_bar_1' => 'Atlassian Marketplace Verified',
        'trust_bar_2' => 'Native Jira Integration',
        'trust_bar_3' => 'Jira Service Management Support',
        'trust_bar_4' => 'Enterprise-Ready Reporting',
        
        'benefits_title' => 'Spend less time tracking, more time building',
        'benefits_subtitle' => 'Simple, powerful time management built directly into your Jira workspace.',
        
        'benefit_1_title' => 'Keyboard-First Time Tracking',
        'benefit_1_desc' => 'Navigate and log time across multiple Jira issues entirely from your keyboard.',
        'benefit_2_title' => 'Real-Time Timers',
        'benefit_2_desc' => 'Start and stop active timers directly inside your issues to record work automatically.',
        'benefit_3_title' => '10x Faster Than Native',
        'benefit_3_desc' => 'Eliminate slow modals and click-heavy dialogs to save developers hours weekly.',
        'benefit_4_title' => 'Manager Approval Workflow',
        'benefit_4_desc' => 'Review, approve, or reject team timesheets with notes directly in Jira.',
        'benefit_5_title' => 'Billable Hours Tracking',
        'benefit_5_desc' => 'Configure role-based rates to track project costs and profitability in real time.',
        'benefit_6_title' => 'Excel Export & Reporting',
        'benefit_6_desc' => 'Export clean, invoice-ready timesheets directly to XLSX files with one click.',
        
        'shortcuts_badge' => 'Efficiency First',
        'shortcuts_title' => 'True Mouse-Free Tracking',
        'shortcuts_lead' => 'We built Timesheet for Jira around high-performance workflows. Never lose your focus or reach for the mouse. Control your logs entirely with simple keyboard commands.',
        'shortcuts_accent' => 'Keyboard shortcuts supported out of the box',
        
        'shortcut_1_key' => 'Tab',
        'shortcut_1_action' => 'Move cursor down to the next row',
        'shortcut_2_key' => 'Shift + Tab',
        'shortcut_2_action' => 'Move cursor up to the previous row',
        'shortcut_3_key' => 'Enter',
        'shortcut_3_action' => 'Edit cell value or execute operation',
        'shortcut_4_key' => 'Space',
        'shortcut_4_action' => 'Toggle state or open dropdown menus',
        'shortcut_5_key' => 'Escape',
        'shortcut_5_action' => 'Close current editing state or modal dialog',
        
        'video_title' => 'See Timesheet in Action',
        'video_desc' => 'Watch how fast it is to log work and explore AI automation directly inside Jira.',
        'video_1_title' => 'Product Walkthrough',
        'video_1_lang' => 'English',
        'video_1_id' => 'K8XBQzz0yuY',
        'video_1_desc' => 'Complete walkthrough of the keyboard-first grid, active timers, manager approval flow, and XLSX export reports.',
        'video_2_title' => 'AI Automation Overview',
        'video_2_lang' => 'English',
        'video_2_id' => 'qoh-hIkkIEA',
        'video_2_desc' => 'A deep dive into our built-in AI-assisted worklog automation features and localized interface settings.',
        
        'gallery_title' => 'Built for Modern Jira Workflows',
        'gallery_desc' => 'A friction-free interface tailored for developers, managers, and billing teams. Click any screenshot below to expand.',
        'gallery_tab_all' => 'All Features',
        'gallery_tab_dev' => 'For Developers',
        'gallery_tab_mgr' => 'For Managers',
        'gallery_tab_billing' => 'Billing & JSM',
        
        'gallery_img_1_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Dashboard-7-1024x576.png',
        'gallery_img_1_caption' => 'Admin Dashboard — Weekly Team Overview',
        'gallery_img_1_category' => 'mgr',
        
        'gallery_img_2_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/My-Timesheets-9-1024x576.png',
        'gallery_img_2_caption' => 'My Timesheets — Excel-Style Grid View',
        'gallery_img_2_category' => 'dev',
        
        'gallery_img_3_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Approve-7-1024x576.png',
        'gallery_img_3_caption' => 'Worklog Approval Workflow',
        'gallery_img_3_category' => 'mgr',
        
        'gallery_img_4_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Reject-10-1024x576.png',
        'gallery_img_4_caption' => 'Worklog Rejection with Notes',
        'gallery_img_4_category' => 'mgr',
        
        'gallery_img_5_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Click-Start-8-1024x576.png',
        'gallery_img_5_caption' => 'Timer — Start Tracking',
        'gallery_img_5_category' => 'dev',
        
        'gallery_img_6_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Billable-Work-hours-MS-Excel-Export-8-1024x576.png',
        'gallery_img_6_caption' => 'Billable Hours Export to Excel',
        'gallery_img_6_category' => 'billing',
        
        'gallery_img_7_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Customer-Complaints-3-1024x576.png',
        'gallery_img_7_caption' => 'Customer Dispute and Billing Workflow',
        'gallery_img_7_category' => 'billing',
        
        'gallery_img_8_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Disputed-Work-logs-in-JSM-7-1024x576.png',
        'gallery_img_8_caption' => 'Disputed Worklogs in JSM',
        'gallery_img_8_category' => 'billing',
        
        'cta_title' => 'Start Tracking Time Faster in Jira',
        'cta_desc' => 'Try Timesheet for Jira free for 30 days and see how much time your team saves.',
        'cta_btn_text' => 'Start Free 30-Day Trial',
        'cta_btn_url' => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira',
        'cta_trust_line' => 'No credit card required. Cancel anytime.',
        
        'footer_text' => '© 2026 i4ware. All rights reserved. Timesheet for Jira is a product of i4ware. Atlassian, Jira, and Jira Service Management are registered trademarks of Atlassian Pty Ltd.',
        'footer_links' => array(
            array('text' => 'Documentation', 'url' => 'https://i4ware.atlassian.net/wiki/spaces/TFJ/overview'),
            array('text' => 'Support Desk', 'url' => 'https://i4ware.atlassian.net/servicedesk/customer/portal/6'),
            array('text' => 'AI Support Bot', 'url' => 'https://i4ware.atlassian.net/servicedesk/customer/portal/3'),
            array('text' => 'Contact & Team', 'url' => 'https://www.i4ware.fi/en/company/contact-us-team/'),
            array('text' => 'Delivery Terms', 'url' => 'https://www.i4ware.fi/en/delivery-terms-and-conditions/'),
            array('text' => 'Lawyer Services', 'url' => 'https://www.i4ware.fi/en/services/advocates-attorneys/'),
            array('text' => 'EULA', 'url' => 'https://i4ware.atlassian.net/wiki/spaces/TFJ/pages/50495490/EULA'),
            array('text' => 'SLA', 'url' => 'https://i4ware.atlassian.net/wiki/spaces/TFJ/pages/50102283/Service+Level+Agreement'),
            array('text' => 'Privacy Policy', 'url' => 'https://www.i4ware.fi/en/privacy-policy/'),
            array('text' => 'Send Support & Donations', 'url' => 'https://www.i4ware.fi/kauppa/', 'class' => 'tfj-footer-highlight'),
        ),
    ),
    'fi' => array(
        'hero_badge' => 'Atlassian Marketplace -sovellus',
        'hero_title' => 'Kirjaa tunnit Jiraan <span>10x nopeammin</span>',
        'hero_lead' => 'Korvaa hitaat dialogit Excel-tyylisellä näppäimistöruudukolla. Aloita seuranta, hyväksy työlokit ja vie laskutettavat tunnit suoraan Jirassa.',
        'btn_1_text' => 'Aloita ilmainen 30 päivän kokeilu',
        'btn_1_url' => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira',
        'btn_2_text' => 'Näytä Atlassian Marketplacessa',
        'btn_2_url' => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira',
        
        'trust_bar_1' => 'Atlassian Marketplacen vahvistama',
        'trust_bar_2' => 'Natiivi Jira-integraatio',
        'trust_bar_3' => 'Jira Service Management -tuki',
        'trust_bar_4' => 'Yritystason raportointi',
        
        'benefits_title' => 'Käytä vähemmän aikaa seurantaan, enemmän kehitykseen',
        'benefits_subtitle' => 'Yksinkertainen ja tehokas työajanseuranta suoraan Jira-työtilassasi.',
        
        'benefit_1_title' => 'Näppäimistö edellä tapahtuva seuranta',
        'benefit_1_desc' => 'Navigoi ja kirjaa tunteja useille Jira-tehtäville täysin näppäimistölläsi.',
        'benefit_2_title' => 'Reaaliaikaiset ajastimet',
        'benefit_2_desc' => 'Käynnistä ja pysäytä ajastimia suoraan tehtävien sisällä tallentaaksesi työt automaattisesti.',
        'benefit_3_title' => '10x nopeampi kuin natiivi',
        'benefit_3_desc' => 'Eliminoi hitaat modaalit ja klikkausraskaat dialogit säästääksesi kehittäjien aikaa viikoittain.',
        'benefit_4_title' => 'Esihenkilön hyväksyntätyönkulku',
        'benefit_4_desc' => 'Tarkasta, hyväksy tai hylkää tiimin tuntilomakkeet kommenttien kera suoraan Jirassa.',
        'benefit_5_title' => 'Laskutettavien tuntien seuranta',
        'benefit_5_desc' => 'Määritä roolipohjaiset hinnat seurataksesi projektin kustannuksia ja kannattavuutta reaaliajassa.',
        'benefit_6_title' => 'Excel-vienti ja raportointi',
        'benefit_6_desc' => 'Vie selkeät, laskutusvalmiit tuntilomakkeet suoraan XLSX-tiedostoiksi yhdellä klikkauksella.',
        
        'shortcuts_badge' => 'Tehokkuus edellä',
        'shortcuts_title' => 'Täysin hiiritön seuranta',
        'shortcuts_lead' => 'Kehitimme Timesheet for Jira -sovelluksen korkean suorituskyvyn työnkulkujen ympärille. Älä koskaan menetä keskittymistäsi tai kurota hiireen. Hallitse kirjauksiasi täysin yksinkertaisilla näppäimistökomennoilla.',
        'shortcuts_accent' => 'Näppäimistöoikotiet tuettu suoraan paketista',
        
        'shortcut_1_key' => 'Tab',
        'shortcut_1_action' => 'Siirrä kohdistin alas seuraavalle riville',
        'shortcut_2_key' => 'Shift + Tab',
        'shortcut_2_action' => 'Siirrä kohdistin ylös edelliselle riville',
        'shortcut_3_key' => 'Enter',
        'shortcut_3_action' => 'Muokkaa solun arvoa tai suorita toiminto',
        'shortcut_4_key' => 'Space',
        'shortcut_4_action' => 'Vaihda tilaa tai avaa pudotusvalikko',
        'shortcut_5_key' => 'Escape',
        'shortcut_5_action' => 'Sulje nykyinen muokkaustila tai modaali-ikkuna',
        
        'video_title' => 'Katso Timesheet toiminnassa',
        'video_desc' => 'Katso kuinka nopeaa työn kirjaaminen on ja tutustu tekoälyautomaatioon suoraan Jirassa.',
        'video_1_title' => 'Tuote-esittely',
        'video_1_lang' => 'Englanti',
        'video_1_id' => 'K8XBQzz0yuY',
        'video_1_desc' => 'Kattava läpikäynti näppäimistöruudukosta, ajastimistä, esihenkilötason hyväksynnöistä ja XLSX-raportoinnista.',
        'video_2_title' => 'Tekoälyautomaation yleiskatsaus',
        'video_2_lang' => 'Englanti',
        'video_2_id' => 'qoh-hIkkIEA',
        'video_2_desc' => 'Syväsukellus sisäänrakennettuihin tekoälyavusteisiin automaatio-ominaisuuksiin ja paikallisiin käyttöliittymäasetuksiin.',
        
        'gallery_title' => 'Rakennettu nykyaikaisiin Jira-työnkulkuihin',
        'gallery_desc' => 'Kitkaton käyttöliittymä, joka on räätälöity kehittäjille, esihenkilöille ja laskutustiimeille. Klikkaa kuvaa suurentaaksesi.',
        'gallery_tab_all' => 'Kaikki ominaisuudet',
        'gallery_tab_dev' => 'Kehittäjille',
        'gallery_tab_mgr' => 'Esihenkilöille',
        'gallery_tab_billing' => 'Laskutus & JSM',
        
        'gallery_img_1_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Dashboard-7-1024x576.png',
        'gallery_img_1_caption' => 'Ylläpidon työpöytä — Tiimin viikkonäkymä',
        'gallery_img_1_category' => 'mgr',
        
        'gallery_img_2_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/My-Timesheets-9-1024x576.png',
        'gallery_img_2_caption' => 'Omat tuntilomakkeet — Excel-tyylinen ruudukko',
        'gallery_img_2_category' => 'dev',
        
        'gallery_img_3_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Approve-7-1024x576.png',
        'gallery_img_3_caption' => 'Työlokien hyväksyntätyönkulku',
        'gallery_img_3_category' => 'mgr',
        
        'gallery_img_4_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Reject-10-1024x576.png',
        'gallery_img_4_caption' => 'Työlokien hylkääminen kommenteilla',
        'gallery_img_4_category' => 'mgr',
        
        'gallery_img_5_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Click-Start-8-1024x576.png',
        'gallery_img_5_caption' => 'Ajastin — Aloita seuranta',
        'gallery_img_5_category' => 'dev',
        
        'gallery_img_6_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Billable-Work-hours-MS-Excel-Export-8-1024x576.png',
        'gallery_img_6_caption' => 'Laskutettavien tuntien vienti Exceliin',
        'gallery_img_6_category' => 'billing',
        
        'gallery_img_7_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Customer-Complaints-3-1024x576.png',
        'gallery_img_7_caption' => 'Asiakasreklamaatiot ja laskutuksen työnkulku',
        'gallery_img_7_category' => 'billing',
        
        'gallery_img_8_url' => 'https://www.i4ware.fi/wp-content/uploads/2026/05/Disputed-Work-logs-in-JSM-7-1024x576.png',
        'gallery_img_8_caption' => 'Kiistanalaiset työlokit JSM:ssä',
        'gallery_img_8_category' => 'billing',
        
        'cta_title' => 'Aloita nopeampi työajanseuranta Jirassa',
        'cta_desc' => 'Kokeile Timesheet for Jiraa ilmaiseksi 30 päivän ajan ja katso kuinka paljon tiimisi säästää aikaa.',
        'cta_btn_text' => 'Aloita ilmainen 30 päivän kokeilu',
        'cta_btn_url' => 'https://marketplace.atlassian.com/apps/1223446/timesheet-for-jira',
        'cta_trust_line' => 'Ei luottokorttivaatimusta. Peruuta milloin tahansa.',
        
        'footer_text' => '© 2026 i4ware. Kaikki oikeudet pidätetään. Timesheet for Jira on i4waren tuote. Atlassian, Jira ja Jira Service Management ovat Atlassian Pty Ltd:n rekisteröityjä tavaramerkkejä.',
        'footer_links' => array(
            array('text' => 'Dokumentaatio', 'url' => 'https://i4ware.atlassian.net/wiki/spaces/TFJ/overview'),
            array('text' => 'Tukipalvelu', 'url' => 'https://i4ware.atlassian.net/servicedesk/customer/portal/6'),
            array('text' => 'Tekoälytukibotti', 'url' => 'https://i4ware.atlassian.net/servicedesk/customer/portal/3'),
            array('text' => 'Yhteystiedot & Tiimi', 'url' => 'https://www.i4ware.fi/en/company/contact-us-team/'),
            array('text' => 'Toimitusehdot', 'url' => 'https://www.i4ware.fi/en/delivery-terms-and-conditions/'),
            array('text' => 'Lakimiespalvelut', 'url' => 'https://www.i4ware.fi/en/services/advocates-attorneys/'),
            array('text' => 'EULA (Käyttöoikeussopimus)', 'url' => 'https://i4ware.atlassian.net/wiki/spaces/TFJ/pages/50495490/EULA'),
            array('text' => 'SLA (Palvelutasosopimus)', 'url' => 'https://i4ware.atlassian.net/wiki/spaces/TFJ/pages/50102283/Service+Level+Agreement'),
            array('text' => 'Tietosuojaseloste', 'url' => 'https://www.i4ware.fi/en/privacy-policy/'),
            array('text' => 'Lähetä tukea & lahjoituksia', 'url' => 'https://www.i4ware.fi/kauppa/', 'class' => 'tfj-footer-highlight'),
        ),
    )
);

/**
 * Get current language from Polylang
 */
function get_tfj_landing_language() {
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language();
        return ( $lang === 'fi' ) ? 'fi' : 'en';
    }
    
    $locale = get_locale();
    return ( strpos( $locale, 'fi' ) === 0 ) ? 'fi' : 'en';
}

/**
 * Get field value with fallback
 */
function get_tfj_landing_field( $field_name, $lang ) {
    global $tfj_defaults;
    $val = get_field( $field_name );
    if ( empty( $val ) ) {
        $key = str_replace( 'tfj_', '', $field_name );
        return isset( $tfj_defaults[$lang][$key] ) ? $tfj_defaults[$lang][$key] : '';
    }
    return $val;
}

/**
 * Get image value with fallback, supporting different ACF formats
 */
function get_tfj_landing_image( $field_name, $lang ) {
    global $tfj_defaults;
    $val = get_field( $field_name );
    if ( empty( $val ) ) {
        $key = str_replace( 'tfj_', '', $field_name );
        return isset( $tfj_defaults[$lang][$key] ) ? $tfj_defaults[$lang][$key] : '';
    }
    if ( is_array( $val ) && isset( $val['url'] ) ) {
        return $val['url'];
    }
    if ( is_numeric( $val ) ) {
        $url = wp_get_attachment_image_url( $val, 'full' );
        if ( $url ) return $url;
    }
    return $val;
}

/**
 * Register ACF Fields programmatically on init
 */
add_action( 'acf/init', 'tfj_register_acf_fields' );
function tfj_register_acf_fields() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    $fields = array(
        // Tabs & Hero
        array(
            'key' => 'field_tfj_hero_tab',
            'label' => 'Hero Section',
            'type' => 'tab',
            'placement' => 'top',
            'endpoint' => 0,
        ),
        array(
            'key' => 'field_tfj_hero_badge',
            'label' => 'Hero Badge',
            'name' => 'tfj_hero_badge',
            'type' => 'text',
        ),
        array(
            'key' => 'field_tfj_hero_title',
            'label' => 'Hero Title',
            'name' => 'tfj_hero_title',
            'type' => 'text',
            'instructions' => 'Supports HTML tags like &lt;span&gt; for gradient text (e.g. <span>10x Faster</span>).',
        ),
        array(
            'key' => 'field_tfj_hero_lead',
            'label' => 'Hero Lead Paragraph',
            'name' => 'tfj_hero_lead',
            'type' => 'textarea',
            'rows' => 3,
        ),
        array(
            'key' => 'field_tfj_btn_1_text',
            'label' => 'Button 1 Text',
            'name' => 'tfj_btn_1_text',
            'type' => 'text',
        ),
        array(
            'key' => 'field_tfj_btn_1_url',
            'label' => 'Button 1 URL',
            'name' => 'tfj_btn_1_url',
            'type' => 'text',
        ),
        array(
            'key' => 'field_tfj_btn_2_text',
            'label' => 'Button 2 Text',
            'name' => 'tfj_btn_2_text',
            'type' => 'text',
        ),
        array(
            'key' => 'field_tfj_btn_2_url',
            'label' => 'Button 2 URL',
            'name' => 'tfj_btn_2_url',
            'type' => 'text',
        ),

        // Trust Bar
        array(
            'key' => 'field_tfj_trust_tab',
            'label' => 'Trust Bar',
            'type' => 'tab',
            'placement' => 'top',
            'endpoint' => 0,
        ),
        array(
            'key' => 'field_tfj_trust_bar_1',
            'label' => 'Trust Item 1',
            'name' => 'tfj_trust_bar_1',
            'type' => 'text',
        ),
        array(
            'key' => 'field_tfj_trust_bar_2',
            'label' => 'Trust Item 2',
            'name' => 'tfj_trust_bar_2',
            'type' => 'text',
        ),
        array(
            'key' => 'field_tfj_trust_bar_3',
            'label' => 'Trust Item 3',
            'name' => 'tfj_trust_bar_3',
            'type' => 'text',
        ),
        array(
            'key' => 'field_tfj_trust_bar_4',
            'label' => 'Trust Item 4',
            'name' => 'tfj_trust_bar_4',
            'type' => 'text',
        ),

        // Benefits
        array(
            'key' => 'field_tfj_benefits_tab',
            'label' => 'Key Benefits',
            'type' => 'tab',
            'placement' => 'top',
            'endpoint' => 0,
        ),
        array(
            'key' => 'field_tfj_benefits_title',
            'label' => 'Section Title',
            'name' => 'tfj_benefits_title',
            'type' => 'text',
        ),
        array(
            'key' => 'field_tfj_benefits_subtitle',
            'label' => 'Section Subtitle',
            'name' => 'tfj_benefits_subtitle',
            'type' => 'text',
        ),
    );

    // Dynamic fields for the 6 benefits
    for ( $i = 1; $i <= 6; $i++ ) {
        $fields[] = array(
            'key' => "field_tfj_benefit_{$i}_title",
            'label' => "Benefit {$i} Title",
            'name' => "tfj_benefit_{$i}_title",
            'type' => 'text',
            'wrapper' => array('width' => '50'),
        );
        $fields[] = array(
            'key' => "field_tfj_benefit_{$i}_desc",
            'label' => "Benefit {$i} Description",
            'name' => "tfj_benefit_{$i}_desc",
            'type' => 'textarea',
            'rows' => 2,
            'wrapper' => array('width' => '50'),
        );
    }

    // Shortcuts
    $fields[] = array(
        'key' => 'field_tfj_shortcuts_tab',
        'label' => 'Shortcuts',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
    );
    $fields[] = array(
        'key' => 'field_tfj_shortcuts_badge',
        'label' => 'Badge Text',
        'name' => 'tfj_shortcuts_badge',
        'type' => 'text',
    );
    $fields[] = array(
        'key' => 'field_tfj_shortcuts_title',
        'label' => 'Title',
        'name' => 'tfj_shortcuts_title',
        'type' => 'text',
    );
    $fields[] = array(
        'key' => 'field_tfj_shortcuts_lead',
        'label' => 'Description Paragraph',
        'name' => 'tfj_shortcuts_lead',
        'type' => 'textarea',
        'rows' => 3,
    );
    $fields[] = array(
        'key' => 'field_tfj_shortcuts_accent',
        'label' => 'Accent Text (inside badge)',
        'name' => 'tfj_shortcuts_accent',
        'type' => 'text',
    );

    // Dynamic fields for 5 shortcuts
    for ( $i = 1; $i <= 5; $i++ ) {
        $fields[] = array(
            'key' => "field_tfj_shortcut_{$i}_key",
            'label' => "Shortcut {$i} Key Command",
            'name' => "tfj_shortcut_{$i}_key",
            'type' => 'text',
            'wrapper' => array('width' => '50'),
        );
        $fields[] = array(
            'key' => "field_tfj_shortcut_{$i}_action",
            'label' => "Shortcut {$i} Action / Operation",
            'name' => "tfj_shortcut_{$i}_action",
            'type' => 'text',
            'wrapper' => array('width' => '50'),
        );
    }

    // Videos
    $fields[] = array(
        'key' => 'field_tfj_video_tab',
        'label' => 'Video Demos',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
    );
    $fields[] = array(
        'key' => 'field_tfj_video_title',
        'label' => 'Section Title',
        'name' => 'tfj_video_title',
        'type' => 'text',
    );
    $fields[] = array(
        'key' => 'field_tfj_video_desc',
        'label' => 'Section Description',
        'name' => 'tfj_video_desc',
        'type' => 'textarea',
        'rows' => 2,
    );

    for ( $i = 1; $i <= 2; $i++ ) {
        $fields[] = array(
            'key' => "field_tfj_video_{$i}_title",
            'label' => "Video {$i} Title",
            'name' => "tfj_video_{$i}_title",
            'type' => 'text',
            'wrapper' => array('width' => '33'),
        );
        $fields[] = array(
            'key' => "field_tfj_video_{$i}_lang",
            'label' => "Video {$i} Language Tag",
            'name' => "tfj_video_{$i}_lang",
            'type' => 'text',
            'wrapper' => array('width' => '33'),
        );
        $fields[] = array(
            'key' => "field_tfj_video_{$i}_id",
            'label' => "Video {$i} YouTube ID",
            'name' => "tfj_video_{$i}_id",
            'type' => 'text',
            'wrapper' => array('width' => '33'),
        );
        $fields[] = array(
            'key' => "field_tfj_video_{$i}_desc",
            'label' => "Video {$i} Description Text",
            'name' => "tfj_video_{$i}_desc",
            'type' => 'textarea',
            'rows' => 2,
        );
    }

    // Gallery
    $fields[] = array(
        'key' => 'field_tfj_gallery_tab',
        'label' => 'Screenshots Gallery',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
    );
    $fields[] = array(
        'key' => 'field_tfj_gallery_title',
        'label' => 'Section Title',
        'name' => 'tfj_gallery_title',
        'type' => 'text',
    );
    $fields[] = array(
        'key' => 'field_tfj_gallery_desc',
        'label' => 'Section Description',
        'name' => 'tfj_gallery_desc',
        'type' => 'textarea',
        'rows' => 2,
    );
    $fields[] = array(
        'key' => 'field_tfj_gallery_tab_all',
        'label' => '"All Features" Tab Label',
        'name' => 'tfj_gallery_tab_all',
        'type' => 'text',
        'wrapper' => array('width' => '25'),
    );
    $fields[] = array(
        'key' => 'field_tfj_gallery_tab_dev',
        'label' => '"For Developers" Tab Label',
        'name' => 'tfj_gallery_tab_dev',
        'type' => 'text',
        'wrapper' => array('width' => '25'),
    );
    $fields[] = array(
        'key' => 'field_tfj_gallery_tab_mgr',
        'label' => '"For Managers" Tab Label',
        'name' => 'tfj_gallery_tab_mgr',
        'type' => 'text',
        'wrapper' => array('width' => '25'),
    );
    $fields[] = array(
        'key' => 'field_tfj_gallery_tab_billing',
        'label' => '"Billing & JSM" Tab Label',
        'name' => 'tfj_gallery_tab_billing',
        'type' => 'text',
        'wrapper' => array('width' => '25'),
    );

    // CTA
    $fields[] = array(
        'key' => 'field_tfj_cta_tab',
        'label' => 'Bottom CTA Box',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
    );
    $fields[] = array(
        'key' => 'field_tfj_cta_title',
        'label' => 'CTA Title',
        'name' => 'tfj_cta_title',
        'type' => 'text',
    );
    $fields[] = array(
        'key' => 'field_tfj_cta_desc',
        'label' => 'CTA Description Paragraph',
        'name' => 'tfj_cta_desc',
        'type' => 'textarea',
        'rows' => 2,
    );
    $fields[] = array(
        'key' => 'field_tfj_cta_btn_text',
        'label' => 'CTA Button Text',
        'name' => 'tfj_cta_btn_text',
        'type' => 'text',
    );
    $fields[] = array(
        'key' => 'field_tfj_cta_btn_url',
        'label' => 'CTA Button URL',
        'name' => 'tfj_cta_btn_url',
        'type' => 'text',
    );
    $fields[] = array(
        'key' => 'field_tfj_cta_trust_line',
        'label' => 'CTA Subtext / Trust line',
        'name' => 'tfj_cta_trust_line',
        'type' => 'text',
    );

    // Footer tab
    $fields[] = array(
        'key' => 'field_tfj_footer_tab',
        'label' => 'Footer',
        'type' => 'tab',
        'placement' => 'top',
        'endpoint' => 0,
    );
    $fields[] = array(
        'key' => 'field_tfj_footer_text',
        'label' => 'Footer Copyright Bottom Text',
        'name' => 'tfj_footer_text',
        'type' => 'textarea',
        'rows' => 3,
    );

    acf_add_local_field_group(array(
        'key' => 'group_tfj_landing',
        'title' => 'Timesheet Landing Page Fields',
        'fields' => $fields,
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ),
            ),
        ),
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ));

    // Register ACF field group for Screenshots CPT
    acf_add_local_field_group(array(
        'key' => 'group_tfj_screenshot',
        'title' => 'Timesheet Screenshot Settings',
        'fields' => array(
            array(
                'key' => 'field_tfj_screenshot_category',
                'label' => 'Category',
                'name' => 'tfj_screenshot_category',
                'type' => 'select',
                'choices' => array(
                    'dev' => 'For Developers',
                    'mgr' => 'For Managers',
                    'billing' => 'Billing & JSM',
                ),
                'default_value' => 'dev',
                'allow_null' => 0,
                'multiple' => 0,
                'ui' => 0,
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_tfj_screenshot_image',
                'label' => 'Screenshot Image',
                'name' => 'tfj_screenshot_image',
                'type' => 'image',
                'return_format' => 'url',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'tfj_screenshot',
                ),
            ),
        ),
        'position' => 'side',
        'style' => 'default',
        'active' => true,
    ));
}

/**
 * Register CSS for landing page
 */
add_action( 'wp_enqueue_scripts', 'tfj_register_landing_styles' );
function tfj_register_landing_styles() {
    wp_register_style( 'tfj-landing-style', get_template_directory_uri() . '/assets/css/css-time.css', array(), '1.0' );
}

/**
 * Shortcode callback
 */
function jira_timesheet_landing_shortcode( $atts ) {
    wp_enqueue_style( 'tfj-landing-style' );
    $lang = get_tfj_landing_language();
    
    // Resolve values
    $hero_badge = get_tfj_landing_field( 'tfj_hero_badge', $lang );
    $hero_title = get_tfj_landing_field( 'tfj_hero_title', $lang );
    $hero_lead  = get_tfj_landing_field( 'tfj_hero_lead', $lang );
    $btn_1_text = get_tfj_landing_field( 'tfj_btn_1_text', $lang );
    $btn_1_url  = get_tfj_landing_field( 'tfj_btn_1_url', $lang );
    $btn_2_text = get_tfj_landing_field( 'tfj_btn_2_text', $lang );
    $btn_2_url  = get_tfj_landing_field( 'tfj_btn_2_url', $lang );

    $trust_bar_1 = get_tfj_landing_field( 'tfj_trust_bar_1', $lang );
    $trust_bar_2 = get_tfj_landing_field( 'tfj_trust_bar_2', $lang );
    $trust_bar_3 = get_tfj_landing_field( 'tfj_trust_bar_3', $lang );
    $trust_bar_4 = get_tfj_landing_field( 'tfj_trust_bar_4', $lang );

    $benefits_title    = get_tfj_landing_field( 'tfj_benefits_title', $lang );
    $benefits_subtitle = get_tfj_landing_field( 'tfj_benefits_subtitle', $lang );

    $benefit_1_title = get_tfj_landing_field( 'tfj_benefit_1_title', $lang );
    $benefit_1_desc  = get_tfj_landing_field( 'tfj_benefit_1_desc', $lang );
    $benefit_2_title = get_tfj_landing_field( 'tfj_benefit_2_title', $lang );
    $benefit_2_desc  = get_tfj_landing_field( 'tfj_benefit_2_desc', $lang );
    $benefit_3_title = get_tfj_landing_field( 'tfj_benefit_3_title', $lang );
    $benefit_3_desc  = get_tfj_landing_field( 'tfj_benefit_3_desc', $lang );
    $benefit_4_title = get_tfj_landing_field( 'tfj_benefit_4_title', $lang );
    $benefit_4_desc  = get_tfj_landing_field( 'tfj_benefit_4_desc', $lang );
    $benefit_5_title = get_tfj_landing_field( 'tfj_benefit_5_title', $lang );
    $benefit_5_desc  = get_tfj_landing_field( 'tfj_benefit_5_desc', $lang );
    $benefit_6_title = get_tfj_landing_field( 'tfj_benefit_6_title', $lang );
    $benefit_6_desc  = get_tfj_landing_field( 'tfj_benefit_6_desc', $lang );

    $shortcuts_badge  = get_tfj_landing_field( 'tfj_shortcuts_badge', $lang );
    $shortcuts_title  = get_tfj_landing_field( 'tfj_shortcuts_title', $lang );
    $shortcuts_lead   = get_tfj_landing_field( 'tfj_shortcuts_lead', $lang );
    $shortcuts_accent = get_tfj_landing_field( 'tfj_shortcuts_accent', $lang );

    $shortcut_1_key    = get_tfj_landing_field( 'tfj_shortcut_1_key', $lang );
    $shortcut_1_action = get_tfj_landing_field( 'tfj_shortcut_1_action', $lang );
    $shortcut_2_key    = get_tfj_landing_field( 'tfj_shortcut_2_key', $lang );
    $shortcut_2_action = get_tfj_landing_field( 'tfj_shortcut_2_action', $lang );
    $shortcut_3_key    = get_tfj_landing_field( 'tfj_shortcut_3_key', $lang );
    $shortcut_3_action = get_tfj_landing_field( 'tfj_shortcut_3_action', $lang );
    $shortcut_4_key    = get_tfj_landing_field( 'tfj_shortcut_4_key', $lang );
    $shortcut_4_action = get_tfj_landing_field( 'tfj_shortcut_4_action', $lang );
    $shortcut_5_key    = get_tfj_landing_field( 'tfj_shortcut_5_key', $lang );
    $shortcut_5_action = get_tfj_landing_field( 'tfj_shortcut_5_action', $lang );

    $video_title = get_tfj_landing_field( 'tfj_video_title', $lang );
    $video_desc  = get_tfj_landing_field( 'tfj_video_desc', $lang );

    $video_1_title = get_tfj_landing_field( 'tfj_video_1_title', $lang );
    $video_1_lang  = get_tfj_landing_field( 'tfj_video_1_lang', $lang );
    $video_1_id    = get_tfj_landing_field( 'tfj_video_1_id', $lang );
    $video_1_desc  = get_tfj_landing_field( 'tfj_video_1_desc', $lang );

    $video_2_title = get_tfj_landing_field( 'tfj_video_2_title', $lang );
    $video_2_lang  = get_tfj_landing_field( 'tfj_video_2_lang', $lang );
    $video_2_id    = get_tfj_landing_field( 'tfj_video_2_id', $lang );
    $video_2_desc  = get_tfj_landing_field( 'tfj_video_2_desc', $lang );

    $gallery_title       = get_tfj_landing_field( 'tfj_gallery_title', $lang );
    $gallery_desc        = get_tfj_landing_field( 'tfj_gallery_desc', $lang );
    $gallery_tab_all     = get_tfj_landing_field( 'tfj_gallery_tab_all', $lang );
    $gallery_tab_dev     = get_tfj_landing_field( 'tfj_gallery_tab_dev', $lang );
    $gallery_tab_mgr     = get_tfj_landing_field( 'tfj_gallery_tab_mgr', $lang );
    $gallery_tab_billing = get_tfj_landing_field( 'tfj_gallery_tab_billing', $lang );

    // Query custom screenshots from the CPT
    $screenshot_args = array(
        'post_type'      => 'tfj_screenshot',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );
    if ( function_exists( 'pll_current_language' ) ) {
        $screenshot_args['lang'] = pll_current_language();
    }
    $screenshot_query = new WP_Query( $screenshot_args );
    $screenshots = array();

    if ( $screenshot_query->have_posts() ) {
        while ( $screenshot_query->have_posts() ) {
            $screenshot_query->the_post();
            $s_id = get_the_ID();
            
            // Get ACF Image (try to handle both URL/Array/ID types)
            $img_val = get_field( 'tfj_screenshot_image', $s_id );
            $img_url = '';
            if ( ! empty( $img_val ) ) {
                if ( is_array( $img_val ) && isset( $img_val['url'] ) ) {
                    $img_url = $img_val['url'];
                } elseif ( is_numeric( $img_val ) ) {
                    $img_url = wp_get_attachment_image_url( $img_val, 'full' );
                } else {
                    $img_url = $img_val;
                }
            }
            
            // Fallback to Featured Image
            if ( empty( $img_url ) ) {
                $img_url = get_the_post_thumbnail_url( $s_id, 'full' );
            }
            
            if ( ! empty( $img_url ) ) {
                $screenshots[] = array(
                    'url'      => $img_url,
                    'caption'  => get_the_title( $s_id ),
                    'category' => get_field( 'tfj_screenshot_category', $s_id ),
                );
            }
        }
        wp_reset_postdata();
    }

    // Fallback to defaults if no CPT screenshots are found
    if ( empty( $screenshots ) ) {
        global $tfj_defaults;
        for ( $i = 1; $i <= 8; $i++ ) {
            $fallback_url = isset( $tfj_defaults[$lang]["gallery_img_{$i}_url"] ) ? $tfj_defaults[$lang]["gallery_img_{$i}_url"] : '';
            $fallback_cap = isset( $tfj_defaults[$lang]["gallery_img_{$i}_caption"] ) ? $tfj_defaults[$lang]["gallery_img_{$i}_caption"] : '';
            $fallback_cat = isset( $tfj_defaults[$lang]["gallery_img_{$i}_category"] ) ? $tfj_defaults[$lang]["gallery_img_{$i}_category"] : 'dev';
            
            // Check if there are ACF overrides on the page for these static images
            $acf_url = get_tfj_landing_image( "tfj_gallery_img_{$i}_url", $lang );
            $acf_cap = get_tfj_landing_field( "tfj_gallery_img_{$i}_caption", $lang );
            $acf_cat = get_tfj_landing_field( "tfj_gallery_img_{$i}_category", $lang );
            
            $img_url  = ! empty( $acf_url ) ? $acf_url : $fallback_url;
            $caption  = ! empty( $acf_cap ) ? $acf_cap : $fallback_cap;
            $category = ! empty( $acf_cat ) ? $acf_cat : $fallback_cat;
            
            if ( ! empty( $img_url ) ) {
                $screenshots[] = array(
                    'url'      => $img_url,
                    'caption'  => $caption,
                    'category' => $category,
                );
            }
        }
    }

    $cta_title      = get_tfj_landing_field( 'tfj_cta_title', $lang );
    $cta_desc       = get_tfj_landing_field( 'tfj_cta_desc', $lang );
    $cta_btn_text   = get_tfj_landing_field( 'tfj_cta_btn_text', $lang );
    $cta_btn_url    = get_tfj_landing_field( 'tfj_cta_btn_url', $lang );
    $cta_trust_line = get_tfj_landing_field( 'tfj_cta_trust_line', $lang );

    $footer_text = get_tfj_landing_field( 'tfj_footer_text', $lang );
    
    // Resolve footer links
    global $tfj_defaults;
    $footer_links = get_field( 'tfj_footer_links' );
    if ( empty( $footer_links ) ) {
        $footer_links = $tfj_defaults[$lang]['footer_links'];
    }

    // Build translations for hardcoded accessibility labels
    $labels = array(
        'en' => array(
            'image_viewer' => 'Image viewer',
            'close' => 'Close',
            'prev' => 'Previous',
            'next' => 'Next',
            'play_video' => 'Play video',
        ),
        'fi' => array(
            'image_viewer' => 'Kuvan katseluohjelma',
            'close' => 'Sulje',
            'prev' => 'Edellinen',
            'next' => 'Seuraava',
            'play_video' => 'Toista video',
        ),
    );
    $l = isset( $labels[$lang] ) ? $labels[$lang] : $labels['en'];

    ob_start();
    ?>
    <!-- LIGHTBOX -->
    <div class="tfj-lightbox" id="tfjLightbox" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $l['image_viewer'] ); ?>">
      <button class="tfj-lb-close" id="tfjLbClose" aria-label="<?php echo esc_attr( $l['close'] ); ?>">✕</button>
      <button class="tfj-lb-prev" id="tfjLbPrev" aria-label="<?php echo esc_attr( $l['prev'] ); ?>">‹</button>
      <div class="tfj-lb-inner"><img id="tfjLbImg" src="" alt=""></div>
      <button class="tfj-lb-next" id="tfjLbNext" aria-label="<?php echo esc_attr( $l['next'] ); ?>">›</button>
      <div class="tfj-lb-counter" id="tfjLbCounter"></div>
    </div>

    <div class="tfj">

      <!-- HERO SECTION -->
      <section class="tfj-hero">
        <div class="tfj-wrap">
          <div class="tfj-hero-inner">
            <div class="tfj-badge"><?php echo esc_html( $hero_badge ); ?></div>
            <span class="tfj-hero-title"><?php echo wp_kses( $hero_title, array( 'span' => array() ) ); ?></span>
            <p class="tfj-hero-lead"><?php echo esc_html( $hero_lead ); ?></p>
            <div class="tfj-hero-btns">
              <a href="<?php echo esc_url( $btn_1_url ); ?>" target="_blank" rel="noopener" class="tfj-btn tfj-btn--white">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                <?php echo esc_html( $btn_1_text ); ?>
              </a>
              <a href="<?php echo esc_url( $btn_2_url ); ?>" target="_blank" rel="noopener" class="tfj-btn tfj-btn--outline">
                <?php echo esc_html( $btn_2_text ); ?>
              </a>
            </div>
          </div>
          
          <!-- SOCIAL PROOF / TRUST SECTION -->
          <div class="tfj-trust-bar">
            <div class="tfj-trust-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
              <?php echo esc_html( $trust_bar_1 ); ?>
            </div>
            <div class="tfj-trust-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              <?php echo esc_html( $trust_bar_2 ); ?>
            </div>
            <div class="tfj-trust-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
              <?php echo esc_html( $trust_bar_3 ); ?>
            </div>
            <div class="tfj-trust-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
              <?php echo esc_html( $trust_bar_4 ); ?>
            </div>
          </div>
        </div>
      </section>

      <!-- KEY BENEFITS SECTION -->
      <section class="tfj-section">
        <div class="tfj-wrap">
          <div class="tfj-section-head">
            <div class="tfj-divider"></div>
            <span class="tfj-section-title"><?php echo esc_html( $benefits_title ); ?></span>
            <p><?php echo esc_html( $benefits_subtitle ); ?></p>
          </div>

          <div class="tfj-benefits-grid">
            <!-- Benefit 1 -->
            <div class="tfj-benefit-card">
              <div class="tfj-benefit-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><line x1="6" y1="8" x2="6.01" y2="8"></line><line x1="10" y1="8" x2="10.01" y2="8"></line><line x1="14" y1="8" x2="14.01" y2="8"></line><line x1="18" y1="8" x2="18.01" y2="8"></line><line x1="6" y1="12" x2="6.01" y2="12"></line><line x1="10" y1="12" x2="10.01" y2="12"></line><line x1="14" y1="12" x2="14.01" y2="12"></line><line x1="18" y1="12" x2="18.01" y2="12"></line><line x1="7" y1="16" x2="17" y2="16"></line></svg>
              </div>
              <h3><?php echo esc_html( $benefit_1_title ); ?></h3>
              <p><?php echo esc_html( $benefit_1_desc ); ?></p>
            </div>

            <!-- Benefit 2 -->
            <div class="tfj-benefit-card">
              <div class="tfj-benefit-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline><path d="M12 2v2"></path></svg>
              </div>
              <h3><?php echo esc_html( $benefit_2_title ); ?></h3>
              <p><?php echo esc_html( $benefit_2_desc ); ?></p>
            </div>

            <!-- Benefit 3 -->
            <div class="tfj-benefit-card">
              <div class="tfj-benefit-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
              </div>
              <h3><?php echo esc_html( $benefit_3_title ); ?></h3>
              <p><?php echo esc_html( $benefit_3_desc ); ?></p>
            </div>

            <!-- Benefit 4 -->
            <div class="tfj-benefit-card">
              <div class="tfj-benefit-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
              </div>
              <h3><?php echo esc_html( $benefit_4_title ); ?></h3>
              <p><?php echo esc_html( $benefit_4_desc ); ?></p>
            </div>

            <!-- Benefit 5 -->
            <div class="tfj-benefit-card">
              <div class="tfj-benefit-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
              </div>
              <h3><?php echo esc_html( $benefit_5_title ); ?></h3>
              <p><?php echo esc_html( $benefit_5_desc ); ?></p>
            </div>

            <!-- Benefit 6 -->
            <div class="tfj-benefit-card">
              <div class="tfj-benefit-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
              </div>
              <h3><?php echo esc_html( $benefit_6_title ); ?></h3>
              <p><?php echo esc_html( $benefit_6_desc ); ?></p>
            </div>
          </div>
        </div>
      </section>

      <!-- KEYBOARD SHORTCUTS SECTION -->
      <section class="tfj-section tfj-section--alt">
        <div class="tfj-wrap">
          <div class="tfj-shortcuts-container">
            <div class="tfj-shortcuts-info">
              <div class="tfj-badge"><?php echo esc_html( $shortcuts_badge ); ?></div>
              <span class="tfj-shortcuts-title"><?php echo esc_html( $shortcuts_title ); ?></span>
              <p><?php echo esc_html( $shortcuts_lead ); ?></p>
              <div class="tfj-shortcuts-accent">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><line x1="6" y1="8" x2="6.01" y2="8"></line><line x1="10" y1="8" x2="10.01" y2="8"></line><line x1="14" y1="8" x2="14.01" y2="8"></line><line x1="18" y1="8" x2="18.01" y2="8"></line><line x1="6" y1="12" x2="6.01" y2="12"></line><line x1="10" y1="12" x2="10.01" y2="12"></line><line x1="14" y1="12" x2="14.01" y2="12"></line><line x1="18" y1="12" x2="18.01" y2="12"></line><line x1="7" y1="16" x2="17" y2="16"></line></svg>
                <span><?php echo esc_html( $shortcuts_accent ); ?></span>
              </div>
            </div>
            <div class="tfj-shortcuts-table-wrap">
              <table class="tfj-shortcuts-table">
                <thead>
                  <tr>
                    <th>Key Command</th>
                    <th>Action / Operation</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ( ! empty( $shortcut_1_key ) ): ?>
                  <tr>
                    <td><kbd><?php echo esc_html( $shortcut_1_key ); ?></kbd></td>
                    <td><?php echo esc_html( $shortcut_1_action ); ?></td>
                  </tr>
                  <?php endif; ?>
                  <?php if ( ! empty( $shortcut_2_key ) ): ?>
                  <tr>
                    <td><kbd><?php echo esc_html( $shortcut_2_key ); ?></kbd></td>
                    <td><?php echo esc_html( $shortcut_2_action ); ?></td>
                  </tr>
                  <?php endif; ?>
                  <?php if ( ! empty( $shortcut_3_key ) ): ?>
                  <tr>
                    <td><kbd><?php echo esc_html( $shortcut_3_key ); ?></kbd></td>
                    <td><?php echo esc_html( $shortcut_3_action ); ?></td>
                  </tr>
                  <?php endif; ?>
                  <?php if ( ! empty( $shortcut_4_key ) ): ?>
                  <tr>
                    <td><kbd><?php echo esc_html( $shortcut_4_key ); ?></kbd></td>
                    <td><?php echo esc_html( $shortcut_4_action ); ?></td>
                  </tr>
                  <?php endif; ?>
                  <?php if ( ! empty( $shortcut_5_key ) ): ?>
                  <tr>
                    <td><kbd><?php echo esc_html( $shortcut_5_key ); ?></kbd></td>
                    <td><?php echo esc_html( $shortcut_5_action ); ?></td>
                  </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </section>

      <!-- VIDEO DEMO SECTION -->
      <section class="tfj-section">
        <div class="tfj-wrap">
          <div class="tfj-section-head">
            <div class="tfj-divider"></div>
            <span class="tfj-section-title"><?php echo esc_html( $video_title ); ?></span>
            <p><?php echo esc_html( $video_desc ); ?></p>
          </div>

          <div class="tfj-videos-grid">
            <!-- Video 1 -->
            <?php if ( ! empty( $video_1_id ) ): ?>
            <div class="tfj-video-card">
              <div class="tfj-video-header">
                <h3><?php echo esc_html( $video_1_title ); ?></h3>
                <span class="tfj-video-lang"><?php echo esc_html( $video_1_lang ); ?></span>
              </div>
              <div class="tfj-video-wrap" id="vid1" data-vid="<?php echo esc_attr( $video_1_id ); ?>">
                <img class="tfj-video-thumb" src="https://img.youtube.com/vi/<?php echo esc_attr( $video_1_id ); ?>/maxresdefault.jpg" alt="<?php echo esc_attr( $video_1_title ); ?>" referrerpolicy="no-referrer" crossorigin="anonymous" onerror="this.src='https://img.youtube.com/vi/<?php echo esc_attr( $video_1_id ); ?>/hqdefault.jpg'">
                <div class="tfj-video-play" aria-label="<?php echo esc_attr( $l['play_video'] ); ?>">
                  <svg width="56" height="56" viewBox="0 0 72 72" fill="none"><circle cx="36" cy="36" r="36" fill="rgba(99, 102, 241, 0.95)"></circle><polygon points="30,24 30,48 50,36" fill="white"></polygon></svg>
                </div>
              </div>
              <p class="tfj-video-desc"><?php echo esc_html( $video_1_desc ); ?></p>
            </div>
            <?php endif; ?>

            <!-- Video 2 -->
            <?php if ( ! empty( $video_2_id ) ): ?>
            <div class="tfj-video-card">
              <div class="tfj-video-header">
                <h3><?php echo esc_html( $video_2_title ); ?></h3>
                <span class="tfj-video-lang"><?php echo esc_html( $video_2_lang ); ?></span>
              </div>
              <div class="tfj-video-wrap" id="vid2" data-vid="<?php echo esc_attr( $video_2_id ); ?>">
                <img class="tfj-video-thumb" src="https://img.youtube.com/vi/<?php echo esc_attr( $video_2_id ); ?>/maxresdefault.jpg" alt="<?php echo esc_attr( $video_2_title ); ?>" referrerpolicy="no-referrer" crossorigin="anonymous" onerror="this.src='https://img.youtube.com/vi/<?php echo esc_attr( $video_2_id ); ?>/hqdefault.jpg'">
                <div class="tfj-video-play" aria-label="<?php echo esc_attr( $l['play_video'] ); ?>">
                  <svg width="56" height="56" viewBox="0 0 72 72" fill="none"><circle cx="36" cy="36" r="36" fill="rgba(99, 102, 241, 0.95)"></circle><polygon points="30,24 30,48 50,36" fill="white"></polygon></svg>
                </div>
              </div>
              <p class="tfj-video-desc"><?php echo esc_html( $video_2_desc ); ?></p>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- SCREENSHOTS SECTION -->
      <section class="tfj-section tfj-section--alt">
        <div class="tfj-wrap">
          <div class="tfj-section-head">
            <div class="tfj-divider"></div>
            <span class="tfj-section-title"><?php echo esc_html( $gallery_title ); ?></span>
            <p><?php echo esc_html( $gallery_desc ); ?></p>
          </div>

          <!-- Categories Filter Tabs -->
          <div class="tfj-gallery-tabs">
            <button class="tfj-tab-btn tfj-tab-active" data-category="all"><?php echo esc_html( $gallery_tab_all ); ?></button>
            <button class="tfj-tab-btn" data-category="dev"><?php echo esc_html( $gallery_tab_dev ); ?></button>
            <button class="tfj-tab-btn" data-category="mgr"><?php echo esc_html( $gallery_tab_mgr ); ?></button>
            <button class="tfj-tab-btn" data-category="billing"><?php echo esc_html( $gallery_tab_billing ); ?></button>
          </div>

          <div class="tfj-gallery-grid" id="tfjGallery">
            <?php foreach ( $screenshots as $s ): 
                $img_url = $s['url'];
                $caption = $s['caption'];
                $category = $s['category'];
            ?>
            <div class="tfj-gallery-item" data-category="<?php echo esc_attr( $category ); ?>" tabindex="0" role="button" aria-label="<?php echo esc_attr( $caption ); ?>">
              <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $caption ); ?>" loading="lazy" referrerpolicy="no-referrer">
              <div class="tfj-gallery-caption"><?php echo esc_html( $caption ); ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <!-- CTA SECTION -->
      <section class="tfj-section">
        <div class="tfj-wrap">
          <div class="tfj-cta-box">
            <div class="tfj-cta-box-inner">
              <span class="tfj-cta-title"><?php echo esc_html( $cta_title ); ?></span>
              <p><?php echo esc_html( $cta_desc ); ?></p>
              <div class="tfj-hero-btns">
                <a href="<?php echo esc_url( $cta_btn_url ); ?>" target="_blank" rel="noopener" class="tfj-btn tfj-btn--white">
                  <?php echo esc_html( $cta_btn_text ); ?>
                </a>
              </div>
              <div class="tfj-trust-line"><?php echo esc_html( $cta_trust_line ); ?></div>
            </div>
          </div>
        </div>
      </section>

      <!-- FOOTER -->
      <footer class="tfj-footer">
        <div class="tfj-wrap">
          <div class="tfj-footer-links">
            <?php foreach ( $footer_links as $link ): 
                $class = isset( $link['class'] ) ? $link['class'] : '';
            ?>
            <a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener" class="<?php echo esc_attr( $class ); ?>">
                <?php echo esc_html( $link['text'] ); ?>
            </a>
            <?php endforeach; ?>
          </div>
          <div class="tfj-footer-bottom">
            <p><?php echo esc_html( $footer_text ); ?></p>
          </div>
        </div>
      </footer>

    </div>

    <!-- Interactive Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Lightbox & Filtering functionality
      const lightbox = document.getElementById('tfjLightbox');
      const lbImg = document.getElementById('tfjLbImg');
      const lbClose = document.getElementById('tfjLbClose');
      const lbPrev = document.getElementById('tfjLbPrev');
      const lbNext = document.getElementById('tfjLbNext');
      const lbCounter = document.getElementById('tfjLbCounter');
      
      const tabBtns = document.querySelectorAll('.tfj-tab-btn');
      const galleryItems = document.querySelectorAll('.tfj-gallery-item');
      
      let currentIndex = 0;
      let currentImages = [];

      // Update the collection of images based on visible items
      function updateLightboxImages() {
        const visibleItems = Array.from(galleryItems).filter(item => !item.classList.contains('tfj-item-hidden'));
        currentImages = visibleItems.map(item => {
          const img = item.querySelector('img');
          return {
            src: img.getAttribute('src'),
            alt: img.getAttribute('alt') || '',
            element: item
          };
        });
      }

      // Filter gallery items by category
      function filterCategory(category) {
        galleryItems.forEach(item => {
          const itemCat = item.getAttribute('data-category');
          if (category === 'all' || itemCat === category) {
            item.classList.remove('tfj-item-hidden');
          } else {
            item.classList.add('tfj-item-hidden');
          }
        });
        updateLightboxImages();
      }

      // Tab Button Handlers
      tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          tabBtns.forEach(b => b.classList.remove('tfj-tab-active'));
          btn.classList.add('tfj-tab-active');
          filterCategory(btn.getAttribute('data-category'));
        });
      });

      // Initialize list
      updateLightboxImages();

      function openLightbox(element) {
        const index = currentImages.findIndex(img => img.element === element);
        if (index !== -1) {
          currentIndex = index;
          updateLightboxImage();
          lightbox.classList.add('tfj-lb-open');
          document.body.style.overflow = 'hidden';
        }
      }

      function closeLightbox() {
        lightbox.classList.remove('tfj-lb-open');
        document.body.style.overflow = '';
      }

      function showPrev() {
        if (currentImages.length === 0) return;
        currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
        updateLightboxImage();
      }

      function showNext() {
        if (currentImages.length === 0) return;
        currentIndex = (currentIndex + 1) % currentImages.length;
        updateLightboxImage();
      }

      function updateLightboxImage() {
        if (currentImages.length === 0) return;
        const imgData = currentImages[currentIndex];
        lbImg.src = imgData.src;
        lbImg.alt = imgData.alt;
        lbCounter.textContent = `${currentIndex + 1} / ${currentImages.length}`;
      }

      galleryItems.forEach((item) => {
        item.addEventListener('click', () => {
          openLightbox(item);
        });
        item.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openLightbox(item);
          }
        });
      });

      lbClose.addEventListener('click', closeLightbox);
      
      lbPrev.addEventListener('click', (e) => {
        e.stopPropagation();
        showPrev();
      });
      
      lbNext.addEventListener('click', (e) => {
        e.stopPropagation();
        showNext();
      });

      lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox || e.target.classList.contains('tfj-lb-inner') || e.target === lbImg.parentElement) {
          closeLightbox();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('tfj-lb-open')) return;
        
        if (e.key === 'ArrowLeft') {
          showPrev();
        } else if (e.key === 'ArrowRight') {
          showNext();
        } else if (e.key === 'Escape') {
          closeLightbox();
        }
      });

      // Custom click-to-load video players
      const videoWraps = document.querySelectorAll('.tfj-video-wrap');
      videoWraps.forEach(wrap => {
        wrap.addEventListener('click', function() {
          const vidId = this.getAttribute('data-vid');
          if (vidId) {
            this.innerHTML = `<iframe src="https://www.youtube.com/embed/${vidId}?autoplay=1" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
          }
        });
      });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Register shortcode
add_shortcode( 'jira_timesheet_landing', 'jira_timesheet_landing_shortcode' );

/**
 * Register Screenshot Custom Post Type
 */
add_action( 'init', 'tfj_register_screenshot_cpt' );
function tfj_register_screenshot_cpt() {
    $labels = array(
        'name'               => 'Timesheet Screenshots',
        'singular_name'      => 'Timesheet Screenshot',
        'menu_name'          => 'Timesheet Screenshots',
        'name_admin_bar'     => 'Timesheet Screenshot',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Screenshot',
        'new_item'           => 'New Screenshot',
        'edit_item'          => 'Edit Screenshot',
        'view_item'          => 'View Screenshot',
        'all_items'          => 'All Screenshots',
        'search_items'       => 'Search Screenshots',
        'parent_item_colon'  => 'Parent Screenshots:',
        'not_found'          => 'No screenshots found.',
        'not_found_in_trash' => 'No screenshots found in Trash.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'tfj-screenshot' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-images-alt',
        'supports'           => array( 'title', 'thumbnail', 'page-attributes' ),
    );

    register_post_type( 'tfj_screenshot', $args );
}

/**
 * Register Screenshot CPT with Polylang for translation programmatically
 */
add_filter( 'pll_get_post_types', 'tfj_register_screenshot_cpt_pll' );
function tfj_register_screenshot_cpt_pll( $post_types ) {
    $post_types['tfj_screenshot'] = 'tfj_screenshot';
    return $post_types;
}

/**
 * One-time Content Importer Utility
 * Trigger by visiting: /wp-admin/?import_tfj_content=1&page_id=YOUR_PAGE_ID&lang=en_or_fi
 */
add_action( 'admin_init', 'tfj_import_landing_content_utility' );
function tfj_import_landing_content_utility() {
    if ( ! isset( $_GET['import_tfj_content'] ) || ! isset( $_GET['page_id'] ) || ! isset( $_GET['lang'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized access.' );
    }

    $page_id = (int) $_GET['page_id'];
    $lang    = sanitize_key( $_GET['lang'] ); // 'en' or 'fi'

    if ( ! get_post( $page_id ) ) {
        wp_die( 'Target page ID does not exist.' );
    }

    $json_path = get_template_directory() . '/timesheet-landing-content.json';
    if ( ! file_exists( $json_path ) ) {
        wp_die( 'Content JSON file not found at: ' . esc_html( $json_path ) );
    }

    $json_data = file_get_contents( $json_path );
    $data = json_decode( $json_data, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        wp_die( 'Invalid JSON in: ' . esc_html( $json_path ) );
    }

    if ( ! isset( $data[$lang] ) ) {
        wp_die( 'Language key "' . esc_html( $lang ) . '" not found in JSON data.' );
    }

    $count = 0;
    foreach ( $data[$lang] as $key => $val ) {
        // Skip static screenshots gallery fields since we use CPT screenshots
        if ( strpos( $key, 'tfj_gallery_img_' ) === 0 ) {
            continue;
        }
        
        // update_field works with either field key or field name
        if ( function_exists( 'update_field' ) ) {
            update_field( $key, $val, $page_id );
            $count++;
        }
    }

    // Auto-seed CPT screenshots for this language
    for ( $i = 1; $i <= 8; $i++ ) {
        $url_key = "tfj_gallery_img_{$i}_url";
        $cap_key = "tfj_gallery_img_{$i}_caption";
        $cat_key = "tfj_gallery_img_{$i}_category";
        
        if ( isset( $data[$lang][$url_key] ) ) {
            $img_url  = $data[$lang][$url_key];
            $caption  = isset( $data[$lang][$cap_key] ) ? $data[$lang][$cap_key] : '';
            $category = isset( $data[$lang][$cat_key] ) ? $data[$lang][$cat_key] : 'dev';
            
            // Check if this screenshot post already exists (by title and language) to avoid duplicates
            $existing = get_posts( array(
                'post_type'   => 'tfj_screenshot',
                'title'       => $caption,
                'post_status' => 'any',
                'lang'        => $lang,
                'suppress_filters' => false,
            ) );
            
            if ( empty( $existing ) ) {
                $post_id = wp_insert_post( array(
                    'post_title'   => $caption,
                    'post_status'  => 'publish',
                    'post_type'    => 'tfj_screenshot',
                    'menu_order'   => $i,
                ) );
                
                if ( $post_id && ! is_wp_error( $post_id ) ) {
                    if ( function_exists( 'pll_set_post_language' ) ) {
                        pll_set_post_language( $post_id, $lang );
                    }
                    if ( function_exists( 'update_field' ) ) {
                        update_field( 'tfj_screenshot_category', $category, $post_id );
                        update_field( 'tfj_screenshot_image', $img_url, $post_id );
                    }
                    $count++;
                }
            }
        }
    }

    wp_die( sprintf( 'Success! Imported %d items (page fields and CPT screenshot posts) for language "%s" on page ID %d.', $count, esc_html( $lang ), $page_id ) );
}
