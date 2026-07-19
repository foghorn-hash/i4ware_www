<?php
/**
 * Shortcode for WordPress Development page [i4ware_wordpress_page]
 * Supports Polylang localizations and enqueues separate CSS from the SDK page.
 */
add_shortcode('i4ware_wordpress_page', function() {
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';

    // Enqueue the SDK page CSS for consistent layout and styling
    wp_enqueue_style('i4ware-sdk-page', get_template_directory_uri() . '/assets/css/sdk-page.css', array(), '1.1');

    // Localized strings
    $data = array(
        'fi' => array(
            'eyebrow' => 'WordPress · Avoin Lähdekoodi · Räätälöity',
            'hero_title' => 'WordPress-kehitys ammattitaidolla.',
            'hero_desc' => 'Tarjoamme kattavia WordPress-kehityspalveluita, jotka räätälöidään juuri sinun liiketoimintasi tarpeisiin. Toteutamme laadukkaat ja toimivat ratkaisut vuosien kokemuksella.',
            'request_quote' => 'Pyydä tarjous →',
            'github_code' => 'Lähdekoodi GitHubissa',
            'flexibility' => 'Joustavuus & Hinta',
            'flex_title' => 'Koodia tarpeidesi ja budjettisi mukaan.',
            'flex_lead' => 'Alempaa löydät tarjouspyyntölomakkeen. Tarjoamme palveluita suoraan loppuasiakkaille sekä alihankintana mainostoimistoille ja ohjelmistoyrityksille, jotka tarvitsevat kokeneen ohjelmoijan joustavasti kausiavuksi, sijaiseksi tai tuuraajaksi.',
            'flex_p1' => 'Olitpa köyhä opiskelijayrittäjä tai varakas pitkän linjan yrittäjä, löydämme yhdessä hinnan, joka miellyttää tilaajaa – sillä jo 20 € + ALV 25,5 % saat noin 20 € edestä koodia, ja esimerkiksi 500 €:lla saat vastaavan arvon mukaisen määrän koodirivejä.',
            'flex_p2' => 'Tämä on mahdollista, koska hyödynnämme tekoälyä koodin generoinnissa – voit esimerkiksi lähettää meille kuvatiedoston siitä, mitä haluat, ja tuotamme ratkaisun sen pohjalta nopeasti ja kustannustehokkaasti.',
            'badge1' => '20+ vuoden kokemus',
            'badge2' => 'Tekoälyavusteinen',
            'badge3' => 'Joustava kausiapulainen',
            'features' => 'Palvelumme',
            'features_title' => 'Räätälöidyt WordPress-ratkaisut.',
            'features_lead' => 'Suunnittelemme ja toteutamme asiakaskohtaiset lisäosat ja teemat modernilla teknologiapinolla.',
            'feat1_title' => 'Räätälöidyt lisäosat',
            'feat1_desc' => 'Toteutamme asiakaskohtaiset plugin-ratkaisut hyödyntäen JavaScriptiä, jQueryä, Reactia, PHP:tä ja MySQL-tietokantoja.',
            'feat2_title' => 'Tekoäly & Integraatiot',
            'feat2_desc' => 'OpenAI API -rajapinnat automaatioon ja sisällöntuotantoon. Monipuoliset API-integraatiot muihin järjestelmiin.',
            'feat3_title' => 'ACF & Monikielisyys',
            'feat3_desc' => 'Sisäänrakennettu Advanced Custom Fields (ACF) -tuki ja Polylang-monikielisyystuki vaativiin tarpeisiin.',
            'feat4_title' => 'Teemojen HTML/CSS-taitto',
            'feat4_desc' => 'Rakennamme yksilölliset ja responsiiviset WordPress-teemat, jotka näyttävät hyvältä ja toimivat nopeasti kaikilla laitteilla.',
            'feat5_title' => 'Selainkäyttöiset asetukset',
            'feat5_desc' => 'Teemoihin voidaan rakentaa selainkäyttöiset asetussivut, joiden kautta hallitset sivustosi sisältöä ja ominaisuuksia.',
            'feat6_title' => 'Salasanasuojatut asetukset',
            'feat6_desc' => 'Muokkaa väriteemoja, logoja, metatietoja tai valikoita turvallisesti suoraan selaimesta ilman hallintapaneelia.',
            'why_us' => 'Miksi valita meidät?',
            'why_title' => 'Kokemus, laatu ja nopeus yhdessä.',
            'why_lead' => 'Kehitysprosessimme on hiottu huippuunsa vastaamaan moderneja vaatimuksia.',
            'why1_title' => 'Pitkä kokemus',
            'why1_desc' => 'Yli 20 vuoden kokemus web- ja ohjelmistokehityksestä takaa toimivat ratkaisut.',
            'why2_title' => 'Nopeus & Joustavuus',
            'why2_desc' => 'Tekoälyavusteinen koodin generointi tekee kehityksestä nopeaa ja erittäin kustannustehokasta.',
            'why3_title' => 'Asiakaslähtöisyys',
            'why3_desc' => 'Suunnittelemme ratkaisut juuri sinun liiketoimintasi tarpeita ja budjettia ajatellen.',
            'why4_title' => 'Turvallinen koodi',
            'why4_desc' => 'Kirjoitamme laadukasta, tietoturvallista ja helposti ylläpidettävää koodia parhaiden käytäntöjen mukaan.',
            'quote_request' => 'Tarjouspyyntö',
            'quote_title' => 'Ota yhteyttä ja pyydä tarjous WordPress-ratkaisusta.',
            'quote_desc' => 'WordPress-ratkaisu, joka palvelee liiketoimintaasi parhaalla mahdollisella tavalla!',
            'help_text' => '💡 Apua täyttöön? Soita +358 40 8200 491 tai meilaa osoitteeseen',
        ),
        'en' => array(
            'eyebrow' => 'WordPress · Open Source · Custom',
            'hero_title' => 'Professional WordPress Development.',
            'hero_desc' => 'We offer comprehensive WordPress development services tailored to your business needs. We deliver high-quality and functional solutions with years of experience.',
            'request_quote' => 'Request a quote →',
            'github_code' => 'Source Code on GitHub',
            'flexibility' => 'Flexibility & Pricing',
            'flex_title' => 'Code according to your needs and budget.',
            'flex_lead' => 'Below you will find the request for quote form. We offer services directly to end customers and as subcontracting to advertising agencies and software companies who need an experienced programmer flexibly for seasonal help, substitutes or stand-ins.',
            'flex_p1' => 'Whether you are a low-budget student entrepreneur or a wealthy long-time business owner, we will find a price that pleases the subscriber – because for just €20 + VAT 25.5% you get about €20 worth of code, and for €500 you get a corresponding value in lines of code.',
            'flex_p2' => 'This is possible because we utilize AI in code generation – for example, you can send us an image file of what you want, and we produce the solution quickly and cost-effectively.',
            'badge1' => '20+ years of experience',
            'badge2' => 'AI-Assisted',
            'badge3' => 'Flexible contractor helper',
            'features' => 'Our Services',
            'features_title' => 'Tailored WordPress Solutions.',
            'features_lead' => 'We design and implement custom plugins and themes with a modern technology stack.',
            'feat1_title' => 'Custom Plugins',
            'feat1_desc' => 'We build custom plugin solutions utilizing JavaScript, jQuery, React, PHP, and MySQL databases.',
            'feat2_title' => 'AI & Integrations',
            'feat2_desc' => 'OpenAI API interfaces for automation and content generation. Versatile API integrations with other systems.',
            'feat3_title' => 'ACF & Multilingual',
            'feat3_desc' => 'Built-in support for Advanced Custom Fields (ACF) and Polylang multilingual integration for demanding needs.',
            'feat4_title' => 'Theme HTML/CSS Layout',
            'feat4_desc' => 'We build unique and responsive WordPress themes that look great and load fast on all devices.',
            'feat5_title' => 'Browser Settings Pages',
            'feat5_desc' => 'Browser-based settings pages can be built into themes to help you easily manage content and features.',
            'feat6_title' => 'Password-Protected Settings',
            'feat6_desc' => 'Modify color themes, logos, metadata, or menus securely direct from browser without the WP admin panel.',
            'why_us' => 'Why Choose Us?',
            'why_title' => 'Experience, quality and speed combined.',
            'why_lead' => 'Our development process is optimized to meet modern requirements.',
            'why1_title' => 'Decades of Experience',
            'why1_desc' => 'Over 20 years of experience in web and software development guarantees solid and working solutions.',
            'why2_title' => 'Speed & Flexibility',
            'why2_desc' => 'AI-assisted code generation makes our development process extremely fast and cost-effective.',
            'why3_title' => 'Customer Centric',
            'why3_desc' => 'We design solutions with your specific business goals, requirements, and budget in mind.',
            'why4_title' => 'Secure Codebase',
            'why4_desc' => 'We write high-quality, highly secure, and easy-to-maintain code following WordPress standards.',
            'quote_request' => 'Request Quote',
            'quote_title' => 'Contact us and request a quote for a WordPress solution.',
            'quote_desc' => 'A WordPress solution that serves your business in the best possible way!',
            'help_text' => '💡 Need help filling in? Call +358 40 8200 491 or email us at',
        )
    );

    // Default to 'fi' if language not supported
    $s = isset($data[$lang]) ? $data[$lang] : $data['fi'];

    ob_start();
    ?>
    <div class="ok-wrap">

        <!-- HERO -->
        <section class="ok-hero">
            <span class="ok-eyebrow"><?php echo esc_html($s['eyebrow']); ?></span>
            <h1><?php echo esc_html($s['hero_title']); ?></h1>
            <p><?php echo esc_html($s['hero_desc']); ?></p>
            <div class="ok-cta">
                <a class="ok-btn ok-btn-primary" href="#tarjous"><?php echo esc_html($s['request_quote']); ?></a>
                <a class="ok-btn ok-btn-ghost" href="https://github.com/foghorn-hash/i4ware_www" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                    </svg>
                    <?php echo esc_html($s['github_code']); ?>
                </a>
            </div>
            <div class="ok-links">
                <a class="ok-chip" href="https://antigravity.google/" target="_blank" rel="noopener">⚡ Antigravity</a>
                <a class="ok-chip" href="https://openai.com/" target="_blank" rel="noopener">🤖 OpenAI API</a>
                <a class="ok-chip" href="https://wordpress.org/" target="_blank" rel="noopener">💻 WordPress</a>
            </div>
        </section>

        <!-- COOPERATION / PRICING & FLEXIBILITY -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['flexibility']); ?></span>
                <h2><?php echo esc_html($s['flex_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['flex_lead']); ?></p>
            </div>

            <div class="ok-coop-grid">
                <div class="ok-coop-info">
                    <p><?php echo esc_html($s['flex_p1']); ?></p>
                    <p><?php echo esc_html($s['flex_p2']); ?></p>
                    <div class="ok-links" style="margin-top: 20px;">
                        <a class="ok-chip" href="https://github.com/foghorn-hash/i4ware_www" target="_blank" rel="noopener">
                            💻 GitHub Source Code
                        </a>
                    </div>
                </div>
                <div class="ok-coop-badge-container">
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['badge1']); ?>
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['badge2']); ?>
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['badge3']); ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- SERVICES / FEATURES GRID -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['features']); ?></span>
                <h2><?php echo esc_html($s['features_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['features_lead']); ?></p>
            </div>

            <div class="ok-grid">
                <!-- Custom Plugins -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat1_title']); ?></h3>
                    <p><?php echo esc_html($s['feat1_desc']); ?></p>
                </div>
                <!-- AI & Integrations -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat2_title']); ?></h3>
                    <p><?php echo esc_html($s['feat2_desc']); ?></p>
                </div>
                <!-- ACF & Multilingual -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat3_title']); ?></h3>
                    <p><?php echo esc_html($s['feat3_desc']); ?></p>
                </div>
                <!-- Theme Layout -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat4_title']); ?></h3>
                    <p><?php echo esc_html($s['feat4_desc']); ?></p>
                </div>
                <!-- Settings Pages -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="4" y1="21" x2="4" y2="14"></line>
                            <line x1="4" y1="10" x2="4" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="3"></line>
                            <line x1="20" y1="21" x2="20" y2="16"></line>
                            <line x1="20" y1="12" x2="20" y2="3"></line>
                            <line x1="1" y1="14" x2="7" y2="14"></line>
                            <line x1="9" y1="8" x2="15" y2="8"></line>
                            <line x1="17" y1="16" x2="23" y2="16"></line>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat5_title']); ?></h3>
                    <p><?php echo esc_html($s['feat5_desc']); ?></p>
                </div>
                <!-- Password Protection -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat6_title']); ?></h3>
                    <p><?php echo esc_html($s['feat6_desc']); ?></p>
                </div>
            </div>
        </section>

        <!-- WHY CHOOSE US -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['why_us']); ?></span>
                <h2><?php echo esc_html($s['why_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['why_lead']); ?></p>
            </div>

            <div class="ok-grid">
                <div class="ok-card">
                    <h3><?php echo esc_html($s['why1_title']); ?></h3>
                    <p><?php echo esc_html($s['why1_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <h3><?php echo esc_html($s['why2_title']); ?></h3>
                    <p><?php echo esc_html($s['why2_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <h3><?php echo esc_html($s['why3_title']); ?></h3>
                    <p><?php echo esc_html($s['why3_desc']); ?></p>
                </div>
                <div class="ok-card">
                    <h3><?php echo esc_html($s['why4_title']); ?></h3>
                    <p><?php echo esc_html($s['why4_desc']); ?></p>
                </div>
            </div>
        </section>

        <!-- PRICING & ORDER FORM -->
        <section class="ok-section" id="tarjous">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['quote_request']); ?></span>
                <h2><?php echo esc_html($s['quote_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['quote_desc']); ?></p>
            </div>

            <div class="ok-form-area">
                <div class="ok-note" style="margin-bottom: 28px;">
                    <strong><?php echo wp_kses($s['help_text'], array('strong' => array())); ?></strong> <a href="mailto:matti.kiviharju@i4ware.fi">matti.kiviharju@i4ware.fi</a>.
                </div>
                <?php echo do_shortcode('[wp_quote]'); ?>
            </div>
        </section>

    </div>
    <?php
    return ob_get_clean();
});
