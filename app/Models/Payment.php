<?php

namespace App\Models;

use App\Constants\AppConstant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['payment_gateway_id', 'project_id', 'customer_id', 'customer_card_id', 'amount', 'transaction_id', 'reference', 'response', 'incoming_request'];
}
