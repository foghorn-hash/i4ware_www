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
        'itemDescription' => 'Item / Service',
        'vendorAmount' => 'Vendor Amount',
        'totalSales' => 'Total Sales',
        'transactionCount' => 'Transactions',
        'averageSale' => 'Average Sale',
        'total' => 'Total',
        'loading' => 'Loading...',
        'error' => 'Failed to fetch transactions. Please try again.',
        'noData' => 'No transactions found.'
    ),
    'fi' => array(
        'allTransactions' => 'Kaikki tapahtumat & myyntitilastot',
        'saleDate' => 'Myyntipäivä',
        'revenueSource' => 'Tulojen lähde',
        'itemDescription' => 'Palvelu / Tuote',
        'vendorAmount' => 'Toimittajan määrä',
        'totalSales' => 'Kokonaismyynti',
        'transactionCount' => 'Tapahtumia yht.',
        'averageSale' => 'Keskiostos',
        'total' => 'Yhteensä',
        'loading' => 'Ladataan...',
        'error' => 'Tapahtumien hakeminen epäonnistui. Yritä uudelleen.',
        'noData' => 'Tapahtumia ei löytynyt.'
    ),
    'ar' => array(
        'allTransactions' => 'جميع المعاملات وإحصاءات المبيعات',
        'saleDate' => 'تاريخ البيع',
        'revenueSource' => 'مصدر الدخل',
        'itemDescription' => 'الخدمة / المنتج',
        'vendorAmount' => 'مبلغ المورد',
        'totalSales' => 'إجمالي المبيعات',
        'transactionCount' => 'عدد المعاملات',
        'averageSale' => 'متوسط المعاملة',
        'total' => 'المجموع',
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
    $api_url = $api_base_url . '/api/reports/merged-sales?source=' . urlencode(strtolower($revenue_source));

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

    // Calculate summary statistics
    $total_amount = 0.0;
    $count = count($transactions);
    foreach ($transactions as $row) {
        $total_amount += !empty($row['vendorAmount']) ? floatval($row['vendorAmount']) : 0.0;
    }
    $avg_amount = $count > 0 ? ($total_amount / $count) : 0.0;

    // Build table HTML
    $html = '<div class="transactions-table-ai">';
    $html .= '<h3 class="transactions-title">' . esc_html(get_transactions_ai_string('allTransactions', $language)) . '</h3>';

    // Summary Statistics Cards
    $html .= '<div class="transactions-stats-bar">';
    $html .= '<div class="stat-card"><span class="stat-label">' . esc_html(get_transactions_ai_string('totalSales', $language)) . '</span><span class="stat-value highlight">' . esc_html(number_format($total_amount, 2, '.', ' ')) . ' €</span></div>';
    $html .= '<div class="stat-card"><span class="stat-label">' . esc_html(get_transactions_ai_string('transactionCount', $language)) . '</span><span class="stat-value">' . esc_html($count) . ' kpl</span></div>';
    $html .= '<div class="stat-card"><span class="stat-label">' . esc_html(get_transactions_ai_string('averageSale', $language)) . '</span><span class="stat-value">' . esc_html(number_format($avg_amount, 2, '.', ' ')) . ' €</span></div>';
    $html .= '</div>';

    $html .= '<div class="transactions-table-responsive">';
    $html .= '<table class="transactions-table striped bordered hover">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th class="text-start">' . esc_html(get_transactions_ai_string('saleDate', $language)) . '</th>';
    $html .= '<th class="text-start">' . esc_html(get_transactions_ai_string('revenueSource', $language)) . '</th>';
    $html .= '<th class="text-start">' . esc_html(get_transactions_ai_string('itemDescription', $language)) . '</th>';
    $html .= '<th class="text-end">' . esc_html(get_transactions_ai_string('vendorAmount', $language)) . '</th>';
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
        $source = !empty($row['revenueSource']) ? $row['revenueSource'] : 'PayPal';
        $item_name = !empty($row['itemName']) ? $row['itemName'] : (!empty($row['item_name']) ? $row['item_name'] : '-');

        $badge_class = 'source-badge source-' . sanitize_html_class(strtolower(str_replace(' ', '-', $source)));

        $html .= '<tr>';
        $html .= '<td class="text-start">' . esc_html($sale_date) . '</td>';
        $html .= '<td class="text-start"><span class="' . esc_attr($badge_class) . '">' . esc_html($source) . '</span></td>';
        $html .= '<td class="text-start">' . esc_html($item_name) . '</td>';
        $html .= '<td class="text-end font-mono"><strong>' . esc_html(number_format($amount, 2, '.', ' ')) . ' €</strong></td>';
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '<tfoot>';
    $html .= '<tr class="table-total-row">';
    $html .= '<td colspan="3" class="text-start"><strong>' . esc_html(get_transactions_ai_string('total', $language)) . '</strong></td>';
    $html .= '<td class="text-end font-mono"><strong>' . esc_html(number_format($total_amount, 2, '.', ' ')) . ' €</strong></td>';
    $html .= '</tr>';
    $html .= '</tfoot>';
    $html .= '</table>';
    $html .= '</div>';
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
            margin: 25px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        .transactions-title {
            font-size: 1.6em;
            font-weight: 700;
            margin-bottom: 18px;
            color: #1e293b;
        }

        .transactions-stats-bar {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .transactions-stats-bar .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 20px;
            min-width: 160px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            display: flex;
            flex-direction: column;
        }

        .transactions-stats-bar .stat-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 4px;
        }

        .transactions-stats-bar .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f172a;
        }

        .transactions-stats-bar .stat-value.highlight {
            color: #0284c7;
        }

        .transactions-table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        .transactions-table thead {
            background-color: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .transactions-table th {
            padding: 12px 16px;
            text-align: start;
            font-weight: 700;
            color: #334155;
            font-size: 0.9rem;
        }

        .transactions-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.95rem;
        }

        .transactions-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .transactions-table tfoot {
            background-color: #f8fafc;
            border-top: 2px solid #cbd5e1;
        }

        .table-total-row td {
            font-size: 1.05rem;
            color: #0f172a;
        }

        .source-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e2e8f0;
            color: #334155;
        }

        .source-badge.source-paypal {
            background: #e0f2fe;
            color: #0369a1;
        }

        .source-badge.source-atlassian-pty-ltd {
            background: #ede9fe;
            color: #6d28d9;
        }

        .source-badge.source-tallenteen-tekijänoikeuskorvaus-(radio/tv),
        .source-badge.source-tekijänoikeuskorvaus {
            background: #fef9c3;
            color: #854d0e;
        }

        .source-badge.source-mainostulo-(youtube/google),
        .source-badge.source-mainostulo {
            background: #fee2e2;
            color: #991b1b;
        }

        .text-start { text-align: start; }
        .text-end { text-align: right; }
        .font-mono { font-variant-numeric: tabular-nums; }
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