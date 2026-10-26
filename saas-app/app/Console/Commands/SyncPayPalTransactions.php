<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\AtlassianSalesController;
use Illuminate\Support\Facades\Cache;

class SyncPayPalTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paypal:sync {--clear-cache : Clear existing cached PayPal transactions} {--debug : Run full PayPal API diagnostic test} {--reset-token : Terminate current cached PayPal OAuth token and request a fresh one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync real PayPal transactions from PayPal REST API and WordPress webhooks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $controller = new AtlassianSalesController();

        if ($this->option('reset-token')) {
            request()->merge(['reset_token' => 1]);
        }

        if ($this->option('debug') || $this->option('reset-token')) {
            $this->info('--- PayPal API Diagnostic Test ---');
            $response = $controller->debugPayPalApi();
            $data = $response->getData(true);
            $this->line('Status: ' . ($data['status'] ?? 'N/A'));
            $this->line('Mode: ' . ($data['mode'] ?? 'N/A'));
            $this->line('Endpoint: ' . ($data['base_url'] ?? 'N/A'));
            $this->line('Client ID: ' . ($data['client_id_preview'] ?? 'N/A'));
            $this->line('OAuth Status: ' . ($data['oauth_status'] ?? $data['http_status'] ?? 'N/A'));
            $this->line('Has reporting/search/read scope: ' . (!empty($data['has_reporting_scope']) ? 'YES' : 'NO'));
            if (!empty($data['granted_scopes'])) {
                $this->line('Granted scopes (' . count($data['granted_scopes']) . '):');
                foreach ($data['granted_scopes'] as $s) {
                    $this->line('  - ' . $s);
                }
            }
            $this->line('Reporting API HTTP Status: ' . ($data['reporting_api_http_status'] ?? 'N/A'));
            $this->line('Reporting API Response: ' . json_encode($data['reporting_api_response'] ?? [], JSON_UNESCAPED_SLASHES));
            if (!empty($data['solution'])) {
                $this->warn('Ohje: ' . $data['solution']);
            }
            $this->info('----------------------------------');
            return 0;
        }

        $this->info('Starting PayPal transaction sync...');

        if ($this->option('clear-cache')) {
            Cache::forget('paypal_transactions_cache_v2');
            Cache::forget('paypal_transactions_cache_v1');
            $this->info('Cache cleared.');
        }

        $data = $controller->fetchPayPalTransactions();

        $count = count($data['root'] ?? []);
        $source = $data['source'] ?? 'unknown';

        $this->info("Sync completed! Real transactions found: {$count} (Source: {$source})");

        if (!empty($controller->lastPayPalDiagnostic)) {
            $this->warn($controller->lastPayPalDiagnostic);
        }

        return 0;
    }
}
