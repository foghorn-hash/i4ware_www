<?php
/**
 * Automated Verification & Test Suite for i4ware PayPal Subscriptions & VAT Architecture
 *
 * Runs tests for all 10+ required cases:
 * 1. Finnish company (25.5% ALV)
 * 2. Other EU company with valid VAT ID (Reverse charge 0% ALV)
 * 3. Other EU company with invalid VAT ID (Blocked / Manual review)
 * 4. VIES service outage / timeout simulation (Manual review)
 * 5. Non-EU company without EU VAT ID (Non-EU export 0% ALV, AVL 65 §)
 * 6. Non-EU company with EU VAT ID (Atlassian case -> Manual review)
 * 7. Valid VAT ID with divergent company name (Manual review)
 * 8. Private individuals (FI, EU, Non-EU; no business/VAT ID demanded)
 * 9. Direct API bypass attempt / forged token
 * 10. Tampering with customer data or price after verification (HMAC signature mismatch)
 * 11. PayPal Webhook signature & Idempotent redelivery
 * 12. Gratuitous support disabled by default under Rahankeräyslaki
 */

// Define ABSPATH and load mock WordPress environment if running in CLI
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

// Minimal WordPress Environment Mocks for CLI testing
if (!function_exists('get_option')) {
    $GLOBALS['_wp_options'] = [
        'i4ware_allow_gratuitous_support' => false,
        'i4ware_standard_vat_rate' => 25.5,
        'i4ware_paypal_environment' => 'sandbox',
        'i4ware_paypal_webhook_id' => 'WH-MOCK-WEBHOOK-ID'
    ];
    function get_option($key, $default = false) {
        return isset($GLOBALS['_wp_options'][$key]) ? $GLOBALS['_wp_options'][$key] : $default;
    }
    function update_option($key, $val) {
        $GLOBALS['_wp_options'][$key] = $val;
        return true;
    }
}
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) { return trim(strip_tags((string)$str)); }
}
if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) { return trim(strip_tags((string)$str)); }
}
if (!function_exists('sanitize_email')) {
    function sanitize_email($email) { return filter_var(trim((string)$email), FILTER_SANITIZE_EMAIL); }
}
if (!function_exists('is_email')) {
    function is_email($email) { return filter_var($email, FILTER_VALIDATE_EMAIL); }
}
if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) { return filter_var($url, FILTER_SANITIZE_URL); }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) { return json_encode($data); }
}
if (!function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
if (!function_exists('wp_salt')) {
    function wp_salt($scheme = 'auth') { return 'test_secret_salt_for_hmac_1234567890'; }
}
if (!function_exists('set_transient')) {
    $GLOBALS['_wp_transients'] = [];
    function set_transient($key, $value, $expiration = 0) {
        $GLOBALS['_wp_transients'][$key] = $value;
        return true;
    }
    function get_transient($key) {
        return isset($GLOBALS['_wp_transients'][$key]) ? $GLOBALS['_wp_transients'][$key] : false;
    }
    function delete_transient($key) {
        unset($GLOBALS['_wp_transients'][$key]);
        return true;
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        private $code;
        private $message;
        private $data;
        public function __construct($code = '', $message = '', $data = '') {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
        public function get_error_data() { return $this->data; }
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return ($thing instanceof WP_Error);
    }
}
if (!function_exists('wp_remote_post')) {
    $GLOBALS['_wp_remote_post_mock'] = null;
    $GLOBALS['_wp_remote_post_calls'] = [];
    function wp_remote_post($url, $args = []) {
        $GLOBALS['_wp_remote_post_calls'][] = ['url' => $url, 'args' => $args];
        if (is_callable($GLOBALS['_wp_remote_post_mock'])) {
            return call_user_func($GLOBALS['_wp_remote_post_mock'], $url, $args);
        }
        return $GLOBALS['_wp_remote_post_mock'];
    }
}
if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = []) {
        return wp_remote_post($url, $args);
    }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return isset($response['response']['code']) ? (int)$response['response']['code'] : 0;
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return isset($response['body']) ? $response['body'] : '';
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        $temp = sys_get_temp_dir() . '/i4ware_test_uploads';
        if (!is_dir($temp)) {
            @mkdir($temp, 0777, true);
        }
        return ['basedir' => $temp, 'baseurl' => 'http://example.com/uploads'];
    }
}
if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($dir) {
        if (!is_dir($dir)) {
            return @mkdir($dir, 0777, true);
        }
        return true;
    }
}
if (!function_exists('wp_mail')) {
    $GLOBALS['_wp_mail_calls'] = [];
    $GLOBALS['_wp_mail_mock'] = null;
    function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
        $GLOBALS['_wp_mail_calls'][] = [
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
            'attachments' => $attachments
        ];
        if (is_callable($GLOBALS['_wp_mail_mock'])) {
            return call_user_func($GLOBALS['_wp_mail_mock'], $to, $subject, $message, $headers, $attachments);
        }
        return true;
    }
}
if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook) { return false; }
}
if (!function_exists('wp_schedule_single_event')) {
    $GLOBALS['_wp_scheduled_events'] = [];
    function wp_schedule_single_event($timestamp, $hook, $args = []) {
        $GLOBALS['_wp_scheduled_events'][] = ['timestamp' => $timestamp, 'hook' => $hook, 'args' => $args];
        return true;
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can($cap) { return true; }
}
if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $query_arg = '_wpnonce') { return 1; }
}
if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') { return '/wp-admin/' . $path; }
}
if (!function_exists('wp_nonce_url')) {
    function wp_nonce_url($actionurl, $action = -1, $name = '_wpnonce') { return $actionurl . '&_wpnonce=test'; }
}

// Enhanced Mock $wpdb for CLI testing
class MockWPDB {
    public $prefix = 'wp_';
    public $tables = [];
    public $rows = [];
    private $_auto_inc = 1;
    public $insert_id = 0;

    public function esc_like($text) {
        return addcslashes($text, '_%\\');
    }

    public function prepare($query, ...$args) {
        $query = str_replace('%s', "'%s'", $query);
        $query = str_replace('%d', "%d", $query);
        $escaped = array_map(function($a) { return is_numeric($a) ? $a : addslashes((string)$a); }, $args);
        return vsprintf($query, $escaped);
    }

    public function insert($table, $data) {
        $id = isset($data['id']) ? (int)$data['id'] : $this->_auto_inc++;
        $data['id'] = $id;
        $this->insert_id = $id;
        $this->tables[$table][$id] = $data;
        if (strpos($table, 'webhook_events') !== false && isset($data['event_id'])) {
            $this->tables['events'][$data['event_id']] = $data;
        }
        return 1;
    }

    public function update($table, $data, $where) {
        if (!isset($this->tables[$table])) {
            return 0;
        }
        $updated = 0;
        foreach ($this->tables[$table] as $id => &$row) {
            $match = true;
            foreach ($where as $wk => $wv) {
                if (!isset($row[$wk]) || (string)$row[$wk] !== (string)$wv) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                foreach ($data as $dk => $dv) {
                    $row[$dk] = $dv;
                }
                $updated++;
            }
        }
        return $updated;
    }

    public function get_var($query) {
        if (strpos($query, 'SHOW TABLES') !== false) {
            if (preg_match("/LIKE '([^']+)'/", $query, $m)) {
                return $m[1];
            }
            return 'wp_mock_table';
        }
        if (strpos($query, 'WHERE event_id =') !== false) {
            preg_match("/WHERE event_id = '([^']+)'/", $query, $m);
            if (!empty($m[1]) && isset($this->tables['events'][$m[1]])) {
                return 1;
            }
            return null;
        }
        if (strpos($query, 'SELECT receipt_number FROM') !== false) {
            preg_match("/FROM ([^ ]+) WHERE receipt_number LIKE '([^']+)'/", $query, $m);
            if (!empty($m[1]) && !empty($m[2]) && isset($this->tables[$m[1]])) {
                $prefix = str_replace('%', '', $m[2]);
                $matched = [];
                foreach ($this->tables[$m[1]] as $row) {
                    if (isset($row['receipt_number']) && strpos($row['receipt_number'], $prefix) === 0) {
                        $matched[] = $row['receipt_number'];
                    }
                }
                if (!empty($matched)) {
                    sort($matched);
                    return end($matched);
                }
            }
            return null;
        }
        if (strpos($query, 'COUNT(*)') !== false) {
            preg_match("/FROM ([^ ]+)/", $query, $m);
            $table = isset($m[1]) ? trim($m[1]) : '';
            if (isset($this->tables[$table])) {
                return count($this->tables[$table]);
            }
            return 0;
        }
        return null;
    }

