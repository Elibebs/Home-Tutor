<?php

namespace App\Events;

use App\Services\OneSignal;
use App\Constants;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ClientEvents{

    public static function accountTypeChanged(User $user){
        $playerIds = [];

        if(isset($user)){
            array_push($playerIds, $user->player_id);

            $notificationMsg = "Client {$user->name} has {$user->type}";

            $metaData = [
                "account_type" => 'your account type has changed'
            ];

            Log::notice($notificationMsg);
            OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_ACCOUNT_TYPE, $metaData);
        }
    }

    public static function newComplaintMessage($complaint_id, $chat_id, $message, User $user){
        $playerIds = [];

        if(isset($user)){
            array_push($playerIds, $user->player_id);

            $notificationMsg = $message;

            $metaData = [
                "complaint_id" => $complaint_id,
                "complaint_chat_id" => $chat_id
            ];

            Log::notice($notificationMsg);
            OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_COMPLAINT, $metaData);
        }
    }
}
