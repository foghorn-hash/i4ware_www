<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


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

}

