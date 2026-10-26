<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayPalOrder extends Model
{
    use HasFactory;

    protected $table = 'paypal_orders';

    protected $fillable = [
        'transaction_id',
        'subscription_id',
        'customer_name',
        'customer_email',
        'company_name',
        'item_name',
        'gross_amount',
        'vat_amount',
        'net_amount',
        'currency',
        'country',
        'status',
        'payment_date',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'payment_date' => 'datetime',
    ];
}
