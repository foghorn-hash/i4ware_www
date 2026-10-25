<?php
/**
 * i4ware PayPal Subscriptions & VAT Architecture - Master Initializer
 *
 * Coordinates:
 * - Constants and country tables
 * - Official EU VIES REST client
 * - Tax decision module
 * - Cryptographic checkout session tokens
 * - PayPal REST API & Webhooks
 * - Admin UI & database migration
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/class-vies-client.php';
require_once __DIR__ . '/class-tax-decision-engine.php';
require_once __DIR__ . '/class-checkout-session.php';
require_once __DIR__ . '/class-paypal-rest.php';
require_once __DIR__ . '/class-paypal-api.php';
require_once __DIR__ . '/class-paypal-admin.php';
require_once __DIR__ . '/class-pdf-engine.php';
require_once __DIR__ . '/class-receipt-pdf.php';
require_once __DIR__ . '/class-receipt-mailer.php';
require_once __DIR__ . '/class-receipt-manager.php';
require_once __DIR__ . '/class-receipt-queue.php';

/**
 * Initialize Database Tables
 */
function i4ware_paypal_ensure_database_tables()
{
    global $wpdb;
    $installed_ver = get_option('i4ware_paypal_db_version', '0');
    $current_ver = '1.1.0';

    if ($installed_ver === $current_ver) {
        return;
    }

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $charset_collate = $wpdb->get_charset_collate();

    // 1. VIES Audit Logs Table
    $table_logs = $wpdb->prefix . 'i4ware_vat_audit_logs';
    $sql_logs = "CREATE TABLE $table_logs (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        vat_number varchar(32) NOT NULL,
        vat_country varchar(8) NOT NULL,
        status varchar(32) NOT NULL,
        consultation_number varchar(64) DEFAULT '',
        source varchar(128) DEFAULT '',
        name_match_status varchar(64) DEFAULT '',
        request_utc datetime NOT NULL,
        response_summary text DEFAULT NULL,
        ip_address varchar(64) DEFAULT '',
        created_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY vat_number_idx (vat_number),
        KEY request_utc_idx (request_utc)
    ) $charset_collate;";
    dbDelta($sql_logs);

    // 2. PayPal Orders & Verification Sessions Table
    $table_orders = $wpdb->prefix . 'i4ware_paypal_orders';
    $sql_orders = "CREATE TABLE $table_orders (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        session_id varchar(64) NOT NULL,
        payment_type varchar(32) NOT NULL,
        customer_type varchar(16) NOT NULL,
        company_name varchar(255) DEFAULT '',
        establishment_country varchar(8) NOT NULL,
        fixed_establishment_country varchar(8) DEFAULT '',
        billing_country varchar(8) NOT NULL,
        billing_address text NOT NULL,
        business_id varchar(64) DEFAULT '',
        vat_number varchar(32) DEFAULT '',
        vat_country varchar(8) DEFAULT '',
        contact_name varchar(255) NOT NULL,
        contact_email varchar(255) NOT NULL,
        contact_phone varchar(64) DEFAULT '',
        plan_id varchar(128) NOT NULL,
        item_name varchar(255) NOT NULL,
        net_amount decimal(10,2) NOT NULL,
        vat_rate decimal(5,2) DEFAULT NULL,
        vat_amount decimal(10,2) NOT NULL,
        gross_amount decimal(10,2) NOT NULL,
        currency varchar(8) NOT NULL DEFAULT 'EUR',
        tax_classification varchar(64) NOT NULL,
        tax_note text NOT NULL,
        status varchar(32) NOT NULL DEFAULT 'pending',
        paypal_subscription_id varchar(128) DEFAULT NULL,
        created_at_utc datetime NOT NULL,
        updated_at_utc datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY session_id_idx (session_id),
        KEY status_idx (status),
        KEY paypal_subscription_id_idx (paypal_subscription_id)
    ) $charset_collate;";
    dbDelta($sql_orders);

    // 3. PayPal Webhook Events Table (for Idempotency)
    $table_events = $wpdb->prefix . 'i4ware_paypal_webhook_events';
    $sql_events = "CREATE TABLE $table_events (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        event_id varchar(128) NOT NULL,
        transmission_id varchar(128) DEFAULT '',
        event_type varchar(128) NOT NULL,
        status varchar(32) NOT NULL DEFAULT 'processed',
        payload longtext DEFAULT NULL,
        created_at_utc datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY event_id_idx (event_id),
        KEY event_type_idx (event_type)
    ) $charset_collate;";
    dbDelta($sql_events);

    // 4. Official PDF Receipts & Credit Notes Table
    $table_receipts = $wpdb->prefix . 'i4ware_receipts';
    $sql_receipts = "CREATE TABLE $table_receipts (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        receipt_number varchar(64) NOT NULL,
        receipt_type varchar(32) NOT NULL DEFAULT 'receipt',
        original_receipt_id bigint(20) unsigned DEFAULT NULL,
        original_receipt_number varchar(64) DEFAULT NULL,
        order_session_id varchar(64) DEFAULT '',
        paypal_subscription_id varchar(128) DEFAULT '',
        paypal_transaction_id varchar(128) NOT NULL,
        customer_name varchar(255) NOT NULL,
        customer_email varchar(255) NOT NULL,
        company_name varchar(255) DEFAULT '',
        billing_address text NOT NULL,
        billing_country varchar(8) NOT NULL,
        vat_number varchar(32) DEFAULT '',
        item_name varchar(255) NOT NULL,
        ad_description text NOT NULL,
        visibility_period varchar(255) NOT NULL,
        net_amount decimal(10,2) NOT NULL,
        vat_rate decimal(5,2) DEFAULT NULL,
        vat_amount decimal(10,2) NOT NULL,
        gross_amount decimal(10,2) NOT NULL,
        currency varchar(8) NOT NULL DEFAULT 'EUR',
        tax_classification varchar(64) NOT NULL,
        tax_note text NOT NULL,
        payment_method varchar(32) NOT NULL DEFAULT 'PayPal',
        paid_status varchar(32) NOT NULL DEFAULT 'paid',
        refund_amount decimal(10,2) NOT NULL DEFAULT 0.00,
        pdf_path varchar(512) DEFAULT '',
        pdf_filename varchar(255) DEFAULT '',
        email_status varchar(32) NOT NULL DEFAULT 'pending',
        email_sent_at datetime DEFAULT NULL,
        email_error text DEFAULT NULL,
        retry_count int(11) NOT NULL DEFAULT 0,
        payment_date datetime NOT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY receipt_number_idx (receipt_number),
        KEY paypal_tx_idx (paypal_transaction_id),
        KEY paypal_sub_idx (paypal_subscription_id),
        KEY email_status_idx (email_status)
    ) $charset_collate;";
    dbDelta($sql_receipts);

    // 5. Receipt Processing & Email Queue Table
    $table_queue = $wpdb->prefix . 'i4ware_receipt_queue';
    $sql_queue = "CREATE TABLE $table_queue (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        receipt_id bigint(20) unsigned NOT NULL,
        action_type varchar(32) NOT NULL DEFAULT 'generate_and_send',
        status varchar(32) NOT NULL DEFAULT 'pending',
        attempts int(11) NOT NULL DEFAULT 0,
        max_attempts int(11) NOT NULL DEFAULT 3,
        last_error text DEFAULT NULL,
        scheduled_at datetime NOT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY status_sched_idx (status, scheduled_at),
        KEY receipt_id_idx (receipt_id)
    ) $charset_collate;";
    dbDelta($sql_queue);

    update_option('i4ware_paypal_db_version', $current_ver);
}
add_action('after_setup_theme', 'i4ware_paypal_ensure_database_tables');

// Initialize Mailer & Background Queue
I4ware_Receipt_Mailer::init();
I4ware_Receipt_Queue::init();

// Register REST API Endpoints & Filter Auth Errors for Public Checkout
add_action('rest_api_init', ['I4ware_PayPal_API', 'register_routes']);
add_filter('rest_authentication_errors', ['I4ware_PayPal_API', 'filter_rest_auth_errors'], 101);

// Admin PDF Streaming Download & Resend Hooks
add_action('admin_post_i4ware_download_receipt', function() {
    if (isset($_GET['receipt_id'])) {
        check_admin_referer('i4ware_download_receipt_' . $_GET['receipt_id']);
        I4ware_Receipt_Manager::stream_receipt_pdf((int) $_GET['receipt_id']);
    }
});

// Initialize Admin Interface
if (is_admin()) {
    I4ware_PayPal_Admin::init();
}

