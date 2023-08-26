<?php

namespace App\Events;

use App\Services\OneSignal;
use App\Constants;
use Illuminate\Support\Facades\Log;
use App\Models\ServiceRequestInvoice;
use App\Models\InvoiceRequest;
use App\Models\Worker;
use App\Models\User;

class InvoiceEvents{

    public static function invoiceRequestRejected(InvoiceRequest $invoice_request, Worker $worker){
        $playerIds = [];

        if(isset($worker)){
            array_push($playerIds, $worker->player_id);

            $notificationMsg = "Your invoice request has been rejected";

            $metaData = [
                "invoice_request_id" => $invoice_request->invoice_request_id
            ];

            Log::notice($notificationMsg);
            OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_INVOICE_REQUEST_REJECTED, $metaData);
        }
    }

    public static function invoiceCreated(ServiceRequestInvoice $invoice, Worker $worker, User $user){
        $userPlayerIds = [];
        $workerPlayerIds = [];

        if($worker){
            array_push($workerPlayerIds, $worker['player_id']);
        }

        if($user){
            array_push($userPlayerIds, $user['player_id']);
        }

        $userMessage = "Hello, {$user['name']}, an invoice has been created relating to the work to be done for you by {$worker['name']}";
        $workerMessage = "Hello, {$worker['name']}, an invoice has been created for {$user['name']} based on your request";

        // Add invoice number to meta data to send in push notification
        $metaData = [
            "invoice_number" => $invoice['invoice_number'],
            "invoice_request_type" => $invoice['service_request_type']
        ];

        OneSignal::sendNotification($userMessage, $userPlayerIds, Constants::PUSH_NOTIFICATION_TYPE_INVOICE, $metaData);
        OneSignal::sendNotification($workerMessage, $workerPlayerIds, Constants::PUSH_NOTIFICATION_TYPE_INVOICE, $metaData);
    }
}
