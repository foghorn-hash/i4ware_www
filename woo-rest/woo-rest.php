<?php
/**
 * Plugin Name: WooCommerce REST Orders API
 * Plugin URI: https://i4ware.fi
 * Description: Custom REST API endpoint for WooCommerce orders with VAT handling (Finnish, EU reverse charge, non-EU)
 * Version: 1.0.0
 * Author: i4ware
 * Author URI: https://i4ware.fi
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: woo-rest
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

class WooCommerce_REST_Orders_Server {
    
    private $namespace = 'woo-rest/v1';
    private $resource_name = 'orders';
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Get all orders with financial data
        register_rest_route($this->namespace, '/' . $this->resource_name, array(
            'methods' => 'GET',
            'callback' => array($this, 'get_orders'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'status' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Order status (e.g., completed, processing, any)',
                    'default' => 'completed',
                ),
                'limit' => array(
                    'required' => false,
                    'type' => 'integer',
                    'description' => 'Number of orders to retrieve',
                    'default' => 100,
                ),
                'compact' => array(
                    'required' => false,
                    'type' => 'boolean',
                    'description' => 'If true, return only date and vendor balance to speed up response',
                    'default' => false,
                ),
                'date_from' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'Start date (YYYY-MM-DD)',
                ),
                'date_to' => array(
                    'required' => false,
                    'type' => 'string',
                    'description' => 'End date (YYYY-MM-DD)',
                ),
            ),
        ));
        
        // Get single order by ID
        register_rest_route($this->namespace, '/' . $this->resource_name . '/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_order'),
            'permission_callback' => array($this, 'check_permissions'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Order ID',
                ),
            ),
        ));
    }
    
    /**
     * Check API permissions
     */
    public function check_permissions() {
        // Public API - no authentication required (data is anonymized)
        return true;
    }
    
    /**
     * Get multiple orders
     */
    public function get_orders($request) {
        $status = $request->get_param('status');
        if (empty($status) || $status === 'any') {
            $status = 'completed';
        }
        $limit = $request->get_param('limit');
        $compact = filter_var($request->get_param('compact'), FILTER_VALIDATE_BOOLEAN);
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        $args = array(
            'limit' => $limit,
            'status' => $status,
        );
        
        // Add date filters if provided
        if ($date_from) {
            $args['date_created'] = '>=' . $date_from;
        }
        if ($date_to) {
            if (isset($args['date_created'])) {
                $args['date_created'] = $date_from . '...' . $date_to;
            } else {
                $args['date_created'] = '<=' . $date_to;
            }
        }
        
        // Compact mode: query posts directly and return only date + vendor balance (faster)
        if ($compact) {
            // Simple transient cache to speed repeated identical requests briefly
            $cache_key = 'woo_rest_compact_' . md5(serialize(array($status, $limit, $date_from, $date_to)));
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return rest_ensure_response($cached);
            }

            $query_args = array(
                'post_type' => 'shop_order',
                'posts_per_page' => $limit,
                'post_status' => ($status === 'any' || empty($status)) ? 'any' : 'wc-' . $status,
                'fields' => 'ids',
            );

            $date_query = array();
            if ($date_from && $date_to) {
                $date_query[] = array('after' => $date_from, 'before' => $date_to, 'inclusive' => true);
            } elseif ($date_from) {
                $date_query[] = array('after' => $date_from, 'inclusive' => true);
            } elseif ($date_to) {
                $date_query[] = array('before' => $date_to, 'inclusive' => true);
            }
            if (!empty($date_query)) $query_args['date_query'] = $date_query;

            $posts = get_posts($query_args);
            $orders_data = array();
            foreach ($posts as $post_id) {
                $total = get_post_meta($post_id, '_order_total', true);
                // fallback to 0 if empty
                $amount = $total !== '' ? floatval($total) : 0.0;
                $date = get_post_field('post_date', $post_id);
                $orders_data[] = array(
                    'saleDate' => date('Y-m-d', strtotime($date)),
                    'vendorAmount' => number_format($amount, 2, '.', ''),
                    'vendorAmountFormatted' => wc_price($amount),
                );
            }

            $resp = array('success' => true, 'count' => count($orders_data), 'orders' => $orders_data);
            // cache for short time (30 seconds)
            set_transient($cache_key, $resp, 30);
            return rest_ensure_response($resp);
        }

        // Full mode (legacy) — slower due to creating WC_Order objects and formatting
        $orders = wc_get_orders($args);
        
        $orders_data = array();
        foreach ($orders as $order) {
            $orders_data[] = $this->format_order_data($order);
        }

        return rest_ensure_response(array(
            'success' => true,
            'count' => count($orders_data),
            'orders' => $orders_data,
        ));
    }
    
    /**
     * Get single order by ID
     */
    public function get_order($request) {
        $order_id = $request->get_param('id');
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new WP_Error('order_not_found', 'Order not found', array('status' => 404));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'order' => $this->format_order_data($order),
        ));
    }
    
    /**
     * Format order data with VAT handling
     */
    private function format_order_data($order) {
        $billing_country = $order->get_billing_country();
        $vat_number = $order->get_meta('_billing_vat_number');
        
        // Determine tax scenario
        $tax_scenario = $this->determine_tax_scenario($billing_country, $vat_number);
        
        // Get order totals
        $subtotal = floatval($order->get_subtotal());
        $total_tax = floatval($order->get_total_tax());
        $total = floatval($order->get_total());
        $shipping_total = floatval($order->get_shipping_total());
        $shipping_tax = floatval($order->get_shipping_tax());
        
        // Calculate VAT rate if applicable
        $vat_rate = 0;
        if ($total_tax > 0 && $subtotal > 0) {
            $vat_rate = round(($total_tax / ($subtotal + $shipping_total)) * 100, 2);
        }
        
        $order_data = array(
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'date_modified' => $order->get_date_modified() ? $order->get_date_modified()->date('Y-m-d H:i:s') : null,
            'date_paid' => $order->get_date_paid() ? $order->get_date_paid()->date('Y-m-d H:i:s') : null,
            'currency' => $order->get_currency(),
            'billing_country' => $billing_country,
            'tax_scenario' => $tax_scenario,
            'financial_data' => array(
                'subtotal' => $subtotal,
                'subtotal_formatted' => wc_price($subtotal, array('currency' => $order->get_currency())),
                'shipping' => $shipping_total,
                'shipping_formatted' => wc_price($shipping_total, array('currency' => $order->get_currency())),
                'shipping_tax' => $shipping_tax,
                'shipping_tax_formatted' => wc_price($shipping_tax, array('currency' => $order->get_currency())),
                'tax' => $total_tax,
                'tax_formatted' => wc_price($total_tax, array('currency' => $order->get_currency())),
                'vat_rate_percent' => $vat_rate,
                'total' => $total,
                'total_formatted' => wc_price($total, array('currency' => $order->get_currency())),
                'total_excl_tax' => $total - $total_tax,
                'total_excl_tax_formatted' => wc_price($total - $total_tax, array('currency' => $order->get_currency())),
            ),
            'payment_method' => $order->get_payment_method(),
        );
        
        return $order_data;
    }
    
    /**
     * Determine tax scenario based on country and VAT number
     */
    private function determine_tax_scenario($country, $vat_number) {
        // Finnish purchase
        if ($country === 'FI') {
            return 'finnish_purchase'; // ALV sisältyy (VAT included)
        }
        
        // EU countries
        $eu_countries = array(
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 
            'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 
            'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
        );
        
        if (in_array($country, $eu_countries)) {
            // EU reverse charge (B2B with VAT number)
            if (!empty($vat_number)) {
                return 'eu_reverse_charge'; // Käänteinen verovelvollisuus (Reverse charge)
            } else {
                return 'eu_b2c'; // EU B2C purchase with VAT
            }
        }
        
        // Non-EU purchase (no VAT)
        return 'non_eu_purchase'; // ALV 0% (VAT 0%)
    }
}

// Initialize the REST API server
function woo_rest_init() {
    $woo_rest_server = new WooCommerce_REST_Orders_Server();
}
add_action('plugins_loaded', 'woo_rest_init');

/**
 * Add CORS headers for REST API
 */
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        return $value;
    });
}, 15);
