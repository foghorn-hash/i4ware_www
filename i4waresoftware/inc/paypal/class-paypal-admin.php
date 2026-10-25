<?php
/**
 * i4ware PayPal & VAT Subscriptions Admin Management
 *
 * Implements:
 * - Admin settings page to toggle gratuitous support (default: disabled)
 * - PayPal credentials & environment configuration
 * - VIES Audit Logs viewer with role protection (manage_options)
 * - Order & Manual Review Queue
 */

if (!defined('ABSPATH')) {
    exit;
}

class I4ware_PayPal_Admin
{
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'register_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function register_admin_menu()
    {
        add_menu_page(
            'PayPal & ALV',
            'PayPal & ALV',
            'manage_options',
            'i4ware-paypal-settings',
            [__CLASS__, 'render_admin_page'],
            'dashicons-cart',
            56
        );
    }

    public static function register_settings()
    {
        register_setting('i4ware_paypal_settings_group', 'i4ware_allow_gratuitous_support');
        register_setting('i4ware_paypal_settings_group', 'i4ware_standard_vat_rate');
        register_setting('i4ware_paypal_settings_group', 'i4ware_paypal_environment');
        register_setting('i4ware_paypal_settings_group', 'i4ware_paypal_client_id');
        register_setting('i4ware_paypal_settings_group', 'i4ware_paypal_client_secret');
        register_setting('i4ware_paypal_settings_group', 'i4ware_paypal_webhook_id');
        register_setting('i4ware_paypal_settings_group', 'i4ware_log_retention_years');
        register_setting('i4ware_paypal_settings_group', 'i4ware_vies_timeout');
        register_setting('i4ware_paypal_settings_group', 'i4ware_vies_use_requester');
        register_setting('i4ware_paypal_settings_group', 'i4ware_vies_requester_country');
        register_setting('i4ware_paypal_settings_group', 'i4ware_vies_requester_vat');

        // Email & SMTP settings for PDF receipts
        register_setting('i4ware_paypal_settings_group', 'i4ware_receipt_sender_email');
        register_setting('i4ware_paypal_settings_group', 'i4ware_receipt_sender_name');
        register_setting('i4ware_paypal_settings_group', 'i4ware_receipt_reply_to');
        register_setting('i4ware_paypal_settings_group', 'i4ware_smtp_host');
        register_setting('i4ware_paypal_settings_group', 'i4ware_smtp_port');
        register_setting('i4ware_paypal_settings_group', 'i4ware_smtp_user');
        register_setting('i4ware_paypal_settings_group', 'i4ware_smtp_pass');
        register_setting('i4ware_paypal_settings_group', 'i4ware_smtp_secure');
    }

