<?php 

namespace App\Services;

use App\Networking\HttpClient;
use Illuminate\Support\Facades\Log;

class OneSignal
{
	public static function sendNotification(String $message, $playerIds, $notificationType=null, $metaData=null)
	{
		$app_id = config('onesignal.app_id');
		$url = config('onesignal.url');

		$postData['app_id'] = $app_id;
		$postData['include_player_ids'] = $playerIds;
		$postData['data']["message"] = $message;
		if($notificationType)
		{
			$postData['data']["notification_type"] = $notificationType;
		}
		if($metaData)
		{
			$postData['data']["metaData"] = $metaData;
		}
		$postData['contents'] = ["en" => $message];

		$pIds = implode(',', $playerIds);
		
		Log::debug("Attempting to send onesignal push to PLAYER-IDs {$pIds}");

		if(isset($playerIds)) {
			try {
				$httpClient = new HttpClient();
				$httpClient->setMethod("POST");
				$httpClient->setUrl($url);

				$httpClient->setdata($postData);
		        $httpClient->makeRequest();

		        $response = $httpClient->getResponseContent();

		        Log::debug("One signal request sent successfully to PLAYER-IDs {$pIds}");
		        return $response;
	    	} catch(\Exception $e) {
	    		 Log::error("Error sending OneSignal Notification to Player.");
	    		 Log::error($e);
	    	}
    	} else {
    		Log::debug("Player ID does not exist");
    	}
	}
}