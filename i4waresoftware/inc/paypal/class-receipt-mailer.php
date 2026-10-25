<?php
/**
 * i4ware Receipt Mailer & VPS SMTP Integration
 *
 * Implements:
 * - Authenticated SMTP sending with TLS from i4ware VPS
 * - Secure credential handling (wp-config.php constants prioritized over database options)
 * - Required Finnish customer receipt text
 * - Attachment of official PDF receipt
 * - Bounded error logging with masked personal data (GDPR compliance)
 */

if (!defined('ABSPATH')) {
    exit;
}

class I4ware_Receipt_Mailer
{
    private static $last_error = null;

    /**
     * Initialize PHPMailer hook for SMTP
     */
    public static function init()
    {
        add_action('phpmailer_init', [__CLASS__, 'configure_smtp']);
        add_action('wp_mail_failed', [__CLASS__, 'capture_mail_failure']);
    }

    /**
     * Configure PHPMailer with authenticated SMTP over TLS
     */
    public static function configure_smtp($phpmailer)
    {
        // 1. Check for wp-config.php constants first (highest security)
        $host = defined('I4WARE_SMTP_HOST') ? I4WARE_SMTP_HOST : get_option('i4ware_smtp_host', '');
        $port = defined('I4WARE_SMTP_PORT') ? (int) I4WARE_SMTP_PORT : (int) get_option('i4ware_smtp_port', 587);
        $user = defined('I4WARE_SMTP_USER') ? I4WARE_SMTP_USER : get_option('i4ware_smtp_user', '');
        $pass = defined('I4WARE_SMTP_PASS') ? I4WARE_SMTP_PASS : get_option('i4ware_smtp_pass', '');
        $secure = defined('I4WARE_SMTP_SECURE') ? I4WARE_SMTP_SECURE : get_option('i4ware_smtp_secure', 'tls');

        if (!empty($host) && !empty($user)) {
            $phpmailer->isSMTP();
            $phpmailer->Host = $host;
            $phpmailer->Port = ($port > 0) ? $port : 587;
            $phpmailer->SMTPAuth = true;
            $phpmailer->Username = $user;
            $phpmailer->Password = $pass;

            if ($secure === 'tls' || $secure === 'ssl') {
                $phpmailer->SMTPSecure = $secure;
            } else {
                $phpmailer->SMTPSecure = 'tls';
            }

            // Ensure timeout is bounded
            $phpmailer->Timeout = 15;
        }

        // Sender Email & Name
        $sender_email = get_option('i4ware_receipt_sender_email', 'info@i4ware.fi');
        $sender_name = get_option('i4ware_receipt_sender_name', 'i4ware Software');

        if (!empty($sender_email) && is_email($sender_email)) {
            $phpmailer->From = $sender_email;
            $phpmailer->FromName = $sender_name;
        }
    }

    /**
     * Capture wp_mail failure details
     */
    public static function capture_mail_failure($wp_error)
    {
        if (is_wp_error($wp_error)) {
            self::$last_error = $wp_error->get_error_message();
        }
    }

    /**
     * Send receipt email to customer.
     *
     * @param array $receipt_record Row from wp_i4ware_receipts
     * @return array [success => bool, message => string, error => string]
     */
    public static function send_receipt_email(array $receipt_record)
    {
        self::$last_error = null;

        $to = $receipt_record['customer_email'];
        if (empty($to) || !is_email($to)) {
            return [
                'success' => false,
                'message' => 'Virheellinen vastaanottajan sähköpostiosoite.',
                'error' => 'invalid_email'
            ];
        }

        $pdf_path = $receipt_record['pdf_path'];
        if (empty($pdf_path) || !file_exists($pdf_path)) {
            return [
                'success' => false,
                'message' => 'PDF-kuittitiedostoa ei löydy palvelimelta.',
                'error' => 'file_not_found'
            ];
        }

        $is_credit_note = (!empty($receipt_record['receipt_type']) && $receipt_record['receipt_type'] === 'credit_note');
        $receipt_no = $receipt_record['receipt_number'];
        $reply_to = get_option('i4ware_receipt_reply_to', 'info@i4ware.fi');

        // Headers
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $reply_to
        ];