    public function get_row($query, $output_type = OBJECT) {
        if (preg_match("/FROM ([^ ]+) WHERE (.+?)(?:LIMIT|$)/i", $query, $m)) {
            $table = trim($m[1]);
            $whereClause = trim($m[2]);
            if (isset($this->tables[$table])) {
                foreach ($this->tables[$table] as $row) {
                    if (preg_match("/paypal_transaction_id = '([^']+)'/", $whereClause, $wm)) {
                        if (isset($row['paypal_transaction_id']) && $row['paypal_transaction_id'] === $wm[1]) {
                            if (preg_match("/receipt_type = '([^']+)'/", $whereClause, $rtm)) {
                                if (isset($row['receipt_type']) && $row['receipt_type'] === $rtm[1]) {
                                    return ($output_type === ARRAY_A) ? $row : (object) $row;
                                }
                            } else {
                                return ($output_type === ARRAY_A) ? $row : (object) $row;
                            }
                        }
                    }
                    if (preg_match("/session_id = '([^']+)'/", $whereClause, $wm)) {
                        if (isset($row['session_id']) && $row['session_id'] === $wm[1]) {
                            return ($output_type === ARRAY_A) ? $row : (object) $row;
                        }
                    }
                    if (preg_match("/paypal_subscription_id = '([^']+)'/", $whereClause, $wm)) {
                        if (isset($row['paypal_subscription_id']) && $row['paypal_subscription_id'] === $wm[1]) {
                            return ($output_type === ARRAY_A) ? $row : (object) $row;
                        }
                    }
                    if (preg_match("/receipt_number = '([^']+)'/", $whereClause, $wm)) {
                        if (isset($row['receipt_number']) && $row['receipt_number'] === $wm[1]) {
                            return ($output_type === ARRAY_A) ? $row : (object) $row;
                        }
                    }
                    if (preg_match("/id = (\d+)/", $whereClause, $wm)) {
                        if (isset($row['id']) && (int)$row['id'] === (int)$wm[1]) {
                            return ($output_type === ARRAY_A) ? $row : (object) $row;
                        }
                    }
                }
            }
        }
        return null;
    }

    public function get_results($query, $output_type = OBJECT) {
        if (preg_match("/FROM ([^ ]+)/i", $query, $m)) {
            $table = trim($m[1]);
            if (isset($this->tables[$table])) {
                $rows = [];
                foreach ($this->tables[$table] as $r) {
                    if (strpos($query, "status = 'pending'") !== false && isset($r['status']) && $r['status'] !== 'pending') {
                        continue;
                    }
                    $rows[] = ($output_type === ARRAY_A) ? $r : (object) $r;
                }
                return $rows;
            }
        }
        return [];
    }
}
global $wpdb;
$wpdb = new MockWPDB();

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {}
}

// Require PayPal Architecture modules
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/class-vies-client.php';
require_once __DIR__ . '/class-tax-decision-engine.php';
require_once __DIR__ . '/class-checkout-session.php';
require_once __DIR__ . '/class-paypal-rest.php';
require_once __DIR__ . '/class-paypal-api.php';
require_once __DIR__ . '/class-pdf-engine.php';
require_once __DIR__ . '/class-receipt-pdf.php';
require_once __DIR__ . '/class-receipt-mailer.php';
require_once __DIR__ . '/class-receipt-manager.php';
require_once __DIR__ . '/class-receipt-queue.php';

// Test Runner
class I4ware_Test_Suite
{
    private $passes = 0;
    private $fails = 0;

    public function assert($condition, $test_name, $details = '')
    {
        if ($condition) {
            $this->passes++;
            echo "  [\033[32mPASS\033[0m] $test_name\n";
        } else {
            $this->fails++;
            echo "  [\033[31mFAIL\033[0m] $test_name" . ($details ? ": $details" : "") . "\n";
        }
    }

    public function run()
    {
        echo "\n=================================================================\n";
        echo " i4ware PayPal Subscriptions & VAT Architecture Automated Test Suite\n";
        echo "=================================================================\n\n";

        $this->test_1_finnish_company();
        $this->test_2_other_eu_company_valid_and_invalid();
        $this->test_3_vies_outage_simulation();
        $this->test_4_non_eu_company_without_eu_vat();
        $this->test_5_non_eu_company_with_eu_vat_atlassian_case();
        $this->test_6_valid_vat_divergent_company_name();
        $this->test_7_private_individuals();
        $this->test_8_api_bypass_attempt();
        $this->test_9_customer_data_or_price_tampering();
        $this->test_10_paypal_sandbox_webhook_idempotency();
        $this->test_11_gratuitous_support_disabled_by_default();
        $this->test_12_cookie_check_protection();
        $this->test_13_vies_network_and_error_classification();
        $this->test_14_commercial_advertising_service();
        $this->test_15_checkbox_isolation_and_foreign_tax();
        $this->test_16_paypal_webhook_receipt_generation_and_idempotency();
        $this->test_17_credit_notes_and_refunds();
        $this->test_18_smtp_failure_and_queue_retries();
        $this->test_19_pdf_encoding_taxes_and_umlauts();

        echo "\n-----------------------------------------------------------------\n";
        echo " Test Results: Total " . ($this->passes + $this->fails) . " | ";
        echo "\033[32m" . $this->passes . " Passed\033[0m | ";
        if ($this->fails > 0) {
            echo "\033[31m" . $this->fails . " Failed\033[0m\n";
        } else {
            echo "0 Failed\n";
        }
        echo "=================================================================\n\n";

        return ($this->fails === 0);
    }

    /**
     * 1. Finnish company (Suomen verollinen myynti 25.5 %)
     */
    private function test_1_finnish_company()
    {
        echo "Test Suite 1: Suomalainen yritys (Finnish Company)\n";

        $input = [
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Esimerkki Yritys Oy',
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'business_id' => '1234567-8',
            'vat_registered' => 'yes',
            'vat_country' => 'FI',
            'vat_number' => '12345678',
            'base_amount' => 100.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];

        $res = I4ware_Tax_Decision_Engine::evaluate($input);

        $this->assert($res['status'] === 'approved', 'Finnish business status is approved');
        $this->assert($res['tax_classification'] === I4WARE_TAX_FI_DOMESTIC_TAXABLE, 'Tax classification is FI_DOMESTIC_TAXABLE');
        $this->assert($res['vat_rate'] === 25.5, 'VAT rate is standard 25.5%');
        $this->assert($res['vat_amount'] === 25.50, 'VAT amount calculated accurately as 25.50 EUR');
        $this->assert($res['gross_amount'] === 125.50, 'Gross amount is 125.50 EUR');
        $this->assert($res['can_proceed_to_paypal'] === true, 'Can proceed to PayPal checkout');
    }

    /**
     * 2. Other EU company with valid and invalid VAT ID
     */
    private function test_2_other_eu_company_valid_and_invalid()
    {
        echo "\nTest Suite 2: Muu EU-yritys (Other EU Company)\n";

        // Case 2A: Valid German VAT ID (VIES returns VALID and matching name)
        $valid_vies = [
            'status' => I4WARE_VIES_VALID,
            'is_valid' => true,
            'country_code' => 'DE',
            'vat_number' => '122268496',
            'consultation_number' => 'WAPIAAAAYua2Xl4O',
            'name' => 'Musterfirma GmbH',
            'name_match_status' => 'matched'
        ];

        $input_valid = [
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Musterfirma GmbH',
            'establishment_country' => 'DE',
            'billing_country' => 'DE',
            'vat_registered' => 'yes',
            'vat_country' => 'DE',
            'vat_number' => '122268496',
            'vies_result' => $valid_vies,
            'base_amount' => 100.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];

        $res_valid = I4ware_Tax_Decision_Engine::evaluate($input_valid);
        $this->assert($res_valid['status'] === 'approved', 'Valid EU company status is approved');
        $this->assert($res_valid['tax_classification'] === I4WARE_TAX_EU_REVERSE_CHARGE, 'EU B2B classification is EU_REVERSE_CHARGE (AVL 65 § / Direktiivi 2006/112/EY art. 44)');
        $this->assert($res_valid['vat_rate'] === 0.0, 'VAT rate is 0.0% under reverse charge');
        $this->assert($res_valid['gross_amount'] === 100.00, 'Gross amount equals net 100.00 EUR');
        $this->assert(strpos($res_valid['tax_note'], 'Käännetty verovelvollisuus') !== false, 'Statutory reverse charge note included');

        // Case 2B: Invalid EU VAT ID
        $invalid_vies = [
            'status' => I4WARE_VIES_INVALID,
            'is_valid' => false,
            'country_code' => 'DE',
            'vat_number' => '999999999',
            'consultation_number' => '',
            'name' => '',
            'name_match_status' => 'invalid'
        ];

        $input_invalid = $input_valid;
        $input_invalid['vat_number'] = '999999999';
        $input_invalid['vies_result'] = $invalid_vies;

        $res_invalid = I4ware_Tax_Decision_Engine::evaluate($input_invalid);
        $this->assert($res_invalid['status'] === 'invalid', 'Invalid EU VAT number is rejected');
        $this->assert($res_invalid['can_proceed_to_paypal'] === false, 'Cannot proceed to PayPal with invalid VAT number');
    }

    /**
     * 3. VIES Service Outage / Timeout Simulation
     */
    private function test_3_vies_outage_simulation()
    {
        echo "\nTest Suite 3: VIES-palvelukatko (VIES Outage Simulation)\n";

        $outage_vies = [
            'status' => I4WARE_VIES_SERVICE_UNAVAILABLE,
            'is_valid' => false,
            'country_code' => 'FR',
            'vat_number' => '12345678901',
            'consultation_number' => '',
            'name' => '',
            'name_match_status' => 'service_unreachable',
            'message' => 'Tarkistuspalvelu ei ole juuri nyt käytettävissä. Yritä uudelleen.'
        ];

        $input = [
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Paris Tech SARL',
            'establishment_country' => 'FR',
            'billing_country' => 'FR',
            'vat_registered' => 'yes',
            'vat_country' => 'FR',
            'vat_number' => '12345678901',
            'vies_result' => $outage_vies,
            'base_amount' => 100.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];

        $res = I4ware_Tax_Decision_Engine::evaluate($input);
        $this->assert($res['status'] === 'requires_manual_review', 'VIES outage routes order to manual review queue');
        $this->assert($res['can_proceed_to_paypal'] === false, 'Checkout blocked from auto-approving 0% VAT during outage');
        $this->assert($res['requires_manual_review'] === true, 'Flagged as requiring administrator review');
    }