    public static function render_admin_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sinulla ei ole oikeuksia tarkastella tätä sivua.');
        }

        global $wpdb;
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

        // Handle manual review action
        if (isset($_GET['action'], $_GET['order_id']) && check_admin_referer('i4ware_review_action_' . $_GET['order_id'])) {
            $order_id = (int) $_GET['order_id'];
            $orders_table = $wpdb->prefix . 'i4ware_paypal_orders';
            if ($_GET['action'] === 'approve') {
                $wpdb->update($orders_table, ['status' => 'approved_manually'], ['id' => $order_id]);
                echo '<div class="notice notice-success is-dismissible"><p>Tilaus hyväksytty manuaalisesti.</p></div>';
            } elseif ($_GET['action'] === 'reject') {
                $wpdb->update($orders_table, ['status' => 'rejected'], ['id' => $order_id]);
                echo '<div class="notice notice-warning is-dismissible"><p>Tilaus hylätty.</p></div>';
            }
        }

        // Handle manual receipt resend action
        if (isset($_GET['action'], $_GET['receipt_id']) && $_GET['action'] === 'resend_receipt' && check_admin_referer('i4ware_resend_receipt_' . $_GET['receipt_id'])) {
            require_once __DIR__ . '/class-receipt-manager.php';
            $resend_res = I4ware_Receipt_Manager::resend_receipt((int) $_GET['receipt_id']);
            if ($resend_res['success']) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($resend_res['message']) . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($resend_res['message']) . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1>i4ware Software – PayPal-kuukausitilaukset, ALV & PDF-kuitit</h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=i4ware-paypal-settings&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">Asetukset</a>
                <a href="?page=i4ware-paypal-settings&tab=receipts" class="nav-tab <?php echo $active_tab === 'receipts' ? 'nav-tab-active' : ''; ?>">Kuitit & Tositteet</a>
                <a href="?page=i4ware-paypal-settings&tab=orders" class="nav-tab <?php echo $active_tab === 'orders' ? 'nav-tab-active' : ''; ?>">Tilaukset</a>
                <a href="?page=i4ware-paypal-settings&tab=review" class="nav-tab <?php echo $active_tab === 'review' ? 'nav-tab-active' : ''; ?>">Manuaalinen tarkistusjono</a>
                <a href="?page=i4ware-paypal-settings&tab=vies_logs" class="nav-tab <?php echo $active_tab === 'vies_logs' ? 'nav-tab-active' : ''; ?>">VIES-tarkistusloki</a>
            </nav>

            <div style="margin-top: 20px;">
                <?php
                if ($active_tab === 'settings') {
                    self::render_settings_tab();
                } elseif ($active_tab === 'receipts') {
                    self::render_receipts_tab();
                } elseif ($active_tab === 'orders') {
                    self::render_orders_tab();
                } elseif ($active_tab === 'review') {
                    self::render_review_tab();
                } elseif ($active_tab === 'vies_logs') {
                    self::render_vies_logs_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    private static function render_settings_tab()
    {
        $allow_gratuitous = get_option('i4ware_allow_gratuitous_support', false);
        $standard_vat = get_option('i4ware_standard_vat_rate', I4WARE_STANDARD_FINNISH_VAT_RATE);
        $environment = get_option('i4ware_paypal_environment', 'sandbox');
        $client_id = get_option('i4ware_paypal_client_id', '');
        $client_secret = get_option('i4ware_paypal_client_secret', '');
        $webhook_id = get_option('i4ware_paypal_webhook_id', '');
        $retention_years = get_option('i4ware_log_retention_years', '6');
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('i4ware_paypal_settings_group'); ?>
            
            <div class="card" style="max-width: 900px; padding: 20px; margin-bottom: 25px; border-left: 4px solid #f59e0b;">
                <h2 style="margin-top: 0;">1. Mainospalvelut, Maksutyypit & Rahankeräyslaki</h2>
                <div style="background: rgba(16, 185, 129, 0.08); border-left: 3px solid #10b981; padding: 12px 16px; margin-bottom: 18px; border-radius: 4px;">
                    <strong style="color: #047857;">✓ Mainospalvelut (Aina käytettävissä):</strong>
                    <p style="margin: 6px 0 0; color: #065f46; font-size: 13px;">
                        Mainospaikkojen myynti i4waren verkkosivustolle (kertamaksu määräajalle tai jatkuva kuukausitilaus) on vastikkeellista elinkeinotoimintaa. Mainosmyyntiin sovelletaan Suomessa aina yleistä <strong>25,5 % arvonlisäveroa</strong> (AVL 1 §). Suomalaisen yritysasiakkaan ALV-tunniste ei oikeuta verottomuuteen kotimaan myynnissä. Mainosmyynnin saatavuus on aina aktiivinen eikä riipu alla olevasta vastikkeettoman tuen asetuksesta.
                    </p>
                </div>
                <table class="form-table">
                    <tr>
                        <th scope="row">Vastikkeeton tuki (Erillinen asetus)</th>
                        <td>
                            <label>
                                <input type="checkbox" name="i4ware_allow_gratuitous_support" value="1" <?php checked(1, $allow_gratuitous); ?> />
                                <strong>Salli vastikkeettoman tuen uudet kuukausitilaukset</strong>
                            </label>
                            <p class="description" style="color: #b45309; margin-top: 8px;">
                                <strong>HUOMAUTUS:</strong> Tämä valinta ohjaa <em>ainoastaan</em> vastikkeettoman tuen (lahjoitusten) tilaamista Rahankeräyslain (863/2019) mukaisesti. Se <strong>ei</strong> ohjaa eikä rajoita mainospalveluiden myyntiä tai niiden verokäsittelyä. Olemassa olevia PayPal-tilauksia ei muuteta automaattisesti.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Suomen ALV-kanta (%)</th>
                        <td>
                            <input type="number" step="0.1" name="i4ware_standard_vat_rate" value="<?php echo esc_attr($standard_vat); ?>" style="width: 100px;" /> %
                            <p class="description">Suomen yleinen arvonlisäverokanta (25,5 % alkaen 1.9.2024). Sovelletaan kotimaan myyntiin ja EU-kuluttajamyyntiin.</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 900px; padding: 20px; margin-bottom: 25px; border-left: 4px solid #0284c7;">
                <h2 style="margin-top: 0;">2. PayPal REST API & Webhookit</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Ympäristö</th>
                        <td>
                            <select name="i4ware_paypal_environment">
                                <option value="sandbox" <?php selected('sandbox', $environment); ?>>Sandbox (Testiympäristö)</option>
                                <option value="live" <?php selected('live', $environment); ?>>Live (Tuotanto)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Client ID</th>
                        <td>
                            <input type="text" name="i4ware_paypal_client_id" value="<?php echo esc_attr($client_id); ?>" class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Secret Key</th>
                        <td>
                            <input type="password" name="i4ware_paypal_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">PayPal Webhook ID</th>
                        <td>
                            <input type="text" name="i4ware_paypal_webhook_id" value="<?php echo esc_attr($webhook_id); ?>" class="large-text" />
                            <p class="description">Määritä PayPal Developer Portalissa webhook-kuuntelija osoitteeseen: <code><?php echo esc_url(rest_url('i4ware-paypal/v1/webhook')); ?></code></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tietojen säilytysaika</th>
                        <td>
                            <input type="number" name="i4ware_log_retention_years" value="<?php echo esc_attr($retention_years); ?>" style="width: 80px;" /> vuotta
                            <p class="description">Kirjanpitolain (1336/1997) 2 luvun 10 § mukainen tositteiden säilytysaika (oletus 6 vuotta).</p>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 900px; padding: 20px; margin-bottom: 25px; border-left: 4px solid #10b981;">
                <h2 style="margin-top: 0;">3. EU VIES -arvonlisäveron tarkistuspalvelu</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">VIES-aikakatkaisu (timeout)</th>
                        <td>
                            <input type="number" name="i4ware_vies_timeout" value="<?php echo esc_attr(get_option('i4ware_vies_timeout', 12)); ?>" min="5" max="30" style="width: 80px;" /> sekuntia
                            <p class="description">VIES-kyselyiden enimmäisaika (oletus 12 s). Jos kysely aikakatkaistaan, koodi suorittaa rajatun uudelleenyrityksen ja ohjaa tilauksen manuaaliseen tarkistukseen.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Kysyjän tiedot (Audit Trail)</th>
                        <td>
                            <label>
                                <input type="checkbox" name="i4ware_vies_use_requester" value="1" <?php checked(1, get_option('i4ware_vies_use_requester', true)); ?> />
                                <strong>Lähetä i4ware Softwaren ALV-tunniste kysyjätietona</strong>
                            </label>
                            <p class="description">Kysyjätietojen välittäminen tuottaa VIES:stä virallisen konsultaatiotunnuksen (requestIdentifier) kirjanpito- ja verotarkastuksia varten. Jos kysyjätiedot hylätään, koodi yrittää automaattisesti ilman kysyjätietoja.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Kysyjän maa & ALV-numero</th>
                        <td>
                            <input type="text" name="i4ware_vies_requester_country" value="<?php echo esc_attr(get_option('i4ware_vies_requester_country', 'FI')); ?>" style="width: 60px;" />
                            <input type="text" name="i4ware_vies_requester_vat" value="<?php echo esc_attr(get_option('i4ware_vies_requester_vat', '27395946')); ?>" style="width: 160px;" />
                            <p class="description">Toiminimen virallinen ALV-numero ilman maakoodia (esim. 27395946).</p>
                        </td>
                    </tr>
                </table>
            <div class="card" style="max-width: 900px; padding: 20px; margin-bottom: 25px; border-left: 4px solid #10b981;">
                <h2 style="margin-top: 0;">4. Sähköposti- ja Kuittiasetukset (VPS SMTP & PDF-kuitit)</h2>
                <div style="background: rgba(14, 165, 233, 0.08); border-left: 3px solid #0284c7; padding: 12px 16px; margin-bottom: 18px; border-radius: 4px;">
                    <strong style="color: #0369a1;">Sähköpostin lähetys i4waren VPS-palvelimelta:</strong>
                    <p style="margin: 6px 0 0; color: #0f172a; font-size: 13px;">
                        Viralliset PDF-kuitit lähetetään asiakkaan sähköpostiin automaattisesti i4waren VPS:n autentikoidulla SMTP-palvelulla TLS-salattuna.
                        Kuitit tallennetaan suojattuun kansioon (<code>wp-content/uploads/i4ware-receipts-secure/</code>), johon julkinen suora pääsy on estetty <code>.htaccess</code>- ja <code>index.php</code>-suojauksilla (kuitteja ei viedä julkiseen WordPress-mediakirjastoon).
                    </p>
                    <p style="margin: 6px 0 0; color: #047857; font-size: 12.5px;">
                        <strong>Tietoturvasuositus:</strong> Voit määritellä SMTP-tunnukset suoraan <code>wp-config.php</code> -tiedostossa vakioina (<code>I4WARE_SMTP_HOST</code>, <code>I4WARE_SMTP_USER</code>, <code>I4WARE_SMTP_PASS</code>, <code>I4WARE_SMTP_PORT</code>, <code>I4WARE_SMTP_SECURE</code>), jolloin salasanaa ei tallenneta tietokantaan.
                    </p>
                </div>

                <div style="background: rgba(245, 158, 11, 0.08); border-left: 3px solid #f59e0b; padding: 12px 16px; margin-bottom: 18px; border-radius: 4px;">
                    <strong style="color: #b45309;">Lähettäjäverkkotunnuksen DNS-tietueet (SPF, DKIM, DMARC):</strong>
                    <ul style="margin: 6px 0 0 18px; font-size: 12.5px; color: #334155; list-style-type: disc;">
                        <li><strong>SPF:</strong> Nykyinen tietue verkkotunnukselle <code>i4ware.fi</code> sisältää vain <code>v=spf1 include:stspg-customer.com ~all</code>. Jotta VPS:n (IP <code>85.17.142.117</code> / <code>mail.i4ware.fi</code>) lähettämät kuitit eivät päädy roskapostiin, SPF-tietueeseen tulee lisätä palvelimen IP: <br><code style="background: #fff; padding: 2px 6px; border: 1px solid #cbd5e1; display: inline-block; margin-top: 3px;">v=spf1 mx ip4:85.17.142.117 include:stspg-customer.com ~all</code></li>
                        <li><strong>DMARC:</strong> Nykyinen tietue <code>_dmarc.i4ware.fi</code> on <code>v=DMARC1; p=none;</code>. Suositellaan raportointisähköpostin lisäystä: <code style="background: #fff; padding: 2px 6px; border: 1px solid #cbd5e1;">v=DMARC1; p=none; rua=mailto:info@i4ware.fi;</code></li>
                        <li><strong>DKIM:</strong> Varmista, että VPS:n Postfix/OpenDKIM allekirjoittaa lähtevät sähköpostit ja vastaava julkinen TXT-avaintietue (esim. <code>default._domainkey.i4ware.fi</code>) on julkaistu DNS:ssä.</li>
                    </ul>
                </div>

                <table class="form-table">
                    <tr>
                        <th scope="row">Lähettäjän sähköpostiosoite</th>
                        <td>
                            <input type="email" name="i4ware_receipt_sender_email" value="<?php echo esc_attr(get_option('i4ware_receipt_sender_email', 'info@i4ware.fi')); ?>" style="width: 300px;" />
                            <p class="description">Sähköpostiosoite, josta PDF-kuitit lähetetään asiakkaille (esim. info@i4ware.fi).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Lähettäjän nimi</th>
                        <td>
                            <input type="text" name="i4ware_receipt_sender_name" value="<?php echo esc_attr(get_option('i4ware_receipt_sender_name', 'i4ware Software')); ?>" style="width: 300px;" />
                            <p class="description">Lähettäjän nimi sähköpostissa (esim. i4ware Software).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vastausosoite (Reply-To)</th>
                        <td>
                            <input type="email" name="i4ware_receipt_reply_to" value="<?php echo esc_attr(get_option('i4ware_receipt_reply_to', 'info@i4ware.fi')); ?>" style="width: 300px;" />
                            <p class="description">Osoite, johon asiakkaan vastaukset ohjataan.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Palvelin (Host)</th>
                        <td>
                            <?php $is_const_host = defined('I4WARE_SMTP_HOST'); ?>
                            <input type="text" name="i4ware_smtp_host" value="<?php echo esc_attr(get_option('i4ware_smtp_host', 'mail.i4ware.fi')); ?>" style="width: 300px;" <?php disabled($is_const_host); ?> />
                            <?php if ($is_const_host) : ?><span style="color: #059669; font-weight: bold;">(Määritelty wp-config.php -tiedostossa)</span><?php endif; ?>
                            <p class="description">i4waren VPS SMTP-palvelimen osoite (esim. mail.i4ware.fi tai 85.17.142.117).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Portti</th>
                        <td>
                            <input type="number" name="i4ware_smtp_port" value="<?php echo esc_attr(get_option('i4ware_smtp_port', 587)); ?>" style="width: 90px;" />
                            <p class="description">Yleensä 587 (TLS/STARTTLS) tai 465 (SSL).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Salaus</th>
                        <td>
                            <?php $smtp_sec = get_option('i4ware_smtp_secure', 'tls'); ?>
                            <select name="i4ware_smtp_secure">
                                <option value="tls" <?php selected('tls', $smtp_sec); ?>>TLS / STARTTLS (Suositus)</option>
                                <option value="ssl" <?php selected('ssl', $smtp_sec); ?>>SSL</option>
                                <option value="none" <?php selected('none', $smtp_sec); ?>>Ei salausta</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Käyttäjätunnus</th>
                        <td>
                            <?php $is_const_user = defined('I4WARE_SMTP_USER'); ?>
                            <input type="text" name="i4ware_smtp_user" value="<?php echo esc_attr(get_option('i4ware_smtp_user', '')); ?>" style="width: 300px;" autocomplete="off" <?php disabled($is_const_user); ?> />
                            <?php if ($is_const_user) : ?><span style="color: #059669; font-weight: bold;">(Määritelty wp-config.php)</span><?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">SMTP Salasana</th>
                        <td>
                            <?php $is_const_pass = defined('I4WARE_SMTP_PASS'); ?>
                            <input type="password" name="i4ware_smtp_pass" value="<?php echo esc_attr(get_option('i4ware_smtp_pass', '')); ?>" style="width: 300px;" autocomplete="new-password" <?php disabled($is_const_pass); ?> />
                            <?php if ($is_const_pass) : ?><span style="color: #059669; font-weight: bold;">(Suojattu wp-config.php)</span><?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <?php submit_button('Tallenna asetukset'); ?>
        </form>
        <?php
    }

    private static function render_receipts_tab()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'i4ware_receipts';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            echo '<div class="notice notice-warning"><p>Kuittitaulua ei ole vielä luotu tietokantaan.</p></div>';
            return;
        }

        require_once __DIR__ . '/class-receipt-mailer.php';

        // Search filter
        $search = isset($_GET['s']) ? sanitize_text_field(trim($_GET['s'])) : '';
        $sql = "SELECT * FROM $table";
        if (!empty($search)) {
            $sql .= $wpdb->prepare(
                " WHERE receipt_number LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s OR company_name LIKE %s OR paypal_transaction_id LIKE %s",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        $sql .= " ORDER BY id DESC LIMIT 100";
        $receipts = $wpdb->get_results($sql, ARRAY_A);

        // Counts
        $total_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $paid_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE receipt_type = 'receipt' AND paid_status = 'paid'");
        $refund_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE receipt_type = 'credit_note'");
        $failed_email_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE email_status = 'failed'");
        ?>
        <h2>Viralliset PDF-Kuitit ja Hyvitystositteet</h2>
        <p style="color: #475569;">
            Kaikki onnistuneista PayPal-maksuista automaattisesti luodut PDF-kuitit ja hyvitystositteet.
            Kuitti luodaan vasta, kun PayPalin allekirjoitukseltaan varmennettu webhook vahvistaa maksun onnistuneen.
        </p>

        <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
            <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 18px; min-width: 130px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Tositteet yht.</div>
                <div style="font-size: 22px; font-weight: 700; color: #0f172a;"><?php echo $total_count; ?> kpl</div>
            </div>
            <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 18px; min-width: 130px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; text-transform: uppercase; color: #047857; font-weight: 700;">Maksetut kuitit</div>
                <div style="font-size: 22px; font-weight: 700; color: #047857;"><?php echo $paid_count; ?> kpl</div>
            </div>
            <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 18px; min-width: 130px; border-left: 4px solid #f43f5e; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; text-transform: uppercase; color: #be123c; font-weight: 700;">Hyvitystositteet</div>
                <div style="font-size: 22px; font-weight: 700; color: #be123c;"><?php echo $refund_count; ?> kpl</div>
            </div>
            <div style="background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 18px; min-width: 130px; border-left: 4px solid <?php echo $failed_email_count > 0 ? '#ef4444' : '#94a3b8'; ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; text-transform: uppercase; color: <?php echo $failed_email_count > 0 ? '#b91c1c' : '#64748b'; ?>; font-weight: 700;">Lähetysvirheet</div>
                <div style="font-size: 22px; font-weight: 700; color: <?php echo $failed_email_count > 0 ? '#b91c1c' : '#64748b'; ?>;"><?php echo $failed_email_count; ?> kpl</div>
            </div>
        </div>

        <form method="get" style="margin-bottom: 15px;">
            <input type="hidden" name="page" value="i4ware-paypal-settings" />
            <input type="hidden" name="tab" value="receipts" />
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Etsi tositenumerolla, asiakkaalla, PayPal-tunnuksella..." style="width: 320px;" />
            <button type="submit" class="button">Hae tositteita</button>
            <?php if (!empty($search)) : ?>
                <a href="?page=i4ware-paypal-settings&tab=receipts" class="button">Tyhjennä haku</a>
            <?php endif; ?>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 120px;">Tositenumero</th>
                    <th style="width: 135px;">Päiväys</th>
                    <th style="width: 150px;">Asiakas / Yritys</th>
                    <th>Palvelu & Näkyvyys</th>
                    <th style="width: 100px; text-align: right;">Loppusumma</th>
                    <th style="width: 140px;">PayPal Tapahtuma</th>
                    <th style="width: 110px;">Tila</th>
                    <th style="width: 130px;">Sähköposti</th>
                    <th style="width: 160px; text-align: center;">Toiminnot</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receipts)) : ?>
                    <tr><td colspan="9">Ei tallennettuja kuitteja.</td></tr>
                <?php else : ?>
                    <?php foreach ($receipts as $r) : ?>
                        <?php
                        $is_cn = ($r['receipt_type'] === 'credit_note');
                        $download_url = wp_nonce_url(admin_url('admin-post.php?action=i4ware_download_receipt&receipt_id=' . $r['id']), 'i4ware_download_receipt_' . $r['id']);
                        $resend_url = wp_nonce_url(admin_url('admin.php?page=i4ware-paypal-settings&tab=receipts&action=resend_receipt&receipt_id=' . $r['id']), 'i4ware_resend_receipt_' . $r['id']);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($r['receipt_number']); ?></strong>
                                <?php if ($is_cn) : ?>
                                    <br><span style="background: #ffe4e6; color: #be123c; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 3px;">HYVITYS</span>
                                    <?php if (!empty($r['original_receipt_number'])) : ?>
                                        <br><small style="color: #64748b;">(Viite: <?php echo esc_html($r['original_receipt_number']); ?>)</small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo esc_html(gmdate('d.m.Y H:i', strtotime($r['payment_date']))); ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html(!empty($r['company_name']) ? $r['company_name'] : $r['customer_name']); ?></strong>
                                <?php if (!empty($r['vat_number'])) : ?>
                                    <br><small style="color: #64748b;">ALV: <?php echo esc_html($r['vat_number']); ?></small>
                                <?php endif; ?>
                                <br><small style="color: #94a3b8;"><?php echo esc_html(I4ware_Receipt_Mailer::mask_email($r['customer_email'])); ?></small>
                            </td>
                            <td>
                                <strong><?php echo esc_html($r['item_name']); ?></strong>
                                <br><small style="color: #475569;"><?php echo esc_html($r['visibility_period']); ?></small>
                            </td>
                            <td style="text-align: right;">
                                <strong style="color: <?php echo $is_cn ? '#be123c' : '#047857'; ?>;">
                                    <?php echo ($is_cn ? '-' : '') . number_format($r['gross_amount'], 2, ',', ' ') . ' ' . esc_html($r['currency']); ?>
                                </strong>
                                <br><small style="color: #64748b;">(ALV <?php echo number_format($r['vat_rate'] ?: 0, 1, ',', ''); ?> %)</small>
                            </td>
                            <td>
                                <code><?php echo esc_html($r['paypal_transaction_id']); ?></code>
                                <?php if (!empty($r['paypal_subscription_id'])) : ?>
                                    <br><small style="color: #64748b;">Tilaus: <?php echo esc_html($r['paypal_subscription_id']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['paid_status'] === 'paid') : ?>
                                    <span style="color: #16a34a; font-weight: 700;">✓ Maksettu</span>
                                <?php elseif ($r['paid_status'] === 'refunded') : ?>
                                    <span style="color: #be123c; font-weight: 700;">✗ Hyvitetty</span>
                                <?php elseif ($r['paid_status'] === 'partially_refunded') : ?>
                                    <span style="color: #ca8a04; font-weight: 700;">⚠ Ositt. hyvitetty</span>
                                <?php else : ?>
                                    <span><?php echo esc_html($r['paid_status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['email_status'] === 'sent') : ?>
                                    <span style="background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; padding: 3px 6px; border-radius: 4px; display: inline-block;">✓ Lähetetty</span>
                                    <?php if (!empty($r['email_sent_at'])) : ?>
                                        <br><small style="color: #64748b;"><?php echo esc_html(gmdate('d.m. H:i', strtotime($r['email_sent_at']))); ?></small>
                                    <?php endif; ?>
                                <?php elseif ($r['email_status'] === 'pending') : ?>
                                    <span style="background: #fef3c7; color: #b45309; font-size: 11px; font-weight: 700; padding: 3px 6px; border-radius: 4px; display: inline-block;">⏳ Jonossa</span>
                                <?php elseif ($r['email_status'] === 'failed') : ?>
                                    <span style="background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: 700; padding: 3px 6px; border-radius: 4px; display: inline-block;" title="<?php echo esc_attr($r['email_error']); ?>">✗ Virhe</span>
                                    <br><small style="color: #b91c1c;">Yritykset: <?php echo (int) $r['retry_count']; ?>/3</small>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <a href="<?php echo esc_url($download_url); ?>" class="button button-small" target="_blank" title="Lataa PDF-kuitti">Lataa PDF</a>
                                <a href="<?php echo esc_url($resend_url); ?>" class="button button-small" onclick="return confirm('Haluatko varmasti lähettää kuitin uudelleen asiakkaalle?');" title="Lähetä kuitti uudelleen samalla tositenumerolla">Lähetä</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_orders_tab()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'i4ware_paypal_orders';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            echo '<p>Tilaustaulua ei ole vielä luotu.</p>';
            return;
        }

        $orders = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at_utc DESC LIMIT 50");
        ?>
        <h2>Viimeisimmät tilaukset</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 140px;">Päivämäärä (UTC)</th>
                    <th>Tyyppi</th>
                    <th>Asiakas / Yritys</th>
                    <th>Maa / ALV</th>
                    <th>Verokohtelu</th>
                    <th>Summa</th>
                    <th>Tila</th>
                    <th>PayPal ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)) : ?>
                    <tr><td colspan="8">Ei rekisteröityjä tilauksia.</td></tr>
                <?php else : ?>
                    <?php foreach ($orders as $order) : ?>
                        <tr>
                            <td><?php echo esc_html($order->created_at_utc); ?></td>
                            <td><code><?php echo esc_html($order->payment_type); ?></code></td>
                            <td>
                                <strong><?php echo esc_html($order->company_name ?: $order->contact_name); ?></strong><br>
                                <small><?php echo esc_html($order->contact_email); ?></small>
                            </td>
                            <td>
                                <?php echo esc_html($order->establishment_country); ?>
                                <?php if (!empty($order->vat_number)) : ?>
                                    <br><small>ALV: <?php echo esc_html(($order->vat_country ?: '') . $order->vat_number); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                    <?php echo esc_html($order->tax_classification); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo esc_html(number_format($order->gross_amount, 2, ',', ' ') . ' ' . $order->currency); ?></strong>
                                <?php if ($order->vat_amount > 0) : ?>
                                    <br><small style="color: #64748b;">(ALV: <?php echo esc_html(number_format($order->vat_amount, 2, ',', ' ')); ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-pill" style="font-weight: 700; color: <?php echo $order->status === 'activated' ? '#16a34a' : ($order->status === 'requires_manual_review' ? '#ea580c' : '#475569'); ?>;">
                                    <?php echo esc_html($order->status); ?>
                                </span>
                            </td>
                            <td><code><?php echo esc_html($order->paypal_subscription_id ?: '-'); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_review_tab()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'i4ware_paypal_orders';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            echo '<p>Tilaustaulua ei ole vielä luotu.</p>';
            return;
        }

        $pending = $wpdb->get_results("SELECT * FROM $table WHERE status = 'requires_manual_review' ORDER BY created_at_utc DESC");
        ?>
        <h2>Manuaalista tarkistusta vaativat tilaukset</h2>
        <p>Näiden tilausten tiedoissa on poikkeama (esim. EU:n ulkopuolinen yritys jolla EU-maan ALV-tunniste, tai VIES-nimen poikkeama), ja ne vaativat hyväksynnän ennen maksun sallimista.</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Pvm (UTC)</th>
                    <th>Asiakas / Yritys</th>
                    <th>Sijoittautumismaa & ALV</th>
                    <th>Veroluokitus</th>
                    <th>Summa</th>
                    <th>Syy tarkistukseen</th>
                    <th>Toimenpiteet</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pending)) : ?>
                    <tr><td colspan="7">Ei manuaalista tarkistusta odottavia tilauksia. Kaikki kunnossa!</td></tr>
                <?php else : ?>
                    <?php foreach ($pending as $item) : ?>
                        <tr>
                            <td><?php echo esc_html($item->created_at_utc); ?></td>
                            <td>
                                <strong><?php echo esc_html($item->company_name ?: $item->contact_name); ?></strong><br>
                                <?php echo esc_html($item->contact_email); ?> | <?php echo esc_html($item->contact_phone); ?>
                            </td>
                            <td>
                                Sijoittautumismaa: <strong><?php echo esc_html($item->establishment_country); ?></strong><br>
                                ALV-maa: <strong><?php echo esc_html($item->vat_country ?: '-'); ?></strong><br>
                                ALV-nro: <code><?php echo esc_html($item->vat_number ?: '-'); ?></code>
                            </td>
                            <td><code><?php echo esc_html($item->tax_classification); ?></code></td>
                            <td><?php echo esc_html(number_format($item->gross_amount, 2, ',', ' ') . ' ' . $item->currency); ?></td>
                            <td style="color: #c2410c;"><?php echo esc_html($item->tax_note); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wp_nonce_url('?page=i4ware-paypal-settings&tab=review&action=approve&order_id=' . $item->id, 'i4ware_review_action_' . $item->id)); ?>" class="button button-primary" style="margin-bottom: 4px;">Hyväksy</a>
                                <a href="<?php echo esc_url(wp_nonce_url('?page=i4ware-paypal-settings&tab=review&action=reject&order_id=' . $item->id, 'i4ware_review_action_' . $item->id)); ?>" class="button" onclick="return confirm('Hylätäänkö tilaus?');">Hylkää</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_vies_logs_tab()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'i4ware_vat_audit_logs';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            echo '<p>VIES-lokitaulua ei ole vielä luotu.</p>';
            return;
        }

        $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY request_utc DESC LIMIT 100");
        ?>
        <h2>VIES-tarkistusloki (UTC)</h2>
        <p>Kaikki palvelimen suorittamat viralliset EU VIES -tarkistukset tallennetaan tähän lokiin kirjanpito- ja verotarkastuksia varten.</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 135px;">Aikaleima (UTC)</th>
                    <th style="width: 125px;">Tunniste</th>
                    <th style="width: 110px;">Tulos</th>
                    <th style="width: 140px;">Konsultaatiotunnus</th>
                    <th style="width: 130px;">Lähde</th>
                    <th style="width: 110px;">Nimitila</th>
                    <th>Diagnostiikka / Virhetieto</th>
                    <th style="width: 100px;">IP-osoite</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)) : ?>
                    <tr><td colspan="8">Ei VIES-tarkistuksia lokissa.</td></tr>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <?php
                        $summary = !empty($log->response_summary) ? json_decode($log->response_summary, true) : [];
                        ?>
                        <tr>
                            <td><?php echo esc_html($log->request_utc); ?></td>
                            <td><strong><?php echo esc_html($log->vat_country . $log->vat_number); ?></strong></td>
                            <td>
                                <?php if ($log->status === I4WARE_VIES_VALID) : ?>
                                    <span style="color: #16a34a; font-weight: bold;">VALID</span>
                                <?php elseif ($log->status === I4WARE_VIES_INVALID) : ?>
                                    <span style="color: #dc2626; font-weight: bold;">INVALID</span>
                                <?php elseif ($log->status === I4WARE_VIES_SERVICE_UNAVAILABLE) : ?>
                                    <span style="color: #ca8a04; font-weight: bold;">UNAVAILABLE</span>
                                <?php elseif ($log->status === I4WARE_VIES_LOCAL_NETWORK_ERROR) : ?>
                                    <span style="color: #dc2626; font-weight: bold;">NETWORK_ERR</span>
                                <?php else : ?>
                                    <span style="color: #ea580c; font-weight: bold;"><?php echo esc_html($log->status); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo esc_html($log->consultation_number ?: '-'); ?></code></td>
                            <td><small><?php echo esc_html($log->source); ?></small></td>
                            <td><small><?php echo esc_html($log->name_match_status); ?></small></td>
                            <td>
                                <?php if ($log->status === I4WARE_VIES_VALID) : ?>
                                    <span style="color: #16a34a; font-weight: 600;">✓ ALV-tunniste voimassa</span>
                                    <?php if (!empty($summary['vies_name'])) : ?>
                                        <br><small style="color: #334155;">Nimi: <strong><?php echo esc_html($summary['vies_name']); ?></strong></small>
                                    <?php endif; ?>
                                    <?php if (!empty($summary['vies_address'])) : ?>
                                        <br><small style="color: #64748b;">Osoite: <?php echo esc_html(str_replace("\n", ", ", $summary['vies_address'])); ?></small>
                                    <?php endif; ?>
                                <?php elseif ($log->status === I4WARE_VIES_LOCAL_NETWORK_ERROR) : ?>
                                    <span style="color: #dc2626; font-weight: 600;">⚠ Paikallinen yhteysvirhe</span>
                                    <?php if (!empty($summary['error_code'])) : ?>
                                        <br><small style="color: #b91c1c;">Koodi: <code><?php echo esc_html($summary['error_code']); ?></code></small>
                                    <?php endif; ?>
                                    <?php if (!empty($summary['error_detail'])) : ?>
                                        <br><small style="color: #475569;"><?php echo esc_html($summary['error_detail']); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($summary['attempts']) && $summary['attempts'] > 1) : ?>
                                        <br><small style="color: #94a3b8;">Yrityskertoja: <?php echo (int) $summary['attempts']; ?></small>
                                    <?php endif; ?>
                                <?php elseif ($log->status === I4WARE_VIES_SERVICE_UNAVAILABLE) : ?>
                                    <span style="color: #ca8a04; font-weight: 600;">⏳ VIES-palvelukatko</span>
                                    <?php if (!empty($summary['error_code'])) : ?>
                                        <br><small style="color: #854d0e;">Virhekoodi: <code><?php echo esc_html($summary['error_code']); ?></code><?php echo isset($summary['http_status']) ? ' (HTTP ' . esc_html($summary['http_status']) . ')' : ''; ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($summary['error_detail'])) : ?>
                                        <br><small style="color: #475569;"><?php echo esc_html($summary['error_detail']); ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($summary['attempts']) && $summary['attempts'] > 1) : ?>
                                        <br><small style="color: #94a3b8;">Yrityskertoja: <?php echo (int) $summary['attempts']; ?></small>
                                    <?php endif; ?>
                                <?php elseif ($log->status === I4WARE_VIES_INVALID) : ?>
                                    <span style="color: #dc2626; font-weight: 600;">✗ ALV-tunniste ei ole voimassa</span>
                                    <?php if (!empty($summary['error_detail'])) : ?>
                                        <br><small style="color: #64748b;"><?php echo esc_html($summary['error_detail']); ?></small>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <small style="color: #64748b;"><?php echo esc_html(!empty($summary['error_detail']) ? $summary['error_detail'] : $log->name_match_status); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?php echo esc_html($log->ip_address); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}
