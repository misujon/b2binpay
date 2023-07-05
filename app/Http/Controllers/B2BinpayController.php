<?php

namespace App\Http\Controllers;

use App\Http\Requests\B2BinpayInvoiceGenerateRequest;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\B2BinPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class B2BinpayController extends Controller
{
    private $payService;
    public function __construct(B2BinPayService $b2BinPayService)
    {
        $this->payService = $b2BinPayService;
    }

    public function invoiceGenerator(B2BinpayInvoiceGenerateRequest $request)
    {
        try
        {

        }
        catch (\Exception $e)
        {
            return response()->json([
                'message' => $e->getMessage(),
                'data'    => [],
            ], 422);
        }
    }

    public function storeCustomer($name, $email): Customer
    {
        $customer = Customer::where('email', $email)->first();
        if ($customer) return $customer;

        $customer                     = new Customer();
        $customer->name               = $name;
        $customer->email              = $email;
        $customer->save();
        return $customer;
    }

    public function storePaymentDb($payment_gateway_id, $project_id, $customer_id, $customer_card_id = null, $amount, $transaction_id, $reference, $response, $incomingRequest, $requestedOn = null): Payment
    {
        $payment                     = new Payment();
        $payment->payment_gateway_id = $payment_gateway_id;
        $payment->project_id         = $project_id;
        $payment->customer_id        = $customer_id;
        $payment->customer_card_id   = $customer_card_id;
        $payment->amount             = ($amount/100);
        $payment->transaction_id     = $transaction_id;
        $payment->reference          = $reference;
        $payment->response           = json_encode($response);
        $payment->incoming_request   = json_encode($incomingRequest);
        $payment->created_at         = $requestedOn ?? Carbon::now();
        $payment->save();

        return $payment;
    }
}
