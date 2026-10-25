<?php
/**
 * i4ware PayPal Checkout Session & Cryptographic Token Manager
 *
 * Enforces server-side integrity:
 * - Calculates and locks prices, currency, and taxes on the server.
 * - Signs the approved session cryptographically with HMAC-SHA256.
 * - Any alteration to customer data, plan, or price invalidates the token.
 * - PayPal subscriptions can only be initialized or approved with a valid session token.
 */

if (!defined('ABSPATH')) {
    exit;
}

class I4ware_Checkout_Session
{
    const EXPIRY_SECONDS = 3600; // 1 hour token validity

    /**
     * Create a validated checkout session and cryptographic token.
     */
    public static function create_session(array $order_data)
    {
        $session_id = wp_generate_uuid4();
        $now = time();
        $expires_at = $now + self::EXPIRY_SECONDS;

        $session_payload = [
            'session_id' => $session_id,
            'created_at_utc' => gmdate('Y-m-d H:i:s', $now),
            'expires_at_utc' => gmdate('Y-m-d H:i:s', $expires_at),
            'expires_timestamp' => $expires_at,
            'plan_id' => isset($order_data['plan_id']) ? sanitize_text_field($order_data['plan_id']) : '',
            'item_name' => isset($order_data['item_name']) ? sanitize_text_field($order_data['item_name']) : '',
            'payment_type' => isset($order_data['payment_type']) ? sanitize_text_field($order_data['payment_type']) : I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'billing_mode' => isset($order_data['billing_mode']) ? sanitize_text_field($order_data['billing_mode']) : 'monthly',
            'ad_format' => isset($order_data['ad_format']) ? sanitize_text_field($order_data['ad_format']) : '',
            'ad_placement' => isset($order_data['ad_placement']) ? sanitize_text_field($order_data['ad_placement']) : '',
            'ad_target_url' => isset($order_data['ad_target_url']) ? (function_exists('esc_url_raw') ? esc_url_raw($order_data['ad_target_url']) : filter_var($order_data['ad_target_url'], FILTER_SANITIZE_URL)) : '',
            'ad_copy' => isset($order_data['ad_copy']) ? sanitize_textarea_field($order_data['ad_copy']) : '',
            'customer_type' => isset($order_data['customer_type']) ? sanitize_text_field($order_data['customer_type']) : I4WARE_CUSTOMER_INDIVIDUAL,
            'company_name' => isset($order_data['company_name']) ? sanitize_text_field($order_data['company_name']) : '',
            'establishment_country' => isset($order_data['establishment_country']) ? sanitize_text_field($order_data['establishment_country']) : '',
            'fixed_establishment_country' => isset($order_data['fixed_establishment_country']) ? sanitize_text_field($order_data['fixed_establishment_country']) : '',
            'billing_country' => isset($order_data['billing_country']) ? sanitize_text_field($order_data['billing_country']) : '',
            'billing_address' => isset($order_data['billing_address']) ? sanitize_textarea_field($order_data['billing_address']) : '',
            'business_id' => isset($order_data['business_id']) ? sanitize_text_field($order_data['business_id']) : '',
            'vat_number' => isset($order_data['vat_number']) ? sanitize_text_field($order_data['vat_number']) : '',
            'vat_country' => isset($order_data['vat_country']) ? sanitize_text_field($order_data['vat_country']) : '',
            'contact_name' => isset($order_data['contact_name']) ? sanitize_text_field($order_data['contact_name']) : '',
            'contact_email' => isset($order_data['contact_email']) ? sanitize_email($order_data['contact_email']) : '',
            'contact_phone' => isset($order_data['contact_phone']) ? sanitize_text_field($order_data['contact_phone']) : '',
            'net_amount' => (float) $order_data['net_amount'],
            'vat_rate' => $order_data['vat_rate'] !== null ? (float) $order_data['vat_rate'] : null,
            'vat_amount' => (float) $order_data['vat_amount'],
            'gross_amount' => (float) $order_data['gross_amount'],
            'currency' => isset($order_data['currency']) ? sanitize_text_field($order_data['currency']) : 'EUR',
            'tax_classification' => isset($order_data['tax_classification']) ? sanitize_text_field($order_data['tax_classification']) : '',
            'tax_note' => isset($order_data['tax_note']) ? sanitize_text_field($order_data['tax_note']) : '',
            'vies_consultation_number' => isset($order_data['vies_consultation_number']) ? sanitize_text_field($order_data['vies_consultation_number']) : '',
            'status' => isset($order_data['status']) ? sanitize_text_field($order_data['status']) : 'approved'
        ];

        // Store session transient in WordPress
        set_transient('i4ware_session_' . $session_id, $session_payload, self::EXPIRY_SECONDS);

        // Also record order in database table
        self::save_order_record($session_payload);

        // Generate signed token
        $token = self::sign_payload($session_payload);

        return [
            'session_id' => $session_id,
            'token' => $token,
            'session' => $session_payload
        ];
    }

