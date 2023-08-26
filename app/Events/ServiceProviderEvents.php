<?php

namespace App\Events;

use App\Services\OneSignal;
use App\Constants;
use Illuminate\Support\Facades\Log;
use App\Models\Worker;

class ServiceProviderEvents{

    public static function serviceProviderVerified(Worker $service_provider){
        $playerIds = [];

        if(isset($service_provider)){
            array_push($playerIds, $service_provider->player_id);

            $notificationMsg = "Ayuda has verified your account";

            $metaData = [
                "account_type" => 'your account type has changed'
            ];

            Log::notice($notificationMsg);
            OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_WORKER_VERIFIED, $metaData);
        }
    }
}
