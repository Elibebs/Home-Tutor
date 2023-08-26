<?php

namespace App\Repos;

use Carbon\Carbon;
use App\Models\TutorVerification;
use App\Models\ForgotPassword;
use App\Utilities\Generators;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Log;

class TutorVerificationRepo extends BaseRepo
{
	public function createVerificationEntry(Array $data)
	{
		// Get and update existing verification for user
		$existingVerification = TutorVerification::where("phone_number", $data['phone_number'])->where("is_active", true)->first();
		if($existingVerification)
		{
			Log::notice("using existing Verification {$data['phone_number']} {$existingVerification->verification_pin}");
			$existingVerification->is_active = true;
			$existingVerification->has_been_validated = false;
			$existingVerification->updated_at = Carbon::now();
			$existingVerification->update();

			return $existingVerification;
		}

		// if updated, create new verification
		$verification = new TutorVerification;

		// $verification->user_id = $data['user_id'];
		$verification->verification_pin = Generators::generateVerificationPin();
		$verification->is_active = true;
		$verification->has_been_validated = false;
		$verification->to_phone_number = $data['phone_number'];
		$verification->created_at = Carbon::now();
		$verification->updated_at = Carbon::now();

		if($verification->save())
		{
			Log::notice("using new Verification {$verification->verification_pin}");
			return $verification;
		}

		return null;
	}

	public function attemptVerifyPin(String $pin, $phone_number)
	{

		$verification = TutorVerification::where("phone_number", $phone_number)->where("is_active", true)->first();
		Log::notice("attemptVerifyPin => {$pin}... ... {$verification} phone_number= {$phone_number}");
		if(!isset($verification))
		{
			return false;
		}

		Log::notice("attemptVerifyPin = {$verification->verification_pin} => {$pin}");
		if($verification->verification_pin !== $pin)
		{
			return false;
		}

		$verification->is_active = false;
		$verification->has_been_validated = true;
		$verification->updated_at = Carbon::now();

		return $verification->update();
	}
}
