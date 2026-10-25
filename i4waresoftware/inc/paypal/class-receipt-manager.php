<?php
/**
 * i4ware Receipt Manager
 *
 * Coordinates:
 * - Sequential invoice numbering (INV-YYYY-XXXX, CN-YYYY-XXXX)
 * - Protected file storage outside public WordPress media library
 * - Idempotent receipt creation from verified PayPal webhook events
 * - Credit notes (hyvitystosite) referencing original receipts on refunds
 * - PDF document generation and queuing
 * - Secure admin streaming downloads & manual resends
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

require_once __DIR__ . '/class-receipt-pdf.php';
require_once __DIR__ . '/class-receipt-mailer.php';

class I4ware_Receipt_Manager
{
    /**
     * Get the absolute path to the secure receipts storage directory.
     * Protected with .htaccess, index.php, and web.config.
     */
    public static function get_storage_directory()
    {
        $upload_dir = wp_upload_dir();
        $base_dir = !empty($upload_dir['basedir']) ? $upload_dir['basedir'] : WP_CONTENT_DIR . '/uploads';
        $secure_dir = $base_dir . '/i4ware-receipts-secure';

        self::ensure_storage_security($secure_dir);
        return $secure_dir;
    }

    /**
     * Enforce strict directory security to prevent direct public downloads.
     */
    public static function ensure_storage_security($dir)
    {
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        // 1. Apache / LiteSpeed protection
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            $rules = "# i4ware Secure Receipts - Deny all public access\n<IfModule authz_core_module>\n  Require all denied\n</IfModule>\n<IfModule !authz_core_module>\n  Deny from all\n</IfModule>\n";
            @file_put_contents($htaccess, $rules);
        }

        // 2. Direct script execution block
        $index = $dir . '/index.php';
        if (!file_exists($index)) {
            @file_put_contents($index, "<?php\nhttp_response_code(403);\nexit;\n");
        }

        // 3. Microsoft IIS protection
        $web_config = $dir . '/web.config';
        if (!file_exists($web_config)) {
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <authorization>\n      <deny users=\"*\" />\n    </authorization>\n  </system.webServer>\n</configuration>\n";
            @file_put_contents($web_config, $xml);
        }
    }

    /**
     * Generate next sequential receipt number atomically.
     *
     * @param string $type 'receipt' (INV-YYYY-XXXX) or 'credit_note' (CN-YYYY-XXXX)
     * @return string Sequential receipt identifier
     */
    public static function generate_sequential_number($type = 'receipt')
    {
        global $wpdb;
        $year = gmdate('Y');
        $prefix = ($type === 'credit_note') ? "CN-{$year}-" : "INV-{$year}-";

        $table = $wpdb->prefix . 'i4ware_receipts';

        // Check highest existing number in DB for the current year
        $max_num = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $latest = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT receipt_number FROM $table WHERE receipt_number LIKE %s ORDER BY id DESC LIMIT 1",
                    $prefix . '%'
                )
            );

            if (!empty($latest) && preg_match('/-(\d+)$/', $latest, $m)) {
                $max_num = (int) $m[1];
            }
        }

        $next_num = $max_num + 1;
        return sprintf('%s%04d', $prefix, $next_num);
    }

    /**
     * Handle one-time payment capture webhook (PAYMENT.CAPTURE.COMPLETED).
     *
     * @param array $resource PayPal Webhook resource data
     * @return array Result [success => bool, receipt => array, message => string]
     */
    public static function handle_payment_capture_completed(array $resource)
    {
        global $wpdb;

        $capture_id = isset($resource['id']) ? sanitize_text_field($resource['id']) : '';
        if (empty($capture_id)) {
            return ['success' => false, 'message' => 'Puuttuva capture ID.'];
        }

        $table_receipts = $wpdb->prefix . 'i4ware_receipts';
        $table_orders = $wpdb->prefix . 'i4ware_paypal_orders';

        // 1. Idempotency Check: Already processed?
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") === $table_receipts) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_receipts WHERE paypal_transaction_id = %s AND receipt_type = 'receipt'", $capture_id),
                ARRAY_A
            );
            if ($existing) {
                return [
                    'success' => true,
                    'receipt' => $existing,
                    'message' => 'Kuitti luotu aiemmin (idempotentti).'
                ];
            }
        }

        // 2. Find matching order session
        $order = null;
        $session_id = isset($resource['custom_id']) ? sanitize_text_field($resource['custom_id']) : '';
        if (empty($session_id) && isset($resource['supplementary_data']['related_ids']['order_id'])) {
            $paypal_order_id = sanitize_text_field($resource['supplementary_data']['related_ids']['order_id']);
            $order = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_orders WHERE paypal_subscription_id = %s OR session_id = %s LIMIT 1", $paypal_order_id, $paypal_order_id),
                ARRAY_A
            );
        }

        if (!$order && !empty($session_id)) {
            $order = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_orders WHERE session_id = %s LIMIT 1", $session_id),
                ARRAY_A
            );
        }

        // Parse amount and date from capture resource
        $capture_gross = isset($resource['amount']['value']) ? (float) $resource['amount']['value'] : 0.0;
        $currency = isset($resource['amount']['currency_code']) ? sanitize_text_field($resource['amount']['currency_code']) : 'EUR';
        $payment_date = isset($resource['create_time']) ? gmdate('Y-m-d H:i:s', strtotime($resource['create_time'])) : gmdate('Y-m-d H:i:s');

        // Calculate visibility period: 30 days for one-time
        $ts_start = strtotime($payment_date);
        $ts_end = $ts_start + (30 * 86400);
        $visibility_period = gmdate('d.m.Y', $ts_start) . ' – ' . gmdate('d.m.Y', $ts_end) . ' (30 päivää määräaikainen)';

        // Prepare receipt data from confirmed order record
        $receipt_no = self::generate_sequential_number('receipt');
        $receipt_date = gmdate('Y-m-d');

        if ($order) {
            $customer_name = !empty($order['contact_name']) ? $order['contact_name'] : '';
            $customer_email = !empty($order['contact_email']) ? $order['contact_email'] : '';
            $company_name = !empty($order['company_name']) ? $order['company_name'] : '';
            $billing_address = !empty($order['billing_address']) ? $order['billing_address'] : '';
            $billing_country = !empty($order['billing_country']) ? $order['billing_country'] : 'FI';
            $vat_number = !empty($order['vat_number']) ? $order['vat_number'] : '';
            $item_name = !empty($order['item_name']) ? $order['item_name'] : 'Mainospalvelu';
            $net_amount = (float) $order['net_amount'];
            $vat_rate = ($order['vat_rate'] !== null) ? (float) $order['vat_rate'] : 25.5;
            $vat_amount = (float) $order['vat_amount'];
            $gross_amount = (float) $order['gross_amount'];
            $tax_classification = !empty($order['tax_classification']) ? $order['tax_classification'] : 'FI_DOMESTIC_TAXABLE';
            $tax_note = !empty($order['tax_note']) ? $order['tax_note'] : 'Suomen arvonlisävero 25,5 % (AVL 1 §)';
            $order_session_id = $order['session_id'];
            $sub_id = $order['paypal_subscription_id'];
        } else {
            // Fallback if session wasn't located in database
            $customer_name = 'Asiakas';
            $customer_email = isset($resource['payer']['email_address']) ? sanitize_email($resource['payer']['email_address']) : '';
            $company_name = '';
            $billing_address = 'Finland';
            $billing_country = 'FI';
            $vat_number = '';
            $item_name = 'Mainospaikka i4waren verkkosivustolla';
            $gross_amount = ($capture_gross > 0) ? $capture_gross : 10.0;
            $vat_rate = 25.5;
            $net_amount = round($gross_amount / (1 + ($vat_rate / 100)), 2);
            $vat_amount = round($gross_amount - $net_amount, 2);
            $tax_classification = 'FI_DOMESTIC_TAXABLE';
            $tax_note = 'Suomen arvonlisävero 25,5 % (AVL 1 §)';
            $order_session_id = $session_id;
            $sub_id = '';
        }

        $receipt_data = [
            'receipt_number' => $receipt_no,
            'receipt_type' => 'receipt',
            'original_receipt_id' => null,
            'original_receipt_number' => null,
            'order_session_id' => $order_session_id,
            'paypal_subscription_id' => $sub_id,
            'paypal_transaction_id' => $capture_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'company_name' => $company_name,
            'billing_address' => $billing_address,
            'billing_country' => $billing_country,
            'vat_number' => $vat_number,
            'item_name' => $item_name,
            'ad_description' => 'Mainospaikka i4waren verkkosivustolla sovitussa muodossa ja sijoittelussa.',
            'visibility_period' => $visibility_period,
            'net_amount' => $net_amount,
            'vat_rate' => $vat_rate,
            'vat_amount' => $vat_amount,
            'gross_amount' => $gross_amount,
            'currency' => $currency,
            'tax_classification' => $tax_classification,
            'tax_note' => $tax_note,
            'payment_method' => 'PayPal',
            'paid_status' => 'paid',
            'refund_amount' => 0.00,
            'payment_date' => $payment_date,
            'receipt_date' => $receipt_date
        ];

        // 3. Build & Save PDF
        $storage_dir = self::get_storage_directory();
        $pdf_filename = $receipt_no . '.pdf';
        $pdf_path = $storage_dir . '/' . $pdf_filename;

        $pdf_binary = I4ware_Receipt_PDF::build($receipt_data);
        @file_put_contents($pdf_path, $pdf_binary);

        $receipt_data['pdf_path'] = $pdf_path;
        $receipt_data['pdf_filename'] = $pdf_filename;
        $receipt_data['email_status'] = 'pending';
        $receipt_data['created_at'] = gmdate('Y-m-d H:i:s');
        $receipt_data['updated_at'] = gmdate('Y-m-d H:i:s');

        // 4. Insert into database
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") === $table_receipts) {
            $wpdb->insert($table_receipts, [
                'receipt_number' => $receipt_no,
                'receipt_type' => 'receipt',
                'order_session_id' => $order_session_id,
                'paypal_subscription_id' => $sub_id,
                'paypal_transaction_id' => $capture_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'company_name' => $company_name,
                'billing_address' => $billing_address,
                'billing_country' => $billing_country,
                'vat_number' => $vat_number,
                'item_name' => $item_name,
                'ad_description' => $receipt_data['ad_description'],
                'visibility_period' => $visibility_period,
                'net_amount' => $net_amount,
                'vat_rate' => $vat_rate,
                'vat_amount' => $vat_amount,
                'gross_amount' => $gross_amount,
                'currency' => $currency,
                'tax_classification' => $tax_classification,
                'tax_note' => $tax_note,
                'payment_method' => 'PayPal',
                'paid_status' => 'paid',
                'refund_amount' => 0.00,
                'pdf_path' => $pdf_path,
                'pdf_filename' => $pdf_filename,
                'email_status' => 'pending',
                'payment_date' => $payment_date,
                'created_at' => gmdate('Y-m-d H:i:s'),
                'updated_at' => gmdate('Y-m-d H:i:s')
            ]);
            $receipt_data['id'] = $wpdb->insert_id;
        }

        // 5. Send receipt email via queue/mailer
        require_once __DIR__ . '/class-receipt-queue.php';
        I4ware_Receipt_Queue::enqueue_receipt_send($receipt_data);

        return [
            'success' => true,
            'receipt' => $receipt_data,
            'message' => 'Kuitti luotu ja jonotettu lähetettäväksi.'
        ];
    }

    /**
     * Handle recurring subscription payment sale (PAYMENT.SALE.COMPLETED).
     *
     * @param array $resource PayPal Webhook resource data
     * @return array Result
     */
    public static function handle_payment_sale_completed(array $resource)
    {
        global $wpdb;

        $sale_id = isset($resource['id']) ? sanitize_text_field($resource['id']) : '';
        $sub_id = isset($resource['billing_agreement_id']) ? sanitize_text_field($resource['billing_agreement_id']) : '';

        if (empty($sale_id)) {
            return ['success' => false, 'message' => 'Puuttuva sale ID.'];
        }

        $table_receipts = $wpdb->prefix . 'i4ware_receipts';
        $table_orders = $wpdb->prefix . 'i4ware_paypal_orders';

        // 1. Idempotency Check: Already processed?
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") === $table_receipts) {
            $existing = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_receipts WHERE paypal_transaction_id = %s AND receipt_type = 'receipt'", $sale_id),
                ARRAY_A
            );
            if ($existing) {
                return [
                    'success' => true,
                    'receipt' => $existing,
                    'message' => 'Kuitti luotu aiemmin (idempotentti).'
                ];
            }
        }

        // 2. Locate order record by subscription ID or custom session ID
        $order = null;
        if (!empty($sub_id) && $wpdb->get_var("SHOW TABLES LIKE '$table_orders'") === $table_orders) {
            $order = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_orders WHERE paypal_subscription_id = %s LIMIT 1", $sub_id),
                ARRAY_A
            );
        }

        $session_id = isset($resource['custom']) ? sanitize_text_field($resource['custom']) : '';
        if (!$order && !empty($session_id) && $wpdb->get_var("SHOW TABLES LIKE '$table_orders'") === $table_orders) {
            $order = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_orders WHERE session_id = %s LIMIT 1", $session_id),
                ARRAY_A
            );
        }

        // Sale amount and date
        $currency = isset($resource['amount']['currency']) ? sanitize_text_field($resource['amount']['currency']) : 'EUR';
        $payment_date = isset($resource['create_time']) ? gmdate('Y-m-d H:i:s', strtotime($resource['create_time'])) : gmdate('Y-m-d H:i:s');

        // Determine billing cycle period: 1 month from payment date
        $ts_start = strtotime($payment_date);
        $ts_end = strtotime('+1 month', $ts_start);
        $visibility_period = 'Kuukausitilaus, jakso: ' . gmdate('d.m.Y', $ts_start) . ' – ' . gmdate('d.m.Y', $ts_end);

        $receipt_no = self::generate_sequential_number('receipt');
        $receipt_date = gmdate('Y-m-d');

        if ($order) {
            $customer_name = !empty($order['contact_name']) ? $order['contact_name'] : '';
            $customer_email = !empty($order['contact_email']) ? $order['contact_email'] : '';
            $company_name = !empty($order['company_name']) ? $order['company_name'] : '';
            $billing_address = !empty($order['billing_address']) ? $order['billing_address'] : '';
            $billing_country = !empty($order['billing_country']) ? $order['billing_country'] : 'FI';
            $vat_number = !empty($order['vat_number']) ? $order['vat_number'] : '';
            $item_name = !empty($order['item_name']) ? $order['item_name'] : 'Kuukausittainen mainospaikka';
            $net_amount = (float) $order['net_amount'];
            $vat_rate = ($order['vat_rate'] !== null) ? (float) $order['vat_rate'] : 25.5;
            $vat_amount = (float) $order['vat_amount'];
            $gross_amount = (float) $order['gross_amount'];
            $tax_classification = !empty($order['tax_classification']) ? $order['tax_classification'] : 'FI_DOMESTIC_TAXABLE';
            $tax_note = !empty($order['tax_note']) ? $order['tax_note'] : 'Suomen arvonlisävero 25,5 % (AVL 1 §)';
            $order_session_id = $order['session_id'];
        } else {
            $customer_name = 'Tilaaja';
            $customer_email = '';
            $company_name = '';
            $billing_address = 'Finland';
            $billing_country = 'FI';
            $vat_number = '';
            $item_name = 'Mainospaikka (Kuukausitilaus)';
            $sale_gross = isset($resource['amount']['total']) ? (float) $resource['amount']['total'] : 10.0;
            $gross_amount = $sale_gross;
            $vat_rate = 25.5;
            $net_amount = round($gross_amount / (1 + ($vat_rate / 100)), 2);
            $vat_amount = round($gross_amount - $net_amount, 2);
            $tax_classification = 'FI_DOMESTIC_TAXABLE';
            $tax_note = 'Suomen arvonlisävero 25,5 % (AVL 1 §)';
            $order_session_id = $session_id;
        }

        $receipt_data = [
            'receipt_number' => $receipt_no,
            'receipt_type' => 'receipt',
            'original_receipt_id' => null,
            'original_receipt_number' => null,
            'order_session_id' => $order_session_id,
            'paypal_subscription_id' => $sub_id,
            'paypal_transaction_id' => $sale_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'company_name' => $company_name,
            'billing_address' => $billing_address,
            'billing_country' => $billing_country,
            'vat_number' => $vat_number,
            'item_name' => $item_name,
            'ad_description' => 'Jatkuva kuukausitilaus i4waren verkkosivustolla sovitussa muodossa ja sijoittelussa.',
            'visibility_period' => $visibility_period,
            'net_amount' => $net_amount,
            'vat_rate' => $vat_rate,
            'vat_amount' => $vat_amount,
            'gross_amount' => $gross_amount,
            'currency' => $currency,
            'tax_classification' => $tax_classification,
            'tax_note' => $tax_note,
            'payment_method' => 'PayPal',
            'paid_status' => 'paid',
            'refund_amount' => 0.00,
            'payment_date' => $payment_date,
            'receipt_date' => $receipt_date
        ];

        // 3. Build & Save PDF
        $storage_dir = self::get_storage_directory();
        $pdf_filename = $receipt_no . '.pdf';
        $pdf_path = $storage_dir . '/' . $pdf_filename;

        $pdf_binary = I4ware_Receipt_PDF::build($receipt_data);
        @file_put_contents($pdf_path, $pdf_binary);

        $receipt_data['pdf_path'] = $pdf_path;
        $receipt_data['pdf_filename'] = $pdf_filename;
        $receipt_data['email_status'] = 'pending';
        $receipt_data['created_at'] = gmdate('Y-m-d H:i:s');
        $receipt_data['updated_at'] = gmdate('Y-m-d H:i:s');

        // 4. Insert into database
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") === $table_receipts) {
            $wpdb->insert($table_receipts, [
                'receipt_number' => $receipt_no,
                'receipt_type' => 'receipt',
                'order_session_id' => $order_session_id,
                'paypal_subscription_id' => $sub_id,
                'paypal_transaction_id' => $sale_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'company_name' => $company_name,
                'billing_address' => $billing_address,
                'billing_country' => $billing_country,
                'vat_number' => $vat_number,
                'item_name' => $item_name,
                'ad_description' => $receipt_data['ad_description'],
                'visibility_period' => $visibility_period,
                'net_amount' => $net_amount,
                'vat_rate' => $vat_rate,
                'vat_amount' => $vat_amount,
                'gross_amount' => $gross_amount,
                'currency' => $currency,
                'tax_classification' => $tax_classification,
                'tax_note' => $tax_note,
                'payment_method' => 'PayPal',
                'paid_status' => 'paid',
                'refund_amount' => 0.00,
                'pdf_path' => $pdf_path,
                'pdf_filename' => $pdf_filename,
                'email_status' => 'pending',
                'payment_date' => $payment_date,
                'created_at' => gmdate('Y-m-d H:i:s'),
                'updated_at' => gmdate('Y-m-d H:i:s')
            ]);
            $receipt_data['id'] = $wpdb->insert_id;
        }

        // 5. Send receipt email via queue/mailer
        require_once __DIR__ . '/class-receipt-queue.php';
        I4ware_Receipt_Queue::enqueue_receipt_send($receipt_data);

        return [
            'success' => true,
            'receipt' => $receipt_data,
            'message' => 'Kuukausitilauksen kuitti luotu ja jonotettu lähetettäväksi.'
        ];
    }

    /**
     * Handle payment refund webhook (PAYMENT.CAPTURE.REFUNDED or PAYMENT.SALE.REFUNDED).
     * Creates an official Credit Note (hyvitystosite) referencing the original receipt.
     * Does NOT overwrite or alter original receipt record.
     *
     * @param array $resource PayPal Webhook resource data
     * @return array Result
     */
    public static function handle_payment_refunded(array $resource)
    {
        global $wpdb;

        $refund_id = isset($resource['id']) ? sanitize_text_field($resource['id']) : '';
        $parent_payment_id = '';
        if (isset($resource['capture_id'])) {
            $parent_payment_id = sanitize_text_field($resource['capture_id']);
        } elseif (isset($resource['sale_id'])) {
            $parent_payment_id = sanitize_text_field($resource['sale_id']);
        } elseif (isset($resource['parent_payment'])) {
            $parent_payment_id = sanitize_text_field($resource['parent_payment']);
        }

        $table_receipts = $wpdb->prefix . 'i4ware_receipts';

        // 1. Idempotency Check: Credit note already created for this refund ID?
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") === $table_receipts) {
            $existing_cn = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_receipts WHERE paypal_transaction_id = %s AND receipt_type = 'credit_note'", $refund_id),
                ARRAY_A
            );
            if ($existing_cn) {
                return [
                    'success' => true,
                    'credit_note' => $existing_cn,
                    'message' => 'Hyvitystosite luotu aiemmin (idempotentti).'
                ];
            }
        }

        // 2. Find original receipt
        $orig_receipt = null;
        if (!empty($parent_payment_id) && $wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") === $table_receipts) {
            $orig_receipt = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table_receipts WHERE paypal_transaction_id = %s AND receipt_type = 'receipt' LIMIT 1", $parent_payment_id),
                ARRAY_A
            );
        }

        if (!$orig_receipt) {
            return [
                'success' => false,
                'message' => 'Alkuperäistä kuittia ei löytynyt maksutapahtumalle: ' . $parent_payment_id
            ];
        }

        // Refund amount
        $refund_amount = 0.0;
        if (isset($resource['amount']['value'])) {
            $refund_amount = (float) $resource['amount']['value'];
        } elseif (isset($resource['amount']['total'])) {
            $refund_amount = (float) $resource['amount']['total'];
        } else {
            $refund_amount = (float) $orig_receipt['gross_amount'];
        }

        $is_full_refund = ($refund_amount >= (float) $orig_receipt['gross_amount']);

        // Calculate proportional net and tax for credit note
        $vat_rate = ($orig_receipt['vat_rate'] !== null) ? (float) $orig_receipt['vat_rate'] : 25.5;
        $cn_net = round($refund_amount / (1 + ($vat_rate / 100)), 2);
        $cn_vat = round($refund_amount - $cn_net, 2);

        $cn_number = self::generate_sequential_number('credit_note');
        $refund_date = isset($resource['create_time']) ? gmdate('Y-m-d H:i:s', strtotime($resource['create_time'])) : gmdate('Y-m-d H:i:s');

        $credit_data = [
            'receipt_number' => $cn_number,
            'receipt_type' => 'credit_note',
            'original_receipt_id' => $orig_receipt['id'],
            'original_receipt_number' => $orig_receipt['receipt_number'],
            'order_session_id' => $orig_receipt['order_session_id'],
            'paypal_subscription_id' => $orig_receipt['paypal_subscription_id'],
            'paypal_transaction_id' => $refund_id,
            'customer_name' => $orig_receipt['customer_name'],
            'customer_email' => $orig_receipt['customer_email'],
            'company_name' => $orig_receipt['company_name'],
            'billing_address' => $orig_receipt['billing_address'],
            'billing_country' => $orig_receipt['billing_country'],
            'vat_number' => $orig_receipt['vat_number'],
            'item_name' => 'Maksunpalautus: ' . $orig_receipt['item_name'],
            'ad_description' => 'Maksunpalautus kuitille ' . $orig_receipt['receipt_number'] . ' (PayPal ' . $parent_payment_id . ')',
            'visibility_period' => 'Peruutettu / Hyvitetty: ' . gmdate('d.m.Y', strtotime($refund_date)),
            'net_amount' => $cn_net,
            'vat_rate' => $vat_rate,
            'vat_amount' => $cn_vat,
            'gross_amount' => $refund_amount,
            'currency' => $orig_receipt['currency'],
            'tax_classification' => $orig_receipt['tax_classification'],
            'tax_note' => 'Hyvitystosite alkuperäiselle kuitille ' . $orig_receipt['receipt_number'] . ' (' . $orig_receipt['tax_note'] . ')',
            'payment_method' => 'PayPal',
            'paid_status' => 'refunded',
            'refund_amount' => $refund_amount,
            'payment_date' => $refund_date,
            'receipt_date' => gmdate('Y-m-d')
        ];

        // Build & Save Credit Note PDF
        $storage_dir = self::get_storage_directory();
        $pdf_filename = $cn_number . '.pdf';
        $pdf_path = $storage_dir . '/' . $pdf_filename;

        $pdf_binary = I4ware_Receipt_PDF::build($credit_data);
        @file_put_contents($pdf_path, $pdf_binary);

        $credit_data['pdf_path'] = $pdf_path;
        $credit_data['pdf_filename'] = $pdf_filename;

        // Insert Credit Note into database
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") === $table_receipts) {
            $wpdb->insert($table_receipts, [
                'receipt_number' => $cn_number,
                'receipt_type' => 'credit_note',
                'original_receipt_id' => $orig_receipt['id'],
                'original_receipt_number' => $orig_receipt['receipt_number'],
                'order_session_id' => $orig_receipt['order_session_id'],
                'paypal_subscription_id' => $orig_receipt['paypal_subscription_id'],
                'paypal_transaction_id' => $refund_id,
                'customer_name' => $orig_receipt['customer_name'],
                'customer_email' => $orig_receipt['customer_email'],
                'company_name' => $orig_receipt['company_name'],
                'billing_address' => $orig_receipt['billing_address'],
                'billing_country' => $orig_receipt['billing_country'],
                'vat_number' => $orig_receipt['vat_number'],
                'item_name' => $credit_data['item_name'],
                'ad_description' => $credit_data['ad_description'],
                'visibility_period' => $credit_data['visibility_period'],
                'net_amount' => $cn_net,
                'vat_rate' => $vat_rate,
                'vat_amount' => $cn_vat,
                'gross_amount' => $refund_amount,
                'currency' => $orig_receipt['currency'],
                'tax_classification' => $orig_receipt['tax_classification'],
                'tax_note' => $credit_data['tax_note'],
                'payment_method' => 'PayPal',
                'paid_status' => 'refunded',
                'refund_amount' => $refund_amount,
                'pdf_path' => $pdf_path,
                'pdf_filename' => $pdf_filename,
                'email_status' => 'pending',
                'payment_date' => $refund_date,
                'created_at' => gmdate('Y-m-d H:i:s'),
                'updated_at' => gmdate('Y-m-d H:i:s')
            ]);
            $credit_data['id'] = $wpdb->insert_id;

            // Update original receipt status without modifying its financial records!
            $new_orig_status = $is_full_refund ? 'refunded' : 'partially_refunded';
            $wpdb->update(
                $table_receipts,
                [
                    'paid_status' => $new_orig_status,
                    'refund_amount' => $refund_amount,
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ],
                ['id' => $orig_receipt['id']]
            );
        }

        // Queue credit note email to customer
        require_once __DIR__ . '/class-receipt-queue.php';
        I4ware_Receipt_Queue::enqueue_receipt_send($credit_data);

        return [
            'success' => true,
            'credit_note' => $credit_data,
            'message' => 'Hyvitystosite luotu ja jonotettu lähetettäväksi.'
        ];
    }

    /**
     * Stream receipt PDF for authorized administrator download.
     */
    public static function stream_receipt_pdf($receipt_id)
    {
        if (!current_user_can('manage_options')) {
            wp_die('Käyttöoikeus evätty.', 403);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'i4ware_receipts';
        $receipt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", (int) $receipt_id), ARRAY_A);

        if (!$receipt || empty($receipt['pdf_path']) || !file_exists($receipt['pdf_path'])) {
            wp_die('PDF-kuittitiedostoa ei löytynyt.', 404);
        }

        $pdf_engine = new I4ware_PDF_Engine();
        $content = file_get_contents($receipt['pdf_path']);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($receipt['pdf_filename']) . '"');
        header('Content-Length: ' . strlen($content));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $content;
        exit;
    }

    /**
     * Manual resend of receipt with the SAME receipt number.
     */
    public static function resend_receipt($receipt_id)
    {
        if (!current_user_can('manage_options')) {
            return ['success' => false, 'message' => 'Käyttöoikeus evätty.'];
        }

        global $wpdb;
        $table = $wpdb->prefix . 'i4ware_receipts';
        $receipt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", (int) $receipt_id), ARRAY_A);

        if (!$receipt) {
            return ['success' => false, 'message' => 'Kuittia ei löytynyt.'];
        }

        // Regenerate PDF if missing from disk
        if (empty($receipt['pdf_path']) || !file_exists($receipt['pdf_path'])) {
            $storage_dir = self::get_storage_directory();
            $pdf_filename = $receipt['receipt_number'] . '.pdf';
            $pdf_path = $storage_dir . '/' . $pdf_filename;

            $pdf_binary = I4ware_Receipt_PDF::build($receipt);
            @file_put_contents($pdf_path, $pdf_binary);

            $receipt['pdf_path'] = $pdf_path;
            $receipt['pdf_filename'] = $pdf_filename;
            $wpdb->update($table, ['pdf_path' => $pdf_path, 'pdf_filename' => $pdf_filename], ['id' => $receipt['id']]);
        }

        // Send email
        $mail_res = I4ware_Receipt_Mailer::send_receipt_email($receipt);

        if ($mail_res['success']) {
            $wpdb->update(
                $table,
                [
                    'email_status' => 'sent',
                    'email_sent_at' => gmdate('Y-m-d H:i:s'),
                    'email_error' => null,
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ],
                ['id' => $receipt['id']]
            );
            return ['success' => true, 'message' => 'Kuitti [' . $receipt['receipt_number'] . '] lähetetty uudelleen onnistuneesti osoitteeseen ' . I4ware_Receipt_Mailer::mask_email($receipt['customer_email'])];
        } else {
            $wpdb->update(
                $table,
                [
                    'email_status' => 'failed',
                    'email_error' => $mail_res['error'],
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ],
                ['id' => $receipt['id']]
            );
            return ['success' => false, 'message' => 'Uudelleenlähetys epäonnistui: ' . $mail_res['message']];
        }
    }
}