    /**
     * 4. Non-EU company without EU VAT ID (Veroton palvelumyynti AVL 65 §)
     */
    private function test_4_non_eu_company_without_eu_vat()
    {
        echo "\nTest Suite 4: EU:n ulkopuolinen yritys (Non-EU Company)\n";

        $input = [
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Acme Cloud Inc.',
            'establishment_country' => 'US',
            'billing_country' => 'US',
            'billing_address' => '100 Main St, San Francisco, CA 94105',
            'business_id' => 'EIN 12-3456789',
            'vat_registered' => 'no',
            'base_amount' => 200.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];

        $res = I4ware_Tax_Decision_Engine::evaluate($input);
        $this->assert($res['status'] === 'approved', 'Non-EU company with business ID approved');
        $this->assert($res['tax_classification'] === I4WARE_TAX_NON_EU_EXPORT, 'Classified as NON_EU_EXPORT (AVL 65 §)');
        $this->assert($res['vat_rate'] === 0.0, 'VAT rate is 0.0%');
        $this->assert($res['gross_amount'] === 200.00, 'Gross amount is 200.00 EUR');
        $this->assert(strpos($res['tax_note'], 'EU:n ulkopuolelle') !== false, 'Statutory non-EU export note included');
    }

    /**
     * 5. Non-EU company with EU VAT ID (The Atlassian-type case)
     */
    private function test_5_non_eu_company_with_eu_vat_atlassian_case()
    {
        echo "\nTest Suite 5: Atlassian-tyyppinen erikoistapaus (Non-EU with EU VAT ID)\n";

        // A US company provides an Irish VAT ID, but has not proven receiving fixed establishment in IE
        $input = [
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Global SaaS Corp (US parent)',
            'establishment_country' => 'US', // Established in USA
            'vat_country' => 'IE',           // VAT registered in Ireland!
            'vat_number' => 'IE1234567T',
            'fixed_establishment_country' => '', // No verified receiving fixed establishment
            'base_amount' => 500.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];

        $res = I4ware_Tax_Decision_Engine::evaluate($input);
        $this->assert($res['status'] === 'requires_manual_review', 'Non-EU domicile with EU VAT ID redirected to manual review');
        $this->assert($res['can_proceed_to_paypal'] === false, 'Cannot automatically proceed to PayPal without fixed establishment clarification');
        $this->assert(strpos($res['reason'], 'kiinteään toimipaikkaan vaatii ylläpidon manuaalisen selvityksen') !== false, 'Reason specifies requirement to clarify receiving fixed establishment');
    }

    /**
     * 6. Valid VAT ID with divergent company name
     */
    private function test_6_valid_vat_divergent_company_name()
    {
        echo "\nTest Suite 6: Nimen vastaavuuden tarkistus (Name Match Analysis)\n";

        // Test name analyzer: user says "Acme Digital Finland Oy", VIES says "Zeta Logistik GmbH"
        $analysis = I4ware_VIES_Client::analyze_name_address_match(
            'Acme Digital Finland Oy',
            'Mannerheimintie 1, Helsinki',
            'Zeta Logistik GmbH',
            'Hauptstrasse 4, Berlin'
        );

        $this->assert($analysis['match_status'] === 'mismatch', 'Divergent company names correctly detected as mismatch');

        // Test with withheld name (Germany / Spain '---' privacy suppression)
        $withheld_analysis = I4ware_VIES_Client::analyze_name_address_match(
            'Acme Digital GmbH',
            'Berlin',
            '---',
            '---'
        );
        $this->assert($withheld_analysis['match_status'] === 'not_provided_by_member_state', 'Withheld name ("---") is NOT flagged as error');
    }

    /**
     * 7. Private individuals (No business ID or VAT ID demanded)
     */
    private function test_7_private_individuals()
    {
        echo "\nTest Suite 7: Yksityishenkilöt (Private Individuals)\n";

        // 7A: Finnish Consumer
        $res_fi = I4ware_Tax_Decision_Engine::evaluate([
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_INDIVIDUAL,
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'base_amount' => 50.00,
            'lang' => 'fi'
        ]);
        $this->assert($res_fi['status'] === 'approved' && $res_fi['vat_rate'] === 25.5, 'Finnish individual charged 25.5% Finnish VAT');
        $this->assert($res_fi['gross_amount'] === 62.75, 'Gross amount for 50€ is 62.75€');

        // 7B: EU Consumer (Sweden)
        $res_eu = I4ware_Tax_Decision_Engine::evaluate([
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_INDIVIDUAL,
            'establishment_country' => 'SE',
            'billing_country' => 'SE',
            'base_amount' => 50.00,
            'lang' => 'fi'
        ]);
        $this->assert($res_eu['tax_classification'] === I4WARE_TAX_EU_CONSUMER_TAXABLE, 'EU individual classified as EU_CONSUMER_TAXABLE');
        $this->assert($res_eu['vat_rate'] === 25.5, 'EU individual charged standard rate without reverse charge');

        // 7C: Non-EU Consumer (USA)
        $res_us = I4ware_Tax_Decision_Engine::evaluate([
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_INDIVIDUAL,
            'establishment_country' => 'US',
            'billing_country' => 'US',
            'base_amount' => 50.00,
            'lang' => 'fi'
        ]);
        $this->assert($res_us['tax_classification'] === I4WARE_TAX_NON_EU_CONSUMER, 'Non-EU consumer outside Finnish VAT scope');
        $this->assert($res_us['vat_rate'] === 0.0, 'Non-EU consumer VAT rate is 0.0%');
    }

    /**
     * 8. Direct API bypass attempt / forged token
     */
    private function test_8_api_bypass_attempt()
    {
        echo "\nTest Suite 8: API-ohitusyrityksen torjunta (Direct API Bypass Protection)\n";

        $forged_token = 'eyJzZXNzaW9uX2lkIjoiZmFrZSJ9.forged_signature_12345';
        $verified = I4ware_Checkout_Session::verify_token($forged_token);
        $this->assert($verified === false, 'Forged token rejected by cryptographic verification');

        $empty_token = '';
        $verified_empty = I4ware_Checkout_Session::verify_token($empty_token);
        $this->assert($verified_empty === false, 'Empty token rejected');
    }

    /**
     * 9. Tampering with customer data or price after verification (HMAC signature mismatch)
     */
    private function test_9_customer_data_or_price_tampering()
    {
        echo "\nTest Suite 9: Tietojen peukalointiyritys (Tampering Detection)\n";

        $original_data = [
            'plan_id' => 'P-TEST-100',
            'item_name' => 'Enterprise Supporter',
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Legit Company Oy',
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'net_amount' => 250.00,
            'vat_rate' => 25.5,
            'vat_amount' => 63.75,
            'gross_amount' => 313.75,
            'currency' => 'EUR',
            'tax_classification' => I4WARE_TAX_FI_DOMESTIC_TAXABLE,
            'tax_note' => 'Suomen arvonlisävero 25,5 %'
        ];

        $session = I4ware_Checkout_Session::create_session($original_data);
        $valid_token = $session['token'];

        // Token verifies cleanly
        $verified = I4ware_Checkout_Session::verify_token($valid_token);
        $this->assert(is_array($verified) && $verified['gross_amount'] === 313.75, 'Legitimate token verifies with exact gross amount');

        // Attacker alters payload (e.g. changes gross amount from 313.75 to 1.00)
        $parts = explode('.', $valid_token);
        $tampered_payload = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $tampered_payload['gross_amount'] = 1.00;
        $tampered_encoded = rtrim(strtr(base64_encode(json_encode($tampered_payload)), '+/', '-_'), '=');
        $tampered_token = $tampered_encoded . '.' . $parts[1]; // Kept old signature

        $tamper_verified = I4ware_Checkout_Session::verify_token($tampered_token);
        $this->assert($tamper_verified === false, 'Tampered price token rejected due to cryptographic HMAC signature mismatch');
    }

    /**
     * 10. PayPal Webhook signature verification and idempotency
     */
    private function test_10_paypal_sandbox_webhook_idempotency()
    {
        echo "\nTest Suite 10: Webhook-varmennus & Idempotenttius (Webhook Verification & Idempotency)\n";

        $mock_event_id = 'WH-EVENT-' . uniqid();
        $mock_payload = json_encode([
            'id' => $mock_event_id,
            'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
            'resource' => [
                'id' => 'I-SUB123456789',
                'status' => 'ACTIVE'
            ]
        ]);

        $mock_headers = [
            'paypal-auth-algo' => 'SHA256withRSA',
            'paypal-cert-url' => 'https://api.paypal.com/certs/mock.pem',
            'paypal-transmission-id' => 'TRANS-12345',
            'paypal-transmission-sig' => 'VALID_MOCK_SIG',
            'paypal-transmission-time' => gmdate('Y-m-d\TH:i:s\Z')
        ];

        // First delivery: processes successfully
        $res1 = I4ware_PayPal_REST::process_webhook_event($mock_headers, $mock_payload);
        $this->assert($res1['success'] === true && $res1['code'] === 200, 'Webhook first delivery processed successfully (200 OK)');

        // Second delivery (duplicate): recognized idempotently without double-processing
        $res2 = I4ware_PayPal_REST::process_webhook_event($mock_headers, $mock_payload);
        $this->assert($res2['success'] === true && strpos($res2['message'], 'idempotentti') !== false, 'Webhook redelivery handled idempotently without re-processing');
    }