        // Subject
        if ($is_credit_note) {
            $subject = 'Hyvitystosite maksetusta mainospalvelusta - i4ware Software [' . $receipt_no . ']';
        } else {
            $subject = 'Kuitti maksetusta mainospalvelusta - i4ware Software [' . $receipt_no . ']';
        }

        // Mandatory Customer Body
        $body = "Hei,\n\n";
        if ($is_credit_note) {
            $body .= "Liitteenä on i4ware Softwaren hyvitystosite koskien mainospalvelun maksunpalautusta.\n";
            $body .= "Tositenumero: " . $receipt_no . "\n";
            if (!empty($receipt_record['original_receipt_number'])) {
                $body .= "Alkuperäinen kuitti: " . $receipt_record['original_receipt_number'] . "\n";
            }
            $body .= "Palautus on käsitelty PayPalin kautta alkuperäiselle maksutavallesi.\n\n";
        } else {
            $body .= "Kiitos tilauksestasi. Liitteenä on i4ware Softwaren kuitti maksetusta mainospalvelusta. Tämä kuitti toimitetaan PayPalin maksuvahvistuksen lisäksi. Maksua ei tarvitse suorittaa uudelleen.\n\n";
            $body .= "Tilauksen tiedot:\n";
            $body .= "• Tositenumero: " . $receipt_no . "\n";
            $body .= "• Palvelu: " . $receipt_record['item_name'] . "\n";
            $body .= "• Näkyvyyden kesto: " . $receipt_record['visibility_period'] . "\n";
            $body .= "• Maksettu loppusumma: " . number_format($receipt_record['gross_amount'], 2, ',', ' ') . " " . $receipt_record['currency'] . "\n";
            $body .= "• Maksutapahtumatunnus (PayPal): " . $receipt_record['paypal_transaction_id'] . "\n\n";
            $body .= "Mainosaineiston toimitus ja hyväksyntä:\n";
            $body .= "Toimitathan mainosaineistosi (logo/banneri, linkkiosoite ja teksti) osoitteeseen info@i4ware.fi, mikäli et ole vielä toimittanut sitä. Aineisto tarkistetaan ja julkaistaan 1–2 arkipäivän kuluessa hyväksymisestä.\n\n";
        }

        $body .= "Ystävällisin terveisin,\n";
        $body .= "i4ware Software\n";
        $body .= "info@i4ware.fi | https://i4ware.fi\n";

        // Attachments
        $attachments = [$pdf_path];

        // Execute wp_mail
        $sent = wp_mail($to, $subject, $body, $headers, $attachments);

        if ($sent) {
            return [
                'success' => true,
                'message' => 'Kuitti lähetetty onnistuneesti asiakkaalle.',
                'error' => null
            ];
        }

        $error_msg = self::$last_error ? self::$last_error : 'Tuntematon SMTP- tai sähköpostivirhe';
        return [
            'success' => false,
            'message' => 'Sähköpostin lähetys epäonnistui: ' . $error_msg,
            'error' => $error_msg
        ];
    }

    /**
     * Mask email address for GDPR compliant logging.
     * Example: "matti.meikalainen@example.com" -> "m***n@example.com"
     */
    public static function mask_email($email)
    {
        if (empty($email) || strpos($email, '@') === false) {
            return '***';
        }
        list($local, $domain) = explode('@', $email, 2);
        $len = strlen($local);
        if ($len <= 2) {
            $masked_local = $local[0] . '***';
        } else {
            $masked_local = $local[0] . '***' . $local[$len - 1];
        }
        return $masked_local . '@' . $domain;
    }
}
