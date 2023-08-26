<?php 

namespace App\Events;

use App\Services\OneSignal;
use App\Constants;
use Illuminate\Support\Facades\Log;
use App\Models\Worker;
use App\Models\User;
use App\Models\ServiceRequest;

class ServiceRequestEvents{

    public static function serviceRquestWorkerAssigned(ServiceRequest $service_request, Worker $service_provider, User $user){
        $playerIds = [];

        $message = "Ayuda assigned to worker : {$service_provider['name']} for client : {$user['name']} ";

        if($service_provider){
            array_push($playerIds, $service_provider['player_id']);   
        }

        if($user){
            array_push($playerIds, $user['player_id']);  
        }

        $notificationMsg = "Ayuda assigned to service provider : {$service_provider['name']} for client : {$user['name']} ";

        $metaData = ["service_request_id" => $service_request['service_request_id']];

        Log::notice($notificationMsg);
        OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_SERVICE_REQUEST, $metaData);
    }
}