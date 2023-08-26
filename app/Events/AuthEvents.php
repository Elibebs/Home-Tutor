<?php

namespace App\Events;

use App\Models\User;
use App\Models\Worker;
use App\Models\Verification;
use App\Services\Sms;
use App\Services\OneSignal;
use App\Services\Twillio;
use App\Utilities\Constants;
use Illuminate\Support\Facades\Log;

class AuthEvents
{
	public static function userHasLoggedIn(User $user)
	{

	}

	public static function userHasRegistered(User $user)
	{

	}

	public static function userPasswordChanged(User $user)
	{

	}

	public static function userVerificationInstanceCreated(Verification $verification)
	{
		Log::notice("Verification pin created. Sending to user for phone number validation...");
		$message = "Welcome to ".config('app.name').". Your verification code is : {$verification->verification_pin}.";
		Log::notice($message);

		if(config("app.env") === Constants::ENV_PRODUCTION)
		{
			Twillio::sendSMS($verification->to_phone_number, $message);
		}
	}

	public static function userPinVerified(User $user)
	{
		Log::notice("User {$user->phone_number} verified successfully");

	}

	public static function userForgotPasswordPinCreated(User $user)
	{
		Log::notice("User {$user->phone_number} forgot password pin created successfully");
	}

	public static function userForgotPasswordPinVerified(User $user)
	{
		Log::notice("User {$user->phone_number} forgot password pin verified successfully");
	}

	public static function userResetPassword(User $user)
	{
		Log::notice("User {$user->phone_number} password reset successfully");
	}

	public static function workerHasLoggedIn(Worker $worker)
	{

	}

	public static function workerHasRegistered(Worker $worker)
	{

	}

	public static function workerPasswordChanged(Worker $worker)
	{

	}

	public static function workerForgotPasswordPinCreated(Worker $worker)
	{
		Log::notice("Worker {$worker->phone_number} forgot password pin created successfully");
	}

	public static function workerForgotPasswordPinVerified(Worker $worker)
	{
		Log::notice("Worker {$worker->phone_number} forgot password pin verified successfully");
	}

	public static function workerResetPassword(Worker $worker)
	{
		Log::notice("Worker {$worker->phone_number} password reset successfully");
	}
}
