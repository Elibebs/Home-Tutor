<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Api\ApiResponse;
use App\Events\AuthEvents;
use Illuminate\Support\Facades\Log;
use App\Events\ErrorEvents;
use App\Utilities\Validator;
use App\Repos\UserRepo;
use App\Repos\WorkerRepo;
use App\Traits\AuthTrait;
use App\Repos\VerificationRepo;
use App\Services\Sms;
use App\Utilities\Generators;
use App\Utilities\Constants;
use App\Models\Rating;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestFlex;

class AuthController extends MasterController
{
	/*
    |--------------------------------------------------------------------------
    | Auth Controller
    |--------------------------------------------------------------------------
    |
    | Controller for authentications: Login, Logout, Register, Verification
    |
    */
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

	public function logoutUser(Request $request)
	{
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');

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
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
	}

    public function verifyUserPhone(Request $request){
        try
        {
            $data=$request->post();

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

			$verification = $this->verificationRepo->createVerificationEntry([
                'phone_number' => $phone_number
            ]);

            if($verification)
            {
                Log::notice("verification called ".$verification);
                AuthEvents::userVerificationInstanceCreated($verification);

                $message = "Successfully created OTP for {$data['phone_number']}. Please wait for SMS";
                return $this->apiResponse->success($message, ["data" => $phone_number] );
            }


            $message = "Unable to complete OTP verification {$data['phone_number']}";
            ErrorEvents::apiErrorOccurred($message);
            return $this->apiResponse->generalError($message);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function verifyUserOTP(Request $request)
    {
        try
        {
            $data = $request->post();

            Log::notice("verifyUserPin pin is {$request->post('pin')}");
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

            // Check if verification pin is correct
            if($this->verificationRepo->attemptVerifyPin($data['pin'], $data['phone_number']))
            {
                $user= $this->userRepo->freeLogin($data);
                if(isset($user)){
                    $message="User verification was successful";
                    return $this->apiResponse->success($message, ["data" => $user->toArray()] );
                }else{
                    $user = $this->userRepo->registerFreeUser($data);
                    if($user){
                        $message = "User : {$data['phone_number']} registered successfully";
                        Log::notice($message);
                        AuthEvents::userHasRegistered($user);

                        $message="User verification was successful";
                        return $this->apiResponse->success($message, ["data" => $user->toArray()] );
                    }else
                    {
                        $message = "Unable to complete registration for {$data['phone_number']}";
                        ErrorEvents::apiErrorOccurred($message);
                        return $this->apiResponse->generalError($message);
                    }
                }

                $message = "Could not set user as verified";
                ErrorEvents::apiErrorOccurred($message);
                return $this->apiResponse->generalError($message);
            }
            else
            {
                $message = "Incorrect verification pin";
                ErrorEvents::apiErrorOccurred($message);
                return $this->apiResponse->generalError($message);
            }
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function resendUserPin(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');
            return  $this->authActivity->resendUserPin($accessToken, $sessionId);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function resendUserPinWithBackup(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');
            return  $this->authActivity->resendUserPinWithBackupTwillio($accessToken, $sessionId);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function userProfile(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');
            return  $this->authActivity->getUserProfile($accessToken, $sessionId);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function updateUserProfile(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');
            return  $this->authActivity->updateUserProfile($request->post(), $accessToken, $sessionId);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function verifyTutorPhone(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');

            $worker = $this->workerRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);

            if($this->workerRepo->logout($worker->worker_id, 'worker_id'))
            {
                $message = "Worker : {$worker->name} - {$worker->phone_number} logged out successfully";
                Log::notice($message);
                return $this->apiResponse->success($message, ["data" => null ] );
            }

            $message = "Unable to logout the user : {$worker->name}";
            ErrorEvents::apiErrorOccurred($message);
            return $this->apiResponse->generalError($message);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function verifyTutorOTP(Request $request)
    {
        try
        {
            $data = $request->post();

            Log::notice("verifyUserPin pin is {$request->post('pin')}");
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

            // Check if verification pin is correct
            if($this->verificationRepo->attemptVerifyPin($data['pin'], $data['phone_number']))
            {
                $worker= $this->workerRepo->freeLogin($data);
                if(isset($worker)){
                    $message="User verification was successful";
                    return $this->apiResponse->success($message, ["data" => $worker->toArray()] );
                }else{
                    $worker = $this->workerRepo->registerFreeUser($data);
                    if($worker){
                        $message = "User : {$data['phone_number']} registered successfully";
                        Log::notice($message);
                        AuthEvents::userHasRegistered($worker);

                        $message="User verification was successful";
                        return $this->apiResponse->success($message, ["data" => $worker->toArray()] );
                    }else
                    {
                        $message = "Unable to complete registration for {$data['phone_number']}";
                        ErrorEvents::apiErrorOccurred($message);
                        return $this->apiResponse->generalError($message);
                    }
                }

                $message = "Could not set user as verified";
                ErrorEvents::apiErrorOccurred($message);
                return $this->apiResponse->generalError($message);
            }
            else
            {
                $message = "Incorrect verification pin";
                ErrorEvents::apiErrorOccurred($message);
                return $this->apiResponse->generalError($message);
            }
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function resendWorkerPinWithBackup(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');
            return  $this->authActivity->resendWorkerPinWithBackupTwillio($accessToken, $sessionId);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function logoutWorker(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');

            $worker = $this->workerRepo->getWhereAccessTokenAndSessionId($accessToken, $sessionId);

            if($this->workerRepo->logout($worker->worker_id, 'worker_id'))
            {
                $message = "Worker : {$worker->name} - {$worker->phone_number} logged out successfully";
                Log::notice($message);
                return $this->apiResponse->success($message, ["data" => null ] );
            }

            $message = "Unable to logout the user : {$worker->name}";
            ErrorEvents::apiErrorOccurred($message);
            return $this->apiResponse->generalError($message);
        }

        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function workerProfile(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');
            return  $this->authActivity->getWorkerProfile($accessToken, $sessionId);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }

    public function updateWorkerProfile(Request $request)
    {
        try
        {
            $accessToken = $request->header('access-token');
            $sessionId = $request->header('session-id');
            return  $this->authActivity->updateWorkerProfile($request->post(), $accessToken, $sessionId);
        }
        catch(\Exception $e)
        {
            ErrorEvents::ServerErrorOccurred($e);
            return $this->apiResponse->serverError();
        }
    }
}
