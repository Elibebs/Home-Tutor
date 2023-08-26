<?php

namespace App\Activities;

use App\Repos\UserRepo;
use App\Repos\WorkerRepo;
use App\Api\ApiResponse;
use App\Traits\AuthTrait;
use Illuminate\Support\Facades\Log;
use App\Events\AuthEvents;
use App\Events\ErrorEvents;
use App\Utilities\Validator;
use App\Repos\VerificationRepo;
use App\Services\Sms;
use App\Utilities\Generators;
use App\Utilities\Constants;
use App\Models\Rating;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestFlex;
use App\Models\Worker;
use App\Models\User;
use App\Services\Twillio;

class AuthActivity extends BaseActivity
{
	use AuthTrait;

	protected $userRepo;
	protected $workerRepo;
	protected $apiResponse;
	protected $verificationRepo;

	public function __construct(
		UserRepo $userRepo,
		WorkerRepo $workerRepo,
		ApiResponse $apiResponse,
		VerificationRepo $verificationRepo
	)
    {
    	$this->userRepo = $userRepo;
    	$this->workerRepo = $workerRepo;
		$this->apiResponse = $apiResponse;
    	$this->verificationRepo = $verificationRepo;
    }

    /*
	*
	*	USER FUNCTIONS
	*
    */

    // Login for ayudahub USER
    public function attemptUserLogin(Array $data)
    {
    	// Validate request parameters
		$missingParams = Validator::validateRequiredParams($this->apiUserLoginParams, $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// Attempt to login user and get appropriate response:
		// Response -> null if unauthorized, User object if authorized
		$userLogin = $this->userRepo->login($data);
		if($userLogin)
		{
			unset($userLogin['password']);
			$message = "User : {$data['identifier']} successfully logged in";
			Log::notice($message);
			AuthEvents::userHasLoggedIn($userLogin);
			return $this->apiResponse->success($message, ["data" => $userLogin->toArray()] );
		}
		else
		{
			ErrorEvents::apiErrorOccurred("Unauthorized login by User {$data['identifier']}", "warning");
			return $this->apiResponse->unauthorized();
		}
    }

		public function freeLoginUser(Array $data){
			$missingParams = Validator::validateRequiredParams(['phone_number','player_id'], $data);
			if(!empty($missingParams))
			{
				$errors = Validator::convertToRequiredValidationErrors($missingParams);
				ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

				return $this->apiResponse->validationError(
					["errors" => $errors]
				);
			}

			$phone_number=$data['phone_number'];
			// Attempt to login user and get appropriate response:
			// Response -> null if unauthorized, User object if authorized

			$userLogin = $this->userRepo->freeLogin($data);
			if($userLogin)
			{
				unset($userLogin['password']);
				$message = "User : {$data['phone_number']} successfully logged in";
				Log::notice($message);
				AuthEvents::userHasLoggedIn($userLogin);

				$verification = $this->verificationRepo->createVerificationEntry([
					'user_id' => $userLogin->user_id,
					'phone_number' => $userLogin->phone_number
				]);

				if($verification)
				{
					Log::notice("verification called ".$verification);
					AuthEvents::userVerificationInstanceCreated($verification, $userLogin);
				}

				return $this->apiResponse->success($message, ["data" => $userLogin->toArray()] );
			} else {
				$registeredUser = $this->userRepo->registerFreeUser($data);
				if($registeredUser){
					unset($registeredUser['password']);
					$message = "User : {$data['phone_number']} registered successfully";
					Log::notice($message);
					AuthEvents::userHasRegistered($registeredUser);

					// return $this->apiResponse->success($message, ["data" => $registeredUser->toArray()] );
					return $this->freeLoginUser($data);
				}
				else
				{
					$message = "Unable to complete registration for {$data['phone_number']}";
					ErrorEvents::apiErrorOccurred($message);
					return $this->apiResponse->generalError($message);
				}

				ErrorEvents::apiErrorOccurred("Unauthorized login by User {$data['phone_number']}", "warning");
				return $this->apiResponse->unauthorized();
			}
		}

    public function logoutUser($accessToken, $sessionId)
    {
    	$user = $this->userRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);

    	if($this->userRepo->logout($user->user_id, 'user_id'))
    	{
    		$message = "User : {$user->name} - {$user->phone_number} logged out successfully";
			Log::notice($message);
    		return $this->apiResponse->success($message, ["data" => null ] );
    	}

    	$message = "Unable to logout the user : {$user->name}";
		ErrorEvents::apiErrorOccurred($message);
		return $this->apiResponse->generalError($message);
    }

