<?php

namespace App\Services;

use App\Models\B2BinpayPayment;
use App\Models\Currency;
use App\Models\PaymentGateway;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;

class B2BinPayService
{
    private $confirmationNeede;
    private $trackingId;
    private $timeLimit;
    private $wallet;
    private $paymentGatewayId;

    public function __construct()
    {
        $this->paymentGatewayId = 4; // our payment gateway id will be here
        $this->wallet = PaymentGateway::find($this->paymentGatewayId); // our wallet id will be here.
        $this->trackingId = "B2B_".date("YmdHis")."_TRACKING_".rand(100000, 999999); // unique tracking id which we will use as transection ids
        $this->confirmationNeede = 3; // blockchain confirmation
        $this->timeLimit = 86400; // 24 hours in seconds
    }

    public function processPayment($label, $amount, $redirectUrl, $reference)
    {
        $body = [
            "data" => [
                "type" => "deposit",
                "attributes" => [
                   "label" => $label,
                   "tracking_id" => $this->trackingId,
                   "confirmations_needed" => $this->confirmationNeede,
                   "time_limit" => $this->timeLimit,
                   "inaccuracy" => 5,
                   "target_amount_requested" => $amount,
                   "callback_url" => $redirectUrl
                ],
                "relationships" => [
                    "wallet" => [
                        "data" => [
                           "type" => "wallet",
                           "id" => $this->wallet->wallet
                        ]
                    ]
                ]
            ]
        ];

        $payment = $this->paymentApiRequest($body);
        if ($payment == false)
        {
            throw new Exception("Error to generate payment request, please try again!");
        }

        $payment = json_decode($payment, true);
        $status = B2BinpayPayment::INVOICE;
        if ($payment['data']['attributes']['status'] == 3) $status = B2BinpayPayment::PAID;
        if ($payment['data']['attributes']['status'] == 4) $status = B2BinpayPayment::CANCELED;
        if ($payment['data']['attributes']['status'] == 5) $status = B2BinpayPayment::UNRESOLVED;

        $data = [
            'invoice_id' => $payment['data']['id'],
            'name' => $payment['data']['attributes']['name'],
            'label' => $payment['data']['attributes']['label'],
            'address' => $payment['data']['attributes']['address'],
            'destination' => $payment['data']['attributes']['address'],
            'tracking_id' => $payment['data']['attributes']['tracking_id'],
            'target_amount_requested' => $payment['data']['attributes']['target_amount_requested'],
            'target_paid' => $payment['data']['attributes']['target_paid'],
            'target_commission' => $payment['data']['attributes']['target_commission'],
            'source_amount_requested' => $payment['data']['attributes']['source_amount_requested'],
            'wallet' => $payment['data']['relationships']['wallet']['data']['id'],
            'status' => $status,
            'expired_at' => date('Y-m-d H:i:s', strtotime($payment['data']['attributes']['expired_at'])),
            'confirmations_needed' => $payment['data']['attributes']['confirmations_needed'],
            'callback_url' => $payment['data']['attributes']['callback_url'],
            'payment_page' => $payment['data']['attributes']['payment_page'],
            'time_limit' => $payment['data']['attributes']['time_limit'],
            'message' => $reference,
            'response' => json_encode($payment)
        ];

        if (!$invoice = B2BinpayPayment::create($data)) throw new Exception("Error to make payment, please try again!");
        return $invoice;
    }

    private function paymentApiRequest($body, $token=null, $retry=0)
    {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => "Bearer ".((is_null($token))?$this->wallet->token:$token),
        ];
        $request = new Request('POST', env('B2B_IN_PAY_ENDPOINT')."/deposit", $headers, json_encode($body));

        try
        {
            $res = $client->sendAsync($request)->wait();
            return $res->getBody()->getContents();
        }
        catch (\GuzzleHttp\Exception\RequestException $e)
        {
            if ($e->hasResponse() && $e->getResponse()->getReasonPhrase() == "Unauthorized")
            {
                $this->wallet->refresh_token = null;
                $this->wallet->save();

                $auth = $this->reAuthenticate();
                $retry++;
                if ($retry < 3)
                {
                    $retryPayment = $this->paymentApiRequest($body, $auth, $retry);
                    return $retryPayment;
                }

                return false;
            }

            return false;
        }
    }

    private function reAuthenticate()
    {
        $client = new Client();
        $headers = [
            'Content-Type' => 'application/vnd.api+json'
        ];

        if (empty($this->wallet->refresh_token))
        {
            $attribute = [
                "login" => $this->wallet->username,
                "password" => $this->wallet->password
            ];
            $url = env('B2B_IN_PAY_ENDPOINT')."/token";
            Log::info("Payment authentication create requested!");
        }
        else
        {
            $attribute = [
                "refresh" => $this->wallet->refresh_token
            ];
            $url = env('B2B_IN_PAY_ENDPOINT')."/token/refresh";
            Log::info("Payment re-authentication requested!");
        }

        $body = [
            "data" => [
                "type" => "auth-token",
                "attributes" => $attribute
            ]
        ];

        $request = new Request("POST", $url, $headers, json_encode($body));
        try
        {
            $res = $client->sendAsync($request)->wait();
            $res = json_decode($res->getBody()->getContents(), true);
            $this->wallet->token = $res['data']['attributes']['access'];
            $this->wallet->refresh_token = $res['data']['attributes']['refresh'];
            $this->wallet->save();


            return $res['data']['attributes']['access'];
        }
        catch (\GuzzleHttp\Exception\RequestException $e)
        {
            Log::error($e->getResponse());
            return false;
        }
    }

    public function verifyPayment($paymentId, $token = null, $retry = 0)
    {
        $getPayment = B2BinpayPayment::where('invoice_id', $paymentId)->first();
        if (!$getPayment) throw new Exception("Payment not found!");

        $client = new Client();
        $headers = [
            'Content-Type' => 'application/vnd.api+json',
            'Authorization' => "Bearer ".((is_null($token))?$this->wallet->token:$token),
        ];
        $url = env('B2B_IN_PAY_ENDPOINT')."/deposit/".$getPayment->invoice_id;
        $request = new Request('GET', $url, $headers);

        try
        {
            $res = $client->sendAsync($request)->wait();
            $result = $res->getBody()->getContents();
            $result = json_decode($result, true);

            if (isset($result['data']['attributes']['status']) == 3)
            {
                $getPayment->status = B2BinpayPayment::PAID;
            }
            else if (isset($result['data']['attributes']['status']) == 4)
            {
                $getPayment->status = B2BinpayPayment::CANCELED;
            }
            else if (isset($result['data']['attributes']['status']) == 5)
            {
                $getPayment->status = B2BinpayPayment::UNRESOLVED;
            }

            $getPayment->save();
            return true;
        }
        catch (\GuzzleHttp\Exception\RequestException $e)
        {
            $errorRes = ["Unauthorized", "Not Found"];
            if ($e->hasResponse() && in_array($e->getResponse()->getReasonPhrase(), $errorRes))
            {
                $this->wallet->refresh_token = null;
                $this->wallet->save();

                $auth = $this->reAuthenticate();
                $retry++;
                if ($retry <= 1)
                {
                    $retryPayment = $this->verifyPayment($paymentId, $auth, $retry);
                    return $retryPayment;
                }

                return false;
            }

            return false;
        }
    }
}