    /**
     * 11. Gratuitous support disabled by default under Money Collection Act (Rahankeräyslaki)
     */
    private function test_11_gratuitous_support_disabled_by_default()
    {
        echo "\nTest Suite 11: Vastikkeettoman tuen esto oletuksena (Rahankeräyslaki Compliance)\n";

        // Admin setting is default false
        update_option('i4ware_allow_gratuitous_support', false);

        $input = [
            'payment_type' => I4WARE_PAYMENT_GRATUITOUS_SUPPORT,
            'customer_type' => I4WARE_CUSTOMER_INDIVIDUAL,
            'base_amount' => 10.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];

        $res_disabled = I4ware_Tax_Decision_Engine::evaluate($input);
        $this->assert($res_disabled['status'] === 'blocked', 'Gratuitous support blocked when setting is disabled');
        $this->assert($res_disabled['can_proceed_to_paypal'] === false, 'Cannot proceed to PayPal for gratuitous support');
        $this->assert(strpos($res_disabled['user_message'], 'rahankeräyslain') !== false, 'Message refers to Rahankeräyslaki');

        // Test when administrator explicitly enables gratuitous support
        update_option('i4ware_allow_gratuitous_support', true);
        $res_enabled = I4ware_Tax_Decision_Engine::evaluate($input);
        $this->assert($res_enabled['status'] === 'approved', 'Gratuitous support approved when explicitly enabled by admin');
        $this->assert($res_enabled['tax_classification'] === I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS, 'Gratuitous support classified as OUTSIDE_SCOPE_GRATUITOUS (NOT "ALV 0%")');
        $this->assert($res_enabled['vat_rate'] === null, 'VAT rate is null (outside tax scope), not 0%');

        // Reset to default false
        update_option('i4ware_allow_gratuitous_support', false);
    }

    /**
     * 12. Protection against "Cookie check failed" (rest_cookie_invalid_nonce)
     */
    private function test_12_cookie_check_protection()
    {
        echo "\nTest Suite 12: Evästetarkistuksen suojaus (Cookie Check Protection)\n";

        $cookie_error = new WP_Error('rest_cookie_invalid_nonce', 'Cookie check failed', ['status' => 403]);

        // Scenario A: Request to validate-checkout endpoint with invalid/missing cookie nonce
        $_SERVER['REQUEST_URI'] = '/wp-json/i4ware-paypal/v1/validate-checkout';
        $filtered = I4ware_PayPal_API::filter_rest_auth_errors($cookie_error);
        $this->assert($filtered === null, 'Cookie check failure cleared for validate-checkout (guest checkout permitted)');

        // Scenario B: Request to confirm-subscription endpoint
        $_SERVER['REQUEST_URI'] = '/wp-json/i4ware-paypal/v1/confirm-subscription';
        $filtered_sub = I4ware_PayPal_API::filter_rest_auth_errors($cookie_error);
        $this->assert($filtered_sub === null, 'Cookie check failure cleared for confirm-subscription');

        // Scenario C: Request to another unrelated WP REST API route (e.g. /wp/v2/posts)
        $_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/posts';
        $unrelated = I4ware_PayPal_API::filter_rest_auth_errors($cookie_error);
        $this->assert(is_wp_error($unrelated) && $unrelated->get_error_code() === 'rest_cookie_invalid_nonce', 'Cookie check failure preserved for core WordPress admin routes');

        // Scenario D: Clean response without errors
        $_SERVER['REQUEST_URI'] = '/wp-json/i4ware-paypal/v1/validate-checkout';
        $clean = I4ware_PayPal_API::filter_rest_auth_errors(true);
        $this->assert($clean === true, 'Valid authentication state preserved cleanly');
    }

