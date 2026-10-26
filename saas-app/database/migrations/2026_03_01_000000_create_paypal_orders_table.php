<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaypalOrdersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('paypal_orders', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 128)->nullable()->unique();
            $table->string('subscription_id', 128)->nullable()->index();
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->string('company_name', 255)->nullable();
            $table->string('item_name', 255);
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0.00);
            $table->decimal('net_amount', 10, 2);
            $table->string('currency', 8)->default('EUR');
            $table->string('country', 8)->default('FI');
            $table->string('status', 32)->default('completed');
            $table->dateTime('payment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paypal_orders');
    }
}
