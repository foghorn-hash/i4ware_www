<?php
/**
 * i4ware PayPal REST API Endpoints
 *
 * Implements:
 * - /wp-json/i4ware-paypal/v1/validate-checkout
 * - /wp-json/i4ware-paypal/v1/confirm-subscription
 * - /wp-json/i4ware-paypal/v1/webhook
 */

if (!defined('ABSPATH')) {
    exit;
}

class I4ware_PayPal_API
{
    const NAMESPACE = 'i4ware-paypal/v1';

    public static function register_routes()
    {
        // Public checkout endpoints do not require WordPress cookie authentication
        add_filter('rest_authentication_errors', [__CLASS__, 'filter_rest_auth_errors'], 101);

        // 1. Pre-Checkout Validation & Tax Calculation
        register_rest_route(self::NAMESPACE, '/validate-checkout', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_validate_checkout'],
            'permission_callback' => '__return_true'
        ]);

        // 2. Subscription Confirmation
        register_rest_route(self::NAMESPACE, '/confirm-subscription', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_confirm_subscription'],
            'permission_callback' => '__return_true'
        ]);

        // 3. PayPal Webhooks
        register_rest_route(self::NAMESPACE, '/webhook', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'handle_webhook'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * Prevent WordPress cookie check failures from blocking public checkout endpoints.
     * When an administrator or user is logged into WordPress in their browser,
     * WordPress rest_cookie_check_errors() checks for a valid X-WP-Nonce.
     * If missing, stale, or empty, it returns 403 WP_Error('rest_cookie_invalid_nonce', 'Cookie check failed').
     * For public checkout endpoints, we clear this error so the customer request proceeds as a guest.
     */
    public static function filter_rest_auth_errors($errors)
    {
        if (is_wp_error($errors) && $errors->get_error_code() === 'rest_cookie_invalid_nonce') {
            $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $query_route = isset($_GET['rest_route']) ? $_GET['rest_route'] : '';

            if (strpos($uri, self::NAMESPACE . '/validate-checkout') !== false ||
                strpos($uri, self::NAMESPACE . '/confirm-subscription') !== false ||
                strpos($uri, self::NAMESPACE . '/webhook') !== false ||
                strpos($query_route, self::NAMESPACE . '/validate-checkout') !== false ||
                strpos($query_route, self::NAMESPACE . '/confirm-subscription') !== false ||
                strpos($query_route, self::NAMESPACE . '/webhook') !== false) {
                return null;
            }
        }

        return $errors;
    }

    /**
     * Handle pre-checkout customer verification and tax calculation
     */
    public static function handle_validate_checkout(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (empty($params) || !is_array($params)) {
            $params = $request->get_params();
        }

        // Language
        $lang = !empty($params['lang']) ? sanitize_text_field($params['lang']) : 'fi';
        if ($lang !== 'fi' && $lang !== 'en' && $lang !== 'ar') {
            $lang = 'fi';
        }

        // Payment type
        $payment_type = !empty($params['payment_type']) ? sanitize_text_field($params['payment_type']) : I4WARE_PAYMENT_ADVERTISING_SERVICE;

        // Customer type
        $customer_type = !empty($params['customer_type']) ? sanitize_text_field($params['customer_type']) : I4WARE_CUSTOMER_INDIVIDUAL;

        // Contact info
        $contact_name = !empty($params['contact_name']) ? sanitize_text_field($params['contact_name']) : '';
        $contact_email = !empty($params['contact_email']) ? sanitize_email($params['contact_email']) : '';
        $contact_phone = !empty($params['contact_phone']) ? sanitize_text_field($params['contact_phone']) : '';

        // Validation for contact info
        $errors = [];
        if (empty($contact_name)) {
            $errors['contact_name'] = ($lang === 'fi') ? 'Nimi on pakollinen kenttä.' : 'Contact name is required.';
        }
        if (empty($contact_email) || !is_email($contact_email)) {
            $errors['contact_email'] = ($lang === 'fi') ? 'Anna voimassa oleva sähköpostiosoite.' : 'Valid email is required.';
        }

        // Address & Country
        $establishment_country = !empty($params['establishment_country']) ? strtoupper(trim(sanitize_text_field($params['establishment_country']))) : '';
        $fixed_establishment_country = !empty($params['fixed_establishment_country']) ? strtoupper(trim(sanitize_text_field($params['fixed_establishment_country']))) : '';
        $billing_country = !empty($params['billing_country']) ? strtoupper(trim(sanitize_text_field($params['billing_country']))) : $establishment_country;
        $billing_address = !empty($params['billing_address']) ? sanitize_textarea_field($params['billing_address']) : '';

        if (empty($billing_country)) {
            $errors['billing_country'] = ($lang === 'fi') ? 'Laskutusmaa on pakollinen.' : 'Billing country is required.';
        }
        if (empty($billing_address)) {
            $errors['billing_address'] = ($lang === 'fi') ? 'Laskutusosoite on pakollinen.' : 'Billing address is required.';
        }

        // Business specific fields
        $company_name = '';
        $business_id = '';
        $vat_registered = 'no';
        $vat_country = '';
        $vat_number = '';

        if ($customer_type === I4WARE_CUSTOMER_BUSINESS) {
            $company_name = !empty($params['company_name']) ? sanitize_text_field($params['company_name']) : '';
            $business_id = !empty($params['business_id']) ? sanitize_text_field($params['business_id']) : '';
            $vat_registered = !empty($params['vat_registered']) ? sanitize_text_field($params['vat_registered']) : 'no';
            $vat_country = !empty($params['vat_country']) ? strtoupper(trim(sanitize_text_field($params['vat_country']))) : '';
            $vat_number = !empty($params['vat_number']) ? sanitize_text_field($params['vat_number']) : '';

            if (empty($company_name)) {
                $errors['company_name'] = ($lang === 'fi') ? 'Yrityksen virallinen nimi on pakollinen.' : 'Official company name is required.';
            }
            if (empty($establishment_country)) {
                $errors['establishment_country'] = ($lang === 'fi') ? 'Yrityksen sijoittautumismaa on pakollinen.' : 'Company establishment country is required.';
            }
        } else {
            // Private individuals must NOT have business or VAT IDs demanded
            $establishment_country = $billing_country;
        }

        if (!empty($errors)) {
            return new WP_REST_Response([
                'success' => false,
                'status' => 'validation_error',
                'errors' => $errors,
                'message' => ($lang === 'fi') ? 'Tarkista lomakkeen kentät.' : 'Please correct the form fields.'
            ], 400);
        }

        // Product / Plan pricing & Advertising specification
        $plan_id = !empty($params['plan_id']) ? sanitize_text_field($params['plan_id']) : '';
        $item_name = !empty($params['item_name']) ? sanitize_text_field($params['item_name']) : 'Mainospalvelu';
        $base_amount = isset($params['base_amount']) ? (float) $params['base_amount'] : 0.0;
        $currency = !empty($params['currency']) ? strtoupper(sanitize_text_field($params['currency'])) : 'EUR';
        $billing_mode = !empty($params['billing_mode']) ? sanitize_text_field($params['billing_mode']) : 'subscription';
        $ad_format = !empty($params['ad_format']) ? sanitize_text_field($params['ad_format']) : 'logo';
        $ad_placement = !empty($params['ad_placement']) ? sanitize_text_field($params['ad_placement']) : 'footer_partner';
        $ad_target_url = !empty($params['ad_target_url']) ? esc_url_raw($params['ad_target_url']) : '';
        $ad_copy = !empty($params['ad_copy']) ? sanitize_textarea_field($params['ad_copy']) : '';

        // Perform VIES check if customer provided an EU VAT number
        $vies_result = null;
        if ($customer_type === I4WARE_CUSTOMER_BUSINESS && $vat_registered === 'yes' && !empty($vat_number)) {
            $vies_country = !empty($vat_country) ? $vat_country : $establishment_country;
            $vies_result = I4ware_VIES_Client::check_vat(
                $vies_country,
                $vat_number,
                $company_name,
                $billing_address,
                5
            );
        }

        // Run Tax Decision Engine
        $decision_input = [
            'payment_type' => $payment_type,
            'customer_type' => $customer_type,
            'establishment_country' => $establishment_country,
            'fixed_establishment_country' => $fixed_establishment_country,
            'billing_country' => $billing_country,
            'billing_address' => $billing_address,
            'vat_registered' => $vat_registered,
            'vat_country' => $vat_country,
            'vat_number' => $vat_number,
            'vies_result' => $vies_result,
            'business_id' => $business_id,
            'base_amount' => $base_amount,
            'price_includes_tax' => !empty($params['price_includes_tax']),
            'currency' => $currency,
            'lang' => $lang
        ];

        $tax_decision = I4ware_Tax_Decision_Engine::evaluate($decision_input);

        // If blocked (e.g. gratuitous support disabled by admin)
        if ($tax_decision['status'] === 'blocked') {
            return new WP_REST_Response([
                'success' => false,
                'status' => 'blocked',
                'can_proceed_to_paypal' => false,
                'message' => $tax_decision['user_message'],
                'reason' => $tax_decision['reason']
            ], 200);
        }

        // If manual review is required
        if ($tax_decision['status'] === 'requires_manual_review' || !empty($tax_decision['requires_manual_review'])) {
            // Save order record as requires_manual_review for admin attention
            $review_order_data = array_merge($tax_decision, [
                'plan_id' => $plan_id,
                'item_name' => $item_name,
                'payment_type' => $payment_type,
                'customer_type' => $customer_type,
                'billing_mode' => $billing_mode,
                'ad_format' => $ad_format,
                'ad_placement' => $ad_placement,
                'ad_target_url' => $ad_target_url,
                'ad_copy' => $ad_copy,
                'company_name' => $company_name,
                'establishment_country' => $establishment_country,
                'fixed_establishment_country' => $fixed_establishment_country,
                'billing_country' => $billing_country,
                'billing_address' => $billing_address,
                'business_id' => $business_id,
                'vat_number' => $vat_number,
                'vat_country' => $vat_country,
                'contact_name' => $contact_name,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'status' => 'requires_manual_review',
                'vies_consultation_number' => isset($vies_result['consultation_number']) ? $vies_result['consultation_number'] : ''
            ]);
            I4ware_Checkout_Session::create_session($review_order_data);

            return new WP_REST_Response([
                'success' => false,
                'status' => 'requires_manual_review',
                'can_proceed_to_paypal' => false,
                'tax_classification' => $tax_decision['tax_classification'],
                'message' => $tax_decision['user_message'],
                'reason' => $tax_decision['reason'],
                'vies' => $vies_result
            ], 200);
        }

        // If invalid VAT ID
        if ($tax_decision['status'] === 'invalid') {
            return new WP_REST_Response([
                'success' => false,
                'status' => 'invalid',
                'can_proceed_to_paypal' => false,
                'message' => $tax_decision['user_message'],
                'reason' => $tax_decision['reason'],
                'vies' => $vies_result
            ], 200);
        }

        // Approved! Create secure signed session token
        $order_data = array_merge($tax_decision, [
            'plan_id' => $plan_id,
            'item_name' => $item_name,
            'payment_type' => $payment_type,
            'customer_type' => $customer_type,
            'billing_mode' => $billing_mode,
            'ad_format' => $ad_format,
            'ad_placement' => $ad_placement,
            'ad_target_url' => $ad_target_url,
            'ad_copy' => $ad_copy,
            'company_name' => $company_name,
            'establishment_country' => $establishment_country,
            'fixed_establishment_country' => $fixed_establishment_country,
            'billing_country' => $billing_country,
            'billing_address' => $billing_address,
            'business_id' => $business_id,
            'vat_number' => $vat_number,
            'vat_country' => $vat_country,
            'contact_name' => $contact_name,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'status' => 'approved',
            'vies_consultation_number' => isset($vies_result['consultation_number']) ? $vies_result['consultation_number'] : ''
        ]);

        $session_result = I4ware_Checkout_Session::create_session($order_data);

        return new WP_REST_Response([
            'success' => true,
            'status' => 'approved',
            'can_proceed_to_paypal' => true,
            'session_id' => $session_result['session_id'],
            'token' => $session_result['token'],
            'calculation' => [
                'net_amount' => $tax_decision['net_amount'],
                'vat_rate' => $tax_decision['vat_rate'],
                'vat_amount' => $tax_decision['vat_amount'],
                'gross_amount' => $tax_decision['gross_amount'],
                'currency' => $tax_decision['currency'],
                'tax_classification' => $tax_decision['tax_classification'],
                'tax_note' => $tax_decision['tax_note'],
            ],
            'vies' => $vies_result,
            'message' => $tax_decision['user_message']
        ], 200);
    }

    /**
     * Confirm subscription after PayPal approval
     */
    public static function handle_confirm_subscription(WP_REST_Request $request)
    {
        $params = $request->get_json_params();
        if (empty($params) || !is_array($params)) {
            $params = $request->get_params();
        }

        $token = isset($params['token']) ? $params['token'] : '';
        $paypal_subscription_id = isset($params['subscription_id']) ? sanitize_text_field($params['subscription_id']) : '';

        if (empty($token) || empty($paypal_subscription_id)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Puuttuva istuntotunniste tai PayPal-tilaustunniste.'
            ], 400);
        }

        // Verify cryptographic session token
        $session = I4ware_Checkout_Session::verify_token($token);
        if (!$session) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Virheellinen tai vanhentunut istuntotunniste. Ole hyvä ja aloita tilaus alusta.'
            ], 403);
        }

        // Verify subscription with PayPal REST API
        $paypal_verification = I4ware_PayPal_REST::verify_subscription($paypal_subscription_id, $session);
        if (!$paypal_verification['verified']) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'PayPal-tilauksen varmennus epäonnistui: ' . $paypal_verification['message']
            ], 400);
        }

        // Activate order in database
        I4ware_Checkout_Session::mark_subscription_activated($session['session_id'], $paypal_subscription_id);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Tilaus vahvistettu onnistuneesti!',
            'subscription_id' => $paypal_subscription_id
        ], 200);
    }

    /**
     * Handle incoming PayPal Webhook
     */
    public static function handle_webhook(WP_REST_Request $request)
    {
        $headers = $request->get_headers();
        $raw_body = $request->get_body();

        $result = I4ware_PayPal_REST::process_webhook_event($headers, $raw_body);

        return new WP_REST_Response([
            'success' => $result['success'],
            'message' => $result['message']
        ], $result['code']);
    }
}