    // Register for ayudahub USER
    public function registerUser(Array $data)
    {
    	// Validate request parameters
		$missingParams = Validator::validateRequiredParams($this->apiUserRegisterParams, $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// Check if email exists if email is specified
		if(isset($data['email']))
		{
			if($this->userRepo->emailExists($data['email']))
			{
				$message = "The specified email {$data['email']} already exists";
				ErrorEvents::apiErrorOccurred($message, "warning");
				return $this->apiResponse->generalError($message);
			}
		}

		// check if phone number already exists
		if(isset($data['phone_number']))
		{
			if($this->userRepo->phoneNumberExists($data['phone_number']))
			{
				$message = "The specified phone number {$data['phone_number']} already exists among users";
				Log::warning($message);
				return $this->apiResponse->generalError($message);
			}
		}


		// Attempt to register user
		$registeredUser = $this->userRepo->registerUser($data);
		if($registeredUser)
		{
			unset($registeredUser['password']);
			$message = "User : {$data['phone_number']} registered successfully";
			Log::notice($message);
			AuthEvents::userHasRegistered($registeredUser);

			$verification = $this->verificationRepo->createVerificationEntry([
				'user_id' => $registeredUser->user_id,
				'phone_number' => $registeredUser->phone_number
			]);
            Log::notice("verification called ".$verification);

			if($verification)
			{
			    Log::notice("verification called ".$verification);
				AuthEvents::userVerificationInstanceCreated($verification, $registeredUser);
			}

			return $this->apiResponse->success($message, ["data" => $registeredUser->toArray()] );
		}
		else
		{
			$message = "Unable to complete registration for {$data['phone_number']}";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}
    }

    public function verifyUserPin(Array $data, String $accessToken, String $sessionId)
    {
    	// Validate request parameters
		$missingParams = Validator::validateRequiredParams($this->apiVerificationPinParams, $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// get user whose access token is provided. If no user return
		$user = $this->userRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
		if(!isset($user))
		{
			$message = "User with supplied access-token/session-id not found";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		// Check if verification pin is correct
		if($this->verificationRepo->attemptVerifyPin($data['pin'], $user->user_id))
		{
			if($this->userRepo->setUserVerified($user['user_id']))
			{
				$message = "User verified successfully";
				AuthEvents::userPinVerified($user);
				return $this->apiResponse->success($message, ["data" => null] );
			}
		}
		else
		{
			$message = "Incorrect verification pin";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}
    }

    public function resendUserPin(String $accessToken, String $sessionId)
    {
    	$user = $this->userRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
    	if($user->verified)
    	{
    		$message = "User already verified";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
    	}

    	$verification = $this->verificationRepo->createVerificationEntry([
			'user_id' => $user->user_id,
			'phone_number' => $user->phone_number
		]);
		Log::notice("resend verification called ".$verification);

		if($verification)
		{
			AuthEvents::userVerificationInstanceCreated($verification, $user);
		}

		$message = "Verification Pin Re-Sent Successfully";
		Log::notice($message);
		return $this->apiResponse->success($message, ["data" => null] );
    }

    public function resendUserPinWithBackupTwillio(String $accessToken, String $sessionId)
    {
    	$user = $this->userRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
    	if($user->verified)
    	{
    		$message = "User already verified";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
    	}

    	$verification = $this->verificationRepo->createVerificationEntry([
			'user_id' => $user->user_id,
			'phone_number' => $user->phone_number
		]);
		Log::notice("resend user pin with backup verification called ".$verification);

		if($verification)
		{
			AuthEvents::userVerificationInstanceCreated($verification, $user, true);
		}

		$message = "Verification backup Pin Re-Sent Successfully";
		Log::notice($message);
		return $this->apiResponse->success($message, ["data" => null] );
    }

    public function changeUserPassword(Array $data, $accessToken, $sessionId)
    {
    	// Validate request parameters
		$missingParams = Validator::validateRequiredParams($this->apiUserChangePasswordParams, $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// Check if password and confirm password match
		if($data['new_password'] !== $data['confirm_password'])
		{
			$message = "Password and password confirmation do not match";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		$user = $this->userRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);

		// Check if old password is correct
		if(!$this->userRepo->validatePassword($data['old_password'], $user->user_id, "user_id"))
		{
			$message = "Existing password does not mactch";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		// Change user password
		$entity = $this->userRepo->changePassword($user->user_id, "user_id", $data['new_password']);
		if($entity)
		{
			AuthEvents::userPasswordChanged($entity);
			$message = "User : {$entity->phone_number} password reset successfully";
			Log::notice($message);
			return $this->apiResponse->success($message, ["data" => null] );
		}

		$errMsg = "Could not change user : {$user->phone_number} password";
		ErrorEvents::apiErrorOccurred($errMsg);
		return $this->apiResponse->generalError($errMsg);
    }

    public function getUserProfile($accessToken, $sessionId)
    {
    	$user = $this->userRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
    	if($user)
    	{
    		unset($user['password']);
    		$message = "User profile obtained successfully";
    		Log::notice($message);
    		return $this->apiResponse->success($message, ['data' => $user->toArray()]);
    	}

    	$errMsg = "Could not obtain user profile";
    	ErrorEvents::apiErrorOccurred($errMsg);
		return $this->apiResponse->generalError($errMsg);
    }

    public function updateUserProfile(Array $data, $accessToken, $sessionId)
    {
		$user = $this->userRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
		if(isset($data['phone_number']) && $data['phone_number']!=$user->phone_number){
			$existingUser = $this->userRepo->getWherePhoneNumber($data['phone_number']);

			if(!empty($existingUser))
			{
				$errors = "the phone number already exist";
				ErrorEvents::apiErrorOccurred("Validation error, {$errors}");

				return $this->apiResponse->validationError(
					["errors" => $errors]
				);
			}
		}

    	if($user)
    	{
    		if($this->userRepo->updateUserProfile($data, $user->user_id))
    		{
    			$updatedUserProfile = $this->userRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
					unset($updatedUserProfile['password']);

    			$message = "User profile updated successfully data=".$updatedUserProfile;
					Log::notice($message);

					if($updatedUserProfile->verified===false){
						Log::notice('User profile is not verified. verification pin incoming...');
						$this->userVerifyPin($data);
					}
	    		return $this->apiResponse->success($message, ['data' => $updatedUserProfile->toArray()]);
    		}
    	}

    	$errMsg = "Could not obtain user for update :(";
    	ErrorEvents::apiErrorOccurred($errMsg);
			return $this->apiResponse->generalError($errMsg);
    }

    public function userVerifyPin($data)
    {
    	$missingParams = Validator::validateRequiredParams(["phone_number"], $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

    	$user = $this->userRepo->getWherePhoneNumber($data['phone_number']);
    	if(!isset($user))
    	{
    		$message = "User with phone number {$data['phone_number']} not found";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->notFoundError($message);
    	}

    	$userPhone = $user->phone_number;
    	$pin = Generators::generateVerificationPin();

    	$verificationObject = $this->verificationRepo->createVerificationEntry([
    		'user_id' => $user->user_id,
			'phone_number' => $user->phone_number
    	]);

    	if(isset($verificationObject))
    	{
    		Log::notice("Verification pin created. Sending to user for phone number verification...{$verificationObject->pin}");
			$message = "Your verification pin has been sent to {$data['phone_number']} via sms.";
			$smsMessage = "Your verification pin is {$verificationObject->verification_pin}.";

			if(config("app.env") === Constants::ENV_PRODUCTION)
			{
				Sms::sendSMS($verificationObject->to_phone_number, $smsMessage);
			}
    		// AuthEvents::userVerificationInstanceCreated($verificationObject);
    		return $this->apiResponse->success($message, ["data" => null] );
    	}

    	$errMsg = "Could not send verification pin to user for verification";
    	ErrorEvents::apiErrorOccurred($errMsg);
		return $this->apiResponse->generalError($errMsg);
    }

	public function userForgotPassword($data)
    {
    	$missingParams = Validator::validateRequiredParams(["phone_number"], $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

    	$user = $this->userRepo->getWherePhoneNumber($data['phone_number']);
    	if(!isset($user))
    	{
    		$message = "User with phone number {$data['phone_number']} not found";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->notFoundError($message);
    	}

    	$userPhone = $user->phone_number;
    	$pin = Generators::generateVerificationPin();

    	$forgotPasswordObject = $this->verificationRepo->userCreateForgotPasswordPinEntry([
    		'user_id' => $user->user_id,
			'phone_number' => $user->phone_number
    	]);

    	if(isset($forgotPasswordObject))
    	{
    		Log::notice("Forgot Password pin created. Sending to user for phone number verification...");
			$message = "Your verification has been sent to {$data['phone_number']} via sms. Kindly use it to reset your password";
			$smsMessage = "Your verification pin is {$forgotPasswordObject->pin}. Kindly use to to reset your password";
    		Sms::sendSMS($forgotPasswordObject->to_phone_number, $smsMessage);
    		AuthEvents::userForgotPasswordPinCreated($user);
    		return $this->apiResponse->success($message, ["data" => null] );
    	}

    	$errMsg = "Could not send forgot password pin to user for verification";
    	ErrorEvents::apiErrorOccurred($errMsg);
		return $this->apiResponse->generalError($errMsg);
    }

    public function userVerifyForgotPasswordPin($data)
    {
    	$missingParams = Validator::validateRequiredParams(["phone_number", "pin"], $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// get user whose access token is provided. If no user return
		$user = $this->userRepo->getWherePhoneNumber($data['phone_number']);
		if(!isset($user))
		{
			$message = "User with phone number {$data['phone_number']} not found";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->notFoundError($message);
		}

		// Check if verification pin is correct
		if($this->verificationRepo->userAttemptVerifyForgotPassword($data['pin'], $user->user_id))
		{
			$message = "User forgot password pin verified successfully, you can now proceed to chage your password";
			AuthEvents::userForgotPasswordPinVerified($user);
			return $this->apiResponse->success($message, ["data" => null] );
		}
		else
		{
			$message = "Incorrect verification pin";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}
    }

    public function userPasswordReset($data)
    {
    	$missingParams = Validator::validateRequiredParams(["phone_number", "password", "confirm_password"], $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// get user whose access token is provided. If no user return
		$user = $this->userRepo->getWherePhoneNumber($data['phone_number']);
		if(!isset($user))
		{
			$message = "User with phone number {$data['phone_number']} not found";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->notFoundError($message);
		}

		if($data['password'] != $data['confirm_password'])
		{
			$message = "Password doesnt match confirm password";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		$canResetPassword = $this->verificationRepo->hasUserForgotPasswordPinBeenGeneratedRecently($user->user_id);

		if(!$canResetPassword)
		{
			$message = "Password reset session expired or has been denied. Please start the process over again";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		if($this->userRepo->updatePassword($user->user_id, $data['password']))
		{
			$message = "User {$user->phone_number} password reset successfully. Please login";
			AuthEvents::userResetPassword($user);
			return $this->apiResponse->success($message, ["data" => null] );
		}

		$message = "An error occurred while resetting password";
		ErrorEvents::apiErrorOccurred($message);
		return $this->apiResponse->generalError($message);
    }

    /*
	*
	*	WORKER FUNCTIONS
	*
    */

    public function attemptWorkerLogin(Array $data)
    {
    	// Validate request parameters
		$missingParams = Validator::validateRequiredParams($this->apiWorkerLoginParams, $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// Attempt to login worker and get appropriate response:
		// Response -> null if unauthorized, Worker object if authorized
		$workerLogin = $this->workerRepo->login($data);
		if($workerLogin)
		{
			unset($workerLogin['password']);
			$message = "Worker : {$data['identifier']} successfully logged in";
			Log::notice($message);
			AuthEvents::workerHasLoggedIn($workerLogin);
			return $this->apiResponse->success($message, ["data" => $workerLogin->toArray()] );
		}
		else
		{
			ErrorEvents::apiErrorOccurred("Unauthorized login by worker : {$data['identifier']}", "warning");
			return $this->apiResponse->unauthorized();
		}
    }

    public function logoutWorker($accessToken, $sessionId)
    {
    	$worker = $this->workerRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);

    	if($this->workerRepo->logout($worker->worker_id, 'worker_id'))
    	{
    		$message = "Worker : {$worker->name} - {$worker->phone_number} logged out successfully";
			Log::notice($message);
    		return $this->apiResponse->success($message, ["data" => null ] );
    	}

    	$message = "Unable to logout the worker : {$worker->name}";
		ErrorEvents::apiErrorOccurred($message);
		return $this->apiResponse->generalError($message);
    }


    public function registerWorker(Array $data)
    {
    	// Validate request parameters
		$missingParams = Validator::validateRequiredParams($this->apiWorkerRegisterParams, $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// Check if email exists if email is specified
		if(isset($data['email']))
		{
			if($this->workerRepo->emailExists($data['email']))
			{
				$message = "The specified email {$data['email']} already exists";
				ErrorEvents::apiErrorOccurred($message, "warning");
				return $this->apiResponse->generalError($message);
			}
		}

		// check if phone number already exists
		if(isset($data['phone_number']))
		{
			if($this->workerRepo->phoneNumberExists($data['phone_number']))
			{
				$message = "The specified phone number {$data['phone_number']} already exists among workers";
				Log::warning($message);
				return $this->apiResponse->generalError($message);
			}
		}

		/*if($data['worker_type'] != Constants::WORKER_TYPE_INDIVIDUAL && $data['worker_type'] != Constants::WORKER_TYPE_ORGANIZATION){
				$message = "The specified worker type {$data['worker_type']} is not valid : Worker can be INDIVIDUAL or ORGANIZATION";
				Log::warning($message);
				return $this->apiResponse->generalError($message);
		}*/

		// Attempt to register worker
		$registeredWorker = $this->workerRepo->registerWorker($data);
		if($registeredWorker)
		{
			unset($registeredWorker['password']);
			$message = "Worker : {$data['phone_number']} registered successfully";
			Log::notice($message);
			AuthEvents::workerHasRegistered($registeredWorker);
			return $this->apiResponse->success($message, ["data" => $registeredWorker->toArray()] );
		}
		else
		{
			$message = "Unable to complete registration for {$data['phone_number']}";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}
    }

    public function changeWorkerPassword(Array $data, $accessToken, $sessionId)
    {
    	// Validate request parameters
		$missingParams = Validator::validateRequiredParams($this->apiWorkerChangePasswordParams, $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// Check if password and confirm password match
		if($data['new_password'] !== $data['confirm_password'])
		{
			$message = "Password and password confirmation do not match";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		$worker = $this->workerRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);

		// Check if old password is correct
		if(!$this->workerRepo->validatePassword($data['old_password'], $worker->worker_id, "worker_id"))
		{
			$message = "Existing password does not mactch";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		// Change worker password
		$entity = $this->workerRepo->changePassword($worker->worker_id, "worker_id", $data['new_password']);
		if($entity)
		{
			AuthEvents::workerPasswordChanged($entity);
			$message = "Worker : {$entity->phone_number} password reset successfully";
			Log::notice($message);
			return $this->apiResponse->success($message, ["data" => null] );
		}

		$errMsg = "Could not change worker : {$worker->phone_number} password";
		ErrorEvents::apiErrorOccurred($errMsg);
		return $this->apiResponse->generalError($errMsg);
    }

    public function getWorkerProfile($accessToken, $sessionId)
    {
    	$worker = $this->workerRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
    	if($worker)
    	{
				unset($worker['password']);
				$specialities= $worker->workerSpecialties()->orderBy('name')->get();
				if(isset($specialities)){
					$worker['specialities'] = $specialities;
				}

				$ratingCount = Rating::where('worker_id', $worker->worker_id)
					->where('review','<>','null')->count();

				if(isset($ratingCount)){
					$worker['review_count']=$ratingCount;
				}

				$sr_completed = ServiceRequest::where('worker_id', $worker->worker_id)
					->where("status", Constants::SR_STATUS_USER_COMPLETED)->count();
				$sr_completed_flex = ServiceRequestFlex::where('worker_id', $worker->worker_id)
					->where("status", Constants::SR_STATUS_USER_COMPLETED)->count();

				$ayuda_completed=$sr_completed + $sr_completed_flex;
				if(isset($ayuda_completed)){
					$worker['ayuda_completed']=$ayuda_completed;
				}

    		$message = "Worker profile obtained successfully";
    		Log::notice($message);
    		return $this->apiResponse->success($message, ['data' => $worker->toArray()]);
    	}

    	$errMsg = "Could not obtain worker profile";
    	ErrorEvents::apiErrorOccurred($errMsg);
		return $this->apiResponse->generalError($errMsg);
    }

    public function updateWorkerProfile(Array $data, $accessToken, $sessionId)
    {
    	$worker = $this->workerRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
    	if($worker)
    	{
    		if($this->workerRepo->updateWorkerProfile($data, $worker->worker_id))
    		{
    			$updatedWorkerProfile = $this->workerRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);
    			unset($updatedWorkerProfile['password']);
    			$message = "Worker profile updated successfully";
	    		Log::notice($message);
	    		return $this->apiResponse->success($message, ['data' => $updatedWorkerProfile->toArray()]);
    		}
    	}

    	$errMsg = "Could not obtain worker for update :(";
    	ErrorEvents::apiErrorOccurred($errMsg);
		return $this->apiResponse->generalError($errMsg);
    }


    public function workerForgotPassword($data)
    {
    	$missingParams = Validator::validateRequiredParams(["phone_number"], $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

    	$worker = $this->workerRepo->getWherePhoneNumber($data['phone_number']);
    	if(!isset($worker))
    	{
    		$message = "Worker with phone number {$data['phone_number']} not found";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->notFoundError($message);
    	}

    	$workerPhone = $worker->phone_number;
    	$pin = Generators::generateVerificationPin();

    	$forgotPasswordObject = $this->verificationRepo->workerCreateForgotPasswordPinEntry([
    		'worker_id' => $worker->worker_id,
			'phone_number' => $worker->phone_number
    	]);

    	if(isset($forgotPasswordObject))
    	{
    		Log::notice("Forgot Password pin created. Sending to worker for phone number verification...{$forgotPasswordObject->pin}.");
			$message = "Your verification has been sent to {$data['phone_number']} via sms.";
			$smsMessage = "Your verification pin is {$forgotPasswordObject->pin}.";
    		Sms::sendSMS($forgotPasswordObject->to_phone_number, $smsMessage);
    		AuthEvents::workerForgotPasswordPinCreated($worker);
    		return $this->apiResponse->success($message, ["data" => null] );
    	}

    	$errMsg = "Could not send forgot password pin to user for verification";
    	ErrorEvents::apiErrorOccurred($errMsg);
		return $this->apiResponse->generalError($errMsg);
    }

    public function workerVerifyForgotPasswordPin($data)
    {
    	$missingParams = Validator::validateRequiredParams(["phone_number", "pin"], $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// get worker whose access token is provided. If no worker return
		$worker = $this->workerRepo->getWherePhoneNumber($data['phone_number']);
		if(!isset($worker))
		{
			$message = "Worker with phone number {$data['phone_number']} not found";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->notFoundError($message);
		}

		// Check if verification pin is correct
		if($this->verificationRepo->workerAttemptVerifyForgotPassword($data['pin'], $worker->worker_id))
		{
			$message = "Worker forgot password pin verified successfully, you can now proceed to chage your password";
			AuthEvents::workerForgotPasswordPinVerified($worker);
			return $this->apiResponse->success($message, ["data" => null] );
		}
		else
		{
			$message = "Incorrect verification pin";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}
    }

    public function workerPasswordReset($data)
    {
    	$missingParams = Validator::validateRequiredParams(["phone_number", "password", "confirm_password"], $data);
		if(!empty($missingParams))
		{
			$errors = Validator::convertToRequiredValidationErrors($missingParams);
			ErrorEvents::apiErrorOccurred("Validation error, " . join(";", $errors));

			return $this->apiResponse->validationError(
				["errors" => $errors]
			);
		}

		// get worker whose access token is provided. If no worker return
		$worker = $this->workerRepo->getWherePhoneNumber($data['phone_number']);
		if(!isset($worker))
		{
			$message = "worker with phone number {$data['phone_number']} not found";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->notFoundError($message);
		}

		if($data['password'] != $data['confirm_password'])
		{
			$message = "Password doesnt match confirm password";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		$canResetPassword = $this->verificationRepo->hasWorkerForgotPasswordPinBeenGeneratedRecently($worker->worker_id);

		if(!$canResetPassword)
		{
			$message = "Password reset session expired or has been denied. Please start the process over again";
			ErrorEvents::apiErrorOccurred($message);
			return $this->apiResponse->generalError($message);
		}

		if($this->workerRepo->updatePassword($worker->worker_id, $data['password']))
		{
			$message = "Worker {$worker->phone_number} password reset successfully. Please login";
			AuthEvents::workerResetPassword($worker);
			return $this->apiResponse->success($message, ["data" => null] );
		}

		$message = "An error occurred while resetting password";
		ErrorEvents::apiErrorOccurred($message);
		return $this->apiResponse->generalError($message);
    }
}
