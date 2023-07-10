<?php

namespace App\Http\Controllers;

use App\Http\Requests\B2BinpayInvoiceGenerateRequest;
use App\Http\Requests\B2BinpayInvoiceVerifyRequest;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\B2BinPayService;
use Carbon\Carbon;
use Exception;
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
            $response = $this->payService->processPayment(
                $request->invoice_label,
                $request->amount,
                $request->redirect_url,
                $request->reference
            );

            return response()->json([
                'message' => "Invoice generated successfully!",
                'data'    => $response,
            ], 200);
        }
        catch (Exception $e)
        {
            return response()->json([
                'message' => $e->getMessage(),
                'data'    => [],
            ], 422);
        }
    }

    public function verifyInvoice(B2BinpayInvoiceVerifyRequest $request)
    {
        try
        {
            if (empty($request->data['id'])) throw new Exception("Under data, id is required!");
            $verified = $this->payService->verifyPayment($request->data['id']);
            if (!$verified) throw new Exception("Error to verify payment!");

            return response()->json('', 200);
        }
        catch (Exception $e)
        {
            return response()->json([
                'message' => $e->getMessage(),
                'data'    => [],
            ], 422);
        }
    }
}
