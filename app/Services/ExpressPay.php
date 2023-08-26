<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;


class ExpressPay{

    public static function makePayment($phoneNumber, $service, $amount, $referenceNumber){
        $client = new Client();

        $res = $client->request('POST', env('EXPRESSPAY_API_URL_LIVE', ''), [
            'form_params' => [
                'username' => 'ayudahub_billpay@expresspaygh.com',
                'type' => 'PAY',
                'auth-token' => "oux9H03WftkX7e1SFOdDW-kUglvgzRSQyCQEnYVw90-iophcGoxvX7xAv5ZMREj-IYGY0kpZj8vhlJyYmukz-OfBbSR2eX58FszXxRnR",
                'account-number' => $phoneNumber,
                'reference-number' => $referenceNumber,
                'service' => $service,
                'currency'=> 'GHS',
                'amount' => $amount
            ]
        ]);

        if ($res->getStatusCode() == 200) { // 200 OK
            $response=$res->getBody()->getContents();
            Log::notice($response);
            return json_decode(str_replace("status-text","status_text",$response));
        }else{
            return null;
        }
    }

    public static function makeBankPayment($bankAccount, $short_code, $amount, $referenceNumber){
        $client = new Client();

        $res = $client->request('POST', env('EXPRESSPAY_API_URL_LIVE', ''), [
            'form_params' => [
                'username' => 'ayudahub_billpay@expresspaygh.com',
                'type' => 'PAY',
                'auth-token' => "oux9H03WftkX7e1SFOdDW-kUglvgzRSQyCQEnYVw90-iophcGoxvX7xAv5ZMREj-IYGY0kpZj8vhlJyYmukz-OfBbSR2eX58FszXxRnR",
                'account-number' => $bankAccount,
                'reference-number' => $referenceNumber,
                'service' => "BANK_TRANSFER",
                'package' => $short_code,
                'currency'=> 'GHS',
                'amount' => $amount
            ]
        ]);

        if ($res->getStatusCode() == 200) { // 200 OK
            $response=$res->getBody()->getContents();
            Log::notice($response);
            return json_decode(str_replace("status-text","status_text",$response));
        }else{
            return null;
        }
    }

    public static function checkStatus($phoneNumber, $referenceNumber){
        $client = new Client();

        $res = $client->request('POST', env('EXPRESSPAY_API_URL_LIVE', ''), [
            'form_params' => [
                'username' => 'ayudahub_billpay@expresspaygh.com',
                'type' => 'STATUS',
                'auth-token' => "oux9H03WftkX7e1SFOdDW-kUglvgzRSQyCQEnYVw90-iophcGoxvX7xAv5ZMREj-IYGY0kpZj8vhlJyYmukz-OfBbSR2eX58FszXxRnR",
                'account-number' => $phoneNumber,
                'reference-number' => $referenceNumber,
            ]
        ]);

        if ($res->getStatusCode() == 200) { // 200 OK
            $response=$res->getBody()->getContents();
            Log::notice($response);
            return json_decode(str_replace("status-text","status_text",$response));
        }else{
            return null;
        }
    }
}
