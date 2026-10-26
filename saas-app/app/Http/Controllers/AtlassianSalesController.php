<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\PayPalOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Carbon\CarbonImmutable;

class AtlassianSalesController extends Controller
{
    public $lastPayPalDiagnostic = null;

    /**
     * Fetch yearly sales data for pie chart.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getYearlySalesDistribution(Request $request)
    {
        try {
            $source = $request->query('source', 'all'); // 'all', 'atlassian', 'paypal', 'kk', 'hourly', 'capital'

            $yearlySums = [];
            $totalOverall = 0.0;

            // 1. PayPal Sales
            if ($source === 'all' || $source === 'paypal') {
                $paypalData = $this->fetchPayPalTransactions();
                foreach (($paypalData['root'] ?? []) as $tx) {
                    $amount = (float) ($tx['vendorAmount'] ?? 0);
                    $saleDate = $tx['saleDate'] ?? null;
                    if ($saleDate) {
                        $y = (int) date('Y', strtotime($saleDate));
                        $yearlySums[$y] = ($yearlySums[$y] ?? 0) + $amount;
                        $totalOverall += $amount;
                    }
                }
            }

            // 2. Atlassian Sales
            if ($source === 'all' || $source === 'atlassian') {
                try {
                    $atlassianData = $this->fetchTransactions();
                    foreach (($atlassianData['root'] ?? []) as $tx) {
                        $amount = (float) ($tx['vendorAmount'] ?? 0);
                        $saleDate = $tx['saleDate'] ?? null;
                        if ($saleDate) {
                            $y = (int) date('Y', strtotime($saleDate));
                            $yearlySums[$y] = ($yearlySums[$y] ?? 0) + $amount;
                            $totalOverall += $amount;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Atlassian fetch in getYearlySalesDistribution: ' . $e->getMessage());
                }
            }

            // 3. Local Invoices
            if (in_array($source, ['all', 'capital', 'kk', 'hourly', 'copyright', 'ads'], true)) {
                try {
                    $query = DB::table('invoices');
                    if ($source === 'kk') {
                        $query->whereIn('customer_id', [1, 7, 85, 88, 89]);
                    } elseif ($source === 'hourly') {
                        $query->whereNotIn('customer_id', [1, 7, 85, 86, 88, 89, 90, 91]);
                    } elseif ($source === 'capital') {
                        $query->where('customer_id', 86);
                    } elseif ($source === 'copyright') {
                        $query->where('customer_id', 90);
                    } elseif ($source === 'ads') {
                        $query->where('customer_id', 91);
                    }

                    $invoicesData = $query
                        ->select(
                            DB::raw('YEAR(due_date) as year'),
                            DB::raw('SUM(total_including_vat) as total_sales')
                        )
                        ->groupBy(DB::raw('YEAR(due_date)'))
                        ->get();

                    foreach ($invoicesData as $row) {
                        $y = (int) $row->year;
                        $amount = (float) $row->total_sales;
                        $yearlySums[$y] = ($yearlySums[$y] ?? 0) + $amount;
                        $totalOverall += $amount;
                    }
                } catch (\Exception $e) {
                    Log::warning('Local invoices in getYearlySalesDistribution: ' . $e->getMessage());
                }
            }

            ksort($yearlySums);

            $formattedData = [];
            foreach ($yearlySums as $year => $sum) {
                $formattedData[] = [
                    'year' => (int) $year,
                    'salesPercentage' => $totalOverall > 0 ? round(($sum / $totalOverall) * 100, 2) : 0,
                    'totalSales' => round($sum, 2),
                ];
            }

            return response()->json([
                'status' => 'success',
                'source' => $source,
                'totalOverall' => round($totalOverall, 2),
                'root' => $formattedData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch yearly sales data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getSolvencyData()
    {
        try {
            $data = DB::table('financial_records')
                ->select(
                    DB::raw('YEAR(date) as year'),
                    DB::raw('SUM(own_capital) as own_capital'),
                    DB::raw('SUM(total_assets) as total_assets')
                )
                ->groupBy(DB::raw('YEAR(date)'))
                ->get()
                ->map(function ($item) {
                    $solvency = $item->total_assets > 0
                        ? round(($item->own_capital / $item->total_assets) * 100, 2)
                        : 0;

                    return [
                        'year' => $item->year,
                        'solvency' => $solvency,
                    ];
                });

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMergedMonthlyIncomeAllYears(Request $request)
    {
        try {
            $year = (int) $request->query('year', now()->year);
            $source = $request->query('source', 'all'); // 'all', 'atlassian', 'paypal', 'hourly', 'kk', 'capital'

            // --- Fetch Atlassian sales (if requested) ---
            $normalizedAtlassianSales = [];
            if ($source === 'all' || $source === 'atlassian') {
                try {
                    $atlassianSales = $this->fetchTransactions();
                    $normalizedAtlassianSales = array_map(function ($tx) {
                        return [
                            'saleDate' => $tx['saleDate'],
                            'vendorAmount' => (float) $tx['vendorAmount'],
                            'revenueSource' => 'Atlassian Pty Ltd',
                        ];
                    }, $atlassianSales['root'] ?? []);
                } catch (\Exception $e) {
                    Log::warning('Atlassian fetch error in getMergedMonthlyIncomeAllYears: ' . $e->getMessage());
                }
            }

            // --- Fetch PayPal sales (if requested) ---
            $normalizedPayPalSales = [];
            if ($source === 'all' || $source === 'paypal') {
                $paypalSales = $this->fetchPayPalTransactions();
                $normalizedPayPalSales = array_map(function ($tx) {
                    return [
                        'saleDate' => $tx['saleDate'],
                        'vendorAmount' => (float) $tx['vendorAmount'],
                        'revenueSource' => $tx['revenueSource'] ?? 'PayPal',
                    ];
                }, $paypalSales['root'] ?? []);
            }

            // --- Fetch local sales (if requested) ---
            $localSales = collect();
            if (in_array($source, ['all', 'capital', 'kk', 'hourly', 'copyright', 'ads'], true)) {
                try {
                    $query = Invoice::with('customer')->orderBy('due_date');
                    if ($source === 'kk') {
                        $query->whereIn('customer_id', [1, 7, 85, 88, 89]);
                    } elseif ($source === 'hourly') {
                        $query->whereNotIn('customer_id', [1, 7, 85, 86, 88, 89, 90, 91]);
                    } elseif ($source === 'capital') {
                        $query->where('customer_id', 86);
                    } elseif ($source === 'copyright') {
                        $query->where('customer_id', 90);
                    } elseif ($source === 'ads') {
                        $query->where('customer_id', 91);
                    }

                    $localSales = $query->get()->map(function ($invoice) {
                        $revenueSource = 'Confidential (Hourly)';
                        if ($invoice->customer_id == 85) {
                            $revenueSource = 'KELA/Opintotuki';
                        } elseif ($invoice->customer_id == 86) {
                            $revenueSource = 'Perintö';
                        } elseif ($invoice->customer_id == 89) {
                            $revenueSource = 'KELA/Toimeentulotuki';
                        } elseif ($invoice->customer_id == 88) {
                            $revenueSource = 'KELA/Työmarkkinatuki';
                        } elseif ($invoice->customer_id == 1) {
                            $revenueSource = 'KELA/Varma';
                        } elseif ($invoice->customer_id == 7) {
                            $revenueSource = 'VARMA';
                        } elseif ($invoice->customer_id == 90) {
                            $revenueSource = 'Tallenteen tekijänoikeuskorvaus (Radio/TV)';
                        } elseif ($invoice->customer_id == 91) {
                            $revenueSource = 'Mainostulo (YouTube/Google)';
                        } elseif ($invoice->customer && !empty(trim((string) $invoice->customer->name))) {
                            $nameLower = strtolower(trim((string) $invoice->customer->name));
                            if (str_contains($nameLower, 'atlassian')) {
                                $revenueSource = 'Atlassian Pty Ltd';
                            }
                        }

                        return [
                            'saleDate' => $invoice->due_date,
                            'vendorAmount' => (float) $invoice->total_including_vat,
                            'revenueSource' => $revenueSource,
                        ];
                    });
                } catch (\Exception $e) {
                    Log::warning('Invoice query error in getMergedMonthlyIncomeAllYears: ' . $e->getMessage());
                }
            }

            // --- Merge and keep only the requested year ---
            $merged = collect($normalizedAtlassianSales)
                ->merge($normalizedPayPalSales)
                ->merge($localSales)
                ->filter(function ($row) use ($year) {
                    try {
                        $d = CarbonImmutable::parse($row['saleDate']);
                        return $d->year === $year;
                    } catch (\Exception $e) {
                        return false;
                    }
                })
                ->map(function ($row) {
                    $d = CarbonImmutable::parse($row['saleDate']);
                    return [
                        'year' => $d->year,
                        'month' => $d->month,
                        'vendorAmount' => (float) $row['vendorAmount'],
                    ];
                });

            // --- Aggregate by month ---
            $byMonth = $merged
                ->groupBy('month')
                ->map(function (Collection $items) {
                    return $items->sum('vendorAmount');
                });

            $yearTotal = (float) $byMonth->sum();

            // --- Build 12 rows, zero-fill, add label & percentage ---
            $months = collect(range(1, 12))->map(function ($m) use ($byMonth, $yearTotal) {
                $total = (float) ($byMonth[$m] ?? 0);
                return [
                    'month' => $m,
                    'label' => CarbonImmutable::create(null, $m, 1)->format('M'),
                    'totalIncome' => round($total, 2),
                    'incomePercentage' => $yearTotal > 0 ? round(($total / $yearTotal) * 100, 2) : 0.0,
                ];
            });

            return response()->json([
                'status' => 'success',
                'year' => $year,
                'source' => $source,
                'yearTotal' => round($yearTotal, 2),
                'root' => $months->values(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch monthly income data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMergedSales(Request $request)
    {
        try {
            $source = $request->query('source', 'all'); // 'all', 'atlassian', 'paypal', 'hourly', 'kk', 'capital'

            // Fetch Atlassian sales if needed
            $normalizedAtlassianSales = [];
            if ($source === 'all' || $source === 'atlassian') {
                try {
                    $atlassianSales = $this->fetchTransactions();
                    $normalizedAtlassianSales = array_map(function ($transaction) {
                        return [
                            'saleDate' => $transaction['saleDate'],
                            'vendorAmount' => $transaction['vendorAmount'],
                            'revenueSource' => 'Atlassian Pty Ltd',
                            'itemName' => $transaction['itemName'] ?? 'Atlassian Marketplace License',
                            'customer' => $transaction['customer'] ?? 'Atlassian Marketplace',
                        ];
                    }, $atlassianSales['root'] ?? []);
                } catch (\Exception $e) {
                    Log::warning('Atlassian fetch error in getMergedSales: ' . $e->getMessage());
                }
            }

            // Fetch PayPal sales if needed
            $normalizedPayPalSales = [];
            if ($source === 'all' || $source === 'paypal') {
                $paypalSales = $this->fetchPayPalTransactions();
                $normalizedPayPalSales = array_map(function ($transaction) {
                    $rawCustomer = $transaction['customer'] ?? '';
                    $isAtl = (stripos($rawCustomer, 'atlassian') !== false);
                    return [
                        'saleDate' => $transaction['saleDate'],
                        'vendorAmount' => $transaction['vendorAmount'],
                        'revenueSource' => $transaction['revenueSource'] ?? 'PayPal',
                        'itemName' => $transaction['itemName'] ?? 'PayPal Tilaus',
                        'customer' => $isAtl ? 'Atlassian Pty Ltd' : '',
                    ];
                }, $paypalSales['root'] ?? []);
            }

            // Fetch local sales (from invoices) if needed
            $localSales = collect();
            if (in_array($source, ['all', 'capital', 'kk', 'hourly', 'copyright', 'ads'], true)) {
                try {
                    $query = Invoice::with('customer')->orderBy('due_date');
                    if ($source === 'kk') {
                        $query->whereIn('customer_id', [1, 7, 85, 88, 89]);
                    } elseif ($source === 'hourly') {
                        $query->whereNotIn('customer_id', [1, 7, 85, 86, 88, 89, 90, 91]);
                    } elseif ($source === 'capital') {
                        $query->where('customer_id', 86);
                    } elseif ($source === 'copyright') {
                        $query->where('customer_id', 90);
                    } elseif ($source === 'ads') {
                        $query->where('customer_id', 91);
                    }
                    $localSales = $query->get()->map(function ($invoice) {
                        $revenueSource = 'Confidential (Hourly)';
                        if ($invoice->customer_id == 85) {
                            $revenueSource = 'KELA/Opintotuki';
                        } elseif ($invoice->customer_id == 86) {
                            $revenueSource = 'Perintö';
                        } elseif ($invoice->customer_id == 89) {
                            $revenueSource = 'KELA/Toimeentulotuki';
                        } elseif ($invoice->customer_id == 88) {
                            $revenueSource = 'KELA/Työmarkkinatuki';
                        } elseif ($invoice->customer_id == 1) {
                            $revenueSource = 'KELA/Varma';
                        } elseif ($invoice->customer_id == 7) {
                            $revenueSource = 'VARMA';
                        } elseif ($invoice->customer_id == 90) {
                            $revenueSource = 'Tallenteen tekijänoikeuskorvaus (Radio/TV)';
                        } elseif ($invoice->customer_id == 91) {
                            $revenueSource = 'Mainostulo (YouTube/Google)';
                        } elseif ($invoice->customer && !empty(trim((string) $invoice->customer->name))) {
                            $nameLower = strtolower(trim((string) $invoice->customer->name));
                            if (str_contains($nameLower, 'atlassian')) {
                                $revenueSource = 'Atlassian Pty Ltd';
                            }
                        }

                        $customerName = '';
                        if ($invoice->customer && !empty(trim((string) $invoice->customer->name))) {
                            $rawName = trim((string) $invoice->customer->name);
                            if (stripos($rawName, 'atlassian') !== false) {
                                $customerName = 'Atlassian Pty Ltd';
                            }
                        }

                        return [
                            'saleDate' => $invoice->due_date,
                            'vendorAmount' => (string) $invoice->total_including_vat,
                            'revenueSource' => $revenueSource,
                            'itemName' => 'Lasku #' . ($invoice->invoice_number ?? $invoice->id),
                            'customer' => $customerName,
                        ];
                    });
                } catch (\Exception $e) {
                    Log::warning('Invoice query error in getMergedSales: ' . $e->getMessage());
                }
            }

            // Merge sales data
            $mergedSales = collect($normalizedAtlassianSales)
                ->merge($normalizedPayPalSales)
                ->merge($localSales)
                ->sortBy('saleDate')
                ->values()
                ->all();

            // Return merged sales as JSON
            return response()->json(['root' => $mergedSales]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCombinedSales()
    {
        $localSales = $this->getLocalSales();
        $atlassianSales = [];
        try {
            $atlassianSales = $this->fetchTransactions()['root'] ?? [];
        } catch (\Exception $e) {}

        $paypalSales = $this->fetchPayPalTransactions()['root'] ?? [];

        $combinedSales = [
            'localSales' => $localSales,
            'atlassianSales' => $atlassianSales,
            'paypalSales' => $paypalSales,
        ];

        return response()->json($combinedSales);
    }

    private function getLocalSales()
    {
        try {
            $invoices = Invoice::orderBy('due_date')->get();

            $totalExcludingVat = 0;
            $totalIncludingVat = 0;

            foreach ($invoices as $invoice) {
                $totalExcludingVat += $invoice->total_excluding_vat;
                $totalIncludingVat += $invoice->total_including_vat;
            }

            return [
                'totalInvoices' => $invoices->count(),
                'totalExcludingVat' => number_format($totalExcludingVat, 2),
                'totalIncludingVat' => number_format($totalIncludingVat, 2),
                'invoices' => $invoices,
            ];
        } catch (\Exception $e) {
            return [
                'totalInvoices' => 0,
                'totalExcludingVat' => '0.00',
                'totalIncludingVat' => '0.00',
                'invoices' => [],
            ];
        }
    }

    public function getTransactions()
    {
        return response()->json($this->fetchTransactions());
    }

    public function getSalesReport(Request $request)
    {
        $source = $request->query('source', 'all'); // 'all', 'atlassian', 'paypal', 'hourly', 'kk', 'capital'
        $json = ['root' => []];

        // Fetch Atlassian sales data if needed
        if ($source === 'all' || $source === 'atlassian') {
            try {
                $atlassianData = $this->fetchTransactions();
                foreach (($atlassianData['root'] ?? []) as $transaction) {
                    $vendorAmount = (float) ($transaction['vendorAmount'] ?? 0);
                    $saleDate = $transaction['saleDate'] ?? null;
                    if ($saleDate) {
                        $saleYear = date("Y", strtotime($saleDate));
                        if (!isset($json['root'][$saleYear])) {
                            $json['root'][$saleYear] = [
                                'balanceVendor' => 0,
                                'saleYear' => $saleYear,
                            ];
                        }
                        $json['root'][$saleYear]['balanceVendor'] += $vendorAmount;
                    }
                }
            } catch (\Exception $e) {
                Log::error('Atlassian API Error in getSalesReport: ' . $e->getMessage());
            }
        }

        // Fetch PayPal sales data if needed
        if ($source === 'all' || $source === 'paypal') {
            try {
                $paypalData = $this->fetchPayPalTransactions();
                foreach (($paypalData['root'] ?? []) as $transaction) {
                    $vendorAmount = (float) ($transaction['vendorAmount'] ?? 0);
                    $saleDate = $transaction['saleDate'] ?? null;
                    if ($saleDate) {
                        $saleYear = date("Y", strtotime($saleDate));
                        if (!isset($json['root'][$saleYear])) {
                            $json['root'][$saleYear] = [
                                'balanceVendor' => 0,
                                'saleYear' => $saleYear,
                            ];
                        }
                        $json['root'][$saleYear]['balanceVendor'] += $vendorAmount;
                    }
                }
            } catch (\Exception $e) {
                Log::error('PayPal Error in getSalesReport: ' . $e->getMessage());
            }
        }

        // Fetch local sales data if needed
        if (in_array($source, ['all', 'capital', 'kk', 'hourly', 'copyright', 'ads'], true)) {
            try {
                $query = Invoice::orderBy('due_date');
                if ($source === 'kk') {
                    $query->whereIn('customer_id', [1, 7, 85, 88, 89]);
                } elseif ($source === 'hourly') {
                    $query->whereNotIn('customer_id', [1, 7, 85, 86, 88, 89, 90, 91]);
                } elseif ($source === 'capital') {
                    $query->where('customer_id', 86);
                } elseif ($source === 'copyright') {
                    $query->where('customer_id', 90);
                } elseif ($source === 'ads') {
                    $query->where('customer_id', 91);
                }
                $localSales = $query->get()->map(function ($invoice) {
                    return [
                        'saleYear' => date('Y', strtotime($invoice->due_date)),
                        'amount' => $invoice->total_including_vat,
                    ];
                });

                foreach ($localSales as $localSale) {
                    $saleYear = $localSale['saleYear'];
                    $amount = (float) $localSale['amount'];
                    if (!isset($json['root'][$saleYear])) {
                        $json['root'][$saleYear] = [
                            'balanceVendor' => 0,
                            'saleYear' => $saleYear,
                        ];
                    }
                    $json['root'][$saleYear]['balanceVendor'] += $amount;
                }
            } catch (\Exception $e) {
                Log::warning('Local invoices in getSalesReport: ' . $e->getMessage());
            }
        }

        // Convert the aggregated data into a flat array
        ksort($json['root']);
        $yearData = ['root' => []];
        $i = 0;
        foreach ($json['root'] as $year => $data) {
            $yearData['root'][$i] = [
                'saleYear' => (string) $data['saleYear'],
                'balanceVendor' => round($data['balanceVendor'], 2),
            ];
            $i++;
        }

        return response()->json($yearData);
    }

    /**
     * Fetch PayPal transactions and orders
    /**
     * Fetch real PayPal transactions.
     * Checks local DB table (excluding demo PP-TX data), direct PayPal REST Reporting API,
     * and WordPress / PayPal REST API endpoint (real webhooks & receipts).
     * Demo data is completely removed.
     */
    public function fetchPayPalTransactions()
    {
        $cacheKey = 'paypal_transactions_cache_v2';

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            // 1. Check local DB table if exists and populated with real orders
            try {
                if (DB::getSchemaBuilder()->hasTable('paypal_orders')) {
                    $orders = DB::table('paypal_orders')
                        ->where('status', 'completed')
                        ->where('transaction_id', 'NOT LIKE', 'PP-TX-%') // Exclude any mock seeder data
                        ->orderBy('payment_date', 'asc')
                        ->get();

                    if ($orders->isNotEmpty()) {
                        $root = [];
                        foreach ($orders as $order) {
                            $root[] = [
                                'transactionId' => $order->transaction_id,
                                'subscriptionId' => $order->subscription_id ?? '',
                                'saleDate' => $order->payment_date,
                                'vendorAmount' => number_format((float) $order->gross_amount, 2, '.', ''),
                                'currency' => $order->currency ?? 'EUR',
                                'revenueSource' => 'PayPal',
                                'itemName' => $order->item_name ?? 'PayPal-tilaus',
                                'customer' => '', // Always hide customer names
                                'status' => $order->status ?? 'completed',
                            ];
                        }
                        return ['status' => 'success', 'source' => 'paypal_db', 'root' => $root];
                    }
                }
            } catch (\Exception $e) {
                Log::info('Local paypal_orders table query skipped: ' . $e->getMessage());
            }

            // 2. Direct live PayPal REST Reporting API (If PAYPAL_CLIENT_ID & PAYPAL_CLIENT_SECRET configured)
            try {
                $paypalApiOrders = $this->fetchFromPayPalRestApi();
                if (!empty($paypalApiOrders)) {
                    return ['status' => 'success', 'source' => 'paypal_rest_api', 'root' => $paypalApiOrders];
                }
            } catch (\Exception $e) {
                Log::info('PayPal REST API fetch error: ' . $e->getMessage());
            }

            // 3. Check WordPress / PayPal webhook & receipt API endpoint
            $url = env('PAYPAL_API_URL', 'https://www.i4ware.fi/wp-json/i4ware-paypal/v1/orders');
            try {
                $response = Http::timeout(8)->accept('application/json')->get($url);
                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['root']) && is_array($data['root'])) {
                        $realOrders = array_values(array_filter($data['root'], function ($tx) {
                            $txId = $tx['transactionId'] ?? '';
                            return !str_starts_with($txId, 'PP-TX-') && !str_starts_with($txId, 'MOCK-');
                        }));
                        if (!empty($realOrders)) {
                            return ['status' => 'success', 'source' => 'wordpress_paypal_api', 'root' => $realOrders];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::info('WordPress PayPal API fetch skipped: ' . $e->getMessage());
            }

            // 4. No demo data fallback: return empty list if no real transactions are recorded
            return ['status' => 'success', 'source' => 'paypal', 'root' => []];
        });
    }

    /**
     * Query real transactions directly from PayPal REST Reporting API.
     */
    private function fetchFromPayPalRestApi()
    {
        $clientId = env('PAYPAL_CLIENT_ID');
        $clientSecret = env('PAYPAL_CLIENT_SECRET');
        $env = env('PAYPAL_MODE', 'live');

        if (empty($clientId) || empty($clientSecret)) {
            return [];
        }

        $baseUrl = ($env === 'sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        // 1. Get OAuth2 Token
        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->timeout(10)
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials'
            ]);

        if (!$tokenResponse->successful()) {
            $err = $tokenResponse->body();
            Log::warning('PayPal OAuth token request failed: ' . $err);
            $this->lastPayPalDiagnostic = 'PayPal OAuth token request failed: ' . $err;
            return [];
        }

        $accessToken = $tokenResponse->json()['access_token'] ?? null;
        if (!$accessToken) {
            $this->lastPayPalDiagnostic = 'PayPal OAuth token response missing access_token.';
            return [];
        }

        // 2. Fetch Transactions from PayPal Reporting API (last 31 days)
        $endDate = gmdate('Y-m-d\TH:i:s\Z');
        $startDate = gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days'));

        $txResponse = Http::withToken($accessToken)
            ->timeout(15)
            ->get("{$baseUrl}/v1/reporting/transactions", [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'fields' => 'all',
                'page_size' => 100,
            ]);

        if (!$txResponse->successful()) {
            $errorBody = $txResponse->body();
            Log::warning('PayPal Reporting API query failed: ' . $errorBody);
            if (str_contains($errorBody, 'insufficient permissions') || str_contains($errorBody, 'AUTHORIZATION_FAILURE')) {
                $this->lastPayPalDiagnostic = "PayPal Reporting API: Authorization failed due to insufficient permissions. Syy: REST Appilta puuttuu 'Transaction search' -oikeus PayPal Developer -portaalissa tai PayPal-tili ei ole yritystili (Business Account).";
            } else {
                $this->lastPayPalDiagnostic = 'PayPal Reporting API virhe: ' . $errorBody;
            }
            return [];
        }

        $txData = $txResponse->json();
        $transactions = $txData['transaction_details'] ?? [];

        $root = [];
        foreach ($transactions as $detail) {
            $info = $detail['transaction_info'] ?? [];
            $status = strtolower($info['transaction_status'] ?? '');

            if (!in_array($status, ['s', 'success', 'completed', 'cleared'], true)) {
                continue;
            }

            $amount = (float) ($info['transaction_amount']['value'] ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $txId = $info['transaction_id'] ?? '';
            $saleDate = $info['transaction_initiation_date'] ?? $info['transaction_updated_date'] ?? now()->toIso8601String();
            $currency = $info['transaction_amount']['currency_code'] ?? 'EUR';
            $item = $detail['cart_info']['item_details'][0]['item_name'] ?? 'PayPal Tilaus';

            $formattedDate = date('Y-m-d H:i:s', strtotime($saleDate));

            // Persist real transactions to database table paypal_orders
            try {
                if (DB::getSchemaBuilder()->hasTable('paypal_orders')) {
                    DB::table('paypal_orders')->updateOrInsert(
                        ['transaction_id' => $txId],
                        [
                            'item_name' => $item,
                            'gross_amount' => $amount,
                            'vat_amount' => round($amount * 0.255 / 1.255, 2),
                            'net_amount' => round($amount / 1.255, 2),
                            'currency' => $currency,
                            'country' => 'FI',
                            'status' => 'completed',
                            'payment_date' => $formattedDate,
                            'updated_at' => now(),
                        ]
                    );
                }
            } catch (\Exception $e) {
                Log::info('Error storing paypal transaction in DB: ' . $e->getMessage());
            }

            $root[] = [
                'transactionId' => $txId,
                'saleDate' => $formattedDate,
                'vendorAmount' => number_format($amount, 2, '.', ''),
                'currency' => $currency,
                'revenueSource' => 'PayPal',
                'itemName' => $item,
                'customer' => '',
                'status' => 'completed',
            ];
        }

        return $root;
    }

    /**
     * Real-time Webhook receiver for live PayPal events.
     * URL: POST /api/paypal/webhook
     */
    public function handlePayPalWebhook(Request $request)
    {
        try {
            $payload = $request->all();
            $eventType = $payload['event_type'] ?? '';
            $resource = $payload['resource'] ?? [];

            Log::info("PayPal Webhook received event: {$eventType}");

            // Process payment completion events
            if (in_array($eventType, ['PAYMENT.SALE.COMPLETED', 'CHECKOUT.ORDER.APPROVED', 'PAYMENT.CAPTURE.COMPLETED'], true)) {
                $txId = $resource['id'] ?? ($resource['transaction_id'] ?? ('PP-' . uniqid()));
                $amount = (float) ($resource['amount']['total'] ?? ($resource['amount']['value'] ?? 0));
                $currency = $resource['amount']['currency'] ?? ($resource['amount']['currency_code'] ?? 'EUR');
                $paymentDate = $resource['create_time'] ?? now()->toDateTimeString();
                $subscriptionId = $resource['billing_agreement_id'] ?? ($resource['subscription_id'] ?? null);

                if ($amount > 0 && DB::getSchemaBuilder()->hasTable('paypal_orders')) {
                    DB::table('paypal_orders')->updateOrInsert(
                        ['transaction_id' => $txId],
                        [
                            'subscription_id' => $subscriptionId,
                            'customer_name' => 'Asiakas',
                            'company_name' => null,
                            'item_name' => 'PayPal Tilaus',
                            'gross_amount' => $amount,
                            'vat_amount' => round($amount * 0.255 / 1.255, 2),
                            'net_amount' => round($amount / 1.255, 2),
                            'currency' => $currency,
                            'country' => 'FI',
                            'status' => 'completed',
                            'payment_date' => date('Y-m-d H:i:s', strtotime($paymentDate)),
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    Cache::forget('paypal_transactions_cache_v2');
                    Cache::forget('paypal_transactions_cache_v1');
                }
            }

            return response()->json(['status' => 'success', 'message' => 'Webhook processed'], 200);
        } catch (\Throwable $e) {
            Log::error('PayPal Webhook handling failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * On-demand sync endpoint to refresh PayPal transactions.
     * URL: GET /api/paypal/sync
     */
    public function syncPayPalTransactions(Request $request)
    {
        Cache::forget('paypal_transactions_cache_v2');
        Cache::forget('paypal_transactions_cache_v1');

        $data = $this->fetchPayPalTransactions();

        return response()->json([
            'status' => 'success',
            'synced_count' => count($data['root'] ?? []),
            'source' => $data['source'] ?? 'paypal',
            'diagnostic_message' => $this->lastPayPalDiagnostic,
            'data' => $data['root'] ?? [],
        ]);
    }

    /**
     * Test and diagnose PayPal API credentials, OAuth scopes, and reporting API access.
     * URL: GET /api/paypal/debug
     */
    public function debugPayPalApi()
    {
        $clientId = env('PAYPAL_CLIENT_ID');
        $clientSecret = env('PAYPAL_CLIENT_SECRET');
        $env = env('PAYPAL_MODE', 'live');
        $baseUrl = ($env === 'sandbox') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        if (empty($clientId) || empty($clientSecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PAYPAL_CLIENT_ID tai PAYPAL_CLIENT_SECRET puuttuu .env-tiedostosta.',
            ], 400);
        }

        // 1. OAuth2 Token Request
        $tokenResponse = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->timeout(10)
            ->post("{$baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials'
            ]);

        if (!$tokenResponse->successful()) {
            return response()->json([
                'status' => 'error',
                'step' => 'oauth_token',
                'http_status' => $tokenResponse->status(),
                'response' => $tokenResponse->json() ?? $tokenResponse->body(),
                'message' => 'Tunnistautuminen epäonnistui. Varmista Client ID ja Secret sekä ympäristö (Live vs Sandbox).',
            ], 401);
        }

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'] ?? null;
        $scopeString = $tokenData['scope'] ?? '';
        $scopes = array_values(array_filter(explode(' ', $scopeString)));
        $hasReportingScope = str_contains($scopeString, 'reporting/search/read');

        // Check if user requested token reset/invalidation to bypass 9h cache
        $resetRequested = request() ? (request()->boolean('reset') || request()->boolean('reset_token')) : false;
        $tokenResetStatus = null;

        if ($resetRequested && !empty($accessToken)) {
            $termResp = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(10)
                ->post("{$baseUrl}/v1/oauth2/token/terminate", [
                    'token' => $accessToken,
                ]);

            $tokenResetStatus = $termResp->successful() ? 'Token invalidated successfully' : 'Token termination returned: ' . $termResp->status();

            // Request fresh token after termination
            $freshTokenResp = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->timeout(10)
                ->post("{$baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($freshTokenResp->successful()) {
                $tokenData = $freshTokenResp->json();
                $accessToken = $tokenData['access_token'] ?? null;
                $scopeString = $tokenData['scope'] ?? '';
                $scopes = array_values(array_filter(explode(' ', $scopeString)));
                $hasReportingScope = str_contains($scopeString, 'reporting/search/read');
            }
        }

        // 2. Test Transaction Search API
        $endDate = gmdate('Y-m-d\TH:i:s\Z');
        $startDate = gmdate('Y-m-d\TH:i:s\Z', strtotime('-30 days'));

        $txResponse = Http::withToken($accessToken)
            ->timeout(15)
            ->get("{$baseUrl}/v1/reporting/transactions", [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'fields' => 'all',
                'page_size' => 10,
            ]);

        $txBody = $txResponse->json() ?? $txResponse->body();

        return response()->json([
            'status' => $txResponse->successful() ? 'success' : 'permission_error',
            'mode' => $env,
            'base_url' => $baseUrl,
            'client_id_preview' => substr($clientId, 0, 8) . '...' . substr($clientId, -4),
            'oauth_status' => $tokenResponse->status(),
            'token_reset_status' => $tokenResetStatus,
            'has_reporting_scope' => $hasReportingScope,
            'reporting_scope_name' => 'https://uri.paypal.com/services/reporting/search/read',
            'granted_scopes' => $scopes,
            'reporting_api_http_status' => $txResponse->status(),
            'reporting_api_response' => $txBody,
            'solution' => !$hasReportingScope
                ? 'Developer Portalissa (developer.paypal.com -> Apps & Credentials -> Live App) pitää laittaa rasti ruutuun: "Transaction search". Huom: Jos teit muutoksen juuri äsken, PayPalin token-välimuistin vuoksi luo uusi Secret tai luo uusi Live App.'
                : 'Oikeudet kunnossa!',
        ]);
    }

    private function fetchTransactions()
    {
        $vendorId = env('ATLASSIAN_VENDOR_ID');
        $username = env('ATLASSIAN_USERNAME');
        $password = env('ATLASSIAN_PASSWORD');

        if (empty($vendorId) || empty($username) || empty($password)) {
            return ['root' => []];
        }

        $uri = "https://marketplace.atlassian.com/rest/2/vendors/{$vendorId}/reporting/sales/transactions/export?accept=json&order=asc";

        try {
            $response = Http::withBasicAuth($username, $password)
                ->timeout(60)
                ->accept('application/json')
                ->get($uri);

            if (!$response->successful()) {
                return ['root' => []];
            }

            $transactions = $response->json();
            $rates = $this->getUsdToEurRates();

            $balanceVendor = 0;
            $json = ['root' => []];

            foreach ($transactions as $i => $transaction) {
                $usdAmount = (float) ($transaction['purchaseDetails']['vendorAmount'] ?? 0);
                $saleDate = $transaction['purchaseDetails']['saleDate'] ?? null;
                
                $rate = 1.0;
                if ($saleDate) {
                    $dateOnly = date('Y-m-d', strtotime($saleDate));
                    $rate = $this->getExchangeRateForDate($rates, $dateOnly);
                }
                
                $eurAmount = $usdAmount * $rate;
                $vendorAmount = number_format($eurAmount, 2, '.', '');
                $balanceVendor += $vendorAmount;

                $json['root'][$i] = [
                    'vendorAmount' => $vendorAmount,
                    'saleDate' => $saleDate,
                    'revenueSource' => 'Atlassian Pty Ltd',
                    'originalUsdAmount' => $usdAmount,
                    'exchangeRate' => $rate,
                ];
            }

            return $json;
        } catch (\Exception $e) {
            Log::warning('Atlassian fetchTransactions failed: ' . $e->getMessage());
            return ['root' => []];
        }
    }

    public function getCumulativeSales(Request $request)
    {
        try {
            $source = $request->query('source', 'all'); // 'all', 'atlassian', 'paypal', 'hourly', 'kk', 'capital'
            $salesByDate = [];
            $cumulativeVendorBalance = 0;

            // Process Atlassian sales if needed
            if ($source === 'all' || $source === 'atlassian') {
                try {
                    $atlassianData = $this->fetchTransactions();
                    foreach (($atlassianData['root'] ?? []) as $transaction) {
                        $saleDate = $transaction['saleDate'] ?? null;
                        $vendorAmount = (float) ($transaction['vendorAmount'] ?? 0);
                        if ($saleDate) {
                            $formattedDate = date('Y-m', strtotime($saleDate));
                            $salesByDate[$formattedDate] = ($salesByDate[$formattedDate] ?? 0) + $vendorAmount;
                        }
                    }
                } catch (\Exception $e) {}
            }

            // Process PayPal sales if needed
            if ($source === 'all' || $source === 'paypal') {
                try {
                    $paypalData = $this->fetchPayPalTransactions();
                    foreach (($paypalData['root'] ?? []) as $transaction) {
                        $saleDate = $transaction['saleDate'] ?? null;
                        $vendorAmount = (float) ($transaction['vendorAmount'] ?? 0);
                        if ($saleDate) {
                            $formattedDate = date('Y-m', strtotime($saleDate));
                            $salesByDate[$formattedDate] = ($salesByDate[$formattedDate] ?? 0) + $vendorAmount;
                        }
                    }
                } catch (\Exception $e) {}
            }

            // Process local sales if needed
            if (in_array($source, ['all', 'capital', 'kk', 'hourly', 'copyright', 'ads'], true)) {
                try {
                    $query = Invoice::orderBy('due_date');
                    if ($source === 'kk') {
                        $query->whereIn('customer_id', [1, 7, 85, 88, 89]);
                    } elseif ($source === 'hourly') {
                        $query->whereNotIn('customer_id', [1, 7, 85, 86, 88, 89, 90, 91]);
                    } elseif ($source === 'capital') {
                        $query->where('customer_id', 86);
                    } elseif ($source === 'copyright') {
                        $query->where('customer_id', 90);
                    } elseif ($source === 'ads') {
                        $query->where('customer_id', 91);
                    }
                    $localSales = $query->get()->map(function ($invoice) {
                        return [
                            'saleDate' => date('Y-m', strtotime($invoice->due_date)),
                            'amount' => (float) $invoice->total_including_vat,
                        ];
                    });

                    foreach ($localSales as $localSale) {
                        $saleDate = $localSale['saleDate'];
                        $amount = (float) $localSale['amount'];
                        $salesByDate[$saleDate] = ($salesByDate[$saleDate] ?? 0) + $amount;
                    }
                } catch (\Exception $e) {}
            }

            // Sort sales by date
            ksort($salesByDate);

            // Calculate cumulative balance
            $cumulativeData = [];
            foreach ($salesByDate as $saleDate => $amount) {
                $cumulativeVendorBalance += $amount;

                $cumulativeData[] = [
                    'saleDate' => $saleDate,
                    'cumulativeVendorBalance' => round($cumulativeVendorBalance, 2),
                ];
            }

            return response()->json(['root' => $cumulativeData]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getIncomeByMonthAllYears(Request $request)
    {
        try {
            $year = (int) $request->query('year', now()->year);
            $source = $request->query('source', 'all');

            $allRows = [];

            // --- Atlassian (if requested) ---
            if ($source === 'all' || $source === 'atlassian') {
                try {
                    $atl = $this->fetchTransactions();
                    foreach (($atl['root'] ?? []) as $tx) {
                        $allRows[] = [
                            'saleDate' => $tx['saleDate'] ?? null,
                            'vendorAmount' => (float) ($tx['vendorAmount'] ?? 0),
                        ];
                    }
                } catch (\Exception $e) {}
            }

            // --- PayPal (if requested) ---
            if ($source === 'all' || $source === 'paypal') {
                try {
                    $paypal = $this->fetchPayPalTransactions();
                    foreach (($paypal['root'] ?? []) as $tx) {
                        $allRows[] = [
                            'saleDate' => $tx['saleDate'] ?? null,
                            'vendorAmount' => (float) ($tx['vendorAmount'] ?? 0),
                        ];
                    }
                } catch (\Exception $e) {}
            }

            // --- Local invoices (if requested) ---
            if (in_array($source, ['all', 'capital', 'kk', 'hourly', 'copyright', 'ads'], true)) {
                try {
                    $q = Invoice::with('customer')->orderBy('due_date');
                    if ($source === 'kk') {
                        $q->whereIn('customer_id', [1, 7, 85, 88, 89]);
                    } elseif ($source === 'hourly') {
                        $q->whereNotIn('customer_id', [1, 7, 85, 86, 88, 89, 90, 91]);
                    } elseif ($source === 'capital') {
                        $q->where('customer_id', 86);
                    } elseif ($source === 'copyright') {
                        $q->where('customer_id', 90);
                    } elseif ($source === 'ads') {
                        $q->where('customer_id', 91);
                    }

                    $invoices = $q->get();
                    foreach ($invoices as $inv) {
                        $allRows[] = [
                            'saleDate' => $inv->due_date,
                            'vendorAmount' => (float) $inv->total_including_vat,
                        ];
                    }
                } catch (\Exception $e) {}
            }

            // --- Merge + keep only requested year ---
            $merged = collect($allRows)
                ->filter(function ($r) use ($year) {
                    try {
                        return CarbonImmutable::parse($r['saleDate'])->year === $year;
                    } catch (\Throwable $e) {
                        return false;
                    }
                });

            // --- Sum per month (1..12) ---
            $perMonth = array_fill(1, 12, 0.0);
            foreach ($merged as $r) {
                $d = CarbonImmutable::parse($r['saleDate']);
                $perMonth[(int) $d->month] += (float) ($r['vendorAmount'] ?? 0);
            }

            // --- Build 12 rows (Jan..Dec), zero-filled, + totals ---
            $labels = [];
            for ($m = 1; $m <= 12; $m++) {
                $labels[$m] = CarbonImmutable::create($year, $m, 1)->format('M');
            }

            $root = [];
            $yearTotal = 0.0;
            for ($m = 1; $m <= 12; $m++) {
                $total = round($perMonth[$m], 2);
                $yearTotal += $total;
                $root[] = ['label' => $labels[$m], 'total' => $total];
            }

            return response()->json([
                'status' => 'success',
                'year' => $year,
                'source' => $source,
                'yearTotal' => round($yearTotal, 2),
                'root' => $root,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch merged monthly sums.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getIncomeYears(Request $request)
    {
        try {
            $source = $request->query('source', 'all');

            // ---- Invoices (local DB) ----
            $invoiceYears = [];
            if (in_array($source, ['all', 'capital', 'kk', 'hourly', 'copyright', 'ads'], true)) {
                try {
                    $invoiceQuery = Invoice::query();
                    if ($source === 'kk') {
                        $invoiceQuery->whereIn('customer_id', [1, 7, 85, 88, 89]);
                    } elseif ($source === 'hourly') {
                        $invoiceQuery->whereNotIn('customer_id', [1, 7, 85, 86, 88, 89, 90, 91]);
                    } elseif ($source === 'capital') {
                        $invoiceQuery->where('customer_id', 86);
                    } elseif ($source === 'copyright') {
                        $invoiceQuery->where('customer_id', 90);
                    } elseif ($source === 'ads') {
                        $invoiceQuery->where('customer_id', 91);
                    }
                    
                    $invoiceYears = $invoiceQuery
                        ->selectRaw('DISTINCT YEAR(due_date) as y')
                        ->orderBy('y')
                        ->pluck('y')
                        ->map(fn($y) => (int) $y)
                        ->all();
                } catch (\Exception $e) {}
            }

            // ---- Atlassian (optional) ----
            $atlassianYears = [];
            if ($source === 'all' || $source === 'atlassian') {
                try {
                    $atl = $this->fetchTransactions();
                    foreach (($atl['root'] ?? []) as $tx) {
                        if (!empty($tx['saleDate'])) {
                            try {
                                $y = CarbonImmutable::parse($tx['saleDate'])->year;
                                $atlassianYears[] = (int) $y;
                            } catch (\Throwable $e) {}
                        }
                    }
                } catch (\Exception $e) {}
            }

            // ---- PayPal (optional) ----
            $paypalYears = [];
            if ($source === 'all' || $source === 'paypal') {
                try {
                    $paypal = $this->fetchPayPalTransactions();
                    foreach (($paypal['root'] ?? []) as $tx) {
                        if (!empty($tx['saleDate'])) {
                            try {
                                $y = CarbonImmutable::parse($tx['saleDate'])->year;
                                $paypalYears[] = (int) $y;
                            } catch (\Throwable $e) {}
                        }
                    }
                } catch (\Exception $e) {}
            }

            // ---- Merge, unique, sort ----
            $years = collect($invoiceYears)
                ->merge($atlassianYears)
                ->merge($paypalYears)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if (empty($years)) {
                $years = [now()->year];
            }

            return response()->json([
                'status' => 'success',
                'source' => $source,
                'years' => $years,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch years.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getUsdToEurRates()
    {
        $today = date('Y-m-d');
        $cacheKey = "usd_to_eur_rates_2010_to_{$today}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () {
            try {
                $url = "https://api.frankfurter.app/2010-01-01..?from=USD&to=EUR";
                $response = Http::timeout(10)->get($url);
                
                if ($response->successful()) {
                    return $response->json()['rates'] ?? [];
                }
            } catch (\Exception $e) {}
            
            return [];
        });
    }

    private function getExchangeRateForDate($rates, $date)
    {
        if (isset($rates[$date]) && isset($rates[$date]['EUR'])) {
            return $rates[$date]['EUR'];
        }

        for ($i = 1; $i <= 7; $i++) {
            $prevDate = date('Y-m-d', strtotime("-{$i} days", strtotime($date)));
            if (isset($rates[$prevDate]) && isset($rates[$prevDate]['EUR'])) {
                return $rates[$prevDate]['EUR'];
            }
        }

        return 1.0; 
    }
}
