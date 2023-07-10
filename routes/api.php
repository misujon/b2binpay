<?php

use Illuminate\Http\Request;
use App\Models\B2BinpayPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\B2BinpayController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('b2binpay-invoice-generator', [B2BinpayController::class, 'invoiceGenerator']);
Route::post('b2binpay-invoice-verify', [B2BinpayController::class, 'verifyInvoice']);
