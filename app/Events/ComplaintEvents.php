<?php

namespace App\Events;

use App\Services\OneSignal;
use App\Constants;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\UserComplaint;

class ComplaintEvents{

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
            OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_COMPLAINT_CHAT, $metaData);
        }
    }

    public static function complaintStatusChanged(UserComplaint $complaint, $message, User $user){
           $playerIds = [];

        if(isset($user)){
            array_push($playerIds, $user->player_id);

            $notificationMsg = $message;

            $metaData = ["complaint_id" => $complaint->id];

            Log::notice($notificationMsg);
            OneSignal::sendNotification($notificationMsg, $playerIds, Constants::PUSH_NOTIFICATION_TYPE_COMPLAINT, $metaData);
        }

    }
}
