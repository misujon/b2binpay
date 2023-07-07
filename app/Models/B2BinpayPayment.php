<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class B2BinpayPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'name',
        'label',
        'address',
        'destination',
        'tracking_id',
        'target_amount_requested',
        'target_paid',
        'target_commission',
        'source_amount_requested',
        'currency',
        'wallet',
        'status',
        'expired_at',
        'confirmations_needed',
        'callback_url',
        'payment_page',
        'time_limit',
        'message',
        'response',
    ];

    public const INVOICE = "INVOICE";
    public const PAID = "PAID";
    public const CANCELED = "CANCELED";
    public const UNRESOLVED = "UNRESOLVED";
}
