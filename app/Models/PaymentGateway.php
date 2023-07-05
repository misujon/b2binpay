<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'token',
        'status',
        'username',
        'password',
        'wallet'
    ];

    protected $casts = [
        'created_at' => "datetime:Y-m-d H:i:s",
        'updated_at' => "datetime:Y-m-d H:i:s",
    ];

    public const PAYMENT_GATEWAY_ENABLE = 1;
    public const PAYMENT_GATEWAY_DISABLE = 0;

    public const NEW_ACCOUNT = 1;
    public const TOPUP       = 2;
    public const RESET       = 3;
}
