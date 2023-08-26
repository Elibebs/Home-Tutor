<?php 

namespace App\Services;

use Twilio\Rest\Client;
use Illuminate\Support\Facades\Log;

class Twilio{

	public static function sendSMS($phoneNumber, $message){
		
			$account_sid = env('TWILIO_ACCOUNT_SID');
			$auth_token = env('TWILIO_AUTH_TOKEN');
			$twilio_number = env('TWILLIO_NUMBER');
			$twillio_sender_id = env('APP_NAME');

			Log::notice("Sending Twillio sms to " . $phoneNumber);
			try {
				$client = new Client($account_sid, $auth_token);
                $client->messages->create(
				// Where to send a text message (your cell phone?)
					$phoneNumber,
					array(
						'from' => $twillio_sender_id,//$twilio_number,
						'body' => $message
					)
				);
            } catch (Exception $e) {
                 throw new Exception($e); 
            }

			Log::notice("Sent!!");
	} 
}