    /**
     * 13. VIES Network Diagnostics, Timeout, Retry & Error Categorization
     */
    private function test_13_vies_network_and_error_classification()
    {
        echo "\nTest Suite 13: VIES-verkkodiagnostiikka, aikakatkaisu ja virheluokittelu\n";

        // 13A: Tax Decision Engine routing on LOCAL_NETWORK_ERROR
        $net_err_vies = [
            'status' => I4WARE_VIES_LOCAL_NETWORK_ERROR,
            'is_valid' => false,
            'country_code' => 'DE',
            'vat_number' => '122268496',
            'consultation_number' => '',
            'name' => '',
            'name_match_status' => 'local_network_error',
            'error_category' => 'local_network_error',
            'error_code' => 'http_request_failed:timeout',
            'error_detail' => 'cURL error 28: Operation timed out'
        ];
        $input_net_err = [
            'payment_type' => I4WARE_PAYMENT_SOFTWARE_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Berlin Tech GmbH',
            'establishment_country' => 'DE',
            'billing_country' => 'DE',
            'vat_registered' => 'yes',
            'vat_country' => 'DE',
            'vat_number' => '122268496',
            'vies_result' => $net_err_vies,
            'base_amount' => 100.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];
        $res_net_err = I4ware_Tax_Decision_Engine::evaluate($input_net_err);
        $this->assert($res_net_err['status'] === 'requires_manual_review', 'Local network error routes to manual review');
        $this->assert($res_net_err['can_proceed_to_paypal'] === false, 'Cannot proceed to PayPal on local network error (no 0% auto-approval)');
        $this->assert($res_net_err['requires_manual_review'] === true, 'Flagged as requiring admin review on network error');

        // 13B: Tax Decision Engine routing on API_ERROR
        $api_err_vies = [
            'status' => I4WARE_VIES_API_ERROR,
            'is_valid' => false,
            'country_code' => 'DE',
            'vat_number' => '122268496',
            'consultation_number' => '',
            'name' => '',
            'name_match_status' => 'api_error',
            'error_category' => 'api_request_error',
            'error_code' => 'INVALID_INPUT'
        ];
        $input_api_err = $input_net_err;
        $input_api_err['vies_result'] = $api_err_vies;
        $res_api_err = I4ware_Tax_Decision_Engine::evaluate($input_api_err);
        $this->assert($res_api_err['status'] === 'requires_manual_review', 'API error routes to manual review');
        $this->assert($res_api_err['can_proceed_to_paypal'] === false, 'Cannot proceed to PayPal on API error');

        // 13C: VIES Client Bounded Retry & Timeout Handling
        $GLOBALS['_wp_remote_post_calls'] = [];
        $GLOBALS['_wp_remote_post_mock'] = function($url, $args) {
            return new WP_Error('http_request_failed', 'cURL error 28: Operation timed out after 12001 milliseconds with 0 bytes received');
        };

        $vies_timeout_res = I4ware_VIES_Client::check_vat('DE', '122268496', 'Berlin Tech GmbH', '', 12);
        $this->assert($vies_timeout_res['status'] === I4WARE_VIES_LOCAL_NETWORK_ERROR, 'Timeout categorized as LOCAL_NETWORK_ERROR');
        $this->assert($vies_timeout_res['error_code'] === 'http_request_failed:timeout', 'Error code specifies timeout subtype');
        $this->assert(count($GLOBALS['_wp_remote_post_calls']) === 2, 'Bounded retry performed exactly 2 attempts');
        $this->assert($GLOBALS['_wp_remote_post_calls'][0]['args']['sslverify'] === true, 'TLS/SSL verification is strictly enabled (sslverify = true)');

        // 13D: DNS failure classification
        $GLOBALS['_wp_remote_post_calls'] = [];
        $GLOBALS['_wp_remote_post_mock'] = function($url, $args) {
            return new WP_Error('http_request_failed', 'cURL error 6: Could not resolve host: ec.europa.eu');
        };
        $vies_dns_res = I4ware_VIES_Client::check_vat('DE', '122268496', 'Berlin Tech GmbH', '', 12);
        $this->assert($vies_dns_res['error_code'] === 'http_request_failed:dns_failure', 'DNS resolution failure categorized accurately');

        // 13E: TLS Certificate failure classification
        $GLOBALS['_wp_remote_post_calls'] = [];
        $GLOBALS['_wp_remote_post_mock'] = function($url, $args) {
            return new WP_Error('http_request_failed', 'cURL error 60: SSL certificate problem: certificate has expired');
        };
        $vies_tls_res = I4ware_VIES_Client::check_vat('DE', '122268496', 'Berlin Tech GmbH', '', 12);
        $this->assert($vies_tls_res['error_code'] === 'http_request_failed:tls_certificate_error', 'TLS certificate problem categorized accurately');

        // 13F: INVALID_REQUESTER_INFO fallback retry without requester
        $GLOBALS['_wp_remote_post_calls'] = [];
        $call_count = 0;
        $GLOBALS['_wp_remote_post_mock'] = function($url, $args) use (&$call_count) {
            $call_count++;
            if ($call_count === 1) {
                // First call with requester fails
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'actionSucceed' => false,
                        'errorWrappers' => [['error' => 'INVALID_REQUESTER_INFO']]
                    ])
                ];
            }
            // Second call without requester succeeds
            return [
                'response' => ['code' => 200],
                'body' => json_encode([
                    'isValid' => true,
                    'valid' => true,
                    'countryCode' => 'DE',
                    'vatNumber' => '122268496',
                    'name' => 'Berlin Tech GmbH',
                    'address' => 'Alexanderplatz 1, Berlin',
                    'requestIdentifier' => 'TEST_CONSULT_12345'
                ])
            ];
        };
        $vies_fallback_res = I4ware_VIES_Client::check_vat('DE', '122268496', 'Berlin Tech GmbH', '', 12);
        $this->assert($vies_fallback_res['status'] === I4WARE_VIES_VALID, 'Fallback succeeded after INVALID_REQUESTER_INFO');
        $this->assert($vies_fallback_res['is_valid'] === true, 'VAT confirmed valid on fallback attempt');
        $first_payload = json_decode($GLOBALS['_wp_remote_post_calls'][0]['args']['body'], true);
        $second_payload = json_decode($GLOBALS['_wp_remote_post_calls'][1]['args']['body'], true);
        $this->assert(isset($first_payload['requesterMemberStateCode']), 'First call included requester member state');
        $this->assert(!isset($second_payload['requesterMemberStateCode']), 'Second fallback call omitted requester member state');

        // 13G: VIES Service Outage (MS_UNAVAILABLE)
        $GLOBALS['_wp_remote_post_calls'] = [];
        $GLOBALS['_wp_remote_post_mock'] = function($url, $args) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode([
                    'actionSucceed' => false,
                    'errorWrappers' => [['error' => 'MS_UNAVAILABLE']]
                ])
            ];
        };
        $vies_outage_res = I4ware_VIES_Client::check_vat('DE', '122268496', 'Berlin Tech GmbH', '', 12);
        $this->assert($vies_outage_res['status'] === I4WARE_VIES_SERVICE_UNAVAILABLE, 'MS_UNAVAILABLE categorized as SERVICE_UNAVAILABLE');
        $this->assert($vies_outage_res['error_category'] === 'vies_outage', 'Error category is vies_outage');
        $this->assert($vies_outage_res['error_code'] === 'MS_UNAVAILABLE', 'Error code is MS_UNAVAILABLE');

        // 13H: Malformed request (INVALID_INPUT)
        $GLOBALS['_wp_remote_post_calls'] = [];
        $GLOBALS['_wp_remote_post_mock'] = function($url, $args) {
            return [
                'response' => ['code' => 200],
                'body' => json_encode([
                    'actionSucceed' => false,
                    'errorWrappers' => [['error' => 'INVALID_INPUT']]
                ])
            ];
        };
        $vies_invalid_res = I4ware_VIES_Client::check_vat('DE', '122268496', 'Berlin Tech GmbH', '', 12);
        $this->assert($vies_invalid_res['status'] === I4WARE_VIES_INVALID, 'INVALID_INPUT categorized as INVALID');
        $this->assert($vies_invalid_res['error_category'] === 'api_request_error', 'Error category is api_request_error');
        $this->assert($vies_invalid_res['error_code'] === 'INVALID_INPUT', 'Error code is INVALID_INPUT');
    }

    /**
     * 14. Commercial Advertising Service & Finnish VAT 25.5% (AVL 1 §)
     *
     * Validates:
     * - Finnish company with valid FI VAT ID is charged 25.5% VAT (no reverse charge domestic)
     * - Finnish individual is charged 25.5% VAT
     * - Fixed duration (30 days for one-time) vs continuous monthly subscription
     * - Net price, 25.5% VAT, and gross amount calculation
     * - Checkout session HMAC token integrity with ad metadata
     */
    private function test_14_commercial_advertising_service()
    {
        echo "\nTest Suite 14: Kaupallinen mainospalvelu ja Suomen ALV 25,5 % (AVL 1 §)\n";

        // Case 14A: Finnish company with valid FI VAT ID
        $input_fi_biz = [
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Mainostaja Suomi Oy',
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'business_id' => '1234567-8',
            'vat_registered' => 'yes',
            'vat_country' => 'FI',
            'vat_number' => 'FI12345678',
            'base_amount' => 50.00,
            'billing_mode' => 'monthly',
            'currency' => 'EUR',
            'lang' => 'fi'
        ];

        $res_fi_biz = I4ware_Tax_Decision_Engine::evaluate($input_fi_biz);

        $this->assert($res_fi_biz['status'] === 'approved', 'Finnish ad advertiser business is approved');
        $this->assert($res_fi_biz['tax_classification'] === I4WARE_TAX_FI_DOMESTIC_TAXABLE, 'Domestic ad classified as FI_DOMESTIC_TAXABLE');
        $this->assert($res_fi_biz['vat_rate'] === 25.5, 'Finnish business is charged 25.5% VAT despite valid FI VAT ID (AVL 1 §)');
        $this->assert($res_fi_biz['vat_amount'] === 12.75, 'VAT amount is accurately 12.75 EUR for 50.00 EUR net');
        $this->assert($res_fi_biz['gross_amount'] === 62.75, 'Total payable is 62.75 EUR');
        $this->assert($res_fi_biz['can_proceed_to_paypal'] === true, 'Can proceed to PayPal checkout');
        $this->assert(strpos($res_fi_biz['tax_note'], '25,5 %') !== false, 'Statutory 25.5% tax note displayed');

        // Case 14B: Finnish individual customer ordering 10€ ad slot
        $input_fi_ind = [
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_INDIVIDUAL,
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'base_amount' => 10.00,
            'billing_mode' => 'one_time',
            'currency' => 'EUR',
            'lang' => 'fi'
        ];

        $res_fi_ind = I4ware_Tax_Decision_Engine::evaluate($input_fi_ind);
        $this->assert($res_fi_ind['status'] === 'approved', 'Finnish individual ad buyer approved');
        $this->assert($res_fi_ind['vat_rate'] === 25.5, 'Finnish individual charged standard 25.5% VAT');
        $this->assert($res_fi_ind['vat_amount'] === 2.55, 'VAT amount is 2.55 EUR for 10.00 EUR net');
        $this->assert($res_fi_ind['gross_amount'] === 12.55, 'Gross total is 12.55 EUR (no double taxation)');

        // Case 14C: Session HMAC creation and preservation of advertising specifications
        $ad_session_data = [
            'plan_id' => 'P-AD-PRONSSI',
            'item_name' => 'Pronssi-mainospaikka',
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Mainostaja Suomi Oy',
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'net_amount' => 100.00,
            'vat_rate' => 25.5,
            'vat_amount' => 25.50,
            'gross_amount' => 125.50,
            'currency' => 'EUR',
            'tax_classification' => I4WARE_TAX_FI_DOMESTIC_TAXABLE,
            'tax_note' => 'Suomen arvonlisävero 25,5 % (AVL 1 §)',
            'billing_mode' => 'monthly',
            'ad_format' => 'banner',
            'ad_placement' => 'sidebar',
            'ad_target_url' => 'https://example.com/promo',
            'ad_copy' => 'Esimerkkimainos'
        ];

        $session = I4ware_Checkout_Session::create_session($ad_session_data);
        $token = $session['token'];
        $verified_ad = I4ware_Checkout_Session::verify_token($token);

        $this->assert(is_array($verified_ad), 'Ad checkout session verified with valid HMAC signature');
        $this->assert($verified_ad['billing_mode'] === 'monthly', 'Billing mode preserved in cryptographically secured session');
        $this->assert($verified_ad['ad_format'] === 'banner', 'Ad format preserved in session');
        $this->assert($verified_ad['ad_placement'] === 'sidebar', 'Ad placement preserved in session');
        $this->assert($verified_ad['gross_amount'] === 125.50, 'Verified gross amount matches exact calculation for PayPal');
    }

    /**
     * 15. Checkbox Decoupling & Foreign Sales Tax Separation
     *
     * Validates:
     * - i4ware_allow_gratuitous_support setting does NOT block commercial advertising
     * - i4ware_allow_gratuitous_support setting does NOT alter commercial advertising tax rate
     * - Foreign EU business sales require valid VIES check for 0% reverse charge
     * - Foreign Non-EU business sales are 0% export (AVL 65 §)
     */
    private function test_15_checkbox_isolation_and_foreign_tax()
    {
        echo "\nTest Suite 15: Checkboxin vaikutuksen eriyttäminen ja ulkomaanmyynnin verokäsittely\n";

        // Step 15A: Setting is disabled (default false)
        update_option('i4ware_allow_gratuitous_support', false);

        // Commercial advertising MUST NOT be blocked when gratuitous support is disabled!
        $ad_input = [
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Kotimainen Yritys Oy',
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'base_amount' => 50.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];
        $res_ad_isolated = I4ware_Tax_Decision_Engine::evaluate($ad_input);
        $this->assert($res_ad_isolated['status'] === 'approved', 'Advertising service is approved even when gratuitous support checkbox is FALSE');
        $this->assert($res_ad_isolated['can_proceed_to_paypal'] === true, 'Advertising checkout can proceed to PayPal when gratuitous support is FALSE');
        $this->assert($res_ad_isolated['vat_rate'] === 25.5, 'Finnish advertising is charged 25.5% ALV when checkbox is FALSE');

        // Gratuitous support IS blocked when setting is false
        $gratuitous_input = [
            'payment_type' => I4WARE_PAYMENT_GRATUITOUS_SUPPORT,
            'customer_type' => I4WARE_CUSTOMER_INDIVIDUAL,
            'base_amount' => 10.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];
        $res_grat_blocked = I4ware_Tax_Decision_Engine::evaluate($gratuitous_input);
        $this->assert($res_grat_blocked['status'] === 'blocked', 'Gratuitous support is blocked when checkbox is FALSE (Rahankeräyslaki)');

        // Step 15B: Setting is enabled by admin
        update_option('i4ware_allow_gratuitous_support', true);

        // Commercial advertising MUST STILL BE TAXED AT 25.5% (NEVER 0% / outside scope!)
        $res_ad_enabled = I4ware_Tax_Decision_Engine::evaluate($ad_input);
        $this->assert($res_ad_enabled['tax_classification'] === I4WARE_TAX_FI_DOMESTIC_TAXABLE, 'Advertising remains FI_DOMESTIC_TAXABLE when checkbox is TRUE');
        $this->assert($res_ad_enabled['vat_rate'] === 25.5, 'Advertising VAT rate remains 25.5% when checkbox is TRUE (AVL 1 §)');

        // Gratuitous support is approved outside tax scope
        $res_grat_approved = I4ware_Tax_Decision_Engine::evaluate($gratuitous_input);
        $this->assert($res_grat_approved['tax_classification'] === I4WARE_TAX_OUTSIDE_SCOPE_GRATUITOUS, 'Gratuitous support is OUTSIDE_SCOPE_GRATUITOUS when checkbox is TRUE');
        $this->assert($res_grat_approved['vat_rate'] === null, 'Gratuitous support VAT rate is null, distinct from commercial advertising');

        // Reset option to false
        update_option('i4ware_allow_gratuitous_support', false);

        // Step 15C: Foreign sales tax separation for advertising services
        // Other-EU B2B with valid VAT ID -> Reverse charge 0%
        $valid_se_vies = [
            'status' => I4WARE_VIES_VALID,
            'is_valid' => true,
            'country_code' => 'SE',
            'vat_number' => '556012345601',
            'consultation_number' => 'WAPIAAAAYua2XlSE',
            'name' => 'Nordic Ad AB',
            'name_match_status' => 'matched'
        ];
        $input_se_b2b = [
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Nordic Ad AB',
            'establishment_country' => 'SE',
            'billing_country' => 'SE',
            'vat_registered' => 'yes',
            'vat_country' => 'SE',
            'vat_number' => '556012345601',
            'vies_result' => $valid_se_vies,
            'base_amount' => 100.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];
        $res_se_b2b = I4ware_Tax_Decision_Engine::evaluate($input_se_b2b);
        $this->assert($res_se_b2b['tax_classification'] === I4WARE_TAX_EU_REVERSE_CHARGE, 'EU B2B advertising is EU_REVERSE_CHARGE (AVL 65 § / art. 44)');
        $this->assert($res_se_b2b['vat_rate'] === 0.0, 'EU B2B advertising VAT rate is 0.0%');
        $this->assert($res_se_b2b['gross_amount'] === 100.00, 'EU B2B advertising gross amount equals net 100.00 EUR');

        // Non-EU B2B (USA) -> 0% export
        $input_us_b2b = [
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Silicon Valley Ads LLC',
            'establishment_country' => 'US',
            'billing_country' => 'US',
            'billing_address' => '500 Howard St, San Francisco, CA',
            'business_id' => 'US-EIN-987654321',
            'vat_registered' => 'no',
            'base_amount' => 100.00,
            'currency' => 'EUR',
            'lang' => 'fi'
        ];
        $res_us_b2b = I4ware_Tax_Decision_Engine::evaluate($input_us_b2b);
        $this->assert($res_us_b2b['tax_classification'] === I4WARE_TAX_NON_EU_EXPORT, 'Non-EU advertising classified as NON_EU_EXPORT (AVL 65 §)');
        $this->assert($res_us_b2b['vat_rate'] === 0.0, 'Non-EU advertising VAT rate is 0.0%');
        $this->assert($res_us_b2b['gross_amount'] === 100.00, 'Non-EU advertising gross amount is 100.00 EUR');
    }

    /**
     * 16. PayPal Webhook - Payment Confirmation, PDF Receipt & Idempotency
     */
    private function test_16_paypal_webhook_receipt_generation_and_idempotency()
    {
        echo "\nTest Suite 16: PayPal Webhook - Maksun vahvistus, kuitti ja idempotenttius\n";
        global $wpdb;

        // Clear mock mail and DB state for tests
        $GLOBALS['_wp_mail_calls'] = [];
        $GLOBALS['_wp_mail_mock'] = null;

        // Seed an order session in wp_i4ware_paypal_orders
        $wpdb->tables['wp_i4ware_paypal_orders'] = [];
        $wpdb->tables['wp_i4ware_receipts'] = [];
        $wpdb->tables['wp_i4ware_receipt_queue'] = [];
        $wpdb->tables['events'] = [];

        $wpdb->insert('wp_i4ware_paypal_orders', [
            'session_id' => 'SES-TEST-ONE-TIME',
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Mainostaja Suomi Oy',
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'billing_address' => 'Mannerheimintie 10, 00100 Helsinki',
            'vat_number' => 'FI12345678',
            'contact_name' => 'Teppo Testaaja',
            'contact_email' => 'teppo@mainostaja.fi',
            'plan_id' => 'P-AD-PRONSSI',
            'item_name' => 'Pronssi-mainospaikka',
            'net_amount' => 50.00,
            'vat_rate' => 25.5,
            'vat_amount' => 12.75,
            'gross_amount' => 62.75,
            'currency' => 'EUR',
            'tax_classification' => I4WARE_TAX_FI_DOMESTIC_TAXABLE,
            'tax_note' => 'Suomen arvonlisävero 25,5 % (AVL 1 §)',
            'status' => 'pending',
            'paypal_subscription_id' => '',
            'created_at_utc' => gmdate('Y-m-d H:i:s'),
            'updated_at_utc' => gmdate('Y-m-d H:i:s')
        ]);

        $mock_headers = [
            'paypal-transmission-id' => 'TRANS-RECEIPT-001',
            'paypal-transmission-sig' => 'VALID_SIG_MOCK'
        ];

        // 16A: One-time payment capture webhook
        $cap_payload = json_encode([
            'id' => 'EVT-CAP-001',
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP-2GG1234567',
                'status' => 'COMPLETED',
                'custom_id' => 'SES-TEST-ONE-TIME',
                'amount' => [
                    'value' => '62.75',
                    'currency_code' => 'EUR'
                ],
                'create_time' => gmdate('Y-m-d\TH:i:s\Z')
            ]
        ]);

        $res_cap = I4ware_PayPal_REST::process_webhook_event($mock_headers, $cap_payload);
        $this->assert($res_cap['success'] === true && $res_cap['code'] === 200, 'One-time capture webhook processed with 200 OK');

        // Verify receipt created
        $receipt = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE paypal_transaction_id = 'CAP-2GG1234567'", ARRAY_A);
        $this->assert(!empty($receipt), 'Receipt record created upon PAYMENT.CAPTURE.COMPLETED');
        $this->assert(strpos($receipt['receipt_number'], 'INV-') === 0, 'Sequential receipt number generated (' . $receipt['receipt_number'] . ')');
        $this->assert((float)$receipt['gross_amount'] === 62.75, 'Receipt displays exact gross amount paid without fee deduction');
        $this->assert(strpos($receipt['visibility_period'], '30 päivää') !== false, 'Receipt specifies 30-day fixed duration for one-time payment');
        $this->assert(file_exists($receipt['pdf_path']), 'PDF receipt file created and stored securely');

        // Verify email was sent with required text and attachment
        $this->assert(count($GLOBALS['_wp_mail_calls']) === 1, 'Receipt email sent to customer');
        $sent_mail = end($GLOBALS['_wp_mail_calls']);
        $this->assert($sent_mail['to'] === 'teppo@mainostaja.fi', 'Receipt sent to customer email');
        $this->assert(strpos($sent_mail['message'], 'Kiitos tilauksestasi. Liitteenä on i4ware Softwaren kuitti') !== false, 'Email body contains mandatory customer receipt text');
        $this->assert(strpos($sent_mail['message'], 'Maksua ei tarvitse suorittaa uudelleen') !== false, 'Email body confirms payment does not need to be repeated');
        $this->assert(count($sent_mail['attachments']) === 1 && strpos($sent_mail['attachments'][0], '.pdf') !== false, 'Official PDF attached to email');

        // 16B: Recurring subscription first payment (PAYMENT.SALE.COMPLETED)
        $wpdb->insert('wp_i4ware_paypal_orders', [
            'session_id' => 'SES-SUB-MONTHLY-001',
            'payment_type' => I4WARE_PAYMENT_ADVERTISING_SERVICE,
            'customer_type' => I4WARE_CUSTOMER_BUSINESS,
            'company_name' => 'Kestomainostaja Oy',
            'establishment_country' => 'FI',
            'billing_country' => 'FI',
            'billing_address' => 'Aleksanterinkatu 5, Helsinki',
            'vat_number' => 'FI87654321',
            'contact_name' => 'Kalle Kuukausi',
            'contact_email' => 'kalle@kestomainos.fi',
            'plan_id' => 'P-AD-HOPEA',
            'item_name' => 'Hopea-mainospaikka',
            'net_amount' => 100.00,
            'vat_rate' => 25.5,
            'vat_amount' => 25.50,
            'gross_amount' => 125.50,
            'currency' => 'EUR',
            'tax_classification' => I4WARE_TAX_FI_DOMESTIC_TAXABLE,
            'tax_note' => 'Suomen arvonlisävero 25,5 % (AVL 1 §)',
            'status' => 'pending',
            'paypal_subscription_id' => 'I-SUB-RECUR-001',
            'created_at_utc' => gmdate('Y-m-d H:i:s'),
            'updated_at_utc' => gmdate('Y-m-d H:i:s')
        ]);

        $sale_1_payload = json_encode([
            'id' => 'EVT-SALE-001',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource' => [
                'id' => 'SALE-TX-001',
                'status' => 'completed',
                'billing_agreement_id' => 'I-SUB-RECUR-001',
                'amount' => [
                    'total' => '125.50',
                    'currency' => 'EUR'
                ],
                'create_time' => gmdate('Y-m-d\TH:i:s\Z')
            ]
        ]);

        $res_sale1 = I4ware_PayPal_REST::process_webhook_event($mock_headers, $sale_1_payload);
        $this->assert($res_sale1['success'] === true, 'First recurring sale webhook processed');
        $receipt_sale1 = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE paypal_transaction_id = 'SALE-TX-001'", ARRAY_A);
        $this->assert(!empty($receipt_sale1), 'Receipt created for first subscription cycle');
        $this->assert($receipt_sale1['receipt_number'] === 'INV-2026-0002', 'Sequential receipt number incremented (INV-2026-0002)');
        $this->assert(strpos($receipt_sale1['visibility_period'], 'Kuukausitilaus, jakso:') !== false, 'Receipt describes monthly subscription billing cycle');

        // 16C: Recurring subscription renewal payment in subsequent month (PAYMENT.SALE.COMPLETED with new sale ID)
        $sale_2_payload = json_encode([
            'id' => 'EVT-SALE-002',
            'event_type' => 'PAYMENT.SALE.COMPLETED',
            'resource' => [
                'id' => 'SALE-TX-002', // New sale transaction ID
                'status' => 'completed',
                'billing_agreement_id' => 'I-SUB-RECUR-001', // Same subscription
                'amount' => [
                    'total' => '125.50',
                    'currency' => 'EUR'
                ],
                'create_time' => gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 month'))
            ]
        ]);

        $res_sale2 = I4ware_PayPal_REST::process_webhook_event($mock_headers, $sale_2_payload);
        $this->assert($res_sale2['success'] === true, 'Subscription renewal webhook processed');
        $receipt_sale2 = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE paypal_transaction_id = 'SALE-TX-002'", ARRAY_A);
        $this->assert(!empty($receipt_sale2), 'Separate receipt created for renewal payment cycle');
        $this->assert($receipt_sale2['receipt_number'] === 'INV-2026-0003', 'Sequential receipt number generated for renewal (INV-2026-0003)');
        $this->assert($receipt_sale2['receipt_number'] !== $receipt_sale1['receipt_number'], 'Renewal receipt is completely distinct from initial cycle receipt');

        // 16D: Idempotent duplicate webhook redelivery
        $mail_count_before = count($GLOBALS['_wp_mail_calls']);
        $res_dup = I4ware_PayPal_REST::process_webhook_event($mock_headers, $cap_payload);
        $this->assert($res_dup['success'] === true && strpos($res_dup['message'], 'idempotentti') !== false, 'Duplicate capture webhook recognized idempotently');
        $this->assert(count($GLOBALS['_wp_mail_calls']) === $mail_count_before, 'No duplicate receipt email sent on webhook redelivery');

        // 16E: Subscription activation alone does NOT generate a receipt
        $act_payload = json_encode([
            'id' => 'EVT-ACT-001',
            'event_type' => 'BILLING.SUBSCRIPTION.ACTIVATED',
            'resource' => [
                'id' => 'I-SUB-FRESH-001',
                'status' => 'ACTIVE'
            ]
        ]);
        I4ware_PayPal_REST::process_webhook_event($mock_headers, $act_payload);
        $no_receipt_act = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE paypal_subscription_id = 'I-SUB-FRESH-001'", ARRAY_A);
        $this->assert(empty($no_receipt_act), 'BILLING.SUBSCRIPTION.ACTIVATED alone does NOT create receipt (waits for SALE)');

        // 16F: Failed payment does NOT generate a paid receipt
        $fail_payload = json_encode([
            'id' => 'EVT-FAIL-001',
            'event_type' => 'PAYMENT.CAPTURE.DENIED',
            'resource' => [
                'id' => 'CAP-FAILED-001',
                'status' => 'DENIED',
                'custom_id' => 'SES-TEST-ONE-TIME'
            ]
        ]);
        I4ware_PayPal_REST::process_webhook_event($mock_headers, $fail_payload);
        $no_receipt_fail = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE paypal_transaction_id = 'CAP-FAILED-001'", ARRAY_A);
        $this->assert(empty($no_receipt_fail), 'Payment failure does NOT generate a paid receipt');
    }

    /**
     * 17. Credit Notes & Refunds (Hyvitystositteet)
     */
    private function test_17_credit_notes_and_refunds()
    {
        echo "\nTest Suite 17: Hyvitystositteet ja palautukset (Refunds & Credit Notes)\n";
        global $wpdb;

        $GLOBALS['_wp_mail_calls'] = [];
        $mock_headers = [
            'paypal-transmission-id' => 'TRANS-REFUND-001',
            'paypal-transmission-sig' => 'VALID_SIG_MOCK'
        ];

        // 17A: Full refund of capture (PAYMENT.CAPTURE.REFUNDED)
        $refund_full_payload = json_encode([
            'id' => 'EVT-REF-001',
            'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
            'resource' => [
                'id' => 'REF-FULL-001',
                'capture_id' => 'CAP-2GG1234567', // References receipt INV-2026-0001
                'amount' => [
                    'value' => '62.75',
                    'currency_code' => 'EUR'
                ],
                'create_time' => gmdate('Y-m-d\TH:i:s\Z')
            ]
        ]);

        $res_ref1 = I4ware_PayPal_REST::process_webhook_event($mock_headers, $refund_full_payload);
        $this->assert($res_ref1['success'] === true, 'Full refund webhook processed successfully');

        // Verify Credit Note created
        $cn = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE paypal_transaction_id = 'REF-FULL-001'", ARRAY_A);
        $this->assert(!empty($cn), 'Credit note created for refund');
        $this->assert($cn['receipt_type'] === 'credit_note', 'Receipt type is credit_note');
        $this->assert(strpos($cn['receipt_number'], 'CN-') === 0, 'Credit note has dedicated CN prefix (' . $cn['receipt_number'] . ')');
        $this->assert($cn['original_receipt_number'] === 'INV-2026-0001', 'Credit note references original invoice INV-2026-0001');
        $this->assert((float)$cn['gross_amount'] === 62.75, 'Credit note gross amount is 62.75 EUR');

        // Verify original invoice was preserved without deleting financial records!
        $orig = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE receipt_number = 'INV-2026-0001'", ARRAY_A);
        $this->assert(!empty($orig), 'Original receipt record is preserved untouched');
        $this->assert((float)$orig['gross_amount'] === 62.75, 'Original gross amount remains intact');
        $this->assert($orig['paid_status'] === 'refunded', 'Original receipt status updated to refunded');

        // Verify credit note email sent
        $sent_cn_mail = end($GLOBALS['_wp_mail_calls']);
        $this->assert(strpos($sent_cn_mail['subject'], 'Hyvitystosite') !== false, 'Credit note email subject indicates Hyvitystosite');
        $this->assert(strpos($sent_cn_mail['message'], 'INV-2026-0001') !== false, 'Credit note email references original invoice number');

        // 17B: Partial refund of subscription payment (PAYMENT.SALE.REFUNDED)
        $refund_part_payload = json_encode([
            'id' => 'EVT-REF-002',
            'event_type' => 'PAYMENT.SALE.REFUNDED',
            'resource' => [
                'id' => 'REF-PART-002',
                'sale_id' => 'SALE-TX-001', // References INV-2026-0002 (125.50 EUR)
                'amount' => [
                    'total' => '50.00',
                    'currency' => 'EUR'
                ],
                'create_time' => gmdate('Y-m-d\TH:i:s\Z')
            ]
        ]);

        $res_ref2 = I4ware_PayPal_REST::process_webhook_event($mock_headers, $refund_part_payload);
        $this->assert($res_ref2['success'] === true, 'Partial refund webhook processed');

        $cn_part = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE paypal_transaction_id = 'REF-PART-002'", ARRAY_A);
        $this->assert(!empty($cn_part), 'Credit note created for partial refund');
        $this->assert((float)$cn_part['gross_amount'] === 50.00, 'Partial refund gross amount is 50.00 EUR');
        $this->assert($cn_part['original_receipt_number'] === 'INV-2026-0002', 'References original subscription invoice');

        $orig_sub = $wpdb->get_row("SELECT * FROM wp_i4ware_receipts WHERE receipt_number = 'INV-2026-0002'", ARRAY_A);
        $this->assert($orig_sub['paid_status'] === 'partially_refunded', 'Original subscription invoice marked partially_refunded');

        // 17C: Duplicate refund webhook idempotency
        $mail_count_ref = count($GLOBALS['_wp_mail_calls']);
        $res_ref_dup = I4ware_PayPal_REST::process_webhook_event($mock_headers, $refund_full_payload);
        $this->assert($res_ref_dup['success'] === true, 'Duplicate refund webhook acknowledged');
        $this->assert(count($GLOBALS['_wp_mail_calls']) === $mail_count_ref, 'No duplicate credit note email sent on refund redelivery');
    }

    /**
     * 18. SMTP Failure, Queue & Bounded Retries
     */
    private function test_18_smtp_failure_and_queue_retries()
    {
        echo "\nTest Suite 18: SMTP-virhe, taustajono ja rajatut uudelleenyritykset\n";
        global $wpdb;

        $table_queue = 'wp_i4ware_receipt_queue';
        $table_receipts = 'wp_i4ware_receipts';

        // 18A: Simulate SMTP connection error
        $GLOBALS['_wp_mail_mock'] = function() {
            return false; // Simulate SMTP failure
        };

        // Create mock receipt needing send
        $test_receipt = [
            'id' => 999,
            'receipt_number' => 'INV-2026-TESTFAIL',
            'receipt_type' => 'receipt',
            'customer_email' => 'asiakas@testfail.fi',
            'customer_name' => 'Testi Asiakas',
            'item_name' => 'Mainospaikka',
            'visibility_period' => '30 päivää',
            'gross_amount' => 12.55,
            'currency' => 'EUR',
            'paypal_transaction_id' => 'TX-FAIL-001',
            'pdf_path' => I4ware_Receipt_Manager::get_storage_directory() . '/INV-2026-TESTFAIL.pdf',
            'pdf_filename' => 'INV-2026-TESTFAIL.pdf',
            'email_status' => 'pending'
        ];

        // Write dummy PDF
        @file_put_contents($test_receipt['pdf_path'], '%PDF-1.4 mock %%EOF');

        $wpdb->tables[$table_receipts][999] = $test_receipt;
        $wpdb->tables[$table_queue] = [];

        // Enqueue send
        I4ware_Receipt_Queue::enqueue_receipt_send($test_receipt);

        $failed_receipt = $wpdb->tables[$table_receipts][999];
        $this->assert($failed_receipt['email_status'] === 'failed', 'Receipt email status marked failed on SMTP error');
        $this->assert($failed_receipt['retry_count'] === 1, 'Retry count recorded as 1');

        // 18B: Recover on subsequent attempt when SMTP is working
        $GLOBALS['_wp_mail_mock'] = null; // SMTP recovers
        I4ware_Receipt_Queue::process_queue();

        $recovered_receipt = $wpdb->tables[$table_receipts][999];
        $this->assert($recovered_receipt['email_status'] === 'sent', 'Receipt transitioned to sent upon SMTP recovery');

        // 18C: Bounded retries (maximum 3 attempts)
        $test_exhaust = [
            'id' => 998,
            'receipt_number' => 'INV-2026-EXHAUST',
            'receipt_type' => 'receipt',
            'customer_email' => 'asiakas@exhaust.fi',
            'customer_name' => 'Testi Exhaust',
            'item_name' => 'Mainospaikka',
            'visibility_period' => '30 päivää',
            'gross_amount' => 12.55,
            'currency' => 'EUR',
            'paypal_transaction_id' => 'TX-FAIL-002',
            'pdf_path' => $test_receipt['pdf_path'],
            'pdf_filename' => 'INV-2026-TESTFAIL.pdf',
            'email_status' => 'pending'
        ];
        $wpdb->tables[$table_receipts][998] = $test_exhaust;

        $GLOBALS['_wp_mail_mock'] = function() { return false; }; // Persistent failure
        I4ware_Receipt_Queue::enqueue_receipt_send($test_exhaust); // Attempt 1

        // Force queue items ready for attempts 2 and 3
        foreach ($wpdb->tables[$table_queue] as &$q) {
            if ($q['receipt_id'] == 998) {
                $q['scheduled_at'] = '2000-01-01 00:00:00';
            }
        }
        I4ware_Receipt_Queue::process_queue(); // Attempt 2

        foreach ($wpdb->tables[$table_queue] as &$q) {
            if ($q['receipt_id'] == 998) {
                $q['scheduled_at'] = '2000-01-01 00:00:00';
            }
        }
        I4ware_Receipt_Queue::process_queue(); // Attempt 3

        $exhausted_receipt = $wpdb->tables[$table_receipts][998];
        $this->assert($exhausted_receipt['retry_count'] === 3, 'Retries bounded at 3 attempts');
        $this->assert($exhausted_receipt['email_status'] === 'failed', 'Final state is failed without infinite loop');

        // 18D: Manual resend with the SAME receipt number
        $GLOBALS['_wp_mail_mock'] = null; // Working SMTP
        $resend_res = I4ware_Receipt_Manager::resend_receipt(998);
        $this->assert($resend_res['success'] === true, 'Manual resend succeeded from admin');
        $resent_receipt = $wpdb->tables[$table_receipts][998];
        $this->assert($resent_receipt['receipt_number'] === 'INV-2026-EXHAUST', 'Resend preserves exact original receipt number');
        $this->assert($resent_receipt['email_status'] === 'sent', 'Resent receipt status updated to sent');

        // Cleanup
        $GLOBALS['_wp_mail_mock'] = null;
    }

    /**
     * 19. PDF Content, Taxes, Currency & Finnish Umlauts (ä, ö, å, €)
     */
    private function test_19_pdf_encoding_taxes_and_umlauts()
    {
        echo "\nTest Suite 19: PDF-kuitin sisällön, verojen ja ääkkösten varmennus\n";

        // 19A: PDF binary structure
        $sample_receipt_data = [
            'receipt_number' => 'INV-2026-SAMPLE',
            'receipt_type' => 'receipt',
            'original_receipt_id' => null,
            'original_receipt_number' => null,
            'customer_name' => 'Matti Äyräväinen',
            'company_name' => 'Sähkö Oy & Co',
            'customer_email' => 'matti@sahko.fi',
            'billing_address' => 'Väinämöisenkatu 15 A, 00100 Helsinki',
            'billing_country' => 'FI',
            'vat_number' => 'FI99887766',
            'item_name' => 'Kulta-mainospaikka',
            'ad_description' => 'Banneri 728x90 alatunnisteessa ja etusivulla.',
            'visibility_period' => '30 päivää määräaikainen: 25.10.2026 – 24.11.2026',
            'net_amount' => 100.00,
            'vat_rate' => 25.5,
            'vat_amount' => 25.50,
            'gross_amount' => 125.50,
            'currency' => 'EUR',
            'tax_classification' => 'FI_DOMESTIC_TAXABLE',
            'tax_note' => 'Suomen arvonlisävero 25,5 % (AVL 1 §)',
            'paypal_transaction_id' => 'TX-SAMPLE-999',
            'payment_date' => '25.10.2026 12:00:00 UTC',
            'receipt_date' => '25.10.2026'
        ];

        $pdf_content = I4ware_Receipt_PDF::build($sample_receipt_data);

        $this->assert(strpos($pdf_content, '%PDF-1.4') === 0, 'PDF starts with valid %PDF-1.4 header');
        $this->assert(strpos($pdf_content, '%%EOF') !== false, 'PDF ends with valid %%EOF trailer');

        // 19B: WinAnsiEncoding conversion for Finnish characters
        $test_string = 'i4ware Software – Ääkköset: ä, ö, å, Ä, Ö, Å ja Euro: 125,50 €';
        $encoded = I4ware_PDF_Engine::conv($test_string);

        // Check exact WinAnsiEncoding bytes
        $has_lower_a = (strpos($encoded, chr(0xE4)) !== false); // ä = 228
        $has_lower_o = (strpos($encoded, chr(0xF6)) !== false); // ö = 246
        $has_lower_ao = (strpos($encoded, chr(0xE5)) !== false); // å = 229
        $has_upper_A = (strpos($encoded, chr(0xC4)) !== false); // Ä = 196
        $has_upper_O = (strpos($encoded, chr(0xD6)) !== false); // Ö = 214
        $has_upper_AO = (strpos($encoded, chr(0xC5)) !== false); // Å = 197
        $has_euro = (strpos($encoded, chr(128)) !== false);     // € = 128
        $has_endash = (strpos($encoded, chr(150)) !== false);   // – = 150

        $this->assert($has_lower_a && $has_lower_o && $has_lower_ao, 'Finnish lower-case umlauts (ä, ö, å) mapped accurately to WinAnsiEncoding');
        $this->assert($has_upper_A && $has_upper_O && $has_upper_AO, 'Finnish upper-case umlauts (Ä, Ö, Å) mapped accurately to WinAnsiEncoding');
        $this->assert($has_euro, 'Euro currency sign (€) mapped accurately to WinAnsi byte 128');
        $this->assert($has_endash, 'En-dash (–) mapped accurately to WinAnsi byte 150');

        // 19C: Credit Note PDF build
        $sample_cn_data = $sample_receipt_data;
        $sample_cn_data['receipt_number'] = 'CN-2026-SAMPLE';
        $sample_cn_data['receipt_type'] = 'credit_note';
        $sample_cn_data['original_receipt_number'] = 'INV-2026-SAMPLE';
        $sample_cn_data['item_name'] = 'HYVITYS: Kulta-mainospaikka';

        $cn_pdf_content = I4ware_Receipt_PDF::build($sample_cn_data);
        $this->assert(strpos($cn_pdf_content, '%PDF-1.4') === 0 && strpos($cn_pdf_content, '%%EOF') !== false, 'Credit Note PDF generated with valid PDF structure');

        // 19D: Storage security directory and files
        $dir = I4ware_Receipt_Manager::get_storage_directory();
        $this->assert(is_dir($dir), 'Secure storage directory exists');
        $this->assert(file_exists($dir . '/.htaccess'), '.htaccess protection file exists in receipt storage');
        $htaccess_content = file_get_contents($dir . '/.htaccess');
        $this->assert(strpos($htaccess_content, 'Require all denied') !== false, '.htaccess denies all public access');
        $this->assert(file_exists($dir . '/index.php'), 'index.php 403 silence file exists');
        $this->assert(file_exists($dir . '/web.config'), 'web.config IIS denial file exists');
    }
}

// Execute tests
$suite = new I4ware_Test_Suite();
$success = $suite->run();
exit($success ? 0 : 1);