    /**
     * Verify token signature and expiration.
     */
    public static function verify_token($token)
    {
        if (empty($token) || !is_string($token)) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return false;
        }

        list($encoded_payload, $signature) = $parts;
        $expected_sig = hash_hmac('sha256', $encoded_payload, self::get_signing_key());

        if (!hash_equals($expected_sig, $signature)) {
            return false; // Signature tampered with!
        }

        $json = base64_decode(strtr($encoded_payload, '-_', '+/'));
        $payload = json_decode($json, true);

        if (!is_array($payload) || empty($payload['session_id']) || empty($payload['expires_timestamp'])) {
            return false;
        }

        if (time() > $payload['expires_timestamp']) {
            return false; // Expired!
        }

        return $payload;
    }

    /**
     * Generate HMAC-SHA256 signature
     */
    private static function sign_payload(array $payload)
    {
        $json = wp_json_encode($payload);
        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $encoded, self::get_signing_key());
        return $encoded . '.' . $signature;
    }

    /**
     * Get secret key from WordPress salts
     */
    private static function get_signing_key()
    {
        if (function_exists('wp_salt')) {
            return wp_salt('auth') . '_i4ware_paypal_checkout_key';
        }
        return 'i4ware_fallback_signing_key_2026';
    }

    /**
     * Save or update order record in database
     */
    public static function save_order_record(array $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i4ware_paypal_orders';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }

        $session_id = $data['session_id'];
        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE session_id = %s", $session_id));

        $fields = [
            'session_id' => $session_id,
            'payment_type' => $data['payment_type'],
            'customer_type' => $data['customer_type'],
            'company_name' => $data['company_name'],
            'establishment_country' => $data['establishment_country'],
            'fixed_establishment_country' => $data['fixed_establishment_country'],
            'billing_country' => $data['billing_country'],
            'billing_address' => $data['billing_address'],
            'business_id' => $data['business_id'],
            'vat_number' => $data['vat_number'],
            'vat_country' => $data['vat_country'],
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'],
            'plan_id' => $data['plan_id'],
            'item_name' => $data['item_name'],
            'net_amount' => $data['net_amount'],
            'vat_rate' => $data['vat_rate'],
            'vat_amount' => $data['vat_amount'],
            'gross_amount' => $data['gross_amount'],
            'currency' => $data['currency'],
            'tax_classification' => $data['tax_classification'],
            'tax_note' => $data['tax_note'],
            'status' => $data['status'],
            'created_at_utc' => $data['created_at_utc'],
            'updated_at_utc' => gmdate('Y-m-d H:i:s')
        ];

        if ($existing) {
            $wpdb->update($table_name, $fields, ['id' => $existing]);
            return $existing;
        } else {
            $wpdb->insert($table_name, $fields);
            return $wpdb->insert_id;
        }
    }

    /**
     * Mark an order as approved with PayPal subscription ID
     */
    public static function mark_subscription_activated($session_id, $paypal_subscription_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'i4ware_paypal_orders';

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return false;
        }

        $res = $wpdb->update(
            $table_name,
            [
                'status' => 'activated',
                'paypal_subscription_id' => sanitize_text_field($paypal_subscription_id),
                'updated_at_utc' => gmdate('Y-m-d H:i:s')
            ],
            ['session_id' => $session_id]
        );

        delete_transient('i4ware_session_' . $session_id);
        return $res;
    }
}
