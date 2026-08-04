<?php
/**
 * Google AI Search - Transactions Table Shortcode
 * 
 * This shortcode creates a searchable transactions table with Polylang support.
 * Usage: [transactions_table_ai revenue_source="ALL"]
 * 
 * Translations: Finnish (Fi) and English (En)
 */

// Add this to your WordPress plugin or theme functions.php

// Define translations
$transactions_ai_strings = array(
    'en' => array(
        'allTransactions' => 'All Transactions',
        'saleDate' => 'Sale Date',
        'revenueSource' => 'Revenue Source',
        'vendorAmount' => 'Vendor Amount',
        'loading' => 'Loading...',
        'error' => 'Failed to fetch transactions. Please try again.',
        'noData' => 'No transactions found.'
    ),
    'fi' => array(
        'allTransactions' => 'Kaikki tapahtumat',
        'saleDate' => 'Myyntipäivä',
        'revenueSource' => 'Tulojen lähde',
        'vendorAmount' => 'Toimittajan määrä',
        'loading' => 'Ladataan...',
        'error' => 'Tapahtumien hakeminen epäonnistui. Yritä uudelleen.',
        'noData' => 'Tapahtumia ei löytynyt.'
    ),
    'ar' => array(
        'allTransactions' => 'جميع المعاملات',
        'saleDate' => 'تاريخ البيع',
        'revenueSource' => 'مصدر الدخل',
        'vendorAmount' => 'مبلغ المورد',
        'loading' => 'جارٍ التحميل...',
        'error' => 'فشل في جلب المعاملات. يرجى المحاولة مرة أخرى.',
        'noData' => 'لم يتم العثور على معاملات.'
    )
);

/**
 * Get current language from Polylang
 */
function get_transactions_ai_language()
{
    // Check if Polylang is active
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();
        if ($lang === 'fi' || $lang === 'ar' || $lang === 'en') {
            return $lang;
        }
    }

    // Fallback to WordPress locale
    $locale = get_locale();
    if (strpos($locale, 'fi') === 0) {
        return 'fi';
    } elseif (strpos($locale, 'ar') === 0) {
        return 'ar';
    }
    return 'en';
}

/**
 * Get translated string
 */
function get_transactions_ai_string($key, $language = null)
{
    global $transactions_ai_strings;

    if ($language === null) {
        $language = get_transactions_ai_language();
    }

    return isset($transactions_ai_strings[$language][$key])
        ? $transactions_ai_strings[$language][$key]
        : $transactions_ai_strings['en'][$key];
}

/**
 * Shortcode callback
 */
function transactions_table_ai_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'revenue_source' => 'ALL',
        'api_base_url' => 'https://api.example.com',
    ), $atts);

    $revenue_source = sanitize_text_field($atts['revenue_source']);
    $api_base_url = sanitize_url($atts['api_base_url']);
    $language = get_transactions_ai_language();

    // Fetch data from API
    $api_url = $api_base_url . '/api/reports/merged-sales?source=' . urlencode($revenue_source);

    $response = wp_remote_get($api_url, array(
        'timeout' => 30,
    ));

    if (is_wp_error($response)) {
        return '<p style="color: red;">' . esc_html(get_transactions_ai_string('error', $language)) . '</p>';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data['root'])) {
        return '<p>' . esc_html(get_transactions_ai_string('noData', $language)) . '</p>';
    }

    // Sort transactions by date
    $transactions = $data['root'];
    usort($transactions, function ($a, $b) {
        $date_a = strtotime($a['saleDate'] ?? '0');
        $date_b = strtotime($b['saleDate'] ?? '0');
        return $date_a - $date_b;
    });

    // Build table HTML
    $html = '<div class="transactions-table-ai">';
    $html .= '<h3 class="transactions-title">' . esc_html(get_transactions_ai_string('allTransactions', $language)) . '</h3>';
    $html .= '<table class="transactions-table striped bordered hover">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th class="text-start">' . esc_html(get_transactions_ai_string('saleDate', $language)) . '</th>';
    $html .= '<th class="text-start">' . esc_html(get_transactions_ai_string('vendorAmount', $language)) . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    foreach ($transactions as $row) {
        $sale_date = !empty($row['saleDate']) ? substr($row['saleDate'], 0, 10) : '';
        if (!empty($sale_date)) {
            $timestamp = strtotime($sale_date);
            if ($timestamp) {
                if ($language === 'fi') {
                    $sale_date = date('j.n.Y', $timestamp);
                } elseif ($language === 'ar') {
                    $formatted_date = date('d/m/Y', $timestamp);
                    $western = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
                    $eastern = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
                    $sale_date = str_replace($western, $eastern, $formatted_date);
                }
            }
        }
        $amount = !empty($row['vendorAmount']) ? floatval($row['vendorAmount']) : 0;

        $html .= '<tr>';
        $html .= '<td class="text-start">' . esc_html($sale_date) . '</td>';
        $html .= '<td class="text-start">' . esc_html(number_format($amount, 2, '.', '')) . ' €</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';

    return $html;
}

// Register shortcode
add_shortcode('transactions_table_ai', 'transactions_table_ai_shortcode');

/**
 * Add inline styles for the shortcode
 */
function transactions_table_ai_styles()
{
    ?>
    <style>
        .transactions-table-ai {
            margin: 20px 0;
        }

        .transactions-title {
            font-size: 1.5em;
            font-weight: 600;
            margin-bottom: 15px;
            color: #fff;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .transactions-table thead {
            background-color: #f8f9fa;
        }

        .transactions-table th {
            padding: 12px;
            text-align: start;
            border: 1px solid #dee2e6;
            font-weight: 600;
            color: #333;
        }

        .transactions-table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            color: #666;
        }

        .transactions-table tbody tr:nth-child(odd) {
            background-color: #fff;
        }

        .transactions-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .transactions-table tbody tr:hover {
            background-color: #f0f0f0;
        }

        .text-start {
            text-align: start;
        }
    </style>
    <?php
}

// Add styles to frontend
if (!is_admin()) {
    add_action('wp_head', 'transactions_table_ai_styles');
}

// SEO: Add structured data
function transactions_table_ai_structured_data($atts)
{
    $atts = shortcode_atts(array(
        'revenue_source' => 'ALL',
        'api_base_url' => 'https://api.example.com',
    ), $atts);

    $language = get_transactions_ai_language();
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Table',
        'name' => get_transactions_ai_string('allTransactions', $language),
        'description' => 'Transaction data with sale dates, revenue sources, and vendor amounts',
        'keywords' => 'transactions, sales, revenue, vendor amount, financial data'
    );

    return '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>';
}

add_action('wp_footer', function () {
    echo transactions_table_ai_structured_data(array());
});
?>