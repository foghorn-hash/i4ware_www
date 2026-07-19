<?php
/**
 * Shortcode for Web Hosting page [i4ware_web_hosting_page]
 * Supports Polylang localizations and enqueues separate CSS from the SDK page.
 */
add_shortcode('i4ware_web_hosting_page', function() {
    $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';

    // Enqueue the SDK page CSS for consistent layout and styling
    wp_enqueue_style('i4ware-sdk-page', get_template_directory_uri() . '/assets/css/sdk-page.css', array(), '1.1');

    // Localized strings
    $data = array(
        'fi' => array(
            'eyebrow' => 'Web-hotelli · Leaseweb · Turvallisuus',
            'hero_title' => 'Luotettava web-hotellipalvelu WordPress-sivustoille.',
            'hero_desc' => 'i4ware Software tarjoaa luotettavaa web-hotellipalvelua WordPress-sivustoille ja muille Open Source PHP -sovelluksille. Palvelu toimii virtuaalipalvelimella, joka on sijoitettu suureen Leaseweb-datakeskukseen Amsterdamissa.',
            'ftp_notice' => '<strong>Huomio:</strong> i4ware Software ei luovuta FTP-tunnuksia tietoturvasyistä. Kaikki ohjelmistot (esim. WordPress ja muut PHP-sovellukset) asennetaan ja konfiguroidaan puolestasi turvallisesti suoraan palvelimelle.',
            'request_quote' => 'Pyydä tarjous →',
            'datacenter_link' => 'Leaseweb Datacenter',
            'pricing_title' => 'Selkeä hinnoittelu ilman piilokuluja.',
            'pricing_lead' => 'Tarjoamme laadukkaan ylläpidon ja tehon kiinteällä kuukausihinnalla.',
            'price_label' => '20 €',
            'price_unit' => '/ kk + ALV 25,5 %',
            'billing_info' => 'Laskutusväli: 1 vuosi · Maksuehto: 14 pv netto',
            'included' => 'Palveluun sisältyy',
            'inc_title' => 'Kaikki tarvittava WordPress- ja PHP-sovelluksille.',
            'inc_lead' => 'Käytössäsi on moderni teknologia ja rajattomat resurssit tietokannoille ja sähköpostille.',
            'feat1_title' => 'PHP FastCGI -tekniikka',
            'feat1_desc' => 'PHP-version voi määrittää joustavasti asiakkaan tarpeiden mukaan. Tuettuina versioina mm. PHP 5.2, PHP 7.0 ja PHP 8.2.',
            'feat2_title' => 'Rajattomat MySQL-tietokannat',
            'feat2_desc' => 'Luo niin monta MySQL-tietokantaa kuin verkkopalvelusi ja sovelluksesi tarvitsevat.',
            'feat3_title' => 'Rajattomat PostgreSQL-tietokannat',
            'feat3_desc' => 'Täysi tuki myös rajattomille PostgreSQL-tietokannoille vaativampaan datanhallintaan.',
            'feat4_title' => 'Rajattomat sähköpostilaatikot',
            'feat4_desc' => 'Sähköpostilaatikot omalla verkkotunnuksella ilman keinotekoisia rajoituksia tai lisämaksuja.',
            'security' => 'Tietoturva & Sähköpostiturva',
            'sec_title' => 'Ennakoiva ja monitasoinen suojaus.',
            'sec_lead' => 'Suojaamme sähköpostiliikenteen ja verkkoyhteydet nykyaikaisilla teknologioilla.',
            'sec_p1' => 'Roskapostin suodatus SpamAssassin- ja Abusix AI-teknologioilla varmistaa puhtaan postilaatikon. Kaikki sähköpostit skannataan haittaohjelmien varalta ClamAV-ohjelmistolla ennen kuin ne saapuvat laitteellesi, mikä estää haitallisten liitteiden lataamisen.',
            'sec_p2' => 'SSL-salaus toteutetaan ilmaisella Let’s Encrypt SSL/TLS -sertifikaatilla. Konfiguraatio optimoidaan aina saavuttamaan erinomainen Grade A tai A+ -tietoturvaluokitus SSL Labs SSL Test -palvelussa.',
            'sec_badge1' => 'SpamAssassin & Abusix AI roskapostisuodatin',
            'sec_badge2' => 'ClamAV haittaohjelmaskannaus',
            'sec_badge3' => 'SSL Labs Grade A / A+ optimointi',
            'target_group' => 'Kenelle palvelu sopii?',
            'target_title' => 'Helppo ja turvallinen valinta.',
            'target_desc' => 'Palvelu sopii erityisesti asiakkaille, jotka tarvitsevat edullisen ja joustavan web-hotellin WordPressille, PHP-sovelluksille, sähköpostille ja tietokannoille ilman monimutkaisia rajoituksia sekä korkealla tietoturvatasolla varustettuna.',
            'quote_request' => 'Tarjouspyyntö',
            'quote_title' => 'Tilaa web-hotellipalvelu tai kysy lisää.',
            'quote_desc' => 'Ota yhteyttä ja valitse luotettava ylläpito verkkosivuillesi jo tänään!',
            'help_text' => '💡 Apua täyttöön? Soita +358 40 8200 491 tai meilaa osoitteeseen',
        ),
        'en' => array(
            'eyebrow' => 'Web Hosting · Leaseweb · Security',
            'hero_title' => 'Reliable Web Hosting for WordPress.',
            'hero_desc' => 'i4ware Software offers reliable web hosting services for WordPress sites and other Open Source PHP applications. The service runs on a virtual private server housed in the state-of-the-art Leaseweb data center in Amsterdam.',
            'ftp_notice' => '<strong>Notice:</strong> For security reasons, i4ware Software does not hand over FTP credentials. All software (e.g., WordPress and other PHP applications) is installed and configured securely directly on the server on behalf of the customer.',
            'request_quote' => 'Request a quote →',
            'datacenter_link' => 'Leaseweb Datacenter',
            'pricing_title' => 'Clear pricing with no hidden fees.',
            'pricing_lead' => 'We offer high-performance hosting at a fixed monthly rate.',
            'price_label' => '20 €',
            'price_unit' => '/ month + VAT 25.5%',
            'billing_info' => 'Billing cycle: 1 year · Payment terms: 14 days net',
            'included' => 'What is Included',
            'inc_title' => 'Everything you need for WordPress and PHP apps.',
            'inc_lead' => 'Benefit from modern technology and unlimited resources for databases and email accounts.',
            'feat1_title' => 'PHP FastCGI Technology',
            'feat1_desc' => 'The PHP version can be flexibly configured to match client needs. Supported versions include PHP 5.2, PHP 7.0, and PHP 8.2.',
            'feat2_title' => 'Unlimited MySQL Databases',
            'feat2_desc' => 'Create as many MySQL databases as your web services and applications require.',
            'feat3_title' => 'Unlimited PostgreSQL Databases',
            'feat3_desc' => 'Full support for unlimited PostgreSQL databases for advanced data management needs.',
            'feat4_title' => 'Unlimited Email Mailboxes',
            'feat4_desc' => 'Email accounts on your own domain with no artificial limitations or extra charges.',
            'security' => 'Security & Email Protection',
            'sec_title' => 'Proactive and multi-layered security.',
            'sec_lead' => 'We protect your email traffic and web connections using modern technologies.',
            'sec_p1' => 'Spam filtering using SpamAssassin and Abusix AI technologies ensures a clean inbox. All emails are scanned for malware using ClamAV software before they reach your local device, preventing harmful attachments from executing.',
            'sec_p2' => 'SSL encryption is set up with a free Let’s Encrypt SSL/TLS certificate. The server configuration is optimized to achieve an outstanding Grade A or A+ security rating in the SSL Labs SSL Test service.',
            'sec_badge1' => 'SpamAssassin & Abusix AI spam filter',
            'sec_badge2' => 'ClamAV malware scanner',
            'sec_badge3' => 'SSL Labs Grade A / A+ optimization',
            'target_group' => 'Who is it for?',
            'target_title' => 'Simple and secure choice.',
            'target_desc' => 'The service is particularly suitable for customers who need an affordable and flexible web host for WordPress, PHP applications, email, and databases, without complex limitations and with a high level of security.',
            'quote_request' => 'Request Quote',
            'quote_title' => 'Order web hosting or request more details.',
            'quote_desc' => 'Get in touch and choose reliable hosting for your website today!',
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
            <div class="ok-note" style="margin-top: 24px; border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05); color: #fff;">
                <?php echo wp_kses($s['ftp_notice'], array('strong' => array())); ?>
            </div>
            <div class="ok-cta">
                <a class="ok-btn ok-btn-primary" href="#tarjous"><?php echo esc_html($s['request_quote']); ?></a>
                <a class="ok-btn ok-btn-ghost" href="https://www.leaseweb.com/" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                        <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
                    </svg>
                    <?php echo esc_html($s['datacenter_link']); ?>
                </a>
            </div>
            <div class="ok-links">
                <a class="ok-chip" href="https://antigravity.google/" target="_blank" rel="noopener">⚡ Antigravity</a>
                <a class="ok-chip" href="https://www.ssllabs.com/ssltest/" target="_blank" rel="noopener">🔒 SSL Labs Test</a>
                <a class="ok-chip" href="https://letsencrypt.org/" target="_blank" rel="noopener">🔑 Let's Encrypt</a>
            </div>
        </section>

        <!-- PRICING SECTION -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['pricing_title']); ?></span>
                <h2><?php echo esc_html($s['pricing_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['pricing_lead']); ?></p>
            </div>

            <div class="ok-form-area" style="padding: 40px; text-align: center; background: var(--ok-panel); border: 1px solid var(--ok-border); border-radius: 24px;">
                <div class="ok-price-badge" style="margin: 0 auto 20px auto; display: inline-flex;">
                    <span class="ok-price-val"><?php echo esc_html($s['price_label']); ?></span>
                    <span class="ok-price-unit"><?php echo esc_html($s['price_unit']); ?></span>
                </div>
                <h3 style="margin-top: 10px; font-size: 18px; color: var(--ok-text);"><?php echo esc_html($s['billing_info']); ?></h3>
            </div>
        </section>

        <!-- INCLUDED FEATURES GRID -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['included']); ?></span>
                <h2><?php echo esc_html($s['inc_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['inc_lead']); ?></p>
            </div>

            <div class="ok-grid">
                <!-- PHP Support -->
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
                <!-- MySQL Databases -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat2_title']); ?></h3>
                    <p><?php echo esc_html($s['feat2_desc']); ?></p>
                </div>
                <!-- PostgreSQL Databases -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                            <path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"></path>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat3_title']); ?></h3>
                    <p><?php echo esc_html($s['feat3_desc']); ?></p>
                </div>
                <!-- Email Accounts -->
                <div class="ok-card">
                    <div class="ok-ico">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($s['feat4_title']); ?></h3>
                    <p><?php echo esc_html($s['feat4_desc']); ?></p>
                </div>
            </div>
        </section>

        <!-- SECURITY & EMAIL PROTECTION -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['security']); ?></span>
                <h2><?php echo esc_html($s['sec_title']); ?></h2>
                <p class="ok-lead"><?php echo esc_html($s['sec_lead']); ?></p>
            </div>

            <div class="ok-coop-grid">
                <div class="ok-coop-info">
                    <p><?php echo esc_html($s['sec_p1']); ?></p>
                    <p><?php echo esc_html($s['sec_p2']); ?></p>
                    <div class="ok-links" style="margin-top: 20px;">
                        <a class="ok-chip" href="https://www.ssllabs.com/ssltest/" target="_blank" rel="noopener">
                            🔒 SSL Labs Verification
                        </a>
                    </div>
                </div>
                <div class="ok-coop-badge-container">
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['sec_badge1']); ?>
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['sec_badge2']); ?>
                    </div>
                    <div class="ok-coop-badge">
                        <div class="ok-coop-badge-icon"></div>
                        <?php echo esc_html($s['sec_badge3']); ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- TARGET GROUP / FOR WHOM -->
        <section class="ok-section">
            <div class="ok-section-header">
                <span class="ok-eyebrow"><?php echo esc_html($s['target_group']); ?></span>
                <h2><?php echo esc_html($s['target_title']); ?></h2>
            </div>
            <div class="ok-card" style="padding: 40px; background: linear-gradient(180deg, var(--ok-panel) 0%, rgba(17, 22, 37, 0.5) 100%); border: 1px solid var(--ok-border); border-radius: 24px;">
                <p class="ok-lead" style="margin: 0; font-size: 18px; line-height: 1.7;"><?php echo esc_html($s['target_desc']); ?></p>
            </div>
        </section>

        <!-- CONTACT & ORDER FORM -->
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
