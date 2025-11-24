<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Carbon\CarbonImmutable;


class AtlassianSalesController extends Controller
{
    /**
     * Fetch yearly sales data for pie chart.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getYearlySalesDistribution(Request $request)
    {
        try {
            $source = $request->query('source', 'all'); // 'all', 'kela', 'hourly'

            $query = DB::table('invoices');

            // Apply revenue source filter
            if ($source === 'kela') {
                $query->whereIn('customer_id', [1, 7]);
            } elseif ($source === 'hourly') {
                $query->whereNotIn('customer_id', [1, 7, 8]); // Or use whereNotIn for more complex logic
            } elseif ($source === 'grandparents') {
                $query->where('customer_id', 8); // Replace 2 with the actual customer_id for Grandparents' Inheritance
            }

            $salesData = $query
                ->select(
                    DB::raw('YEAR(due_date) as year'),
                    DB::raw('SUM(total_including_vat) as total_sales'),
                    DB::raw('(SUM(total_including_vat) / (SELECT SUM(total_including_vat) FROM invoices' . 
                        ($source === 'kela' ? ' WHERE customer_id = 1' : ($source === 'hourly' ? ' WHERE customer_id != 1' : '')) . 
                        ') * 100) as sales_percentage')
                )
                ->groupBy(DB::raw('YEAR(due_date)'))
                ->orderBy('year', 'asc')
                ->get();

            // Transform data into a response format
            $formattedData = $salesData->map(function ($item) {
                return [
                    'year' => $item->year,
                    'salesPercentage' => round($item->sales_percentage, 2),
                ];
            });

            return response()->json([
                'status' => 'success',
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
            $year   = (int) $request->query('year', now()->year);
            $source = $request->query('source', 'all'); // 'all', 'atlassian', 'kela', 'hourly', 'grandparents'

            // --- Fetch Atlassian sales (if requested) ---
            $normalizedAtlassianSales = [];
            if ($source === 'all' || $source === 'atlassian') {
                $atlassianSales = $this->fetchTransactions(); // expects ['root' => [ ['saleDate' => ..., 'vendorAmount' => ...], ... ]]
                $normalizedAtlassianSales = array_map(function ($tx) {
                    return [
                        'saleDate'     => $tx['saleDate'],
                        'vendorAmount' => (float) $tx['vendorAmount'],
                    ];
                }, $atlassianSales['root'] ?? []);
            }

            // --- Fetch local sales (if requested) ---
            $localSales = collect();
            if (in_array($source, ['all', 'kela', 'hourly', 'grandparents'], true)) {
                $query = Invoice::orderBy('due_date');
                if ($source === 'kela') {
                    $query->whereIn('customer_id', [1, 7]);
                } elseif ($source === 'hourly') {
                    $query->whereNotIn('customer_id', [1, 7, 8]);
                } elseif ($source === 'grandparents') {
                    $query->where('customer_id', 8);
                }
                $localSales = $query->get()->map(function ($invoice) {
                    return [
                        'saleDate'     => $invoice->due_date,                 // date/datetime
                        'vendorAmount' => (float) $invoice->total_including_vat,
                    ];
                });
            }

            // --- Merge and keep only the requested year ---
            // Map first: parse/normalize dates into a consistent structure, drop invalids,
            // then filter by the requested year.
            $merged = collect($normalizedAtlassianSales)
                ->merge($localSales)
                ->map(function ($row) {
                    $d = $this->parseDateToImmutable($row['saleDate'] ?? null);
                    if (!$d) return null; // invalid/unknown date
                    return [
                        'year'         => $d->year,
                        'month'        => (int) $d->month,
                        'vendorAmount' => (float) $row['vendorAmount'],
                    ];
                })
                ->filter() // remove nulls
                ->filter(function ($row) use ($year) {
                    return ($row['year'] ?? null) === $year;
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
                    'month'            => $m,
                    'label'            => CarbonImmutable::create(null, $m, 1)->format('M'),
                    'totalIncome'      => round($total, 2),
                    'incomePercentage' => $yearTotal > 0 ? round(($total / $yearTotal) * 100, 2) : 0.0,
                ];
            });

            return response()->json([
                'status'    => 'success',
                'year'      => $year,
                'source'    => $source,
                'yearTotal' => round($yearTotal, 2),
                'root'      => $months->values(), // 12 items Jan..Dec
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch merged monthly income.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse various date representations into a CarbonImmutable or return null.
     * Accepts DateTime, timestamps, ISO strings and common formats like d.m.Y.
     */
    private function parseDateToImmutable($date): ?CarbonImmutable
    {
        if (empty($date) && $date !== '0') {
            return null;
        }

        try {
            // If already a DateTime instance
            if ($date instanceof \DateTimeInterface) {
                return CarbonImmutable::instance($date);
            }

            // If numeric timestamp
            if (is_numeric($date)) {
                return CarbonImmutable::createFromTimestamp((int) $date);
            }

            // Normalize string
            $s = trim((string) $date);

            // Try common formats first when dots are present (e.g., 21.11.2025)
            if (strpos($s, '.') !== false) {
                $formats = ['d.m.Y H:i:s', 'd.m.Y'];
                foreach ($formats as $fmt) {
                    $c = CarbonImmutable::createFromFormat($fmt, $s);
                    if ($c !== false) return $c;
                }
            }

            // Try slashed dates
            if (strpos($s, '/') !== false) {
                $formats = ['d/m/Y H:i:s', 'd/m/Y', 'm/d/Y H:i:s', 'm/d/Y'];
                foreach ($formats as $fmt) {
                    $c = CarbonImmutable::createFromFormat($fmt, $s);
                    if ($c !== false) return $c;
                }
            }

            // Fallback to Carbon parse (ISO, RFC, etc.)
            return CarbonImmutable::parse($s);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getMergedSales(Request $request)
    {
        try {
            $source = $request->query('source', 'all'); // 'all', 'atlassian', 'kela', 'hourly'

            // Fetch Atlassian sales if needed
            $atlassianSales = [];
            if ($source === 'all' || $source === 'atlassian') {
                $atlassianSales = $this->fetchTransactions();
                $normalizedAtlassianSales = array_map(function ($transaction) {
                    return [
                        'saleDate' => $transaction['saleDate'],
                        'vendorAmount' => $transaction['vendorAmount'],
                    ];
                }, $atlassianSales['root']);
            } else {
                $normalizedAtlassianSales = [];
            }

            // Fetch local sales (from invoices) if needed
            $localSales = collect();
            if ($source === 'all' || $source === 'kela' || $source === 'hourly' || $source === 'grandparents') {
                $query = Invoice::orderBy('due_date');
                if ($source === 'kela') {
                    $query->whereIn('customer_id', [1, 7]);
                } elseif ($source === 'hourly') {
                    $query->whereNotIn('customer_id', [1, 7, 8]); // Or use whereNotIn for more complex logic
                } elseif ($source === 'grandparents') {
                    $query->where('customer_id', 8); // Replace 2 with the actual customer_id for Grandparents' Inheritance
                }
                $localSales = $query->get()->map(function ($invoice) {
                    return [
                        'saleDate' => $invoice->due_date,
                        'vendorAmount' => $invoice->total_including_vat,
                    ];
                });
            }

            // Merge sales data
            $mergedSales = collect($normalizedAtlassianSales)
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
        // Step 1: Fetch local sales
        $localSales = $this->getLocalSales();

        // Step 2: Fetch Atlassian sales
        $atlassianSales = $this->fetchTransactions()['root'];

        // Step 3: Combine data
        $combinedSales = [
            'localSales' => $localSales,
            'atlassianSales' => $atlassianSales,
        ];

        // Step 4: Return combined data
        return response()->json($combinedSales);
    }

    private function getLocalSales()
    {
        // Fetch invoices and calculate totals
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
    }

    public function getTransactions()
    {
        // Set response header type
        return response()->json($this->fetchTransactions());
    }

    public function getSalesReport(Request $request)
    {
        $source = $request->query('source', 'all'); // 'all', 'atlassian', 'kela', 'hourly'

        // Configurations
        $vendorId = env('ATLASSIAN_VENDOR_ID');
        $username = env('ATLASSIAN_USERNAME');
        $password = env('ATLASSIAN_PASSWORD');

        $json = ['root' => []];

        // Fetch Atlassian sales data if needed
        if ($source === 'all' || $source === 'atlassian') {
            $uri = "https://marketplace.atlassian.com/rest/2/vendors/{$vendorId}/reporting/sales/transactions/export";
            $response = Http::withBasicAuth($username, $password)
                ->timeout(60)
                ->accept('application/json')
                ->get($uri, [
                    'accept' => 'json',
                    'order' => 'asc',
                ]);
            if ($response->successful()) {
                $transactions = $response->json();
                foreach ($transactions as $transaction) {
                    $vendorAmount = (float)($transaction['purchaseDetails']['vendorAmount'] ?? 0);
                    $saleDate = $transaction['purchaseDetails']['saleDate'] ?? null;
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
            }
        }

        // Fetch local sales data if needed
        if ($source === 'all' || $source === 'kela' || $source === 'hourly' || $source === 'grandparents') {
            $query = Invoice::orderBy('due_date');
            if ($source === 'kela') {
                $query->whereIn('customer_id', [1, 7]);
            } elseif ($source === 'hourly') {
                $query->whereNotIn('customer_id', [1, 7, 8]); // Or use whereNotIn for more complex logic
            } elseif ($source === 'grandparents') {
                $query->where('customer_id', 8); // Replace 2 with the actual customer_id for Grandparents' Inheritance
            }
            $localSales = $query->get()->map(function ($invoice) {
                return [
                    'saleYear' => date('Y', strtotime($invoice->due_date)),
                    'amount' => $invoice->total_including_vat,
                ];
            });

            foreach ($localSales as $localSale) {
                $saleYear = $localSale['saleYear'];
                $amount = (float)$localSale['amount'];
                if (!isset($json['root'][$saleYear])) {
                    $json['root'][$saleYear] = [
                        'balanceVendor' => 0,
                        'saleYear' => $saleYear,
                    ];
                }
                $json['root'][$saleYear]['balanceVendor'] += $amount;
            }
        }

        // Convert the aggregated data into a flat array
        ksort($json['root']);
        $yearData = ['root' => []];
        $i = 0;
        foreach ($json['root'] as $year => $data) {
            $yearData['root'][$i] = $data;
            $i++;
        }

        return response()->json($yearData);
    }

    private function fetchTransactions()
    {
        // Configurations
        $vendorId = env('ATLASSIAN_VENDOR_ID');
        $username = env('ATLASSIAN_USERNAME');
        $password = env('ATLASSIAN_PASSWORD');
        
        $uri = "https://marketplace.atlassian.com/rest/2/vendors/{$vendorId}/reporting/sales/transactions/export?accept=json&order=asc";

        // Make GET request with basic authentication
        $response = Http::withBasicAuth($username, $password)
            ->timeout(60)
            ->accept('application/json')
            ->get($uri);

        // Check if response is successful
        if (!$response->successful()) {
            abort($response->status(), 'Error fetching data from Atlassian API');
        }

        $transactions = $response->json();

        // Process data
        $balanceVendor = 0;
        $json = ['root' => []];

        foreach ($transactions as $i => $transaction) {
            $vendorAmount = number_format((float)($transaction['purchaseDetails']['vendorAmount'] ?? 0), 2, '.', '');
            $balanceVendor += $vendorAmount;

            $json['root'][$i] = [
                'vendorAmount' => $vendorAmount,
                'saleDate' => $transaction['purchaseDetails']['saleDate'] ?? null,
            ];
        }

        return $json;
    }

    public function getCumulativeSales(Request $request)
    {
        $vendorId = env('ATLASSIAN_VENDOR_ID');
        $username = env('ATLASSIAN_USERNAME');
        $password = env('ATLASSIAN_PASSWORD');

        try {
            $source = $request->query('source', 'all'); // 'all', 'atlassian', 'kela', 'hourly'

            $salesByDate = [];
            $cumulativeVendorBalance = 0;

            // Process Atlassian sales if needed
            if ($source === 'all' || $source === 'atlassian') {
                $apiUrl = "https://marketplace.atlassian.com/rest/2/vendors/{$vendorId}/reporting/sales/transactions/export?accept=json&order=asc";
                $response = Http::withBasicAuth($username, $password)->get($apiUrl);

                if (!$response->successful()) {
                    return response()->json(['error' => 'Failed to fetch data from Atlassian'], 500);
                }

                $salesData = $response->json();

                foreach ($salesData as $transaction) {
                    $saleDate = $transaction['purchaseDetails']['saleDate'] ?? null;
                    $vendorAmount = (float)($transaction['purchaseDetails']['vendorAmount'] ?? 0);

                    if ($saleDate) {
                        $formattedDate = date('Y-m', strtotime($saleDate)); // Group by Year-Month
                        $salesByDate[$formattedDate] = ($salesByDate[$formattedDate] ?? 0) + $vendorAmount;
                    }
                }
            }

            // Process local sales if needed
            if ($source === 'all' || $source === 'kela' || $source === 'hourly' || $source === 'grandparents') {
                $query = Invoice::orderBy('due_date');
                if ($source === 'kela') {
                    $query->whereIn('customer_id', [1, 7]);
                } elseif ($source === 'hourly') {
                    $query->whereNotIn('customer_id', [1, 7, 8]); // Or use whereNotIn for more complex logic
                } elseif ($source === 'grandparents') {
                    $query->where('customer_id', 8); // Replace 2 with the actual customer_id for Grandparents' Inheritance
                }
                $localSales = $query->get()->map(function ($invoice) {
                    return [
                        'saleDate' => date('Y-m', strtotime($invoice->due_date)),
                        'amount' => (float) $invoice->total_including_vat,
                    ];
                });

                foreach ($localSales as $localSale) {
                    $saleDate = $localSale['saleDate'];
                    $amount = (float)$localSale['amount'];
                    $salesByDate[$saleDate] = ($salesByDate[$saleDate] ?? 0) + $amount;
                }
            }

            // Sort sales by date
            ksort($salesByDate);

            // Calculate cumulative balance
            $cumulativeData = [];
            foreach ($salesByDate as $saleDate => $amount) {
                $cumulativeVendorBalance += $amount;

                $cumulativeData[] = [
                    'saleDate' => $saleDate,
                    'cumulativeVendorBalance' => $cumulativeVendorBalance,
                ];
            }

            return response()->json(['root' => $cumulativeData]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

        /**
     * GET /api/reports/income-by-month-all-years?source=all
     * source: 'all' | 'atlassian' | 'kela' | 'hourly' | 'grandparents'
     *
     * Response:
     * {
     *   status: "success",
     *   source: "all",
     *   years: [2019, 2020, 2021, ...],
     *   root: [
     *     {"label":"Jan","2019":123.45,"2020":0,...},
     *     ... (12 rows) ...
     *     {"label":"Dec","2019":10.00,"2020":5.25,...}
     *   ]
     * }
     */
    public function getIncomeByMonthAllYears(Request $request)
    {
        try {
            $year   = (int) $request->query('year', now()->year);
            $source = $request->query('source', 'all');

            // --- Atlassian (if requested) ---
            $atlRows = [];
            if ($source === 'all' || $source === 'atlassian') {
                // Must return: ['root' => [ ['saleDate' => 'YYYY-MM-DD', 'vendorAmount' => 123.45], ... ]]
                $atl = $this->fetchTransactions();
                foreach (($atl['root'] ?? []) as $tx) {
                    $atlRows[] = [
                        'saleDate'     => $tx['saleDate'] ?? null,
                        'vendorAmount' => (float) ($tx['vendorAmount'] ?? 0),
                    ];
                }
            }

            // --- Local invoices (if requested) ---
            $localRows = collect();
            if (in_array($source, ['all','kela','hourly','grandparents'], true)) {
                $q = Invoice::query()->orderBy('due_date');
                if ($source === 'kela') {
                    $q->whereIn('customer_id', [1, 7]);
                } elseif ($source === 'hourly') {
                    $q->whereNotIn('customer_id', [1, 7, 8]);
                } elseif ($source === 'grandparents') {
                    $q->where('customer_id', 8);
                }

                $localRows = $q->get()->map(fn($inv) => [
                    'saleDate'     => $inv->due_date,
                    'vendorAmount' => (float) $inv->total_including_vat,
                ]);
            }

            // --- Merge + keep only requested year ---
            $merged = collect($atlRows)->merge($localRows)
                ->filter(function ($r) use ($year) {
                    try { return CarbonImmutable::parse($r['saleDate'])->year === $year; }
                    catch (\Throwable $e) { return false; }
                });

            // --- Sum per month (1..12) ---
            $perMonth = array_fill(1, 12, 0.0);
            foreach ($merged as $r) {
                $d = CarbonImmutable::parse($r['saleDate']);
                $perMonth[(int)$d->month] += (float) ($r['vendorAmount'] ?? 0);
            }

            // --- Build 12 rows (Jan..Dec), zero-filled, + totals ---
            $labels = [];
            for ($m=1;$m<=12;$m++) $labels[$m] = CarbonImmutable::create($year, $m, 1)->format('M');

            $root = [];
            $yearTotal = 0.0;
            for ($m=1;$m<=12;$m++) {
                $total = round($perMonth[$m], 2);
                $yearTotal += $total;
                $root[] = ['label' => $labels[$m], 'total' => $total];
            }

            return response()->json([
                'status'    => 'success',
                'year'      => $year,
                'source'    => $source,
                'yearTotal' => round($yearTotal, 2),
                'root'      => $root, // 12 items
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch merged monthly sums.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

     /**
     * GET /api/reports/income-years?source=all
     * source: 'all' | 'atlassian' | 'kela' | 'hourly' | 'grandparents'
     *
     * Response:
     * { "status":"success", "source":"all", "years":[2003,2004,...,2025] }
     */
    public function getIncomeYears(Request $request)
    {
        try {
            $source = $request->query('source', 'all');

            // ---- Invoices (local DB) ----
            $invoiceQuery = Invoice::query();
            if ($source === 'kela') {
                $invoiceQuery->whereIn('customer_id', [1, 7]);
            } elseif ($source === 'hourly') {
                $invoiceQuery->whereNotIn('customer_id', [1, 7, 8]);
            } elseif ($source === 'grandparents') {
                $invoiceQuery->where('customer_id', 8);
            }
            // DISTINCT years from due_date
            $invoiceYears = $invoiceQuery
                ->selectRaw('DISTINCT YEAR(due_date) as y')
                ->orderBy('y')
                ->pluck('y')
                ->map(fn ($y) => (int) $y)
                ->all();

            // ---- Atlassian (optional) ----
            $atlassianYears = [];
            if ($source === 'all' || $source === 'atlassian') {
                // Expected: ['root' => [ ['saleDate' => 'YYYY-MM-DD...', 'vendorAmount' => ...], ... ]]
                $atl = $this->fetchTransactions();
                foreach (($atl['root'] ?? []) as $tx) {
                    if (!empty($tx['saleDate'])) {
                        try {
                            $y = CarbonImmutable::parse($tx['saleDate'])->year;
                            $atlassianYears[] = (int) $y;
                        } catch (\Throwable $e) {
                            // skip bad dates
                        }
                    }
                }
            }

            // ---- Merge, unique, sort ----
            $years = collect($invoiceYears)
                ->merge($atlassianYears)
                ->unique()
                ->sort()
                ->values()
                ->all();

            return response()->json([
                'status' => 'success',
                'source' => $source,
                'years'  => $years, // e.g. [2003, 2004, ..., 2025]
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to fetch years.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}

