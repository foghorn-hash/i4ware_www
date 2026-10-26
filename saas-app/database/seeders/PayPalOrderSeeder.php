<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PayPalOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = storage_path('app/paypal_transactions.json');
        if (!File::exists($jsonPath)) {
            return;
        }

        $data = json_decode(File::get($jsonPath), true);
        if (empty($data['root']) || !is_array($data['root'])) {
            return;
        }

        foreach ($data['root'] as $item) {
            $amount = (float) ($item['vendorAmount'] ?? 0);
            DB::table('paypal_orders')->updateOrInsert(
                ['transaction_id' => $item['transactionId'] ?? 'PP-' . uniqid()],
                [
                    'subscription_id' => $item['subscriptionId'] ?? null,
                    'customer_name' => $item['customer'] ?? 'Asiakas',
                    'company_name' => $item['customer'] ?? null,
                    'item_name' => $item['itemName'] ?? 'PayPal Tilaus',
                    'gross_amount' => $amount,
                    'vat_amount' => round($amount * 0.255 / 1.255, 2),
                    'net_amount' => round($amount / 1.255, 2),
                    'currency' => $item['currency'] ?? 'EUR',
                    'country' => $item['country'] ?? 'FI',
                    'status' => $item['status'] ?? 'completed',
                    'payment_date' => $item['saleDate'] ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
